<?php
namespace Sandstorm\PublicWebsite\Tests\Unit;

use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;
use Sandstorm\CookiePunch\Eel\Helper\CookiePunch;

/**
 * Testcase
 */
class BlockVideoTest extends UnitTestCase
{
    /**
     * @test
     */
    public function singleVideoTag() {
        $cookiePunch = new CookiePunch();

        $markup = '<video src="some.mp3"></video>';
        $expected =
            '<video data-src="some.mp3"></video>';
        $actual = $cookiePunch->blockTag("video", $markup);
        self::assertEquals($expected, $actual);

        $markup = '<video src="some.mp3"/>';
        $expected =
            '<video data-src="some.mp3"/>';
        $actual = $cookiePunch->blockTag("video", $markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function singleVideoTagWithType() {
        $cookiePunch = new CookiePunch();

        $markup = '<video src="some.mp3" type="video/mp4"></video>';
        $expected =
            '<video data-src="some.mp3" type="video/mp4" data-type="video/mp4"></video>';
        $actual = $cookiePunch->blockTag("video", $markup);
        self::assertEquals($expected, $actual);

        $markup = '<video src="some.mp3" type="video/mp4"/>';
        $expected =
            '<video data-src="some.mp3" type="video/mp4" data-type="video/mp4"/>';
        $actual = $cookiePunch->blockTag("video", $markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function videoTagWithSource() {
        $cookiePunch = new CookiePunch();

        $markup = '<video><source src="some.mp3"/><source src="some.mp3"/></video>';
        $expected =
            '<video><source data-src="some.mp3"/><source data-src="some.mp3"/></video>';
        $actual = $cookiePunch->blockTag("video", $markup);
        $actual = $cookiePunch->blockTag("source", $actual);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function videoTagWithSourceAndType() {
        $cookiePunch = new CookiePunch();

        $markup = '<video><source src="some.mp3" type="video/mp4"/><source src="some.mp3" type="video/mp4"/></video>';
        $expected =
            '<video><source data-src="some.mp3" type="video/mp4" data-type="video/mp4"/><source data-src="some.mp3" type="video/mp4" data-type="video/mp4"/></video>';
        $actual = $cookiePunch->blockTag("video", $markup);
        $actual = $cookiePunch->blockTag("source", $actual);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function videoTagWithSourceTypeAndTrack() {
        $cookiePunch = new CookiePunch();

        $markup = '<video><source src="some.mp3" type="video/mp4"/><track src="foo.vtt"/></video>';
        $expected =
            '<video><source data-src="some.mp3" type="video/mp4" data-type="video/mp4"/><track data-src="foo.vtt"/></video>';
        $actual = $cookiePunch->blockTag("video", $markup);
        $actual = $cookiePunch->blockTag("source", $actual);
        $actual = $cookiePunch->blockTag("track", $actual);
        self::assertEquals($expected, $actual);
    }
}
