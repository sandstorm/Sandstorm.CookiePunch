<?php
namespace Sandstorm\PublicWebsite\Tests\Unit;

use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;
use Sandstorm\CookiePunch\Eel\Helper\CookiePunch;

/**
 * Testcase
 */
class AddContextualConsentTest extends UnitTestCase
{
    /**
     * @test
     */
    public function contextualConsentWasAdded()
    {
        $cookiePunch = new CookiePunch();

        $markup = '<audio><source src="some.mp3"/><source src="some.mp3"/></audio>';
        $expected =
            '<div data-name="myservice"><audio><source data-src="some.mp3"/><source data-src="some.mp3"/></audio></div>';

        $actual = $cookiePunch->addContextualConsent("myservice", $markup);
        $actual = $cookiePunch->blockTags(["audio", "source"], $actual);

        self::assertEquals($expected, $actual);
    }
}
