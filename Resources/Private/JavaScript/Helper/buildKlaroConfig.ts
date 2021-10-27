import {
  KlaroService,
  KlaroConfig,
  CookiePunchConfig,
  CookiePunchServices,
  KlaroServiceTranslations,
  CookiePunchServiceCookies,
  KlaroServiceCookies,
  CookiePunchPurposes,
  KlaroPurposeTranslations,
} from "../Types/types";

export default function buildKlaroConfig(
  cookiePunchConfig: CookiePunchConfig
): KlaroConfig {
  validateCookiePunchConfigOnWindow(cookiePunchConfig);
  return {
    version: 1,

    // IMPORTANT: we disable the language handling of klaro because
    // we want to use translations provided the Neos-way
    // however we cannot use the zz language as for some reasons default translations
    // will be used when switching languages. This is why we pin the language here so
    // klaro will use the translations provided by us.
    lang: "en",

    elementID: cookiePunchConfig.consent.elementID,
    noAutoLoad: cookiePunchConfig.consent.noAutoLoad,
    htmlTexts: cookiePunchConfig.consent.htmlTexts,
    embedded: cookiePunchConfig.consent.embedded,
    groupByPurpose: cookiePunchConfig.consent.groupByPurpose,
    storageMethod: cookiePunchConfig.consent.storageMethod,
    cookieName: cookiePunchConfig.consent.cookieName,
    cookieExpiresAfterDays: cookiePunchConfig.consent.cookieExpiresAfterDays,
    default: cookiePunchConfig.consent.default,
    mustConsent: cookiePunchConfig.consent.mustConsent,
    acceptAll: cookiePunchConfig.consent.acceptAll,
    hideDeclineAll: cookiePunchConfig.consent.hideDeclineAll,
    hideLearnMore: cookiePunchConfig.consent.hideLearnMore,
    noticeAsModal: cookiePunchConfig.consent.noticeAsModal,
    disablePoweredBy: cookiePunchConfig.consent.disablePoweredBy,
    additionalClass: cookiePunchConfig.consent.additionalClass,
    cookiePath: cookiePunchConfig.consent.cookiePath,
    cookieDomain: cookiePunchConfig.consent.cookieDomain,

    purposes: Object.keys(cookiePunchConfig.consent.purposes),

    services: buildKlaroServicesConfig(cookiePunchConfig.consent.services),

    translations: {
      en: {
        privacyPolicyUrl: cookiePunchConfig.consent.privacyPolicyUrl,
        ...cookiePunchConfig.consent.translations,
        ...buildKlaroServiceTranslations(cookiePunchConfig.consent.services),
        purposes: {
          ...buildKlaroPurposeTranslations(cookiePunchConfig.consent.purposes),
        },
      },
    },
  };
}

function validateCookiePunchConfigOnWindow(
  cookiePunchConfig: CookiePunchConfig
) {
  if (!cookiePunchConfig) {
    throw new Error(
      "No cookiePunchConfig was found on window. This should not happen! Please check your config and logs."
    );
  }
}

function buildKlaroPurposeTranslations(
  cookiePunchPurposes: CookiePunchPurposes
): KlaroPurposeTranslations {
  let result = {} as KlaroPurposeTranslations;
  Object.keys(cookiePunchPurposes).forEach((name) => {
    const purpose = cookiePunchPurposes[name];
    result[name] = {
      title: purpose.title,
      description: purpose.description,
    };
  });
  return result;
}

function buildKlaroServiceTranslations(
  cookiePunchServices: CookiePunchServices
): KlaroServiceTranslations {
  let result = {} as KlaroServiceTranslations;
  Object.keys(cookiePunchServices).forEach((name) => {
    const service = cookiePunchServices[name];
    result[name] = {
      title: service.title,
      description: service.description,
    };
  });
  return result;
}

function buildKlaroServicesConfig(
  cookiePunchServices: CookiePunchServices
): KlaroService[] {
  let result: KlaroService[] = [];
  Object.keys(cookiePunchServices).forEach((name) => {
    const cookiePunchService = cookiePunchServices[name];
    const klaroService: KlaroService = {
      name: name,
    };

    if (cookiePunchService.purposes) {
      klaroService.purposes = cookiePunchService.purposes;
    } else {
      // For some reasons we currently need an empty array here to not break klaro
      klaroService.purposes = [];
    }
    if (typeof cookiePunchService.contextualConsentOnly === "boolean")
      klaroService.contextualConsentOnly =
        cookiePunchService.contextualConsentOnly;
    if (typeof cookiePunchService.default === "boolean")
      klaroService.default = cookiePunchService.default;
    if (cookiePunchService.cookies) {
      klaroService.cookies = buildKlaroServiceCookiesConfig(
        cookiePunchService.cookies
      );
    }
    if (typeof cookiePunchService.required === "boolean")
      klaroService.required = cookiePunchService.required;
    if (typeof cookiePunchService.optOut === "boolean")
      klaroService.optOut = cookiePunchService.optOut;
    if (typeof cookiePunchService.onlyOnce === "boolean")
      klaroService.onlyOnce = cookiePunchService.onlyOnce;

    result.push(klaroService);
  });

  return result;
}

function buildKlaroServiceCookiesConfig(
  cookiePunchServiceCookies: CookiePunchServiceCookies
): KlaroServiceCookies {
  return cookiePunchServiceCookies.map((cookie, index) => {
    if (cookie.pattern && cookie.path && cookie.domain) {
      return [
        cookie.patternIsRegex ? new RegExp(cookie.pattern) : cookie.pattern,
        cookie.path,
        cookie.domain,
      ];
    }

    if (cookie.pattern) {
      return cookie.patternIsRegex
        ? new RegExp(cookie.pattern)
        : cookie.pattern;
    }

    throw new Error(
      `The cookie config for index "${index}" is not supported. It should be an object containing the required properties 'pattern', 'path' and 'domain'`
    );
  });
}
