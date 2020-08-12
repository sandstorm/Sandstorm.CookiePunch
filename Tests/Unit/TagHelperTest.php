<?php
namespace Sandstorm\PublicWebsite\Tests\Unit;

use Neos\Flow\Tests\UnitTestCase;
use Sandstorm\CookiePunch\TagHelper;

/**
 * Testcase for the ConvertNodeUris Fusion implementation
 */
class TagHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function tagHelper()
    {
        // ### Rename Attribute ###
        $markup = '<div style="with-style" data-foo="bar"></div>';
        $expected = '<div data-style="with-style" data-foo="bar"></div>';
        $actual = TagHelper::tagRenameAttribute($markup, "style", "data-style");

        self::assertEquals($expected, $actual);

        // selfclosing
        $markup = '<div style="with-style"/>';
        $expected = '<div data-style="with-style"/>';
        $actual = TagHelper::tagRenameAttribute($markup, "style", "data-style");

        self::assertEquals($expected, $actual);

        // no value
        $markup = '<div data-foo/>';
        $expected = '<div data-bar/>';
        $actual = TagHelper::tagRenameAttribute(
            $markup,
            "data-foo",
            "data-bar"
        );

        self::assertEquals($expected, $actual);

        // ### Add Attribute ###
        $markup = '<div style="with-style"></div>';
        $expected = '<div style="with-style" data-new="new"></div>';
        $actual = TagHelper::tagAddAttribute($markup, "data-new", "new");

        self::assertEquals($expected, $actual);

        // selfclosing
        $markup = '<div style="with-style"/>';
        $expected = '<div style="with-style" data-new="new"/>';
        $actual = TagHelper::tagAddAttribute($markup, "data-new", "new");

        self::assertEquals($expected, $actual);

        // no value
        $markup = '<div style="with-style"/>';
        $expected = '<div style="with-style" data-new/>';
        $actual = TagHelper::tagAddAttribute($markup, "data-new");

        self::assertEquals($expected, $actual);

        // ### Change Attribute Value ###
        $markup = '<div style="with-style"></div>';
        $expected = '<div style="changed"></div>';
        $actual = TagHelper::tagChangeAttributeValue(
            $markup,
            "style",
            "changed"
        );

        self::assertEquals($expected, $actual);

        // selfclosing
        $markup = '<div style="with-style"/>';
        $expected = '<div style="changed"/>';
        $actual = TagHelper::tagChangeAttributeValue(
            $markup,
            "style",
            "changed"
        );

        self::assertEquals($expected, $actual);

        // ### Has Attribute ###
        $markup = '<div style="with-style" data-foo></div>';

        self::assertEquals(true, TagHelper::tagHasAttribute($markup, "style"));
        self::assertEquals(
            true,
            TagHelper::tagHasAttribute($markup, "style", "with-style")
        );
        self::assertEquals(
            true,
            TagHelper::tagHasAttribute($markup, "data-foo")
        );
        self::assertEquals(
            false,
            TagHelper::tagHasAttribute($markup, "style", "other")
        );
    }
}
