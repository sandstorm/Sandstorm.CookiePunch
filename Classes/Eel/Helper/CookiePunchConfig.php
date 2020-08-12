<?php

namespace Sandstorm\CookiePunch\Eel\Helper;

use Neos\Eel\Helper\ConfigurationHelper;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\I18n\EelHelper\TranslationHelper;
use phpDocumentor\Reflection\Types\Boolean;

class CookiePunchConfig implements ProtectedContextAwareInterface
{
    public function translate(string $path): string
    {
        $settingsValue = (new ConfigurationHelper())->setting($path);
        if ($settingsValue) {
            return (new TranslationHelper())->translate($settingsValue);
        } else {
            return (new TranslationHelper())->translate($path);
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
