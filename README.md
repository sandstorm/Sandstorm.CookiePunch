# Sandstorm.CookieCutter

**THIS PACKAGE IS STILL WIP ;) FEEDBACK IS HIGHLY WELCOME.**

This Neos package provides a general approach for blocking elements like script tags and iframes before the markup reaches the browser and therefore provides a general approach for blocking cookies or other concepts of tracking user behaviour. It also provides a UI in the browser for displaying a cookie-consent and partially unblocking groups of elements.

## Features

* helpers to block elements (scripts and iframes) before the markup is send to the client
* blocking modes:
    * whitelist elements to not be blocked
    * blacklist elements to be blocked
* grouping of elements
* patterns to target elements in markup
* blocking of HTML snippets created by the editor (Neos.NodeTypes.Html:Html)
* cookie-consent (provided by Klaro.js) to block/unblock grouped elements 
* localization support
* useful default config and styling
* SCSS to customize cookie-consent styling

## Instalation

`composer require sandstorm/cookiecutter`

## Basic Setup

### STEP 1: Register Eel-Helpers

In your `Settings.yaml` add

```yaml
Neos:
  Fusion:
    defaultContext:
      'CookieCutter': Sandstorm\CookieCutter\Eel\Helper\CookieCutter
```

### STEP 2: Adding the consent-modal

In your `Overrides.fusion` or `Root.fusion` add

```neosfusion
prototype(Neos.Neos:Page) {
    // This adds the javascript and css needed for the cookie-consent
    head.javascripts.cookieCutterConsent = Sandstorm.CookieCutter:Consent
}
```

This will add the needed js and css to your page. If you reload the page you should see the consent-modal.

### STEP 3: Blocking everything

```neosfusion
prototype(Neos.Neos:Page) {
    // This adds the javascript and css needed for the cookie-consent
    head.javascripts.cookieCutterConsent = Sandstorm.CookieCutter:Consent
    
    // This will block all iframes & script tags
    @process.blockIframes = ${CookieCutter.blockIframes(value, !node.context.inBackend)}
    @process.blockScripts = ${CookieCutter.blockScripts(value, !node.context.inBackend)}
}
```

Open the console of your browser inspector an type `klaro.show()`. Make sure all switches are turned off and reload your page again.

As the default behaviour we will block all iframes and scripts. This way nothing slips through e.g. when you install a new plugin. As a developper you should allways check if can trust the markup added by a plugin. 

Don't worry, we provide some tools to make it easiert for you to configure ;)

Now that your page probably looks broken, let's try to fix it ;)

### STEP 4: Never block your own javascript

You might have some scripts that you never want to be blocked because your page would not be usable at all. They are often called `main.js`, `app.js`, `index.js`, ...

```neosfusion
Neos.Fusion:Tag {
    tagName = "script"
    attrbutes.src = "resource://Vendor.Example/Public/JavaScript/index.js"
    @process.neverBlock = ${CookieCutter.neverBlockScripts(value)}
}
```

This Eel helper will add `data-never-block` attribute to you script tag. These tags will be ignored by `CookieCutter.blockScripts()`.

### STEP 5: Create groups, purposes and add elements

In `Configuration/` create a `Settings.CookieCutter.yaml`.

```yaml
Sandstorm:
  CookieCutter:
    purposes:
      mediaembeds: Media Embeds
    groups:
      media:
        title: Bar
        purposes:
          - mediaembeds
        description: Some bar description
    elements:
      "https://www.youtube.com/embed/":
        type: iframe
        block: true
        group: media
```

Now all tags containing `"https://www.youtube.com/embed/"` will be blocked and added to the group `media`. Reload you page, open the consent modal -> you should see a new switch.
