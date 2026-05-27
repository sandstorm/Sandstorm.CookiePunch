# Sandstorm.CookiePunch

A Neos package that blocks elements like `<script>` and `<iframe>` server-side — *before* the markup reaches the browser — and ships [Klaro](https://heyklaro.com/docs/) as the consent UI to selectively unblock them once the user agrees.

## Contents

- [Features](#features)
- [Installation](#installation)
- [Basic Configuration and Usages](#basic-configuration-and-usages)
  - [Step 1: Adding the consent-modal](#step-1-adding-the-consent-modal)
  - [Step 2: Always allow your own JavaScript](#step-2-always-allow-your-own-javascript)
  - [Step 3: Blocking via YAML config](#step-3-blocking-via-yaml-config)
  - [Step 4: Providing a link to your privacy statement](#step-4-providing-a-link-to-your-privacy-statement)
  - [Step 5: Let the user reopen the consent modal later](#step-5-let-the-user-reopen-the-consent-modal-later)
  - [Step 6: Styling](#step-6-styling)
- [Advanced Usages](#advanced-usages)
  - [Full list of consent / service options](#full-list-of-consent--service-options)
  - [Supported tags](#supported-tags)
  - [Pattern reference](#pattern-reference)
  - [How blocking transforms markup](#how-blocking-transforms-markup)
  - [Blocking a rendered Fusion subtree](#blocking-a-rendered-fusion-subtree)
  - [Adding a contextual consent for non-iframe elements](#adding-a-contextual-consent-for-non-iframe-elements)
  - [Let the editor choose a service from the inspector](#let-the-editor-choose-a-service-from-the-inspector)
  - [Let the editor change the text of the consent](#let-the-editor-change-the-text-of-the-consent)
  - [Privacy URL alternatives](#privacy-url-alternatives)
  - [Manual styling](#manual-styling)
  - [Translations](#translations)
  - [Conditional Rendering of Services in the Consent Modal](#conditional-rendering-of-services-in-the-consent-modal)
  - [Editor-defined dynamic services](#editor-defined-dynamic-services)
  - [Per-service lifecycle callbacks (`onInit` / `onAccept` / `onDecline`)](#per-service-lifecycle-callbacks-oninit--onaccept--ondecline)
  - [Contextual Consent Only Mode](#contextual-consent-only-mode)
- [Troubleshooting](#troubleshooting)
- [Migration guide](./MIGRATIONS.md)
- [Contributing](./CONTRIBUTING.md)

## Features

- Eel helpers to block elements (scripts, iframes, and more) before the markup is sent to the client.
- Eel helper to place contextual consents anywhere in the markup.
- YAML configuration with patterns for targeting tags in the markup.
- Contextual-consent-only mode — no initial banner / modal.
- Localization via YAML and/or Fusion.
- Data source providing all services as a dropdown in the inspector.
- **A polished cookie-consent provided by [Klaro](https://heyklaro.com/docs/)**, bundled directly with this package:
  - Unblocking of elements after consent.
  - Contextual consents — temporarily or permanently unblock content from the element itself, without opening the modal.

## Installation

```bash
# bash — from the application root
composer require sandstorm/cookiepunch
```

This puts the dependency in the outer `composer.json` / `composer.lock` (usually in your repo root or `/app`).

> **Important:** If you want to declare CookiePunch settings *inside one of your Flow packages*, also add the composer dependency to that package's `composer.json` to ensure correct Flow package and configuration loading order.

```jsonc
// DistributionPackages/Your.SitePackage/composer.json
{
    "require": {
        "sandstorm/cookiepunch": "*"
    }
}
```

For a minimal starting-point config, see [`Examples/Settings.CookiePunch.Basic.yaml`](./Examples/Settings.CookiePunch.Basic.yaml). For exhaustive references, see [`FullConsentConfig.yaml`](./Examples/Settings.CookiePunch.FullConsentConfig.yaml) and [`FullServiceConfig.yaml`](./Examples/Settings.CookiePunch.FullServiceConfig.yaml).

## Basic Configuration and Usages

### Step 1: Adding the consent-modal

Drop a `CookiePunch.fusion` file in your site package:

```neosfusion
// Resources/Private/Fusion/CookiePunch.fusion
prototype(Neos.Neos:Page) {
    head.javascripts.cookiepunchConsent = Sandstorm.CookiePunch:Consent
    @process.blockTags = ${CookiePunch.blockTags(["iframe","script"], value, !node.context.inBackend)}
}
```

This adds the consent modal and starts blocking every `<iframe>` and `<script>`. The `!node.context.inBackend` flag keeps the Neos backend functional.

Reload the page — it will likely look broken. Open the DevTools console and call `klaro.show()` to confirm Klaro is loaded; the next steps fix the breakage. For the full list of tag names you can pass to `blockTags`, see [Supported tags](#supported-tags).

### Step 2: Always allow your own JavaScript

Some scripts (`main.js`, `app.js`, …) must always be allowed or your site won't work. You most likely have a Fusion prototype that bundles them — something like `Vendor.Site:HeaderAssets` — and that's the natural place to attach `neverBlockTags`:

```neosfusion
// Resources/Private/Fusion/Component/HeaderAssets.fusion
prototype(Vendor.Site:HeaderAssets) < prototype(Neos.Fusion:Component) {
    renderer = afx`
        <script src={StaticResource.uri('Vendor.Site', 'JavaScript/main.js')}></script>
        <script src={StaticResource.uri('Vendor.Site', 'JavaScript/menu.js')}></script>
    `
    @process.neverBlockTags = ${CookiePunch.neverBlockTags(["script"], value)}
}
```

For a one-off script tag, attach the helper directly:

```neosfusion
// Resources/Private/Fusion/YourComponent.fusion
renderer = afx`
    <script src={props.src} type="application/javascript" @process.neverBlockTags={CookiePunch.neverBlockTags(["script"], value)}></script>
`
```

The same effect can be achieved via a YAML pattern (Step 3), but the helper makes the intent — *I checked, this script is required* — explicit at the call site.

### Step 3: Blocking via YAML config

Create `Configuration/Settings.CookiePunch.yaml`. Tip: register the package's `schema.json` in your IDE for auto-completion.

```yaml
# Configuration/Settings.CookiePunch.yaml
Sandstorm:
  CookiePunch:
    consent:
      purposes:
        mediaembeds:
          title: Media Embeds
          description: Some Description
      services:
        anchor:
          title: Anchor FM
          description: Podcast Player
          purposes:
            - mediaembeds
    blocking:
      tagPatterns:
        script:
          "Packages/Neos.Neos":
            block: false
          "Packages/Vendor.ExampleLibrary":
            block: false
        iframe:
          "https://anchor.fm":
            service: anchor
```

The config has two parts: **`consent`** drives the Klaro UI (purposes group services), while **`blocking`** matches tags by substring pattern and either lets them through (`block: false`), blocks them permanently (`block: true`), or attaches them to a service so the user can allow them via consent (`service: anchor`).

Given the config above, this **input markup**:

```html
<!-- Markup as rendered by Fusion before CookiePunch processes it -->
<script src="/_Resources/Static/Packages/Neos.Neos/JavaScript/main.js"></script>
<script src="/_Resources/Static/Packages/Vendor.ExampleLibrary/slider.js"></script>
<iframe src="https://anchor.fm/embed/episodes/foo"></iframe>
<script src="https://cdn.example.com/tracker.js"></script>
```

…is transformed into this **output markup**:

```html
<!-- Markup after CookiePunch processing -->
<script src="…/Packages/Neos.Neos/…/main.js"></script>                               <!-- untouched -->
<script src="…/Packages/Vendor.ExampleLibrary/slider.js"></script>                   <!-- untouched -->
<iframe data-src="https://anchor.fm/embed/episodes/foo" data-name="anchor"></iframe> <!-- broken; Klaro can restore via the "anchor" service -->
<script type="text/plain" data-src="https://cdn.example.com/tracker.js" data-type="text/javascript"></script> <!-- broken; no service → permanently blocked -->
```

For substring-matching rules, the wildcard `*`, and the difference between `block: false` / `block: true` / `service: …`, see [Pattern reference](#pattern-reference). For the exact attribute rewrites done to a "broken" tag, see [How blocking transforms markup](#how-blocking-transforms-markup).

### Step 4: Providing a link to your privacy statement

The default URL is `/privacy`. Override it with a string for the simplest case:

```yaml
# Configuration/Settings.CookiePunch.yaml
Sandstorm:
  CookiePunch:
    consent:
      privacyPolicyUrl: /imprint/privacy
```

For most projects you'll want editors to pick the privacy page from the inspector. Add a reference property on your Homepage NodeType:

```yaml
# Configuration/NodeTypes.Homepage.yaml
"Vendor.Site:Homepage":
  properties:
    privacyPolicyUrl:
      type: reference
      ui:
        label: "Privacy page"
        inspector:
          group: "settings"
          editorOptions:
            nodeTypes: ["Neos.Neos:Document"]
```

…and point CookiePunch at it. `site` is already the Homepage, so no `q(site).find(...)` is needed:

```neosfusion
// Resources/Private/Fusion/CookiePunch.fusion
prototype(Sandstorm.CookiePunch:Config) {
    consent.privacyPolicyUrl = ${q(site).property("privacyPolicyUrl")}
    consent.privacyPolicyUrl.@process.convert = Neos.Neos:ConvertUris
}
```

For other approaches (XLIFF translation key, dedicated PrivacyPage node type), see [Privacy URL alternatives](#privacy-url-alternatives).

### Step 5: Let the user reopen the consent modal later

Place a link in Neos (e.g. in your privacy statement) with `href="#open_cookie_punch_modal"`. A click handler picks it up and opens the modal — the browser does not reload because `event.preventDefault()` is called internally.

Alternatively call `klaro.show()` from your own JavaScript.

### Step 6: Styling

Override the CSS variables Klaro exposes via YAML:

```yaml
# Configuration/Settings.CookiePunch.yaml
Sandstorm:
  CookiePunch:
    consent:
      styling:
        font-family: "Work Sans, Helvetica Neue, Helvetica, Arial, sans-serif"
        green1: "#00aa00"
        border-radius: "0"
```

For the full variable list, see [`Examples/Settings.CookiePunch.Styling.yaml`](./Examples/Settings.CookiePunch.Styling.yaml). To replace Klaro's CSS entirely with your own, see [Manual styling](#manual-styling).

## Advanced Usages

### Full list of consent / service options

- [annotated YAML of consent options](./Examples/Settings.CookiePunch.FullConsentConfig.yaml)
- [annotated YAML of service options](./Examples/Settings.CookiePunch.FullServiceConfig.yaml)

Most inline comments are copied directly from the [annotated `config.js`](https://github.com/kiprotect/klaro/blob/ec6e36934db10afdac0183721ddfbcb9c79e7dc3/dist/config.js) of Klaro for convenience.

### Supported tags

`CookiePunch.blockTags(...)` and `CookiePunch.neverBlockTags(...)` accept any of these tag names:

`iframe`, `script`, `audio`, `video`, `source`, `track`, `img`, `embed`, `input`.

The same set is allowed as keys under `Sandstorm.CookiePunch.blocking.tagPatterns` in YAML — see [`schema.json`](./schema.json).

### Pattern reference

Patterns under `tagPatterns.<tagName>` are matched against the raw rendered tag string with `strpos()` — i.e. it's a substring match. Anything in the tag (`src` URL, attribute name, attribute value, …) is fair game.

The `Packages/Neos.Neos` pattern, for example, matches all of these:

```html
<!-- Example HTML matched by the "Packages/Neos.Neos" pattern -->
<script src="/foo/bar/Packages/Neos.Neos/baz/index.js"/>
<script data-foo="Neos.Neos"/>
<script Neos.Neosisawesome src="/some/source/main.js"/>
```

Each pattern carries one of three actions:

```yaml
# Configuration/Settings.CookiePunch.yaml
"Packages/Neos.Neos":
  block: false        # always allowed
"https://really-stuff.bad":
  block: true         # always blocked, the consent cannot allow it
"https://anchor.fm":
  service: anchor     # blocked, but the user can allow via consent
```

#### Wildcard (`"*"`)

The reserved key `"*"` flips the *default* for a tag name. Use sparingly — it defeats the purpose of documenting which services are in use.

The most defensible case is `<img>`: by default you usually do not want every image blocked, only specific tracking pixels. Add `img` to the `blockTags` call so CookiePunch processes it, then flip the default:

```neosfusion
// Resources/Private/Fusion/CookiePunch.fusion
prototype(Neos.Neos:Page) {
    head.javascripts.cookiepunchConsent = Sandstorm.CookiePunch:Consent
    @process.blockTags = ${CookiePunch.blockTags(["iframe","script", "img"], value, !node.context.inBackend)}
}
```

```yaml
# Configuration/Settings.CookiePunch.yaml
Sandstorm:
  CookiePunch:
    blocking:
      tagPatterns:
        img:
          "*":
            block: false
          "tracking-pixel-url":
            service: myservice
```

### How blocking transforms markup

When CookiePunch breaks a tag, it does so by attribute rewriting — nothing is removed from the DOM:

- `src` → `data-src`, so the browser doesn't fetch the resource.
- For `<script>` tags only: the original `type` is moved to `data-type` and the live attribute is replaced with `type="text/plain"`, so the browser refuses to execute the script.
- If the matching pattern points at a service, `data-name="<service>"` is added. Klaro reads this attribute, presents a contextual consent, and on accept swaps the `data-*` attributes back to their live counterparts.

A tag with no `data-name` stays broken forever — there is no service to drive its restoration.

### Blocking a rendered Fusion subtree

An already-blocked piece of markup is *not* re-blocked when running the Eel helpers later on `Neos.Neos:Page`. This means we can hook into specific plugins to block them and attach them to a service.

This is especially useful for inline `<script>...</script>` tags that cannot be matched by a URL pattern.

```neosfusion
// Resources/Private/Fusion/Plugin/FooTube.fusion — plugin implementation
prototype(Vendor.Plugin.FooTube:Embed) < prototype(Neos.Fusion:Component) {
    renderer = afx`
      <div>
        <iframe src="..."></iframe>
        <script type="text/javascript">...</script>
      </div>
    `
}
```

```neosfusion
// Resources/Private/Fusion/CookiePunch.fusion
prototype(Vendor.Plugin.FooTube:Embed) {
  // tags in this part of the tree will be blocked first
  @process.blockTags = ${CookiePunch.blockTags(["iframe","script"], value, !node.context.inBackend, "footube")}
}

prototype(Neos.Neos:Page) {
  head.javascripts.cookiepunchConsent = Sandstorm.CookiePunch:Consent
  // at last, all remaining tags will be blocked according to the config
  // already blocked tags will be ignored
  @process.blockTags = ${CookiePunch.blockTags(["iframe","script"], value, !node.context.inBackend)}
}
```

### Adding a contextual consent for non-iframe elements

When blocking a `<script>` you may end up with a broken UI as some styles or markup never run. Use the helper below to wrap parts of the rendered Fusion tree so Klaro can swap the broken content for a contextual consent.

```neosfusion
// Resources/Private/Fusion/CookiePunch.fusion
prototype(Vendor.Plugin.FooTube:Embed) {
  @process.blockTags = ${CookiePunch.blockTags(["script"], value, !node.context.inBackend, "footube")}
  @process.addContextualConsent = ${CookiePunch.addContextualConsent("footube", value, !node.context.inBackend)}
}
```

Another use case: `<audio>` or `<video>` tags (with or without nested `<source>` tags). You may want to block them so a visitor's IP address isn't sent to a third-party server before consent.

```neosfusion
// Resources/Private/Fusion/Component/ThirdpartyAudio.fusion
prototype(Vendor:Component.ThirdpartyAudio) < prototype(Neos.Fusion:Component) {
  thirdpartySrc = ''

  renderer = afx`
    <audio>
      <source src={props.thirdpartySrc}/>
    </audio>
  `
  @process.blockTags = ${CookiePunch.blockTags(["source"], value, !node.context.inBackend, "thirdpartymedia")}
  @process.addContextualConsent = ${CookiePunch.addContextualConsent("thirdpartymedia", value, !node.context.inBackend)}
}
```

### Let the editor choose a service from the inspector

If editors can place HTML (e.g. via the `Neos.NodeTypes.Html:Html` node type), they can introduce markup that sets cookies. With the default config, CookiePunch blocks this content — and if the markup matches no YAML pattern, it stays blocked permanently.

Add `Sandstorm.CookiePunch:Mixin.ConsentServices` to the affected node type to expose a service dropdown in the inspector:

```yaml
# Configuration/NodeTypes.yaml
"Neos.NodeTypes.Html:Html":
  superTypes:
    "Sandstorm.CookiePunch:Mixin.ConsentServices": true
```

Then wire the chosen service into the actual blocking:

```neosfusion
// Resources/Private/Fusion/CookiePunch.fusion
prototype(Neos.NodeTypes.Html:Html) {
  @process.blockTags = ${CookiePunch.blockTags(["iframe","script"], value, !node.context.inBackend, q(node).property("consentServices"))}
  // Wrap the html element with `<div data-name="myservice">...</div>` to make sure
  // the contextual consent is displayed correctly
  @process.contextualConsent = ${CookiePunch.addContextualConsent(q(node).property("consentServices"), value, !node.context.inBackend)}
}
```

### Let the editor change the text of the consent

Override the corresponding path in the Fusion prototype `Sandstorm.CookiePunch:Config.Translations` with the text property of a content node. If the property contains markup, you also need to flip a config flag so descriptions are rendered as HTML:

```yaml
# Configuration/Settings.CookiePunch.yaml
Sandstorm:
  CookiePunch:
    consent:
      # Renders descriptions in the consent modal/notice as HTML. Use with care.
      htmlTexts: true
```

### Privacy URL alternatives

Beyond the simple-string and Homepage-property forms shown in [Step 4](#step-4-providing-a-link-to-your-privacy-statement), two other paths are available.

#### XLIFF translation key

```yaml
# Configuration/Settings.CookiePunch.yaml
Sandstorm:
  CookiePunch:
    consent:
      privacyPolicyUrl: Vendor.Site:Main:privacyPolicyUrl
```

#### Dedicated PrivacyPage node type

```neosfusion
// Resources/Private/Fusion/CookiePunch.fusion
prototype(Sandstorm.CookiePunch:Config) {
    consent.privacyPolicyUrl = Neos.Neos:NodeUri {
        node = ${q(site).find('[instanceof Vendor.Site:PrivacyPage]').get(0)}
    }
}
```

### Manual styling

To take full control of the consent UI's CSS, disable the bundled stylesheet and provide your own. Note this couples your styling to Klaro's class names — it can break on package updates.

```neosfusion
// Resources/Private/Fusion/CookiePunch.fusion
prototype(Neos.Neos:Page) {
    head.javascripts.cookiepunchConsent = Sandstorm.CookiePunch:Consent {
        noCSS = true
    }
    @process.blockTags = ${CookiePunch.blockTags(["iframe","script"], value, !node.context.inBackend)}
}
```

The original Klaro stylesheet ships at `Resources/Private/KlaroCss/klaro.css` if you want to fork from it.

### Translations

Klaro already provides translations for many languages. They are exposed as XLIFF files in `Resources/Private/Translations`.

You can override translations by:

- creating your own XLIFF files that override the defaults,
- providing a translation key (e.g. `Vendor.Site:CookiePunch:services.youtube.description`) instead of literal text in the YAML config,
- overriding the corresponding path in the Fusion prototypes `Sandstorm.CookiePunch:Config.Translations` or `Sandstorm.CookiePunch:Config`.

**Example: translating service labels**

Service labels in your `Settings.CookiePunch.yaml` can be translated like this:

```yaml
# Configuration/Settings.CookiePunch.yaml
services:
  youtube:
    title: Youtube
    description: Vendor.Site:CookiePunch:services.youtube.description
```

Where:

- `Vendor.Site` is your site package key,
- `CookiePunch` is the name of the XLIFF file containing the translations (any name — must match the file name). See screenshot:

![Screenshot 2022-06-07 at 14 37 57](https://user-images.githubusercontent.com/9661367/172380821-9c374cb4-35ab-4892-afe3-f6cd09885981.png)

- and inside the file you reference the key after the colon (here: `services.youtube.description`):

```xml
<!-- Resources/Private/Translations/de/CookiePunch.xlf -->
<trans-unit id="services.youtube.description">
    <source>Erlaubt die Einbindung von Youtube-Videos.</source>
</trans-unit>
```

### Conditional Rendering of Services in the Consent Modal

You can decide at runtime whether a switch should appear in the consent modal:

```yaml
# Configuration/Settings.CookiePunch.yaml
Sandstorm:
  CookiePunch:
    consent:
      services:
        youtube:
          title: Youtube
          description: ...
          purposes:
            - mediaembeds
          when: "${q(site).find('[instanceof Vendor.Site:YouTube]').count() > 0}"
        googleAnalytics:
          title: Google Analytics
          description: ...
          purposes:
            - analytics
          when: "${q(site).property('googleAnalyticsAccountKey')}"
```

For a complete example see [`Examples/Settings.CookiePunch.WithWhenConditions.yaml`](./Examples/Settings.CookiePunch.WithWhenConditions.yaml).

This is useful in multi-site setups, and to prevent unnecessary consent switches when e.g. no YouTube video has ever been added to the content.

**Notes:**

1. The `when` value must be an Eel expression that evaluates to boolean.
2. With no `when` condition, the default is `${true}` — the switch always renders for that service.
3. When querying the content repository with `q(...)`, only `site` is available. `documentNode` and `node` are not.
4. Klaro stores past consent decisions in a cookie, so removing and re-adding e.g. a YouTube video will not re-prompt users who already consented.

**Important:** adapt your cache config for `Sandstorm.CookiePunch:Consent`. Add tags for every node type referenced in your `when` expressions:

```neosfusion
// Resources/Private/Fusion/CookiePunch.fusion
prototype(Sandstorm.CookiePunch:Consent) {
    @cache {
        mode = 'cached'
        entryIdentifier {
            node = ${node}
        }
        entryTags {
            1 = ${Neos.Caching.nodeTag(node)}
            // RootPage is the nodetype of the site node (used as `q(site)` in the `when` example above)
            2 = ${Neos.Caching.nodeTypeTag('Vendor.Site:RootPage')}  // flush when googleAnalyticsAccountKey changes
            3 = ${Neos.Caching.nodeTypeTag('Vendor.Site:YouTube')}    // flush when a YouTube video is added or removed
        }
    }
}
```

**Preventing an empty consent modal**

If all `when` expressions evaluate to false you can hide the modal entirely:

```neosfusion
// Resources/Private/Fusion/CookiePunch.fusion
prototype(Sandstorm.CookiePunch:Consent) {
    // only render if there is at least one service that has not been filtered out by its 'when' config key
    @if.hasServices = ${Array.length(this.servicesRemainingAfterWhenConditions) > 0}
}
```

### Editor-defined dynamic services

[Let the editor choose a service from the inspector](#let-the-editor-choose-a-service-from-the-inspector) lets editors pick from a **predefined** list of services. Sometimes you want them to **create a new service on the fly** — e.g. a content element where the editor pastes a third-party embed, names the service, and a matching switch appears in the consent automatically.

The trick is to override `Sandstorm.CookiePunch:Consent` and append dynamically-built services to `servicesRemainingAfterWhenConditions` (the same property used in [Conditional Rendering](#conditional-rendering-of-services-in-the-consent-modal)). The service key is derived by hashing the editor's typed name, so the same value can be used on both the blocking side and the consent side.

#### 1. A content element node type

```yaml
# Configuration/NodeTypes.CookieConsentEmbed.yaml
"Vendor.Site:CookieConsentEmbed":
  superTypes:
    "Neos.Neos:Content": true
  ui:
    label: "Third-party embed (with consent)"
    inspector:
      groups:
        consent:
          label: "Cookie consent"
  properties:
    serviceName:
      type: string
      validation:
        "Neos.Neos/Validation/NotEmptyValidator": []
      ui:
        label: "Service name (shown in the cookie consent)"
        inspector:
          group: consent
    serviceDescription:
      type: string
      ui:
        label: "Service description"
        inspector:
          group: consent
    embedCode:
      type: string
      ui:
        label: "Embed code (script / iframe)"
        reloadIfChanged: true
        inspector:
          group: consent
          editor: Neos.Neos/Inspector/Editors/CodeEditor
```

#### 2. Render and block the element's own markup

The element renders the embed, then blocks it and attaches the contextual consent. The service key is the md5 of the editor's `serviceName`:

```neosfusion
// Resources/Private/Fusion/Content/CookieConsentEmbed.fusion
prototype(Vendor.Site:CookieConsentEmbed) < prototype(Neos.Neos:ContentComponent) {
    // derive the service key once; the consent override below MUST hash the same way
    @context.serviceKey = ${String.md5(q(node).property('serviceName'))}

    renderer = afx`
        <div>{String.htmlSpecialCharsDecode(q(node).property('embedCode'))}</div>
    `
    @process.blockTags = ${CookiePunch.blockTags(["iframe","script"], value, !node.context.inBackend, serviceKey)}
    @process.addContextualConsent = ${CookiePunch.addContextualConsent(serviceKey, value, !node.context.inBackend)}
}
```

#### 3. Register a service for every embed

Override the consent prototype to scan the site for these elements and append one service per distinct name:

```neosfusion
// Resources/Private/Fusion/Overrides/CookiePunch.fusion
prototype(Sandstorm.CookiePunch:Consent) {
    // recompute the statically-configured services (we cannot self-reference
    // servicesRemainingAfterWhenConditions, so we rebuild it from the config)
    @context.originalServices = ${CookiePunchConfig.filterServicesArrayByWhenCondition(Configuration.setting("Sandstorm.CookiePunch.consent.services"), site)}

    @context.dynamicServices = Neos.Fusion:Map {
        items = ${q(site).find('[instanceof Vendor.Site:CookieConsentEmbed][serviceName != ""]')}
        itemRenderer = Neos.Fusion:DataStructure {
            // NOTE: no `name` here — Config.fusion derives the Klaro service name
            // from the map KEY (keyRenderer), not from a `name` field.
            title = ${q(item).property('serviceName')}
            description = ${q(item).property('serviceDescription')}
            purposes = ${['externalContent']}
        }
        // The KEY becomes the Klaro service name. It MUST match the `data-name`
        // produced by the element's blockTags/addContextualConsent above —
        // i.e. hash `serviceName` exactly the same way. Using the hash as key
        // also deduplicates: two embeds with the same name share one switch
        // (the last one rendered wins for title/description).
        keyRenderer = ${String.md5(q(item).property('serviceName'))}
    }

    servicesRemainingAfterWhenConditions = ${Array.concat(originalServices, dynamicServices)}

    // REQUIRED: the consent is rendered inside the cached page, and its service
    // list depends on q(site).find(...). Without these tags a newly published
    // embed would not get a switch on already-cached pages.
    @cache {
        mode = 'cached'
        entryIdentifier {
            node = ${node}
        }
        entryTags {
            1 = ${Neos.Caching.nodeTag(node)}
            2 = ${Neos.Caching.nodeTypeTag('Vendor.Site:CookieConsentEmbed')}  // flush every page's consent when an embed changes
        }
    }
}
```

#### 4. Declare the purpose

Every purpose a service references must exist under `consent.purposes` (for its title/description and translations):

```yaml
# Configuration/Settings.CookiePunch.yaml
Sandstorm:
  CookiePunch:
    consent:
      purposes:
        externalContent:
          title: External content
          description: Embedded third-party content that may set cookies.
```

#### Notes & caveats

- **The two `String.md5(...)` expressions must stay byte-identical** (the element in step 2 and the consent override in step 3). If they ever drift, the markup is blocked but no service can unblock it — the content stays broken forever.
- **Deduplication is by name.** Two embeds with the same `serviceName` produce one switch; the last-rendered node wins for `title`/`description`.
- **Make `serviceName` required.** An empty name hashes to a constant (`md5('')`), collapsing unrelated embeds into one bogus service — hence the `NotEmptyValidator` and the `[serviceName != ""]` filter.
- **The `@cache` block is not optional** — see the inline comment above.

### Per-service lifecycle callbacks (`onInit` / `onAccept` / `onDecline`)

Each service can declare JavaScript snippets that run when Klaro initialises, when the user accepts, and when the user declines:

```yaml
# Configuration/Settings.CookiePunch.yaml
Sandstorm:
  CookiePunch:
    consent:
      services:
        googleAnalytics:
          title: Google Analytics
          purposes: [analytics]
          # JS executed when Klaro initialises the service
          onInit: "console.log('GA init');"
          # JS executed when the user gives consent
          onAccept: "window.dataLayer.push({'event': 'cookie_consent_ga'});"
          # JS executed when the user withdraws consent
          onDecline: "console.log('GA declined');"
```

Each value is the *body* of a JS function. The strings are exposed via `window.cookiePunchCallbacks` and registered with Klaro before the main bundle loads — so they work under strict CSP without `unsafe-eval`. (Prior to v5 these were registered via `eval()`. See [MIGRATIONS.md](./MIGRATIONS.md#migrating-from-version-4-to-5).)

A complete service config showing every supported key — including these callbacks — is in [`Examples/Settings.CookiePunch.FullServiceConfig.yaml`](./Examples/Settings.CookiePunch.FullServiceConfig.yaml).

### Contextual Consent Only Mode

If you don't want to show the cookie banner or modal initially, use the global `contextualConsentOnly` mode introduced with [version 4.4.0](https://github.com/sandstorm/Sandstorm.CookiePunch/releases/tag/4.4.0).

```yaml
# Configuration/Settings.CookiePunch.yaml
Sandstorm:
  CookiePunch:
    consent:
      contextualConsentOnly: true
      mustConsent: false
```

## Troubleshooting

### Iframes work after unblocking but are the wrong size or in the wrong place

**Please check**

- Do you have an iframe that you blocked because it sets cookies?
- Do you have JS that manipulates this iframe?
- Is the JS *not* blocked while the iframe *is*?
- Does a reload after consenting fix the problem?

**This could be the problem**

- The JS runs once on page load, but the iframe is still "broken" (e.g. has the wrong size).
- The JS does some styling magic to extend the iframe to the available width.
- The JS needs to run when the iframe is in an unblocked state — otherwise its size calculation fails.

**How to fix**

Block the JS too — even though it doesn't set any cookies — and attach it to the same service as the iframe. The JS will then run *after* the iframe is unblocked.

## Migration guide

For upgrade notes between major versions, see [`MIGRATIONS.md`](./MIGRATIONS.md).

## Contributing

For test, build, and translation workflows, see [`CONTRIBUTING.md`](./CONTRIBUTING.md).
