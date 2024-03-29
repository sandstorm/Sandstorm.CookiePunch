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

        $markup = '<audio><source src="some.mp3"/><source src="some.mp3"/></audio>';
        $expected =
            '<div data-name="bar"><audio><source data-src="some.mp3"/><source data-src="some.mp3"/></audio></div>';

        $actual = $cookiePunch->addContextualConsent("bar", $markup);
        $actual = $cookiePunch->blockTags(["audio", "source"], $actual);

        self::assertEquals($expected, $actual);
    }
}
