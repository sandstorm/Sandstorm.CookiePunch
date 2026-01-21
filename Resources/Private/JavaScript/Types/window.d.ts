import { CookiePunchConfig, KlaroConfig } from "./types";

declare global {
  interface Window {
    // Object containing callback functions to be executed after init, accept or decline.
    // Will use the callbacks defined in the YAML configuration and added via script tag rendered by Fusion.
    cookiePunchCallbacks: { [key: string]: () => void };
    // The generated CookiePunch configuration that will be used to initialize Klaro.
    // Will be added via script tag rendered by Fusion.
    cookiePunchConfig: CookiePunchConfig;
    // The Klaro config generated from the CookiePunch config
    //
    // WHY indirection through cookiePunchConfig?
    // The YAML is first processed by PHP and then by fusion. The easiest way to create to Klaro config
    // is first to create a JSON config and then create a Klaro config from it in the frontend. This way
    // we can use TypeScript types for both configs and ensure type safety in development, because no
    // js is created in PHP or fusion directly.
    klaroConfig: KlaroConfig;
    klaro: any;
  }
}
