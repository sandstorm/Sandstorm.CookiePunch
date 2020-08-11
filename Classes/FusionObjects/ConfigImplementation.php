<?php

namespace Sandstorm\CookieCutter\FusionObjects;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\DataStructureImplementation;
use phpDocumentor\Reflection\Types\Boolean;

class ConfigImplementation extends DataStructureImplementation
{
    const CONSENT_HANDLER_PLACEHOLDER = "### CONSENT_HANDLER ###";
    const CONSENT_HANDLER_PLACEHOLDER_REGEX =
        '/"' . self::CONSENT_HANDLER_PLACEHOLDER . '"/';
    const PHP_REMOVE_REGEX = '/\/\* PHP_REMOVE_START.*?PHP_REMOVE_END \*\//s';

    const CONSENT_CONFIG = "consent";

    public function evaluate()
    {
        $dataStructure = parent::evaluate();

        $apps = [];
        $translations = isset($dataStructure["translations"])
            ? $dataStructure["translations"]
            : [];
        $groups = isset($dataStructure["groups"])
            ? $dataStructure["groups"]
            : null;

        $purposes = isset($dataStructure["purposes"])
            ? $dataStructure["purposes"]
            : null;

        $handleConsentOptions = isset($dataStructure["handleConsentOptions"])
            ? $dataStructure["handleConsentOptions"]
            : [];

        // Consent relevant stuff

        $privacyPolicyUrl = isset(
            $dataStructure[self::CONSENT_CONFIG]["privacyPolicyUrl"]
        )
            ? $dataStructure[self::CONSENT_CONFIG]["privacyPolicyUrl"]
            : "/privacy";

        $storageMethod =
            isset($dataStructure[self::CONSENT_CONFIG]["storageMethod"]) &&
            ($dataStructure[self::CONSENT_CONFIG]["storageMethod"] ===
                "cookie" ||
                $dataStructure[self::CONSENT_CONFIG]["storageMethod"] ===
                    "localStorage")
                ? $dataStructure[self::CONSENT_CONFIG]["storageMethod"]
                : "cookie";

        $cookieName = isset($dataStructure[self::CONSENT_CONFIG]["cookieName"])
            ? $dataStructure[self::CONSENT_CONFIG]["cookieName"]
            : "cookie_cutter";

        $cookieExpiresAfterDays = isset(
            $dataStructure[self::CONSENT_CONFIG]["cookieExpiresAfterDays"]
        )
            ? $dataStructure[self::CONSENT_CONFIG]["cookieExpiresAfterDays"]
            : 120;

        $cookieDomain = isset(
            $dataStructure[self::CONSENT_CONFIG]["cookieDomain"]
        )
            ? $dataStructure[self::CONSENT_CONFIG]["cookieDomain"]
            : null;

        $default = isset($dataStructure[self::CONSENT_CONFIG]["default"])
            ? $dataStructure[self::CONSENT_CONFIG]["default"]
            : false;

        $mustConsent = isset(
            $dataStructure[self::CONSENT_CONFIG]["mustConsent"]
        )
            ? $dataStructure[self::CONSENT_CONFIG]["mustConsent"]
            : true;

        $acceptAll = isset($dataStructure[self::CONSENT_CONFIG]["acceptAll"])
            ? $dataStructure[self::CONSENT_CONFIG]["acceptAll"]
            : true;

        $hideDeclineAll = isset(
            $dataStructure[self::CONSENT_CONFIG]["hideDeclineAll"]
        )
            ? $dataStructure[self::CONSENT_CONFIG]["hideDeclineAll"]
            : false;

        // Groups to translations & apps

        if ($groups) {
            foreach ($groups as $name => $groupConfig) {
                $translations[$name] = $this->buildAppTranslation($groupConfig);
                array_push($apps, $this->buildAppConfig($name, $groupConfig));
            }

            if ($purposes && sizeof($purposes)) {
                $translations["purposes"] = $purposes;
            }
        }

        $replaceVariables = [
            '$php_replaced__apps' => json_encode($apps, JSON_PRETTY_PRINT),
            '$php_replaced__translations' => json_encode(
                $translations,
                JSON_PRETTY_PRINT
            ),
            '$php_replaced__handleConsentOptions' => json_encode(
                $handleConsentOptions,
                JSON_PRETTY_PRINT
            ),

            '$php_replaced__storageMethod' => $this->toJsString($storageMethod),
            '$php_replaced__cookieName' => $this->toJsString($cookieName),
            '$php_replaced__cookieExpiresAfterDays' => $cookieExpiresAfterDays,
            '$php_replaced__cookieDomain' => $this->toJsString($cookieDomain),
            '$php_replaced__privacyPolicyUrl' => $this->toJsString(
                $privacyPolicyUrl
            ),
            '$php_replaced__default' => $this->toJsBoolean($default),
            '$php_replaced__mustConsent' => $this->toJsBoolean($mustConsent),
            '$php_replaced__acceptAll' => $this->toJsBoolean($acceptAll),
            '$php_replaced__hideDeclineAll' => $this->toJsBoolean(
                $hideDeclineAll
            ),
        ];

        return $this->renderConfig($replaceVariables);
    }

    private function renderConfig(array $variables): string
    {
        $fileConents = file_get_contents(
            'resource://Sandstorm.CookieCutter/Private/JavaScript/klaroConfigTemplateCompiled.js'
        );
        $template = preg_replace(self::PHP_REMOVE_REGEX, "", $fileConents);

        $result = strtr($template, $variables);
        // We need to convert the placeholder, to an actual function name in the template.
        // We do this in a separate step as json_encode does not support removing ""
        $result = preg_replace(
            self::CONSENT_HANDLER_PLACEHOLDER_REGEX,
            "handleConsent",
            $result
        );

        return $result;
    }

    private function toJsString(?string $string): string
    {
        if (!$string) {
            return "null";
        }
        return '"' . $string . '"';
    }

    private function toJsBoolean(bool $bool): string
    {
        return $bool ? "true" : "false";
    }

    private function buildAppConfig(string $name, array $groupConfig): array
    {
        $result = [
            "name" => $name,
            "title" => isset($groupConfig["title"])
                ? $groupConfig["title"]
                : $name,
            "purposes" =>
                isset($groupConfig["purposes"]) &&
                is_array($groupConfig["purposes"])
                    ? $groupConfig["purposes"]
                    : [],
            "callback" => self::CONSENT_HANDLER_PLACEHOLDER,
            "cookies" =>
                isset($groupConfig[self::CONSENT_CONFIG]["cookies"]) &&
                is_array($groupConfig[self::CONSENT_CONFIG]["cookies"])
                    ? $groupConfig[self::CONSENT_CONFIG]["cookies"]
                    : [],
        ];

        if (isset($groupConfig[self::CONSENT_CONFIG]["default"])) {
            $result["default"] = $groupConfig[self::CONSENT_CONFIG]["default"];
        }

        if (isset($groupConfig[self::CONSENT_CONFIG]["required"])) {
            $result["required"] =
                $groupConfig[self::CONSENT_CONFIG]["required"];
        }

        return $result;
    }

    private function buildAppTranslation(array $groupConfig): array
    {
        $result = [];
        if (isset($groupConfig["title"])) {
            $result["title"] = $groupConfig["title"];
        }
        if (isset($groupConfig["description"])) {
            $result["description"] = $groupConfig["description"];
        }
        return $result;
    }
}
