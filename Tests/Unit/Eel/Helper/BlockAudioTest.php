<?php
namespace Sandstorm\PublicWebsite\Tests\Unit;

use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;
use Sandstorm\CookiePunch\Eel\Helper\CookiePunch;

/**
 * Testcase
 */
class BlockAudioTest extends UnitTestCase
{
    /**
     * @test
     */
    public function singleAudioTag() {
        $cookiePunch = new CookiePunch();

        $markup = '<audio src="some.mp3"></audio>';
        $expected =
            '<audio data-src="some.mp3"></audio>';
        $actual = $cookiePunch->blockTags(["audio"], $markup);
        self::assertEquals($expected, $actual);

        $markup = '<audio src="some.mp3"/>';
        $expected =
            '<audio data-src="some.mp3"/>';
        $actual = $cookiePunch->blockTags(["audio"], $markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function singleAudioTagWithType() {
        $cookiePunch = new CookiePunch();

        $markup = '<audio src="some.mp3" type="audio/mpeg"></audio>';
        $expected =
            '<audio data-src="some.mp3" type="audio/mpeg" data-type="audio/mpeg"></audio>';
        $actual = $cookiePunch->blockTags(["audio"], $markup);
        self::assertEquals($expected, $actual);

        $markup = '<audio src="some.mp3" type="audio/mpeg"/>';
        $expected =
            '<audio data-src="some.mp3" type="audio/mpeg" data-type="audio/mpeg"/>';
        $actual = $cookiePunch->blockTags(["audio"], $markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function audioTagWithSource() {
        $cookiePunch = new CookiePunch();

        $markup = '<audio><source src="some.mp3"/><source src="some.mp3"/></audio>';
        $expected =
            '<audio><source data-src="some.mp3"/><source data-src="some.mp3"/></audio>';
        $actual = $cookiePunch->blockTags(["audio", "source"], $markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function audioTagWithSourceAndType() {
        $cookiePunch = new CookiePunch();

        $markup = '<audio><source src="some.mp3" type="audio/mpeg"/><source src="some.mp3" type="audio/mpeg"/></audio>';
        $expected =
            '<audio><source data-src="some.mp3" type="audio/mpeg" data-type="audio/mpeg"/><source data-src="some.mp3" type="audio/mpeg" data-type="audio/mpeg"/></audio>';
        $actual = $cookiePunch->blockTags(["audio", "source"], $markup);
        self::assertEquals($expected, $actual);
    }
}
