# Sandstorm.CookiePunch

This Neos package provides a general approach for blocking elements like script tags and iframes before the markup reaches the browser and therefore provides a general approach for blocking cookies or other concepts of tracking user behaviour. It also provides a UI in the browser for displaying a cookie-consent and partially unblocking groups of elements.

## Features

* helpers to block elements (scripts and iframes) before the markup is sent to the client
* possible blocking modes:
    * block all + allowed list
    * allow all + blocked list
* grouping of elements
* patterns to target elements in markup
* blocking of HTML snippets created by the editor (Neos.NodeTypes.Html:Html)
* cookie-consent (provided by Klaro.js) to block/unblock grouped elements 
* localization support
* useful default config and styling
* SCSS to customize cookie-consent styling

## Installation

`composer require sandstorm/cookiepunch`

## Basic Setup

### STEP 1: Adding the consent-modal

In your `Overrides.fusion` or `Root.fusion` add

```neosfusion
prototype(Neos.Neos:Page) {
    // This adds the javascript and css needed for the cookie-consent
    head.javascripts.cookiePunchConsent = Sandstorm.CookiePunch:Consent
}
```

This will add the needed js and css to your page. If you reload the page you should see the consent-modal.

### STEP 2: Blocking everything

```neosfusion
prototype(Neos.Neos:Page) {
    // This adds the javascript and css needed for the cookie-consent
    head.javascripts.cookiePunchConsent = Sandstorm.CookiePunch:Consent
    
    // This will block all iframes & script tags
    @process.blockIframes = ${CookiePunch.blockIframes(value, !node.context.inBackend)}
    @process.blockScripts = ${CookiePunch.blockScripts(value, !node.context.inBackend)}
}
```

Open the console of your browser inspector and type `klaro.show()`. Make sure all switches are turned off and reload your page again.

As the default behaviour we will block all iframes and scripts. This way nothing slips through e.g. when you install a new plugin. As a developer you should allways check if can trust the markup added by a plugin. 

Don't worry, we provide some tools to make it easier for you to configure ;)

Now that your page probably looks broken, let's try to fix it ;)

### STEP 3: Never block your own javascript

You might have some scripts that you never want to be blocked because your page would not be usable at all. They are often called `main.js`, `app.js`, `index.js`, ...

```neosfusion
Neos.Fusion:Tag {
    tagName = "script"
    attrbutes.src = "resource://Vendor.Example/Public/JavaScript/index.js"
    @process.neverBlock = ${CookiePunch.neverBlockScripts(value)}
}
```

This Eel helper will add a `data-never-block` attribute to your script tag. These tags will be ignored by `CookiePunch.blockScripts()`.

### STEP 4: Create groups, purposes and add elements

In `Configuration/` create a `Settings.CookiePunch.yaml`.

```yaml
Sandstorm:
  CookiePunch:
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

Now all tags containing `"https://www.youtube.com/embed/"` will be blocked and added to the group `media`. Reload your page, open the consent modal -> you should see a new switch.
