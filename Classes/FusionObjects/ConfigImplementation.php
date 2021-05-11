<?php

namespace Sandstorm\CookiePunch\FusionObjects;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\DataStructureImplementation;
use phpDocumentor\Reflection\Types\Boolean;

class ConfigImplementation extends DataStructureImplementation {
    const CONSENT_HANDLER_PLACEHOLDER = "### CONSENT_HANDLER ###";
    const CONSENT_HANDLER_PLACEHOLDER_REGEX =
        '/"' . self::CONSENT_HANDLER_PLACEHOLDER . '"/';

    const CONSENT_CONFIG = "consent";

    public function evaluate() {
        $dataStructure = parent::evaluate();

        $groups = isset($dataStructure["groups"])
            ? $dataStructure["groups"]
            : null;

        $purposes = isset($dataStructure["purposes"])
            ? $dataStructure["purposes"]
            : null;

        $apps = [];
        $translations = isset($dataStructure["translations"])
            ? $dataStructure["translations"]
            : [];

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

        $config = [
            "acceptAll" => isset($dataStructure[self::CONSENT_CONFIG]["acceptAll"])
                ? $dataStructure[self::CONSENT_CONFIG]["acceptAll"]
                : true,
            "apps" => $apps,
            "cookieDomain" => isset(
                $dataStructure[self::CONSENT_CONFIG]["cookieDomain"]
            )
                ? $dataStructure[self::CONSENT_CONFIG]["cookieDomain"]
                : null,
            "cookieExpiresAfterDays" => isset(
                $dataStructure[self::CONSENT_CONFIG]["cookieExpiresAfterDays"]
            )
                ? $dataStructure[self::CONSENT_CONFIG]["cookieExpiresAfterDays"]
                : 120,
            "cookieName" => isset($dataStructure[self::CONSENT_CONFIG]["cookieName"])
                ? $dataStructure[self::CONSENT_CONFIG]["cookieName"]
                : "cookie_punch",
            "default" => isset($dataStructure[self::CONSENT_CONFIG]["default"])
                ? $dataStructure[self::CONSENT_CONFIG]["default"]
                : false,

            "handleConsentOptions" => isset($dataStructure["handleConsentOptions"])
                ? $dataStructure["handleConsentOptions"]
                : [],
            "hideDeclineAll" => isset(
                $dataStructure[self::CONSENT_CONFIG]["hideDeclineAll"]
            )
                ? $dataStructure[self::CONSENT_CONFIG]["hideDeclineAll"]
                : false,
            "mustConsent" => isset(
                $dataStructure[self::CONSENT_CONFIG]["mustConsent"]
            )
                ? $dataStructure[self::CONSENT_CONFIG]["mustConsent"]
                : true,
            "privacyPolicy" => isset(

                $dataStructure[self::CONSENT_CONFIG]["privacyPolicyUrl"]
            )
                ? $dataStructure[self::CONSENT_CONFIG]["privacyPolicyUrl"]
                : "/privacy",
            "storageMethod" =>
                isset($dataStructure[self::CONSENT_CONFIG]["storageMethod"]) &&
                ($dataStructure[self::CONSENT_CONFIG]["storageMethod"] === "cookie" ||
                    $dataStructure[self::CONSENT_CONFIG]["storageMethod"] === "localStorage")
                    ? $dataStructure[self::CONSENT_CONFIG]["storageMethod"]
                    : "cookie",
            "translations" => $translations,
        ];

        return $config;
    }

    private function buildAppConfig(string $name, array $groupConfig): array {
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

    private function buildAppTranslation(array $groupConfig): array {
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
