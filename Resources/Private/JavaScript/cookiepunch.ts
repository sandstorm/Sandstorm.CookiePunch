import * as Klaro from "klaro";
import buildKlaroConfig from "./Helper/buildKlaroConfig";
import "./Helper/openModalEventListener";

const cookiePunchConfig = window.cookiePunchConfig;
const config = buildKlaroConfig(cookiePunchConfig);

// we assign the Klaro module to the window, so that we can access it in JS
window.klaro = Klaro;
window.klaroConfig = config;
// we set up Klaro with the config
Klaro.setup(config);

// handle `cookiePunchConfig.consent.contextualConsentOnly` option
if (cookiePunchConfig.consent.contextualConsentOnly && !Klaro.getManager().confirmed) {
    // WHY: We emulate the "reject all" button of klaro.js here.
    Klaro.getManager().changeAll(false)
    Klaro.getManager().saveAndApplyConsents('decline')
}
