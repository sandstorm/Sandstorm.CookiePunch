<?php
namespace Sandstorm\PublicWebsite\Tests\Unit;

use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;
use Sandstorm\CookiePunch\Eel\Helper\CookiePunch;

/**
 * Testcase
 */
class BlockScriptsTest extends UnitTestCase
{
    public function scriptTagsWillBeBlockedWithoutConfig()
    {
        $cookiePunch = new CookiePunch();

        // ### <script> with NO attribute ###

        $markup = '<script>var foo="bar";</script>';
        $expected =
            '<script type="text/plain" data-type="text/javascript">var foo="bar";</script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($expected, $actual);

        // selfclosing
        $markup = '<script/>';
        $expected = '<script type="text/plain" data-type="text/javascript"/>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($expected, $actual);

        // ### <script> with type attribute ###

        $markup = '<script type="text/javascript"></script>';
        $expected =
            '<script data-type="text/javascript" type="text/plain"></script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($expected, $actual);

        // ### <script> with src attribute ###
        // IMPORTANT: we need to add data-type="text/javascript" here to prevent Klaro from
        // not correctly recovering the correct value.
        $markup = '<script src="myscripts.js"></script>';
        $expected =
            '<script data-src="myscripts.js" data-type="text/javascript"></script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($expected, $actual);

        // ### <script> with src and type attribute ###
        $markup =
            '<script src="myscripts.js" defer type="text/javascript"></script>';
        $expected =
            '<script data-src="myscripts.js" defer data-type="text/javascript" type="text/plain"></script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($expected, $actual);

        // selfclosing
        $markup = '<script src="myscripts.js" type="text/javascript"/>';
        $expected =
            '<script data-src="myscripts.js" data-type="text/javascript" type="text/plain"/>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($expected, $actual);

        // ### <script> with "application/javascript" or other types will be blocked ###
        $markup =
            '<script src="myscripts.js" defer type="application/javascript"></script>';
        $expected =
            '<script data-src="myscripts.js" defer data-type="application/javascript" type="text/plain"></script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($expected, $actual);
    }

    public function scriptTagsWillBeBlockedForPatternConfigWithBlockAttribute()
    {
        $cookiePunch = new CookiePunch();

        ObjectAccess::setProperty(
            $cookiePunch,
            "tagPatterns",
            [
                "script" => [
                    "*" => [
                        "bock" => true,
                    ],
                ],
            ],
            true
        );

        $markup = '<script>var foo="bar";</script>';
        $expected =
            '<script type="text/plain" data-type="text/javascript">var foo="bar";</script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function scriptTagsWillBeBlockedForPatternConfigWithServiceAttribute()
    {
        $cookiePunch = new CookiePunch();

        ObjectAccess::setProperty(
            $cookiePunch,
            "tagPatterns",
            [
                "script" => [
                    "*" => [
                        "service" => "default",
                    ],
                ],
            ],
            true
        );

        $markup = '<script>var foo="bar";</script>';
        $expected =
            '<script data-type="text/javascript" type="text/plain" data-name="default">var foo="bar";</script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function scriptTagsWillBeBlockedAsServiceAttributeWins()
    {
        $cookiePunch = new CookiePunch();

        ObjectAccess::setProperty(
            $cookiePunch,
            "tagPatterns",
            [
                "script" => [
                    "*" => [
                        "block" => false,
                        "service" => "default",
                    ],
                ],
            ],
            true
        );

        $markup = '<script>var foo="bar";</script>';
        $expected =
            '<script data-type="text/javascript" type="text/plain" data-name="default">var foo="bar";</script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function scriptTagsWillNotBeBlockedForPatternConfigWithDefaultBlockingEqualsFalse()
    {
        $cookiePunch = new CookiePunch();

        ObjectAccess::setProperty(
            $cookiePunch,
            "tagPatterns",
            [
                "script" => [
                    "*" => [
                        "block" => false,
                    ],
                ],
            ],
            true
        );

        $markup = '<script>var foo="bar";</script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($markup, $actual);
    }


    /**
     * @test
     */
    public function scriptTagsWithSpecialMimetypesWillNeverBeBlocked()
    {
        $cookiePunch = new CookiePunch();

        // Do nothing -> keep the type as it is not "text/javascript"

        $markup = '<script type="text/plain"></script>';
        $expected = '<script type="text/plain"></script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($expected, $actual);

        $markup = '<script type="application/ld+json"></script>';
        $expected = '<script type="application/ld+json"></script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function alreadyBlockedScriptTagsWillNeverBeBlocked()
    {
        $cookiePunch = new CookiePunch();

        $markup =
            '<script data-src="myscripts.js" data-name="default"></script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($markup, $actual);

        $markup =
            '<script type="text/plain" data-type="text/javascript" data-name="default">var foo="bar";</script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($markup, $actual);
    }

    /**
     * @test
     */
    public function onlyScriptTagsWithPatternWillBeBlockedIfDefaultBlockingIsFalse()
    {
        $cookiePunch = new CookiePunch();

        ObjectAccess::setProperty(
            $cookiePunch,
            "tagPatterns",
            [
                "script" => [
                    "*" => [
                        "block" => false,
                    ],
                    "Packages/Vendor.Example" => [
                        "service" => "foo",
                    ],
                ],
            ],
            true
        );

        // no pattern matched -> not blocked
        $markup = '<script src="myscripts.js" type="text/javascript"></script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($markup, $actual);

        // no pattern matched -> not blocked
        $markup =
            '<script src="https://example.com/foobar.js" type="text/javascript"></script><script src="https://example.com/bazbar.js" type="text/javascript"></script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($markup, $actual);

        // pattern matched -> blocked
        $markup =
            '<script src="Packages/Vendor.Example/myscripts.js" type="text/javascript"/>';
        $expected =
            '<script data-src="Packages/Vendor.Example/myscripts.js" data-type="text/javascript" type="text/plain" data-name="foo"/>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function scriptTagsWithPatternWillNotBeBlockedIfDefaultBlockingIsTrue()
    {
        $cookiePunch = new CookiePunch();

        ObjectAccess::setProperty(
            $cookiePunch,
            "tagPatterns",
            [
                "script" => [
                    "*" => [
                        "service" => "foo",
                    ],
                    "Packages/Vendor.Example" => [
                        "block" => false,
                    ],
                ],
            ],
            true
        );

        // no pattern matched -> blocked
        $markup = '<script type="text/javascript"></script>';
        $expected =
            '<script data-type="text/javascript" type="text/plain" data-name="foo"></script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($expected, $actual);

        // pattern matched -> not blocked
        $markup =
            '<script src="Packages/Vendor.Example/myscripts.js" type="text/javascript" />';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($markup, $actual);
    }

    /**
     * @test
     */
    public function tagsWithDataNameWillBeBlocked()
    {
        $cookiePunch = new CookiePunch();

        $markup = '<script src="myscripts.js" data-name="default"></script>';
        $expected =
            '<script data-src="myscripts.js" data-name="default" data-type="text/javascript" type="text/plain"></script>';
        $actual = $cookiePunch->blockTags(["script"], $markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function tagsWillUseServiceNameFromEelHelperAndNotSettings()
    {
        $cookiePunch = new CookiePunch();
        ObjectAccess::setProperty(
            $cookiePunch,
            "services",
            [
                "bar" => [
                    "title" => "Bar"
                ],
            ],
            true
        );

        $markup = '<script src="myscripts.js"></script>';
        $expected =
            '<script data-src="myscripts.js" data-type="text/javascript" type="text/plain" data-name="bar"></script>';
        $actual = $cookiePunch->blockTags(["script"],
            $markup,
            true,
            "bar"
        );
        self::assertEquals($expected, $actual);
    }
}
