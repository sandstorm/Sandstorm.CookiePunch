import * as Klaro from "klaro";
import buildKlaroConfig from "./Helper/buildKlaroConfig";
import "./Helper/openModalEventListener";

const cookiePunchConfig = window.cookiePunchConfig;
const config = buildKlaroConfig(cookiePunchConfig);

// we assign the Klaro module to the window, so that we can access it in JS
window.klaro = Klaro;
window.klaroConfig = config;
// handle `cookiePunchConfig.consent.contextualConsentOnly` option
// WHY before Klaro.setup(): Klaro renders the "Always" button of contextual consent notices only if a consent
// record is stored already. Saving the declined record after setup was too late for the first page view - its
// notices only offered "Yes" (load once). getManager(config) creates the manager that setup() then reuses.
if (cookiePunchConfig.consent.contextualConsentOnly && !Klaro.getManager(config).confirmed) {
    // WHY: We emulate the "reject all" button of klaro.js here.
    Klaro.getManager(config).changeAll(false)
    Klaro.getManager(config).saveAndApplyConsents('decline')
}

// we set up Klaro with the config
Klaro.setup(config);
