"use strict";

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

/* PHP_REMOVE_START */
// Here we define variables to have a valid JS file, which makes development
// more fun. They will later be replaced by the `ConsentConfigImplementation.php`.
// We prefix variables with `$php_replaced__` to show that they will be replaced.
// This file will be processed the following way:
//
// 1. (manually) Make sure you create an new build of this file to get a template that also works
//    in old browsers -> `yarn run build:template`. This will create a file "klaroConfigTemplateCompiled.js".
//    This file will be processed in the next steps!
//
// 2. ConsentConfigImplementation.php will remove content between "PHP_REMOVE_START" and "PHP_REMOVE_END"
//    to remove variable definitions, that are only used for a better dev experience.
//
// 3. ConsentConfigImplementation.php will remove the callback placeholder for all apps with the actual callback.
//    This is necessary as we are using json_encode. Json files do not support Js variables or callbacks.
//
//    Before:    { "name": "default", "title": "Default", "callback": "### CONSENT_HANDLER ###" }
//    After:     { "name": "default", "title": "Default", "callback": handleConsent }
//
// IMPORTANT FOR DEV: When changing the template make sure to flush the cache, otherwise changes will not
// show in the browser, even after reloading!
var $php_replaced__apps, $php_replaced__translations, $php_replaced__privacyPolicyUrl, $php_replaced__cookieDomain, $php_replaced__cookieExpiresAfterDays, $php_replaced__storageMethod, $php_replaced__cookieName, $php_replaced__mustConsent, $php_replaced__default, $php_replaced__acceptAll, $php_replaced__hideDeclineAll;
var $php_replaced__handleConsentOptions = {
  message: "Message",
  messageClass: "messageClass"
};
/* PHP_REMOVE_END */

(function () {
  window.klaroConfig = {
    // IMPORTANT: we disable the language handling of klaro because
    // we want to use translations provided the Neos-way
    lang: "all",
    // How Klaro should store the user's preferences. It can be either 'cookie'
    // (the default) or 'localStorage'.
    storageMethod: $php_replaced__storageMethod,
    // You can customize the name of the cookie that Klaro uses for storing
    // user consent decisions. If undefined, Klaro will use 'klaro'.
    cookieName: $php_replaced__cookieName,
    // You can also set a custom expiration time for the Klaro cookie.
    // By default, it will expire after 120 days.
    cookieExpiresAfterDays: $php_replaced__cookieExpiresAfterDays,
    // You can change to cookie domain for the consent manager itself.
    // Use this if you want to get consent once for multiple matching domains.
    // If undefined, Klaro will use the current domain.
    cookieDomain: $php_replaced__cookieDomain,
    // Put a link to your privacy policy here (relative or absolute).
    privacyPolicy: $php_replaced__privacyPolicyUrl,
    // Defines the default state for applications (true=enabled by default).
    default: $php_replaced__default,
    // If "mustConsent" is set to true, Klaro will directly display the consent
    // manager modal and not allow the user to close it before having actively
    // consented or declines the use of third-party apps.
    mustConsent: $php_replaced__mustConsent,
    // Show "accept all" to accept all apps instead of "ok" that only accepts
    // required and "default: true" apps
    acceptAll: $php_replaced__acceptAll,
    // replace "decline" with cookie manager modal
    hideDeclineAll: $php_replaced__hideDeclineAll,
    translations: {
      all: $php_replaced__translations
    },
    apps: $php_replaced__apps
  };

  function handleConsent(consent, app) {
    // We nee a stable class to identify messages so we can remove them later.
    var PERMANENT_MESSAGE_CLASS = "block-them-all-message"; // -> see Settings.yaml and/or Config.fusion

    var handleConsentOptions = $php_replaced__handleConsentOptions;

    if (consent) {
      removeMessagesForConsentGroup(app.name);
      unhideIframesForGroup(app.name);
    } else {
      var options = null;
      document.querySelectorAll("*[data-name=\"".concat(app.name, "\"]")).forEach(function (element) {
        try {
          // more robust handling of maybe broken JSON
          options = JSON.parse(element.dataset.options);
        } catch (e) {// Do nothing
        } // 1. We set default values


        var message = handleConsentOptions.message || "NO MESSAGE CONFIGURED";
        var messageClass = buildMessageClass(handleConsentOptions.messageClass);
        var messagePosition = "before";
        var targetElements = [];

        if (element.tagName === "IFRAME") {
          targetElements = [element];
        } // 2. We override some, if options are present


        if (options) {
          message = options.message || message;
          messageClass = options.messageClass ? buildMessageClass(options.messageClass) : messageClass;
          messagePosition = options.messagePosition ? options.messagePosition : messagePosition;
          targetElements = options.target ? Array.from(document.querySelectorAll(options.target)) : targetElements;
        }

        if (targetElements.length) {
          targetElements.forEach(function (element) {
            return addMessage(element, message, app, messagePosition, messageClass);
          });
        }
      });
    }

    function removeMessagesForConsentGroup(group) {
      var messageSelector = ".".concat(PERMANENT_MESSAGE_CLASS, "[data-name=\"").concat(group, "\"]");
      document.querySelectorAll(messageSelector).forEach(function (item) {
        item.remove();
      });
    }

    function unhideIframesForGroup(group) {
      var iframeSelector = "iframe[data-name=\"".concat(group, "\"]");
      document.querySelectorAll(iframeSelector).forEach(function (item) {
        item.style.display = "block";
      });
    }

    function buildMessageClass(messageClass) {
      if (messageClass && messageClass !== PERMANENT_MESSAGE_CLASS) {
        return "".concat(PERMANENT_MESSAGE_CLASS, " ").concat(handleConsentOptions.messageClass);
      }

      return PERMANENT_MESSAGE_CLASS;
    }

    function addMessage(targetElement, message, app, messagePosition, messageClass) {
      var newElement = document.createElement("div");
      var classNames = messageClass.split(" ");
      var innerClassNames = classNames.map(function (className) {
        return className + "__inner";
      });
      newElement.innerHTML = "<div class=\"".concat(innerClassNames.join(" "), "\">").concat(message.replace("{group}", app.title), "</div>");

      newElement.onclick = function () {
        klaro.show();
      };

      newElement.style.cursor = "pointer";
      newElement.dataset.name = app.name;

      if (messageClass) {
        var _newElement$classList;

        (_newElement$classList = newElement.classList).add.apply(_newElement$classList, _toConsumableArray(classNames));
      }

      switch (messagePosition) {
        case "before":
          {
            targetElement.parentElement.insertBefore(newElement, targetElement);
            break;
          }

        case "after":
          {
            targetElement.parentElement.insertAfter(newElement, targetElement);
            break;
          }

        case "prepend":
          {
            targetElement.prepend(newElement);
            break;
          }

        case "append":
          {
            targetElement.append(newElement);
            break;
          }
      }
    }
  }
})();
