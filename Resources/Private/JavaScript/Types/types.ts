// We have two different configs. One is provided through the yaml configuration
// and later processed to create the config needed by Klaro in the frontend.

// COOKIEPUNCH
export type CookiePunchService = {
  name: string;
  title?: string;
  description?: string;
  purposes?: string[];
  contextualConsentOnly?: boolean;
  default?: boolean;
  required?: boolean;
  optOut?: boolean;
  cookies?: CookiePunchServiceCookies;
  onlyOnce?: boolean;
  onInit?: string;
  onAccept?: string;
  onDecline?: string;
};

export type CookiePunchPurpose = {
  title?: string;
  description?: string;
};

export type CookiePunchServiceCookies = {
  pattern: string;
  patternIsRegex?: boolean;
  path?: "string";
  domain?: string;
}[];

export type CookiePunchPurposes = {
  [key: string]: CookiePunchPurpose;
};

export type CookiePunchServices = {
  [key: string]: CookiePunchService;
};

export type CookiePunchConfig = {
  consent: {
    privacyPolicyUrl: string;

    elementID: string;
    noAutoLoad: boolean;
    htmlTexts: boolean;
    embedded: false;
    groupByPurpose: boolean;
    storageMethod: "cookie" | "localStorage";
    cookieName: string;
    cookieExpiresAfterDays: number;
    default: boolean;
    mustConsent: boolean;
    acceptAll: boolean;
    hideDeclineAll: boolean;
    hideLearnMore: boolean;
    noticeAsModal: boolean;
    disablePoweredBy: boolean;
    additionalClass?: string;
    cookiePath?: string;
    cookieDomain?: string;

    purposes: CookiePunchPurposes;
    services: CookiePunchServices;
    translations: {
      [key: string]: any;
    };

    contextualConsentOnly: boolean;
  };
};

// KLARO

export type KlaroTranslations = {
  [key: string]: any;
};

export type KlaroServiceTranslations = {
  [key: string]: {
    title?: string;
    description?: string;
  };
};

export type KlaroPurposeTranslations = {
  [key: string]: {
    title?: string;
    description?: string;
  };
};

export type KlaroService = {
  name: string;
  title?: string;
  description?: string;
  purposes?: string[];
  contextualConsentOnly?: boolean;
  default?: boolean;
  cookies?: KlaroServiceCookies;
  required?: boolean;
  optOut?: boolean;
  onlyOnce?: boolean;
  onInit?: () => void;
  onAccept?: () => void;
  onDecline?: () => void;
};

export type KlaroServiceCookies = ((RegExp | string)[] | string | RegExp)[];

export type KlaroServices = KlaroService[];

export type KlaroConfig = {
  version: 1;
  lang: "en";

  elementID: string;
  noAutoLoad: boolean;
  htmlTexts: boolean;
  embedded: false;
  groupByPurpose: boolean;
  storageMethod: "cookie" | "localStorage";
  cookieName: string;
  cookieExpiresAfterDays: number;
  default: boolean;
  mustConsent: boolean;
  acceptAll: boolean;
  hideDeclineAll: boolean;
  hideLearnMore: boolean;
  noticeAsModal: boolean;
  disablePoweredBy: boolean;
  additionalClass?: string;
  cookiePath?: string;
  cookieDomain?: string;

  purposes: string[];
  services: KlaroServices;

  translations: {
    en: KlaroTranslations | KlaroServiceTranslations;
  };
};
