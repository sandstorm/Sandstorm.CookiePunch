var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
(function () {
    //@ts-ignore
    var cookiePunchConfig = "$replaced_by_php__cookiePunchConfig";
    function buildAppsConfiguration() {
        return cookiePunchConfig.apps.map(function (app) {
            return __assign(__assign({}, app), { cookies: [], callback: handleConsent });
        });
    }
    window.klaroConfig = {
        // IMPORTANT: we disable the language handling of klaro because
        // we want to use translations provided the Neos-way
        lang: "all",
        // How Klaro should store the user's preferences. It can be either 'cookie'
        // (the default) or 'localStorage'.
        storageMethod: cookiePunchConfig.storageMethod,
        // You can customize the name of the cookie that Klaro uses for storing
        // user consent decisions. If undefined, Klaro will use 'klaro'.
        cookieName: cookiePunchConfig.cookieName,
        // You can also set a custom expiration time for the Klaro cookie.
        // By default, it will expire after 120 days.
        cookieExpiresAfterDays: cookiePunchConfig.cookieExpiresAfterDays,
        // You can change to cookie domain for the consent manager itself.
        // Use this if you want to get consent once for multiple matching domains.
        // If undefined, Klaro will use the current domain.
        cookieDomain: cookiePunchConfig.cookieDomain,
        // Put a link to your privacy policy here (relative or absolute).
        privacyPolicy: cookiePunchConfig.privacyPolicy,
        // Defines the default state for applications (true=enabled by default).
        "default": cookiePunchConfig["default"],
        // If "mustConsent" is set to true, Klaro will directly display the consent
        // manager modal and not allow the user to close it before having actively
        // consented or declines the use of third-party apps.
        mustConsent: cookiePunchConfig.mustConsent,
        // Show "accept all" to accept all apps instead of "ok" that only accepts
        // required and "default: true" apps
        acceptAll: cookiePunchConfig.acceptAll,
        // replace "decline" with cookie manager modal
        hideDeclineAll: cookiePunchConfig.hideDeclineAll,
        translations: {
            all: cookiePunchConfig.translations
        },
        apps: buildAppsConfiguration()
    };
    function handleConsent(consent, app) {
        // We nee a stable class to identify messages so we can remove them later.
        var PERMANENT_MESSAGE_CLASS = "block-them-all-message";
        // -> see Settings.yaml and/or Config.fusion
        var handleConsentOptions = cookiePunchConfig.handleConsentOptions;
        if (consent) {
            removeMessagesForConsentGroup(app.name);
            unhideIframesForGroup(app.name);
        }
        else {
            var options_1;
            document
                .querySelectorAll("*[data-name=\"" + app.name + "\"]")
                .forEach(function (element) {
                try {
                    // more robust handling of maybe broken JSON
                    //@ts-ignore
                    options_1 = JSON.parse(element.dataset.options);
                }
                catch (e) {
                    // Do nothing
                }
                // 1. We set default values
                var message = handleConsentOptions.message || "NO MESSAGE CONFIGURED";
                var messageClass = buildMessageClass(handleConsentOptions.messageClass);
                var messagePosition = "before";
                var targetElements = [];
                if (element.tagName === "IFRAME") {
                    targetElements = [element];
                }
                // 2. We override some, if options are present
                if (options_1) {
                    message = options_1.message || message;
                    messageClass = options_1.messageClass
                        ? buildMessageClass(options_1.messageClass)
                        : messageClass;
                    messagePosition = options_1.messagePosition
                        ? options_1.messagePosition
                        : messagePosition;
                    targetElements = options_1.target
                        ? Array.from(document.querySelectorAll(options_1.target))
                        : targetElements;
                }
                if (targetElements.length) {
                    targetElements.forEach(function (element) {
                        return addMessage(element, message, app, messagePosition, messageClass);
                    });
                }
            });
        }
        function removeMessagesForConsentGroup(group) {
            var messageSelector = "." + PERMANENT_MESSAGE_CLASS + "[data-name=\"" + group + "\"]";
            document.querySelectorAll(messageSelector).forEach(function (item) {
                item.remove();
            });
        }
        function unhideIframesForGroup(group) {
            var iframeSelector = "iframe[data-name=\"" + group + "\"]";
            document.querySelectorAll(iframeSelector).forEach(function (item) {
                item.style.display = "block";
            });
        }
        function buildMessageClass(messageClass) {
            if (messageClass && messageClass !== PERMANENT_MESSAGE_CLASS) {
                return PERMANENT_MESSAGE_CLASS + " " + handleConsentOptions.messageClass;
            }
            return PERMANENT_MESSAGE_CLASS;
        }
        function addMessage(targetElement, message, app, messagePosition, messageClass) {
            var _a;
            var _b, _c;
            var newElement = document.createElement("div");
            var classNames = messageClass.split(" ");
            var innerClassNames = classNames.map(function (className) { return className + "__inner"; });
            newElement.innerHTML = "<div class=\"" + innerClassNames.join(" ") + "\">" + message.replace("{group}", app.title) + "</div>";
            newElement.onclick = function () {
                //@ts-ignore
                klaro.show();
            };
            newElement.style.cursor = "pointer";
            newElement.dataset.name = app.name;
            if (messageClass) {
                (_a = newElement.classList).add.apply(_a, classNames);
            }
            switch (messagePosition) {
                case "before": {
                    (_b = targetElement === null || targetElement === void 0 ? void 0 : targetElement.parentElement) === null || _b === void 0 ? void 0 : _b.insertBefore(newElement, targetElement);
                    break;
                }
                case "after": {
                    (_c = targetElement === null || targetElement === void 0 ? void 0 : targetElement.parentElement) === null || _c === void 0 ? void 0 : _c.appendChild(newElement);
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
