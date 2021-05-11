type KlaroCallbackApp = {
  name: string;
  title: string;
};

type KlaroAppConfig = {
  name: string;
  title: string;
  purposes: string[];
  cookies: RegExp[];
  callback: (consent: boolean, app: KlaroCallbackApp) => void;
};

type KlaroConfig = {
  lang: string;

  acceptAll: boolean;
  apps: KlaroAppConfig[];
  cookieName: string;
  cookieExpiresAfterDays: number;
  cookieDomain: string;
  default: boolean;
  hideDeclineAll: boolean;
  mustConsent: boolean;
  privacyPolicy: string;
  storageMethod: string;
  translations: { [key: string]: any };
};

type CookiePunchConfig = KlaroConfig & {
  apps: AppJson;
  handleConsentOptions: { [key: string]: any };
};

type AppJson = {
  name: string;
  title: string;
  purposes: string[];
  cookies: string[];
};

//@ts-ignore
if (!window.cookiePunchConfig) {
  throw new Error(
    "No cookiePunchConfig was found on window. This should not happen! Please check your config and logs."
  );
}

(function () {
  //@ts-ignore
  const cookiePunchConfig = window.cookiePunchConfig as CookiePunchConfig;
  function buildAppsConfiguration(): KlaroAppConfig[] {
    return cookiePunchConfig.apps.map((app) => {
      return {
        ...app,
        cookies: [],
        callback: handleConsent,
      };
    });
  }

  // @ts-ignore
  window.klaroConfig = {
    // IMPORTANT: we disable the language handling of klaro because
    // we want to use translations provided the Neos-way
    lang: "all",

    // ############## cookiePunchConfig ##############

    apps: buildAppsConfiguration(),

    // Show "accept all" to accept all apps instead of "ok" that only accepts
    // required and "default: true" apps
    acceptAll: cookiePunchConfig.acceptAll,

    // You can change to cookie domain for the consent manager itself.
    // Use this if you want to get consent once for multiple matching domains.
    // If undefined, Klaro will use the current domain.
    cookieDomain: cookiePunchConfig.cookieDomain,
    // You can also set a custom expiration time for the Klaro cookie.
    // By default, it will expire after 120 days.
    cookieExpiresAfterDays: cookiePunchConfig.cookieExpiresAfterDays,
    // You can customize the name of the cookie that Klaro uses for storing
    // user consent decisions. If undefined, Klaro will use 'klaro'.
    cookieName: cookiePunchConfig.cookieName,

    // Defines the default state for applications (true=enabled by default).
    default: cookiePunchConfig.default,

    // replace "decline" with cookie manager modal
    hideDeclineAll: cookiePunchConfig.hideDeclineAll,

    // If "mustConsent" is set to true, Klaro will directly display the consent
    // manager modal and not allow the user to close it before having actively
    // consented or declines the use of third-party apps.
    mustConsent: cookiePunchConfig.mustConsent,

    // Put a link to your privacy policy here (relative or absolute).
    privacyPolicy: cookiePunchConfig.privacyPolicy,

    // How Klaro should store the user's preferences. It can be either 'cookie'
    // (the default) or 'localStorage'.
    storageMethod: cookiePunchConfig.storageMethod,

    translations: {
      all: cookiePunchConfig.translations,
    },
  };

  function handleConsent(consent: boolean, app: KlaroCallbackApp) {
    // We nee a stable class to identify messages so we can remove them later.
    const PERMANENT_MESSAGE_CLASS = "block-them-all-message";
    // -> see Settings.yaml and/or Config.fusion
    const handleConsentOptions = cookiePunchConfig.handleConsentOptions;

    if (consent) {
      removeMessagesForConsentGroup(app.name);
      unhideIframesForGroup(app.name);
    } else {
      let options: { [key: string]: any };

      document
        .querySelectorAll(`*[data-name="${app.name}"]`)
        .forEach((element) => {
          try {
            // more robust handling of maybe broken JSON
            //@ts-ignore
            options = JSON.parse(element.dataset.options);
          } catch (e) {
            // Do nothing
          }

          // 1. We set default values
          let message = handleConsentOptions.message || "NO MESSAGE CONFIGURED";
          let messageClass = buildMessageClass(
            handleConsentOptions.messageClass
          );
          let messagePosition = "before";
          let targetElements: Element[] = [];

          if (element.tagName === "IFRAME") {
            targetElements = [element];
          }

          // 2. We override some, if options are present
          if (options) {
            message = options.message || message;
            messageClass = options.messageClass
              ? buildMessageClass(options.messageClass)
              : messageClass;
            messagePosition = options.messagePosition
              ? options.messagePosition
              : messagePosition;
            targetElements = options.target
              ? Array.from(document.querySelectorAll(options.target))
              : targetElements;
          }

          if (targetElements.length) {
            targetElements.forEach((element) =>
              addMessage(element, message, app, messagePosition, messageClass)
            );
          }
        });
    }
    function removeMessagesForConsentGroup(group: string) {
      const messageSelector = `.${PERMANENT_MESSAGE_CLASS}[data-name="${group}"]`;
      document.querySelectorAll(messageSelector).forEach((item) => {
        item.remove();
      });
    }

    function unhideIframesForGroup(group: string) {
      const iframeSelector = `iframe[data-name="${group}"]`;
      document.querySelectorAll(iframeSelector).forEach((item) => {
        (item as HTMLElement).style.display = "block";
      });
    }

    function buildMessageClass(messageClass: string) {
      if (messageClass && messageClass !== PERMANENT_MESSAGE_CLASS) {
        return `${PERMANENT_MESSAGE_CLASS} ${handleConsentOptions.messageClass}`;
      }
      return PERMANENT_MESSAGE_CLASS;
    }

    function addMessage(
      targetElement: Element,
      message: string,
      app: KlaroCallbackApp,
      messagePosition: string,
      messageClass: string
    ) {
      const newElement = document.createElement("div");
      const classNames = messageClass.split(" ");
      const innerClassNames = classNames.map(
        (className) => className + "__inner"
      );
      newElement.innerHTML = `<div class="${innerClassNames.join(
        " "
      )}">${message.replace("{group}", app.title)}</div>`;
      newElement.onclick = function () {
        //@ts-ignore
        klaro.show();
      };
      newElement.style.cursor = "pointer";
      newElement.dataset.name = app.name;

      if (messageClass) {
        newElement.classList.add(...classNames);
      }

      switch (messagePosition) {
        case "before": {
          targetElement?.parentElement?.insertBefore(newElement, targetElement);
          break;
        }
        case "after": {
          targetElement?.parentElement?.appendChild(newElement);
          break;
        }
        case "prepend": {
          targetElement.prepend(newElement);
          break;
        }
        case "append": {
          targetElement.append(newElement);
          break;
        }
      }
    }
  }
})();
