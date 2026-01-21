<?php

namespace Sandstorm\CookiePunch\Tests\Functional\Eel\Helper;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Tests\FunctionalTestCase;
use Sandstorm\CookiePunch\Eel\Helper\CookiePunchConfig;
use Symfony\Component\Serializer\Serializer;

/**
 * Testcase
 */
class CookiePunchConfigTest extends FunctionalTestCase
{
    private Node $dummySiteNode;

    /**
     * @before
     */
    public function initDummySiteNode(): void
    {
        $this->dummySiteNode = Node::create(
            ContentRepositoryId::fromString("cr"),
            WorkspaceName::fromString('ws'),
            DimensionSpacePoint::createWithoutDimensions(),
            $nodeAggregateId ?? NodeAggregateId::fromString("na"),
            OriginDimensionSpacePoint::createWithoutDimensions(),
            NodeAggregateClassification::CLASSIFICATION_ROOT,
            NodeTypeName::fromString("nt"),
            new PropertyCollection(
                SerializedPropertyValues::createEmpty(),
                new PropertyConverter(new Serializer())
            ),
            NodeName::fromString("nn"),
            NodeTags::createEmpty(),
            Timestamps::create($now = new \DateTimeImmutable(), $now, null, null),
            VisibilityConstraints::withoutRestrictions(),
        );
    }

    /**
     * @test
     */
    public function validEelExpressionIsEvaluatedCorrectly()
    {
        $cookiePunchConfig = new CookiePunchConfig();

        $eelExpression = '${1 + 1 == 2}';
        $actual = $cookiePunchConfig->evaluateEelExpression($eelExpression, $this->dummySiteNode);

        self::assertTrue($actual, "Not a valid eel expression: " . $eelExpression);
    }

    /**
     * @test
     */
    public function invalidEelExpressionThrowsException()
    {
        self::expectExceptionMessage('Invalid eel expression in CookiePunch config service block. Given: ${1 + 1 == 2');

        $cookiePunchConfig = new CookiePunchConfig();

        $eelExpression = '${1 + 1 == 2';
        $cookiePunchConfig->evaluateEelExpression($eelExpression, $this->dummySiteNode);
    }

    /**
     * @test
     * We want to test that the passed sitenode is added correctly to the context. We mock a simple Node here as $siteNode
     * because that seems just enough to test this behavior.
     */
    public function testIfSitenodeIsPutCorrectlyIntoContext()
    {
        $cookiePunchConfig = new CookiePunchConfig();

        $eelExpression = '${site != null}';

        $actual = $cookiePunchConfig->evaluateEelExpression($eelExpression, $this->dummySiteNode);

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
