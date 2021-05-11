import { CookiePunchConfig, KlaroConfig } from "./types";

declare global {
  interface Window {
    cookiePunchConfig: CookiePunchConfig;
    klaroConfig: KlaroConfig;
    klaro: any;
  }
}
