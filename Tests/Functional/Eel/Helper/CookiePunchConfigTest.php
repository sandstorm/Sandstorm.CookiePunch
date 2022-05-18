<?php

namespace Sandstorm\CookiePunch\Tests\Functional\Eel\Helper;

use DateTime;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\Flow\Tests\FunctionalTestCase;
use Sandstorm\CookiePunch\Eel\Helper\CookiePunchConfig;

/**
 * Testcase
 */
class CookiePunchConfigTest extends FunctionalTestCase
{
    private Node $dummySiteNode;

    /**
     * @before
     */
    public function initDummySiteNode() {
        $this->dummySiteNode = new Node(
            new NodeData(
                "/sites/website",
                new Workspace("")),
            new Context("",
                new DateTime(),
                [],
                [],
                false,
                false,
                false
            )
        );
    }

    /**
     * @test
     */
    public function validEelExpressionIsEvaluatedCorrectly()
    {
        $conditionalServiceRendering = new CookiePunchConfig();

        $eelExpression = '${1 + 1 == 2}';
        $actual = $conditionalServiceRendering->evaluateEelExpression($eelExpression, $this->dummySiteNode);

        self::assertTrue($actual, "Not a valid eel expression: " . $eelExpression);
    }

    /**
     * @test
     */
    public function invalidEelExpressionThrowsException()
    {
        self::expectExceptionMessage('Invalid eel expression in CookiePunch config service block. Given: ${1 + 1 == 2');

        $conditionalServiceRendering = new CookiePunchConfig();

        $eelExpression = '${1 + 1 == 2';
        $conditionalServiceRendering->evaluateEelExpression($eelExpression, $this->dummySiteNode);
    }

    /**
     * @test
     */
    public function eelExpressionThatDoesNotResolveToBoolThrowsException()
    {
        self::expectExceptionMessage('An eel expression was used in CookiePunch config service block that does not resolve to boolean. Given: ${"hello"}');

        $conditionalServiceRendering = new CookiePunchConfig();

        $eelExpression = '${"hello"}';
        $conditionalServiceRendering->evaluateEelExpression($eelExpression, $this->dummySiteNode);
    }

    /**
     * @test
     * We want to test that the passed sitenode is added correctly to the context. We mock a simple Node here as $siteNode
     * because that seems just enough to test this behavior.
     */
    public function testIfSitenodeIsPutCorrectlyIntoContext()
    {
        $conditionalServiceRendering = new CookiePunchConfig();

        $eelExpression = '${site != null}';

        $actual = $conditionalServiceRendering->evaluateEelExpression($eelExpression, $this->dummySiteNode);

        self::assertTrue($actual, "Sitenode not found in context");
    }

    /**
     * @test
     */
    public function noServicePassedReturnsEmptyArray()
    {
        $services = [];

        $expected = [];

        $actual = (new CookiePunchConfig())->filterServicesArrayByWhenCondition($services, $this->dummySiteNode);

        $this->assertArraysAreEqual($expected, $actual);
    }

    /**
     * @test
     */
    public function aServiceWithWhenEvaluatingToTrueIsKept()
    {
        $services = [
            'youtube' => [
                'when' => '${true}'
            ],
        ];

        $expected = [
            'youtube' => [
                'when' => '${true}'
            ],
        ];

        $actual = (new CookiePunchConfig())->filterServicesArrayByWhenCondition($services, $this->dummySiteNode);

        $this->assertArraysAreEqual($expected, $actual);
    }

    /**
     * @test
     */
    public function aServiceWithWhenEvaluatingToFalseIsRemoved()
    {
        $services = [
            'youtube' => [
                'when' => '${false}'
            ],
        ];

        $expected = [
        ];

        $actual = (new CookiePunchConfig())->filterServicesArrayByWhenCondition($services, $this->dummySiteNode);

        $this->assertArraysAreEqual($expected, $actual);
    }

    /**
     * @test
     */
    public function aServiceWithoutAWhenExpressionIsKept()
    {
        $services = [
            'youtube' => [
                'title' => 'youtube'
            ],
        ];

        $expected = [
            'youtube' => [
                'title' => 'youtube'
            ],
        ];

        $actual = (new CookiePunchConfig())->filterServicesArrayByWhenCondition($services, $this->dummySiteNode);

        $this->assertArraysAreEqual($expected, $actual);
    }

    private function assertArraysAreEqual(array $array1, array $array2)
    {
        $arraysAreEqual = strcmp(json_encode($array1), json_encode($array2)) == 0;

        self::assertTrue($arraysAreEqual, "The filtered services array does not contain the expected services");
    }
}
