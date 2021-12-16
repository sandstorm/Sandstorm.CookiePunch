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
        $blockExternalContentHelper = new CookiePunch();

        $markup = '<audio src="some.mp3"></audio>';
        $expected =
            '<audio data-src="some.mp3"></audio>';
        $actual = $blockExternalContentHelper->blockTag("audio", $markup);
        self::assertEquals($expected, $actual);

        $markup = '<audio src="some.mp3"/>';
        $expected =
            '<audio data-src="some.mp3"/>';
        $actual = $blockExternalContentHelper->blockTag("audio", $markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function singleAudioTagWithType() {
        $blockExternalContentHelper = new CookiePunch();

        $markup = '<audio src="some.mp3" type="audio/mpeg"></audio>';
        $expected =
            '<audio data-src="some.mp3" type="audio/mpeg" data-type="audio/mpeg"></audio>';
        $actual = $blockExternalContentHelper->blockTag("audio", $markup);
        self::assertEquals($expected, $actual);

        $markup = '<audio src="some.mp3" type="audio/mpeg"/>';
        $expected =
            '<audio data-src="some.mp3" type="audio/mpeg" data-type="audio/mpeg"/>';
        $actual = $blockExternalContentHelper->blockTag("audio", $markup);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function audioTagWithSource() {
        $blockExternalContentHelper = new CookiePunch();

        $markup = '<audio><source src="some.mp3"/><source src="some.mp3"/></audio>';
        $expected =
            '<audio><source data-src="some.mp3"/><source data-src="some.mp3"/></audio>';
        $actual = $blockExternalContentHelper->blockTag("audio", $markup);
        $actual = $blockExternalContentHelper->blockTag("source", $actual);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function audioTagWithSourceAndType() {
        $blockExternalContentHelper = new CookiePunch();

        $markup = '<audio><source src="some.mp3" type="audio/mpeg"/><source src="some.mp3" type="audio/mpeg"/></audio>';
        $expected =
            '<audio><source data-src="some.mp3" type="audio/mpeg" data-type="audio/mpeg"/><source data-src="some.mp3" type="audio/mpeg" data-type="audio/mpeg"/></audio>';
        $actual = $blockExternalContentHelper->blockTag("audio", $markup);
        $actual = $blockExternalContentHelper->blockTag("source", $actual);
        self::assertEquals($expected, $actual);
    }
}
