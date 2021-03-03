"use strict";
if (!window.cookiePunchConfig) {
    throw new Error("No cookiePunchConfig was found on window. This should not happen! Please check your config and logs.");
}
(function () {
    const cookiePunchConfig = window.cookiePunchConfig;
    function buildAppsConfiguration() {
        return cookiePunchConfig.apps.map((app) => {
            return Object.assign(Object.assign({}, app), { cookies: [], callback: handleConsent });
        });
    }
    window.klaroConfig = {
        lang: "all",
        apps: buildAppsConfiguration(),
        acceptAll: cookiePunchConfig.acceptAll,
        cookieDomain: cookiePunchConfig.cookieDomain,
        cookieExpiresAfterDays: cookiePunchConfig.cookieExpiresAfterDays,
        cookieName: cookiePunchConfig.cookieName,
        default: cookiePunchConfig.default,
        hideDeclineAll: cookiePunchConfig.hideDeclineAll,
        mustConsent: cookiePunchConfig.mustConsent,
        privacyPolicy: cookiePunchConfig.privacyPolicy,
        storageMethod: cookiePunchConfig.storageMethod,
        translations: {
            all: cookiePunchConfig.translations,
        },
    };
    function handleConsent(consent, app) {
        const PERMANENT_MESSAGE_CLASS = "block-them-all-message";
        const handleConsentOptions = cookiePunchConfig.handleConsentOptions;
        if (consent) {
            removeMessagesForConsentGroup(app.name);
            unhideIframesForGroup(app.name);
        }
        else {
            let options;
            document
                .querySelectorAll(`*[data-name="${app.name}"]`)
                .forEach((element) => {
                try {
                    options = JSON.parse(element.dataset.options);
                }
                catch (e) {
                }
                let message = handleConsentOptions.message || "NO MESSAGE CONFIGURED";
                let messageClass = buildMessageClass(handleConsentOptions.messageClass);
                let messagePosition = "before";
                let targetElements = [];
                if (element.tagName === "IFRAME") {
                    targetElements = [element];
                }
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
                    targetElements.forEach((element) => addMessage(element, message, app, messagePosition, messageClass));
                }
            });
        }
        function removeMessagesForConsentGroup(group) {
            const messageSelector = `.${PERMANENT_MESSAGE_CLASS}[data-name="${group}"]`;
            document.querySelectorAll(messageSelector).forEach((item) => {
                item.remove();
            });
        }
        function unhideIframesForGroup(group) {
            const iframeSelector = `iframe[data-name="${group}"]`;
            document.querySelectorAll(iframeSelector).forEach((item) => {
                item.style.display = "block";
            });
        }
        function buildMessageClass(messageClass) {
            if (messageClass && messageClass !== PERMANENT_MESSAGE_CLASS) {
                return `${PERMANENT_MESSAGE_CLASS} ${handleConsentOptions.messageClass}`;
            }
            return PERMANENT_MESSAGE_CLASS;
        }
        function addMessage(targetElement, message, app, messagePosition, messageClass) {
            var _a, _b;
            const newElement = document.createElement("div");
            const classNames = messageClass.split(" ");
            const innerClassNames = classNames.map((className) => className + "__inner");
            newElement.innerHTML = `<div class="${innerClassNames.join(" ")}">${message.replace("{group}", app.title)}</div>`;
            newElement.onclick = function () {
                klaro.show();
            };
            newElement.style.cursor = "pointer";
            newElement.dataset.name = app.name;
            if (messageClass) {
                newElement.classList.add(...classNames);
            }
            switch (messagePosition) {
                case "before": {
                    (_a = targetElement === null || targetElement === void 0 ? void 0 : targetElement.parentElement) === null || _a === void 0 ? void 0 : _a.insertBefore(newElement, targetElement);
                    break;
                }
                case "after": {
                    (_b = targetElement === null || targetElement === void 0 ? void 0 : targetElement.parentElement) === null || _b === void 0 ? void 0 : _b.appendChild(newElement);
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
