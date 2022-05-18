<?php

namespace Sandstorm\CookiePunch\Tests\Functional\Eel\Helper;

use DateTime;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\Flow\Tests\FunctionalTestCase;
use Sandstorm\CookiePunch\Eel\Helper\ConditionalServiceRendering;

/**
 * Testcase
 */
class ConditionalServiceRenderingTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function validEelExpression()
    {
        $conditionalServiceRendering = new ConditionalServiceRendering();

        $eelExpression = '${1 + 1 == 2}';
        $actual = $conditionalServiceRendering->evaluateEelExpression($eelExpression, null);

        self::assertTrue($actual, "Not a valid eel expression: " . $eelExpression);
    }

    /**
     * @test
     */
    public function invalidEelExpressionThrowsException()
    {
        self::expectExceptionMessage('Invalid eel expression in CookiePunch config service block. Given: ${1 + 1 == 2');

        $conditionalServiceRendering = new ConditionalServiceRendering();

        $eelExpression = '${1 + 1 == 2';
        $conditionalServiceRendering->evaluateEelExpression($eelExpression, null);
    }

    /**
     * @test
     * We want to test that the passed sitenode is added correctly to the context. We mock a simple Node here as $siteNode
     * because that seems just enough to test this behavior.
     */
    public function testIfSitenodeIsPutCorrectlyIntoContext()
    {
        $conditionalServiceRendering = new ConditionalServiceRendering();

        $siteNode = new Node(
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

        $eelExpression = '${site != null}';

        $actual = $conditionalServiceRendering->evaluateEelExpression($eelExpression, $siteNode);

        self::assertTrue($actual, "Sitenode not found in context");
    }

}
