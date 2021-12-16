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
        $actualFlagged = $cookiePunch->neverBlockTags(["script"], $markup);
        $actualNotBlocked = $cookiePunch->blockTags(["script"], $actualFlagged);
        $expected = '<script src="myscripts.js" data-never-block></script>';

        self::assertEquals($expected, $actualFlagged);
        self::assertEquals($expected, $actualNotBlocked);

        $markup = '<iframe src="https://www.w3schools.com">';
        $actualFlagged = $cookiePunch->neverBlockTags(["iframe"], $markup);
        $actualNotBlocked = $cookiePunch->blockTags(["iframe"], $actualFlagged);
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
        $actualFlagged = $cookiePunch->neverBlockTags(["script"], $markup);
        $actualFlagged = $cookiePunch->neverBlockTags(["script"], $actualFlagged);
        $actualFlagged = $cookiePunch->neverBlockTags(["script"], $actualFlagged);
        $actualFlagged = $cookiePunch->neverBlockTags(["script"], $actualFlagged);
        $expected = '<script src="myscripts.js" data-never-block></script>';

        self::assertEquals($expected, $actualFlagged);

        $markup = '<iframe src="https://www.w3schools.com">';
        $actualFlagged = $cookiePunch->neverBlockTags(["iframe"], $markup);
        $actualFlagged = $cookiePunch->neverBlockTags(["iframe"], $actualFlagged);
        $actualFlagged = $cookiePunch->neverBlockTags(["iframe"], $actualFlagged);
        $actualFlagged = $cookiePunch->neverBlockTags(["iframe"], $actualFlagged);
        $expected = '<iframe src="https://www.w3schools.com" data-never-block>';

        self::assertEquals($expected, $actualFlagged);
    }
}
