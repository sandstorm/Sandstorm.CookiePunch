<?php
namespace Sandstorm\PublicWebsite\Tests\Unit;

use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;
use Sandstorm\CookiePunch\Eel\Helper\CookiePunch;

/**
 * Testcase
 */
class BlockMultipleTagsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function onlySpecifiedTagsShouldBeBlocked() {
        $cookiePunch = new CookiePunch();
        $markup = '<audio src="some.mp3"/><video src="some.mp4"/><img src="some.png"/>';
        $expected =
            '<audio data-src="some.mp3"/><video data-src="some.mp4"/><img src="some.png"/>';
        $actual = $cookiePunch->blockTags(["audio", "video"], $markup);
        self::assertEquals($expected, $actual);
    }
}
