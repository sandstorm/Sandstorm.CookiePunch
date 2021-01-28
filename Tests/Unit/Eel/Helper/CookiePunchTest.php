<?php
namespace Sandstorm\PublicWebsite\Tests\Unit;

use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;
use Sandstorm\CookiePunch\Eel\Helper\CookiePunch;

/**
 * Testcase
 */
class CookiePunchTest extends UnitTestCase
{
    /**
     * @test
     */
    public function scriptTagsWillBeBlocked()
    {
        $blockExternalContentHelper = new CookiePunch();

        ObjectAccess::setProperty(
            $blockExternalContentHelper,
            CookiePunch::SETTINGS_BLOCK_ALL,
            true,
            true
        );

        // ### <script> with NO attribute ###

        $markup = '<script>var foo="bar";</script>';
        $expected =
            '<script type="text/plain" data-type="text/javascript" data-name="default">var foo="bar";</script>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($expected, $actual);

        // selfclosing
        $markup = '<script/>';
        $expected =
            '<script type="text/plain" data-type="text/javascript" data-name="default"/>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($expected, $actual);

        // ### <script> with type attribute ###

        $markup = '<script type="text/javascript"></script>';
        $expected =
            '<script data-type="text/javascript" type="text/plain" data-name="default"></script>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($expected, $actual);

        // ### <script> with src attribute ###
        // IMPORTANT: we need to add data-type="text/javascript" here to prevent Klaro from
        // not correctly recovering the correct value.
        $markup = '<script src="myscripts.js"></script>';
        $expected =
            '<script data-src="myscripts.js" data-type="text/javascript" data-name="default"></script>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($expected, $actual);

        // ### <script> with src and type attribute ###
        $markup =
            '<script src="myscripts.js" defer type="text/javascript"></script>';
        $expected =
            '<script data-src="myscripts.js" defer data-type="text/javascript" type="text/plain" data-name="default"></script>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($expected, $actual);

        // selfclosing
        $markup = '<script src="myscripts.js" type="text/javascript"/>';
        $expected =
            '<script data-src="myscripts.js" data-type="text/javascript" type="text/plain" data-name="default"/>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($expected, $actual);

        // ### <script> with "application/javascript" or other types will be blocked ###
        $markup =
            '<script src="myscripts.js" defer type="application/javascript"></script>';
        $expected =
            '<script data-src="myscripts.js" defer data-type="application/javascript" type="text/plain" data-name="default"></script>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function tagsWithDataNameWillBeBlocked()
    {
        $blockExternalContentHelper = new CookiePunch();
        ObjectAccess::setProperty(
            $blockExternalContentHelper,
            CookiePunch::SETTINGS_BLOCK_ALL,
            true,
            true
        );

        $markup = '<script src="myscripts.js" data-name="default"></script>';
        $expected =
            '<script data-src="myscripts.js" data-name="default" data-type="text/javascript"></script>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($expected, $actual);

        $markup =
            '<iframe src="https://www.w3schools.com" data-name="default"></iframe>';
        $expected =
            '<iframe data-src="https://www.w3schools.com" data-name="default" style="display: none;"></iframe>';
        $actual = $blockExternalContentHelper->blockIframes($markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function tagsWillUseGroupFromEelHelperAndNotSettings()
    {
        $blockExternalContentHelper = new CookiePunch();
        ObjectAccess::setProperty(
            $blockExternalContentHelper,
            CookiePunch::SETTINGS_BLOCK_ALL,
            true,
            true
        );

        $markup = '<script src="myscripts.js"></script>';
        $expected = '<script data-src="myscripts.js" data-type="text/javascript" data-name="foo"></script>';
        $actual = $blockExternalContentHelper->blockScripts(
            $markup,
            true,
            "foo"
        );
        self::assertEquals($expected, $actual);

        $markup = '<iframe src="https://www.w3schools.com"></iframe>';
        $expected =
            '<iframe data-src="https://www.w3schools.com" data-name="bar" style="display: none;"></iframe>';
        $actual = $blockExternalContentHelper->blockIframes(
            $markup,
            true,
            "bar"
        );
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function scriptTagsWithSpecialMimetypesWillNeverBeBlocked()
    {
        $blockExternalContentHelper = new CookiePunch();
        ObjectAccess::setProperty(
            $blockExternalContentHelper,
            CookiePunch::SETTINGS_BLOCK_ALL,
            true,
            true
        );

        // Do nothing -> keep the type as it is not "text/javascript"

        $markup = '<script type="text/plain"></script>';
        $expected = '<script type="text/plain"></script>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($expected, $actual);

        $markup = '<script type="application/ld+json"></script>';
        $expected = '<script type="application/ld+json"></script>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function alreadyBlockedScriptTagsWillNeverBeBlocked()
    {
        $blockExternalContentHelper = new CookiePunch();
        ObjectAccess::setProperty(
            $blockExternalContentHelper,
            CookiePunch::SETTINGS_BLOCK_ALL,
            true,
            true
        );

        $markup =
            '<script data-src="myscripts.js" data-name="default"></script>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($markup, $actual);

        $markup =
            '<script type="text/plain" data-type="text/javascript" data-name="default">var foo="bar";</script>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($markup, $actual);
    }

    /**
     * @test
     */
    public function markedTagsWillNeverBeBlocked()
    {
        $blockExternalContentHelper = new CookiePunch();

        ObjectAccess::setProperty(
            $blockExternalContentHelper,
            CookiePunch::SETTINGS_BLOCK_ALL,
            true,
            true
        );

        $markup = '<script src="myscripts.js"></script>';
        $actualWithDataAttribute = $blockExternalContentHelper->neverBlockScripts(
            $markup
        );
        $actualNotBlocked = $blockExternalContentHelper->neverBlockScripts(
            $actualWithDataAttribute
        );
        $expected = '<script src="myscripts.js" data-never-block></script>';

        self::assertEquals($expected, $actualWithDataAttribute);
        self::assertEquals($expected, $actualNotBlocked);

        $markup = '<iframe src="https://www.w3schools.com">';
        $actualWithDataAttribute = $blockExternalContentHelper->neverBlockIframes(
            $markup
        );
        $actualNotBlocked = $blockExternalContentHelper->neverBlockIframes(
            $actualWithDataAttribute
        );
        $expected = '<iframe src="https://www.w3schools.com" data-never-block>';

        self::assertEquals($expected, $actualWithDataAttribute);
        self::assertEquals($expected, $actualNotBlocked);
    }

    /**
     * @test
     */
    public function scriptTagsWithPatternWillBeBlocked()
    {
        $blockExternalContentHelper = new CookiePunch();
        $patterns = [
            "Packages/Vendor.Example" => [
                CookiePunch::SETTINGS_BLOCK => true,
                CookiePunch::SETTINGS_GROUP => "vendor",
            ],
        ];

        ObjectAccess::setProperty(
            $blockExternalContentHelper,
            CookiePunch::SETTINGS_BLOCK_PATTERNS,
            $patterns,
            true
        );

        // no pattern matched -> not blocked
        $markup = '<script src="myscripts.js" type="text/javascript"></script>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($markup, $actual);

        // no pattern matched -> not blocked
        $markup =
            '<script src="https://example.com/foobar.js" type="text/javascript"></script><script src="https://example.com/bazbar.js" type="text/javascript"></script>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($markup, $actual);

        // pattern matched -> blocked
        $markup =
            '<script src="Packages/Vendor.Example/myscripts.js" type="text/javascript"/>';
        $expected =
            '<script data-src="Packages/Vendor.Example/myscripts.js" data-type="text/javascript" type="text/plain" data-name="vendor"/>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function scriptTagsWithPatternWillNotBeBlocked()
    {
        $blockExternalContentHelper = new CookiePunch();
        $patterns = [
            "Packages/Vendor.Example" => [
                CookiePunch::SETTINGS_BLOCK => false,
            ],
        ];

        // block all by default
        ObjectAccess::setProperty(
            $blockExternalContentHelper,
            CookiePunch::SETTINGS_BLOCK_ALL,
            true,
            true
        );
        ObjectAccess::setProperty(
            $blockExternalContentHelper,
            CookiePunch::SETTINGS_BLOCK_PATTERNS,
            $patterns,
            true
        );

        // no pattern matched -> blocked
        $markup = '<script type="text/javascript"></script>';
        $expected =
            '<script data-type="text/javascript" type="text/plain" data-name="default"></script>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($expected, $actual);

        // pattern matched -> not blocked
        $markup =
            '<script src="Packages/Vendor.Example/myscripts.js" type="text/javascript"/>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($markup, $actual);
    }

    /**
     * @test
     */
    public function patternOptionsWillBeAddedToTags()
    {
        $blockExternalContentHelper = new CookiePunch();
        $patterns = [
            "Packages/Vendor.Example" => [
                CookiePunch::SETTINGS_BLOCK => true,
                CookiePunch::SETTINGS_OPTIONS => ["foo" => "bar"],
            ],
            "foo/bar.html" => [
                CookiePunch::SETTINGS_BLOCK => true,
                CookiePunch::SETTINGS_OPTIONS => ["foo" => "baz"],
            ],
        ];

        ObjectAccess::setProperty(
            $blockExternalContentHelper,
            CookiePunch::SETTINGS_BLOCK_PATTERNS,
            $patterns,
            true
        );

        // <script>
        $markup = '<script src="Packages/Vendor.Example/myscripts.js"/>';
        $expected =
            '<script data-src="Packages/Vendor.Example/myscripts.js" data-type="text/javascript" data-name="default" data-options="{&quot;foo&quot;:&quot;bar&quot;}"/>';
        $actual = $blockExternalContentHelper->blockScripts($markup);
        self::assertEquals($expected, $actual);

        // <iframe>
        $markup = '<iframe src="foo/bar.html"/>';
        $expected =
            '<iframe data-src="foo/bar.html" data-name="default" style="display: none;" data-options="{&quot;foo&quot;:&quot;baz&quot;}"/>';
        $actual = $blockExternalContentHelper->blockIframes($markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function iframesWillBeBlocked()
    {
        $blockExternalContentHelper = new CookiePunch();

        $patterns = [
            "with-style" => [CookiePunch::SETTINGS_BLOCK => true],
        ];

        ObjectAccess::setProperty(
            $blockExternalContentHelper,
            CookiePunch::SETTINGS_BLOCK_PATTERNS,
            $patterns,
            true
        );
        ObjectAccess::setProperty(
            $blockExternalContentHelper,
            CookiePunch::SETTINGS_BLOCK_ALL,
            true,
            true
        );

        // ### <iframe> with src ###

        $markup = '<iframe src="https://www.w3schools.com"></iframe>';
        $expected =
            '<iframe data-src="https://www.w3schools.com" data-name="default" style="display: none;"></iframe>';
        $actual = $blockExternalContentHelper->blockIframes($markup);

        self::assertEquals($expected, $actual);

        $markup = '<iframe src="with-style"/>';
        $expected =
            '<iframe data-src="with-style" data-name="default" style="display: none;"/>';
        $actual = $blockExternalContentHelper->blockIframes($markup);

        self::assertEquals($expected, $actual);
    }
}
