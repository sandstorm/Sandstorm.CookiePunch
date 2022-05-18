<?php

namespace Sandstorm\CookiePunch\Eel\Helper;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\Utility as EelUtility;
use Neos\Eel\CompilingEvaluator;

class ConditionalServiceRendering implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject(lazy=false)
     * @var CompilingEvaluator
     */
    protected $eelEvaluator;

    /**
     * @Flow\InjectConfiguration(package="Neos.Fusion", path="defaultContext")
     * @var array
     */
    protected $defaultContext;

    /**
     * @param string $eelExpression
     * @param NodeInterface $siteNode
     * @return string
     * @throws \Exception for invalid eel expressions
     */
    public function evaluateEelExpression($eelExpression, $siteNode)
    {
        if (preg_match(\Neos\Eel\Package::EelExpressionRecognizer, $eelExpression)) {
            $defaultContextVariables = EelUtility::getDefaultContextVariables($this->defaultContext);

            $context = [
                'site' => $siteNode
            ];

            $contextVariables = array_merge($defaultContextVariables, $context);

            return EelUtility::evaluateEelExpression($eelExpression, $this->eelEvaluator, $contextVariables);
        } else {
            throw new \Exception("Invalid eel expression in CookiePunch config service block. Given: " . $eelExpression);
        }
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
