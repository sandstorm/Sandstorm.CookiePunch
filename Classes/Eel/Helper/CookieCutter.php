<?php

namespace Sandstorm\CookieCutter\Eel\Helper;

use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use phpDocumentor\Reflection\Types\Boolean;
use Sandstorm\CookieCutter\ConsentConfigImplementation;
use Sandstorm\CookieCutter\TagHelper;

class CookieCutter implements ProtectedContextAwareInterface
{
    const SETTINGS_BLOCK = "block";

    const SETTINGS_GROUP = "group";
    const SETTINGS_OPTIONS = "options";
    const SETTINGS_BLOCK_ALL = "block";

    const SETTINGS_BLOCK_PATTERNS = "patterns";
    const DEFAULT_GROUP = "default";

    /**
     * @Flow\InjectConfiguration(package="Sandstorm.CookieCutter", path="groups")
     */
    protected $groups;

    /**
     * @Flow\InjectConfiguration(package="Sandstorm.CookieCutter", path="elements.patterns")
     */
    protected $patterns;

    /**
     * @Flow\InjectConfiguration(package="Sandstorm.CookieCutter", path="elements.block")
     */
    protected $block;

    /**
     * @Flow\InjectConfiguration(package="Sandstorm.CookieCutter", path="elements.group")
     */
    protected $defaultGroup;

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

    /**
     * @param string $markup
     * @return string
     */
    public function blockIframes(
        string $markup,
        bool $enabled = true,
        string $groupOverride = null
    ): string {
        if (!$enabled) {
            return $markup;
        }

        return $this->replaceTags("iframe", $markup, function (
            $tag,
            $blockConfig
        ) use ($groupOverride) {
            // IMPORTANT: keep the order here or update all tests!
            $tag = TagHelper::tagRenameAttribute(
                $tag,
                TagHelper::SRC,
                TagHelper::DATA_SRC
            );
            $tag = $this->addDataNameAttribute(
                $tag,
                $blockConfig,
                $groupOverride
            );
            $tag = TagHelper::tagAddAttribute($tag, "style", "display: none;");
            $tag = $this->addDataOptionsAttribute($tag, $blockConfig);

            return $tag;
        });
    }

    /**
     * @param string $markup
     * @return string
     */
    public function blockScripts(
        string $markup,
        bool $enabled = true,
        string $groupOverride = null
    ): string {
        if (!$enabled) {
            return $markup;
        }

        return $this->replaceTags("script", $markup, function (
            $tag,
            $blockConfig
        ) use ($groupOverride) {
            // IMPORTANT: keep the order here or update all tests!
            $tag = TagHelper::tagRenameAttribute(
                $tag,
                TagHelper::SRC,
                TagHelper::DATA_SRC
            );
            $hasType = TagHelper::tagHasAttribute($tag, TagHelper::TYPE);
            if (TagHelper::tagHasAttribute($tag, TagHelper::DATA_SRC)) {
                if ($hasType) {
                    $tag = TagHelper::tagRenameAttribute(
                        $tag,
                        TagHelper::TYPE,
                        TagHelper::DATA_TYPE
                    );
                    $tag = TagHelper::tagAddAttribute(
                        $tag,
                        TagHelper::TYPE,
                        TagHelper::TEXT_PLAIN
                    );
                }
            } else {
                // nor src so we have to "break" the tag by setting the type
                if ($hasType) {
                    $tag = TagHelper::tagRenameAttribute(
                        $tag,
                        TagHelper::TYPE,
                        TagHelper::DATA_TYPE
                    );
                    $tag = TagHelper::tagAddAttribute(
                        $tag,
                        TagHelper::TYPE,
                        TagHelper::TEXT_PLAIN
                    );
                } else {
                    $tag = TagHelper::tagAddAttribute(
                        $tag,
                        TagHelper::TYPE,
                        TagHelper::TEXT_PLAIN
                    );
                    $tag = TagHelper::tagAddAttribute(
                        $tag,
                        TagHelper::DATA_TYPE,
                        TagHelper::TEXT_JAVASCRIPT
                    );
                }
            }
            $tag = $this->addDataNameAttribute(
                $tag,
                $blockConfig,
                $groupOverride
            );
            $tag = $this->addDataOptionsAttribute($tag, $blockConfig);

            return $tag;
        });
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

    private function addDataNameAttribute(
        string $tag,
        array $blockConfig,
        string $groupOverride = null
    ): string {
        if (!TagHelper::tagHasAttribute($tag, TagHelper::DATA_NAME)) {
            $value = $groupOverride
                ? $groupOverride
                : $blockConfig[self::SETTINGS_GROUP];
            return TagHelper::tagAddAttribute(
                $tag,
                TagHelper::DATA_NAME,
                $value
            );
        }
        return $tag;
    }

    private function addDataOptionsAttribute(
        string $tag,
        array $blockConfig
    ): string {
        if (isset($blockConfig[self::SETTINGS_OPTIONS])) {
            $encodedOptions = htmlspecialchars(
                json_encode($blockConfig[self::SETTINGS_OPTIONS]),
                ENT_QUOTES,
                'UTF-8'
            );
            return TagHelper::tagAddAttribute(
                $tag,
                TagHelper::DATA_OPTIONS,
                $encodedOptions
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
     * @param string $tag
     * @return array
     */
    private function getBlockConfig(string $tag): array
    {
        if (!$this->patterns) {
            return $this->buildBlockConfigForPatternConfig();
        }

        foreach ($this->patterns as $pattern => $config) {
            if ($this->tagContains($tag, $pattern)) {
                return $this->buildBlockConfigForPatternConfig($config);
            }
        }
        return $this->buildBlockConfigForPatternConfig();
    }

    /**
     * @param array|null $config
     * @return array
     */
    private function buildBlockConfigForPatternConfig(
        array $config = null
    ): array {
        $fallbackBlocked = isset($this->block) ? $this->block : false;
        $fallbackGroup = isset($this->defaultGroup)
            ? $this->defaultGroup
            : self::DEFAULT_GROUP;

        // no config early return
        if (!$config) {
            return [
                self::SETTINGS_BLOCK => $fallbackBlocked,
                self::SETTINGS_GROUP => $fallbackGroup,
            ];
        }

        $blocked = isset($config[self::SETTINGS_BLOCK])
            ? $config[self::SETTINGS_BLOCK]
            : $fallbackBlocked;
        $group = isset($config[self::SETTINGS_GROUP])
            ? $config[self::SETTINGS_GROUP]
            : $fallbackGroup;
        $options = isset($config[self::SETTINGS_OPTIONS])
            ? $config[self::SETTINGS_OPTIONS]
            : null;

        return [
            self::SETTINGS_BLOCK => $blocked,
            self::SETTINGS_GROUP => $group,
            self::SETTINGS_OPTIONS => $options,
        ];
    }

    /**
     * @param string $tagName
     * @param string $text
     * @param callable $hitCallback
     * @return string
     */
    private function replaceTags(
        string $tagName,
        string $text,
        callable $hitCallback
    ): string {
        $regex = '/<' . $tagName . '.*?>/';

        return preg_replace_callback(
            $regex,
            function ($hits) use ($hitCallback) {
                $tag = $hits[0];

                if (!$hitCallback) {
                    return $tag;
                }

                $neverBlock = $this->tagContains(
                    $tag,
                    TagHelper::DATA_NEVER_BLOCK
                );
                if ($neverBlock) {
                    return $tag;
                }

                $hasBlockingAttributes =
                    TagHelper::tagHasAttribute($tag, TagHelper::DATA_SRC) ||
                    TagHelper::tagHasAttribute(
                        $tag,
                        TagHelper::TYPE,
                        TagHelper::TEXT_PLAIN
                    ) ||
                    TagHelper::tagHasAttribute(
                        $tag,
                        TagHelper::DATA_TYPE,
                        TagHelper::TEXT_JAVASCRIPT
                    );
                // We do not check if TagHelper::DATA_NAME is present, because we might want the editor
                // to choose a group, e.g. in the inspector and still block tags.
                if ($hasBlockingAttributes) {
                    return $tag;
                }

                $blockConfig = $this->getBlockConfig($tag);

                if ($blockConfig[self::SETTINGS_BLOCK]) {
                    return call_user_func($hitCallback, $tag, $blockConfig);
                } else {
                    return $tag;
                }
            },
            $text
        );
    }

    private function validateGroup(string $name = null)
    {
        if ($name && !isset($this->groups[$name])) {
            throw new \InvalidArgumentException(
                'The group "' .
                    $name .
                    '" could not be found in your config. Expected config for "Sandstorm.CookieCutter.groups.' .
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
