import * as Klaro from "klaro/dist/klaro-no-css";
import buildConfig from "./KlaroConfig/buildConfig";

const cookiePunchConfig = window.cookiePunchConfig;

const config = buildConfig(cookiePunchConfig);
// we assign the Klaro module to the window, so that we can access it in JS
window.klaro = Klaro;
window.klaroConfig = config;
// we set up Klaro with the config
Klaro.setup(config);
