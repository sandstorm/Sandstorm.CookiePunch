<?php

namespace Sandstorm\CookiePunch;

/**
 * Class TagHelper
 * @package Sandstorm\CookiePunch
 */
class TagHelper
{
    // Attribute Constants
    const SRC = "src";
    const DATA_SRC = "data-src";
    const TYPE = "type";
    const DATA_TYPE = "data-type";
    const DATA_NAME = "data-name";
    const DATA_OPTIONS = "data-options";
    const DATA_NEVER_BLOCK = "data-never-block";

    // Type Constants
    const TYPE_TEXT_PLAIN = "text/plain";
    const TYPE_APPLICATION_JSON_LD = "application/ld+json";

    // TODO: switch to "text/javascript" at some point
    // update tests
    const TYPE_JAVASCRIPT = "text/javascript";

    /**
     * @param string $tag
     * @param string $name
     * @param string|null $value
     * @return bool
     */
    static function tagHasAttribute(
        string $tag,
        string $name,
        string $value = null
    ): bool {
        if (!$value) {
            return !!preg_match(
                self::buildMatchAttributeNameReqex($name),
                $tag
            );
        } else {
            return !!preg_match(
                self::buildMatchAttributeNameWithSpecificValueReqex(
                    $name,
                    $value
                ),
                $tag
            );
        }
    }

    /**
     * @param string $tag
     * @param string $name
     * @param string $newName
     * @return string
     */
    static function tagRenameAttribute(
        string $tag,
        string $name,
        string $newName
    ): string {
        return preg_replace_callback(
            self::buildMatchAttributeNameReqex($name),
            function ($hits) use ($newName) {
                return $hits["pre"] . $newName . $hits["post"];
            },
            $tag
        );
    }

    /**
     * @param string $tag
     * @param string $name
     * @return string
     */
    static function tagGetAttributeValue(string $tag, string $name): ?string
    {
        preg_match(
            self::buildMatchAttributeNameWithAnyValueReqex($name),
            $tag,
            $matches
        );
        return isset($matches['value']) ? $matches['value'] : null;
    }

    /**
     * @param string $tag
     * @param string $name
     * @param string $newValue
     * @return string
     */
    static function tagChangeAttributeValue(
        string $tag,
        string $name,
        string $newValue
    ): string {
        return preg_replace_callback(
            self::buildMatchAttributeNameWithAnyValueReqex($name),
            function ($hits) use ($newValue) {
                return $hits["pre"] .
                    $hits["name"] .
                    $hits["glue"] .
                    $newValue .
                    $hits["post"];
            },
            $tag
        );
    }

    /**
     * @param string $tag
     * @param string $name
     * @param string $value
     * @return string
     */
    static function tagAddAttribute(
        string $tag,
        string $name,
        string $value = null
    ): string {
        return preg_replace_callback(
            self::buildMatchEndOfOpeningTagReqex(),
            function ($hits) use ($name, $value) {
                if ($value) {
                    return $hits["start"] .
                        ' ' .
                        $name .
                        '="' .
                        $value .
                        '"' .
                        $hits["end"];
                } else {
                    return $hits["start"] . ' ' . $name . $hits["end"];
                }
            },
            $tag
        );
    }

    /**
     * @param string $value
     * @return string
     */
    private static function escapeReqexCharsInString(string $value): string
    {
        // for some reason "/" is not escaped
        return str_replace("/", "\/", preg_quote($value));
    }

    // ">" or "/>"

    /**
     * @return string
     */
    private static function buildMatchEndOfOpeningTagReqex(): string
    {
        return '/(?<start><[a-z]+.*?)(?<end>>|\/>)/';
    }

    /**
     * @param string $name
     * @return string
     */
    private static function buildMatchAttributeNameWithAnyValueReqex(
        string $name
    ): string {
        $nameQuoted = self::escapeReqexCharsInString($name);
        return '/(?<pre><.*? )(?<name>' .
            $nameQuoted .
            ')(?<glue>=")(?<value>.*?)(?<post>".*?>)/';
    }

    /**
     * @param string $name
     * @return string
     */
    private static function buildMatchAttributeNameReqex(string $name): string
    {
        $nameQuoted = self::escapeReqexCharsInString($name);
        return '/(?<pre><.*? )(?<name>' . $nameQuoted . ')(?<post>.*?>)/';
    }

    /**
     * @param string $name
     * @param string $value
     * @return string
     */
    private static function buildMatchAttributeNameWithSpecificValueReqex(
        string $name,
        string $value
    ): string {
        $nameQuoted = self::escapeReqexCharsInString($name);
        $valueQuoted = self::escapeReqexCharsInString($value);
        return '/(?<pre><.*? )(?<name>' .
            $nameQuoted .
            ')(?<glue>=")(?<value>' .
            $valueQuoted .
            ')(?<post>".*?>)/';
    }
}
