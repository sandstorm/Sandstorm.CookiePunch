<?php

namespace Sandstorm\CookiePunch\Eel\Helper;

use Exception;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\EelHelper\TranslationHelper;
use Neos\Eel\Helper\ConfigurationHelper;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Eel\Utility as EelUtility;
use Neos\Eel\CompilingEvaluator;
use Neos\ContentRepository\Domain\Model\NodeInterface;

class CookiePunchConfig implements ProtectedContextAwareInterface
{
    public function translate($path): ?string
    {
        if ($path) {
            $settingsValue = (new ConfigurationHelper())->setting($path);
            if ($settingsValue) {
                return (new TranslationHelper())->translate($settingsValue);
            } else {
                return (new TranslationHelper())->translate($path);
            }
        } else {
            return null;
        }
    }

    /**
     * @Flow\Inject(lazy=false)
     * @var CompilingEvaluator
     */
    protected CompilingEvaluator $eelEvaluator;

    /**
     * @Flow\InjectConfiguration(package="Neos.Fusion", path="defaultContext")
     * @var array
     */
    protected array $defaultContext;

    /**
     * @param array $services
     * @param NodeInterface $siteNode
     * @return array
     * @throws Exception
     */
    public function filterServicesArrayByWhenCondition(array $services, NodeInterface $siteNode): array
    {
        return array_filter($services, function($service) use(&$siteNode) {
            return !isset($service['when']) || $this->evaluateEelExpression($service['when'], $siteNode);
        });
    }

    /**
     * @param string $eelExpression
     * @param NodeInterface $siteNode
     * @return bool
     * @throws Exception for invalid eel expressions
     */
    public function evaluateEelExpression(string $eelExpression, NodeInterface $siteNode): bool
    {
        if (preg_match(\Neos\Eel\Package::EelExpressionRecognizer, $eelExpression)) {
            $defaultContextVariables = EelUtility::getDefaultContextVariables($this->defaultContext);

            $context = [
                'site' => $siteNode
            ];

            $contextVariables = array_merge($defaultContextVariables, $context);

            $result = EelUtility::evaluateEelExpression($eelExpression, $this->eelEvaluator, $contextVariables);

            // hard cast to boolean, only boolean expressions are supported. Other expression types are unsupported
            // We do not throw an exception here after checking if (!is_bool($result)), because this would prevent
            // often used eel expression shortcuts like ${q(site).property('googleAnalyticsAccountKey')} that are truthy
            // or falsy but not strictly of type bool from being used
            return (bool) $result;
        } else {
            throw new Exception("Invalid eel expression in CookiePunch config service block. Given: " . $eelExpression);
        }
    }

    /**
     * All methods are considered safe, i.e. can be executed from within Eel
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
