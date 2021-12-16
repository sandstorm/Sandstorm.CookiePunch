<?php
namespace Sandstorm\PublicWebsite\Tests\Unit;

use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;
use Sandstorm\CookiePunch\Eel\Helper\CookiePunch;

/**
 * Testcase
 */
class NeverBlockTest extends UnitTestCase
{
    /**
     * @test
     */
    public function tagsWillNeverBeBlocked()
    {
        $cookiePunch = new CookiePunch();

        $markup = '<script src="myscripts.js"></script>';
        $actualFlagged = $cookiePunch->neverBlockTag("script", $markup);
        $actualNotBlocked = $cookiePunch->blockTag("script", $actualFlagged);
        $expected = '<script src="myscripts.js" data-never-block></script>';

        self::assertEquals($expected, $actualFlagged);
        self::assertEquals($expected, $actualNotBlocked);

        $markup = '<iframe src="https://www.w3schools.com">';
        $actualFlagged = $cookiePunch->neverBlockTag("iframe", $markup);
        $actualNotBlocked = $cookiePunch->blockTag("iframe", $actualFlagged);
        $expected = '<iframe src="https://www.w3schools.com" data-never-block>';

        self::assertEquals($expected, $actualFlagged);
        self::assertEquals($expected, $actualNotBlocked);
    }

    /**
     * @test
     */
    public function alreadyBlockedTagCannotBeBlockedAgain()
    {
        $cookiePunch = new CookiePunch();

        $markup = '<script src="myscripts.js"></script>';
        $actualFlagged = $cookiePunch->neverBlockTag("script", $markup);
        $actualFlagged = $cookiePunch->neverBlockTag("script", $actualFlagged);
        $actualFlagged = $cookiePunch->neverBlockTag("script", $actualFlagged);
        $actualFlagged = $cookiePunch->neverBlockTag("script", $actualFlagged);
        $expected = '<script src="myscripts.js" data-never-block></script>';

        self::assertEquals($expected, $actualFlagged);

        $markup = '<iframe src="https://www.w3schools.com">';
        $actualFlagged = $cookiePunch->neverBlockTag("iframe", $markup);
        $actualFlagged = $cookiePunch->neverBlockTag("iframe", $actualFlagged);
        $actualFlagged = $cookiePunch->neverBlockTag("iframe", $actualFlagged);
        $actualFlagged = $cookiePunch->neverBlockTag("iframe", $actualFlagged);
        $expected = '<iframe src="https://www.w3schools.com" data-never-block>';

        self::assertEquals($expected, $actualFlagged);
    }
}
