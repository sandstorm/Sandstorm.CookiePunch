<?php

namespace Sandstorm\CookiePunch\Eel\Helper;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Sandstorm\CookiePunch\TagHelper;

class CookiePunch implements ProtectedContextAwareInterface {
    /**
     * @Flow\InjectConfiguration(package="Sandstorm.CookiePunch", path="consent.services")
     */
    protected $services;

    /**
     * @Flow\InjectConfiguration(package="Sandstorm.CookiePunch", path="blocking.tagPatterns")
     */
    protected $tagPatterns;

    public function neverBlockTags(array $tagNames, string $markup): string {
        $this->validateTagNames($tagNames);
        return $this->checkTagAndReplaceUsingACallback($tagNames, $markup, function ($tag) {
            $tag = $this->addNeverBlockAttribute($tag);
            return $tag;
        });
    }

    public function blockTags(array $tagNames, string $markup, bool $enabled = true, string $serviceNameOverride = null) {
        $this->validateTagNames($tagNames);
        $this->validateService($serviceNameOverride);

        if (!$enabled) {
            return $markup;
        }

        return $this->checkTagAndReplaceUsingACallback($tagNames, $markup, function (
            $tagMarkup,
            $serviceName,
            $tagName
        ) use ($serviceNameOverride) {
            // 1. RENAME `src` to `data-src` for all tags with this attribute
            if (TagHelper::tagHasAttribute($tagMarkup, TagHelper::SRC)) {
                $tagMarkup = TagHelper::tagRenameAttribute(
                    $tagMarkup,
                    TagHelper::SRC,
                    TagHelper::DATA_SRC
                );
            }

            switch ($tagName) {
                // 2. SPECIAL TREATMENT
                case "script": {
                    // FIXING MISSING TYPE ATTRIBUTE
                    if (!TagHelper::tagHasAttribute($tagMarkup, TagHelper::TYPE)) {
                        // IMPORTANT: we need to add data-type="text/javascript" here to prevent Klaro from
                        // not correctly recovering the correct value.
                        // we add type="text/javascript" which later will be turned into an data attribute
                        $tagMarkup = TagHelper::tagAddAttribute(
                            $tagMarkup,
                            TagHelper::TYPE,
                            TagHelper::TYPE_JAVASCRIPT
                        );
                    }
                    // RENAMING `type` to `data-type`
                    $tagMarkup = TagHelper::tagRenameAttribute(
                        $tagMarkup,
                        TagHelper::TYPE,
                        TagHelper::DATA_TYPE
                    );

                    // ADDING `type="text/plain"`
                    $tagMarkup = TagHelper::tagAddAttribute(
                        $tagMarkup,
                        TagHelper::TYPE,
                        TagHelper::TYPE_TEXT_PLAIN
                    );
                    break;
                }
                default: {
                    // 3. ALL OTHER TAGS
                    if (TagHelper::tagHasAttribute($tagMarkup, TagHelper::TYPE)) {
                        $tagMarkup = TagHelper::tagAddAttribute(
                            $tagMarkup,
                            TagHelper::DATA_TYPE,
                            TagHelper::tagGetAttributeValue($tagMarkup, TagHelper::TYPE)
                        );
                    }
                }
            }

            // 4. FINAL STEPS FOR ALL TAGS - Only if they were processed before
            // otherwise we end up with tags not having a `src` but a `data-name` attribute
            // e.g. `<audio data-name="myservice"/>`
            if (
                TagHelper::tagHasAttribute($tagMarkup, TagHelper::DATA_SRC) ||
                TagHelper::tagHasAttribute($tagMarkup, TagHelper::DATA_TYPE)
            ) {
                $dataNameAttribute = $serviceNameOverride ?: $serviceName;
                if (
                    !TagHelper::tagHasAttribute($tagMarkup, TagHelper::DATA_NAME) &&
                    $dataNameAttribute
                ) {
                    $tagMarkup = TagHelper::tagAddAttribute(
                        $tagMarkup,
                        TagHelper::DATA_NAME,
                        $dataNameAttribute
                    );
                }
            }
            return $tagMarkup;
        });
    }

    /**
     * @param string | null $markup
     * @param string $service
     * @return string
     */
    public function addContextualConsent(
        string  $service,
        string $markup,
        bool   $isEnabled = true
    ) {
        $this->validateService($service);
        if ($isEnabled) {
            return '<div data-name="' . $service . '">' . $markup . "</div>";
        } else {
            return $markup;
        }
    }

    private function addNeverBlockAttribute(string $tag): string {
        if (!TagHelper::tagHasAttribute($tag, TagHelper::DATA_NEVER_BLOCK)) {
            return TagHelper::tagAddAttribute(
                $tag,
                TagHelper::DATA_NEVER_BLOCK
            );
        }
        return $tag;
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    private function tagContains(string $haystack, string $needle): bool {
        return !!strpos($haystack, $needle) !== false;
    }

    /**
     * @param string $tagNames
     * @param string $contentMarkup
     * @param callable $hitCallback
     * @return string
     */
    private function checkTagAndReplaceUsingACallback(
        array    $tagNames,
        string   $contentMarkup,
        callable $hitCallback
    ): string {
        $regex = '/<(' . implode("|", $tagNames) . ').*?>/';

        // STAGE 1:
        //
        // Before making changes to a tag we do some check first
        // and decide if we need to apply more logic.
        // This is basically a collection of early return before using the
        // callback which will the replace the tags.
        //
        // IMPORTANT: here we do not change a tag. We only check if we need to proceed
        return preg_replace_callback(
            $regex,
            function ($hits) use ($hitCallback, $tagNames) {
                $tagMarkup = $hits[0];
                $tagName = $hits[1];

                // EARLY RETURN - NO CALLBACK
                if (!$hitCallback) {
                    return $tagMarkup;
                }

                // EARLY RETURN - NEVER BLOCK
                $neverBlock = $this->tagContains(
                    $tagMarkup,
                    TagHelper::DATA_NEVER_BLOCK
                );

                if ($neverBlock) {
                    return $tagMarkup;
                }

                // EARLY RETURN - SPECIAL MIME TYPE
                $mimeType = TagHelper::tagGetAttributeValue(
                    $tagMarkup,
                    TagHelper::TYPE
                );
                if (
                    $mimeType === TagHelper::TYPE_TEXT_PLAIN ||
                    $mimeType === TagHelper::TYPE_APPLICATION_JSON_LD
                ) {
                    return $tagMarkup;
                }

                // EARLY RETURN - HAS BLOCKING ATTRIBUTES
                // if a part of the markup was already processed
                // We do not check if TagHelper::DATA_NAME is present, because we might want the editor
                // to choose a group, e.g. in the inspector and still block tags.
                $hasBlockingAttributes =
                    TagHelper::tagHasAttribute(
                        $tagMarkup,
                        TagHelper::DATA_SRC
                    ) ||
                    TagHelper::tagHasAttribute(
                        $tagMarkup,
                        TagHelper::DATA_TYPE
                    );

                if ($hasBlockingAttributes) {
                    return $tagMarkup;
                }

                // Blocking based on patterns from the config

                // IMPORTANT: we will always block tags on default when using the Eel-Helper
                // `CookiePunch.blockTags(["iframe","script", "img"]`
                // We do not differentiate between different tags as this makes it easier for
                // the integrator to know what is going on. The default blocking behaviour
                // can be changed in the configuration. This way deviation from the default
                // is documented in the configuration of the project to show the intend.
                $block = true;

                $serviceName = null;

                $tagPatterns = $this->tagPatterns[$tagName] ?? [];

                if (isset($tagPatterns["*"])) {
                    $patternConfig = $tagPatterns["*"];
                    if (isset($patternConfig["block"])) {
                        $block = $patternConfig["block"];
                    }
                    if (isset($patternConfig["service"])) {
                        $block = true;
                        // We also pass the corresponding service name to the next stage
                        $serviceName = $patternConfig["service"];
                    }
                }

                foreach ($tagPatterns as $pattern => $patternConfig) {
                    if ($pattern === "*") {
                        continue;
                    }
                    if ($this->tagContains($tagMarkup, $pattern)) {
                        if (isset($patternConfig["block"])) {
                            $block = $patternConfig["block"];
                        }

                        if (isset($patternConfig["service"])) {
                            // if we have a relating consent service the element will always be blocked
                            // as it will be controlled by the consent itself
                            $block = true;

                            // We also pass the corresponding service name to the next stage
                            $serviceName = $patternConfig["service"];
                        }
                    }
                }

                if ($block) {
                    return call_user_func(
                        $hitCallback,
                        $tagMarkup,
                        $serviceName,
                        $tagName
                    );
                } else {
                    return $tagMarkup;
                }
            },
            $contentMarkup
        );
    }

    private function validateService(string $name = null) {
        if ($name && !isset($this->services[$name])) {
            throw new \InvalidArgumentException(
                'Sandstorm.CookiePunch: The service "' .
                $name .
                '" could not be found in your "Sandstorm.CookiePunch.services" config.',
                1596469884
            );
        }
    }

    private function validateTagNames(array $tagNames) {
        $allowedTagNames = ["audio", "embed", "iframe", "img", "input", "script", "source", "track", "video"];
        $diff = array_diff($tagNames, $allowedTagNames);

        if(sizeof($diff) > 0) {
            throw new \InvalidArgumentException(
                'Sandstorm.CookiePunch: The following tags are not supported for blocking: ' . implode(", ",$diff) .
                ". Supported tags are: " . implode(", ", $allowedTagNames) .
                ". Please check your Fusion code."
                ,
                1596469854
            );
        }
    }

    /**
     * All methods are considered safe, i.e. can be executed from within Eel
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName) {
        return true;
    }
}
