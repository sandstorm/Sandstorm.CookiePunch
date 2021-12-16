<?php

namespace Sandstorm\CookiePunch\Eel\Helper;

use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Media\Domain\Model\Tag;
use phpDocumentor\Reflection\Types\Boolean;
use Sandstorm\CookiePunch\TagHelper;

class CookiePunch implements ProtectedContextAwareInterface
{
    /**
     * @Flow\InjectConfiguration(package="Sandstorm.CookiePunch", path="services")
     */
    protected $services;

    /**
     * @Flow\InjectConfiguration(package="Sandstorm.CookiePunch", path="blocking.tagPatterns")
     */
    protected $tagPatterns;

    public function neverBlockIframes(string $markup): string
    {
        return $this->replaceTags("iframe", $markup, function ($tag) {
            // IMPORTANT: keep the order here or update all tests!
            $tag = $this->addNeverBlockAttribute($tag);
            return $tag;
        });
    }

    public function neverBlockScripts(string $markup): string
    {
        return $this->replaceTags("script", $markup, function ($tag) {
            // IMPORTANT: keep the order here or update all tests!
            $tag = $this->addNeverBlockAttribute($tag);
            return $tag;
        });
    }

    public function neverBlockTag(string $tagName, string $markup): string
    {
        return $this->replaceTags($tagName, $markup, function ($tag) {
            $tag = $this->addNeverBlockAttribute($tag);
            return $tag;
        });
    }

    public function blockTag(string $tagName, string $markup, bool $enabled = true, string $serviceNameOverride = null) {
        if (!$enabled) {
            return $markup;
        }

        return $this->replaceTags($tagName, $markup, function (
            $tagMarkup,
            $serviceName
        ) use ($serviceNameOverride, $tagName) {

            if(TagHelper::tagHasAttribute($tagMarkup, TagHelper::SRC)) {
                $tagMarkup = TagHelper::tagRenameAttribute(
                    $tagMarkup,
                    TagHelper::SRC,
                    TagHelper::DATA_SRC
                );
            }

            if($tagName === "script") {
                if(!TagHelper::tagHasAttribute($tagMarkup,TagHelper::TYPE)) {
                    // IMPORTANT: we need to add data-type="text/javascript" here to prevent Klaro from
                    // not correctly recovering the correct value.
                    // we add type="text/javascript" which later will be turned into an data attribute
                    $tagMarkup = TagHelper::tagAddAttribute(
                        $tagMarkup,
                        TagHelper::TYPE,
                        TagHelper::TYPE_JAVASCRIPT
                    );
                }

                $tagMarkup = TagHelper::tagRenameAttribute(
                    $tagMarkup,
                    TagHelper::TYPE,
                    TagHelper::DATA_TYPE
                );

                $tagMarkup = TagHelper::tagAddAttribute(
                    $tagMarkup,
                    TagHelper::TYPE,
                    TagHelper::TYPE_TEXT_PLAIN
                );
            // ALL OTHER TAGS
            } else {
                if(TagHelper::tagHasAttribute($tagMarkup, TagHelper::TYPE)) {
                    $tagMarkup = TagHelper::tagAddAttribute(
                        $tagMarkup,
                        TagHelper::DATA_TYPE,
                        TagHelper::tagGetAttributeValue($tagMarkup, TagHelper::TYPE)
                    );
                }
            }

            if(
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
     * @param string $markup
     * @return string
     */
    public function blockIframes(
        string $markup,
        bool $enabled = true,
        string $serviceNameOverride = null
    ): string {
        if (!$enabled) {
            return $markup;
        }

        return $this->blockTag("iframe", $markup, $enabled, $serviceNameOverride);

        return $this->replaceTags("iframe", $markup, function (
            $tagMarkup,
            $serviceName
        ) use ($serviceNameOverride) {
            // IMPORTANT: keep the order here or update all tests!
            $tagMarkup = TagHelper::tagRenameAttribute(
                $tagMarkup,
                TagHelper::SRC,
                TagHelper::DATA_SRC
            );

            $dataNameAttribute = $serviceNameOverride
                ? $serviceNameOverride
                : $serviceName;

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

            return $tagMarkup;
        });
    }

    private function processScriptTag($tagMarkup): string {
        if (!TagHelper::tagHasAttribute($tagMarkup, TagHelper::TYPE)) {
            // IMPORTANT: we need to add data-type="text/javascript" here to prevent Klaro from
            // not correctly recovering the correct value.
            // we add type="text/javascript" which later will be turned into an data attribute
            $tagMarkup = TagHelper::tagAddAttribute(
                $tagMarkup,
                TagHelper::DATA_TYPE,
                TagHelper::TYPE_JAVASCRIPT
            );
        }

        $tagMarkup = TagHelper::tagRenameAttribute(
            $tagMarkup,
            TagHelper::TYPE,
            TagHelper::DATA_TYPE
        );

        $tagMarkup = TagHelper::tagAddAttribute(
            $tagMarkup,
            TagHelper::TYPE,
            TagHelper::TYPE_TEXT_PLAIN
        );

        return $tagMarkup;
    }

    /**
     * @param string $contentMarkup
     * @return string
     */
    public function blockScripts(
        string $contentMarkup,
        bool $enabled = true,
        string $serviceNameOverride = null
    ): string {

        return $this->blockTag("script", $contentMarkup, $enabled, $serviceNameOverride);

        #######
        if (!$enabled) {
            return $contentMarkup;
        }

        return $this->replaceTags("script", $contentMarkup, function (
            $tagMarkup,
            $serviceName
        ) use ($serviceNameOverride) {
            // #########################################################################
            // IMPORTANT: keep the order in the following section or update all tests!
            // #########################################################################

            $tagMarkup = TagHelper::tagRenameAttribute(
                $tagMarkup,
                TagHelper::SRC,
                TagHelper::DATA_SRC
            );

            $hasType = TagHelper::tagHasAttribute($tagMarkup, TagHelper::TYPE);

            $typeAttributeValue = TagHelper::tagGetAttributeValue(
                $tagMarkup,
                "type"
            );

            if (!$typeAttributeValue) {
                // We want to be least invasive and try to reuse the type attribute value
                // if none is present we use fallback.
                $typeAttributeValue = TagHelper::TYPE_JAVASCRIPT;
            }

            if (TagHelper::tagHasAttribute($tagMarkup, TagHelper::DATA_SRC)) {
                if ($hasType) {
                    $tagMarkup = TagHelper::tagRenameAttribute(
                        $tagMarkup,
                        TagHelper::TYPE,
                        TagHelper::DATA_TYPE
                    );
                    $tagMarkup = TagHelper::tagAddAttribute(
                        $tagMarkup,
                        TagHelper::TYPE,
                        TagHelper::TYPE_TEXT_PLAIN
                    );
                } else {
                    // IMPORTANT: we need to add data-type="text/javascript" here to prevent Klaro from
                    // not correctly recovering the correct value.
                    // we add type="text/javascript" which later will be turned into an data attribute
                    $tagMarkup = TagHelper::tagAddAttribute(
                        $tagMarkup,
                        TagHelper::DATA_TYPE,
                        $typeAttributeValue
                    );
                }
            } else {
                // nor src so we have to "break" the tag by setting the type
                if ($hasType) {
                    $tagMarkup = TagHelper::tagRenameAttribute(
                        $tagMarkup,
                        TagHelper::TYPE,
                        TagHelper::DATA_TYPE
                    );
                    $tagMarkup = TagHelper::tagAddAttribute(
                        $tagMarkup,
                        TagHelper::TYPE,
                        TagHelper::TYPE_TEXT_PLAIN
                    );
                } else {
                    $tagMarkup = TagHelper::tagAddAttribute(
                        $tagMarkup,
                        TagHelper::TYPE,
                        TagHelper::TYPE_TEXT_PLAIN
                    );
                    $tagMarkup = TagHelper::tagAddAttribute(
                        $tagMarkup,
                        TagHelper::DATA_TYPE,
                        $typeAttributeValue
                    );
                }
            }

            $dataNameAttribute = $serviceNameOverride
                ? $serviceNameOverride
                : $serviceName;

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

            return $tagMarkup;
        });
    }

    /**
     * @param string | null $markup
     * @param string $service
     * @return string
     */
    public function addContextualConsent(
        string $service,
        ?string $markup,
        ?bool $isEnabled
    ) {
        if ($isEnabled) {
            return "<div data-name='" . $service . "'>" . $markup . "</div>";
        } else {
            return $markup;
        }
    }

    private function addNeverBlockAttribute(string $tag): string
    {
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
    private function tagContains(string $haystack, string $needle): bool
    {
        return !!strpos($haystack, $needle) !== false;
    }

    /**
     * @param string $tagName
     * @param string $contentMarkup
     * @param callable $hitCallback
     * @return string
     */
    private function replaceTags(
        string $tagName,
        string $contentMarkup,
        callable $hitCallback
    ): string {
        $regex = '/<' . $tagName . '.*?>/';

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
            function ($hits) use ($hitCallback, $tagName) {
                $tagMarkup = $hits[0];

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
                // tagName can be iframe or script

                // default is always true but can change depending on the next stage
                $block = true;
                $serviceName = null;

                $tagPatterns = isset($this->tagPatterns[$tagName])
                    ? $this->tagPatterns[$tagName]
                    : [];

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
                        $serviceName
                    );
                } else {
                    return $tagMarkup;
                }
            },
            $contentMarkup
        );
    }

    private function validateService(string $name = null)
    {
        if ($name && !isset($this->services[$name])) {
            throw new \InvalidArgumentException(
                'The service "' .
                    $name .
                    '" could not be found in your config. Expected config for "Sandstorm.CookiePunch.services.' .
                    $name .
                    '"',
                1596469884
            );
        }
    }

    /**
     * All methods are considered safe, i.e. can be executed from within Eel
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
