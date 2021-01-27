# Sandstorm.CookiePunch

**THIS PACKAGE IS STILL WIP ;) FEEDBACK IS HIGHLY WELCOME.**

This Neos package provides a general approach for blocking elements like script tags and iframes before the markup reaches the browser and therefore provides a general approach for blocking cookies or other concepts of tracking user behaviour. It also provides a UI in the browser for displaying a cookie-consent and partially unblocking groups of elements.

## Features

* helpers to block elements (scripts and iframes) before the markup is send to the client
* blocking modes:
    * block all + allowed list
    * allow all + blocked list
* grouping of elements
* patterns to target elements in markup
* blocking of HTML snippets created by the editor (Neos.NodeTypes.Html:Html)
* cookie-consent (provided by Klaro.js) to block/unblock grouped elements 
* localization support
* useful default config and styling
* SCSS to customize cookie-consent styling

## Instalation

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

Open the console of your browser inspector an type `klaro.show()`. Make sure all switches are turned off and reload your page again.

As the default behaviour we will block all iframes and scripts. This way nothing slips through e.g. when you install a new plugin. As a developper you should allways check if can trust the content added by a plugin. 

Don't worry, we provide some tools to make it easiert for you to configure ;)

Now that your page probably looks broken, let's try to fix it ;)

### STEP 3: Never, ever block your own javascript

You might have some scripts that you never want to be blocked because your page would not be usable at all. They are often called `main.js`, `app.js`, `index.js`, ...


```neosfusion
Neos.Fusion:Tag {
    tagName = "script"
    attrbutes.src = "resource://Vendor.Example/Public/JavaScript/index.js"
    @process.neverBlock = ${CookiePunch.neverBlockScripts(value)}
}
```

This Eel helper will add `data-never-block` attribute to you script tag. These tags will be ignored by `CookiePunch.blockScripts()`.

### STEP 4: Unblock scripts of other packages

You also might NOT want to block scripts of packages that only use JS to visually enhance your page by adding animated or interactive content. This could e.g. be a slider.

For this you can add elements to your config that should not be blocked.

In `Configuration/` create a `Settings.CookiePunch.yaml`.


```yaml
Sandstorm:
  CookiePunch:
    elements:
      patterns:
	      "Packages/Vendor.Slider":
	        type: script
	        block: false
```

This will match any script tag containing the pattern.

You could also try to use the `CookiePunch.blockScripts()` Eel helper to override some aspects the fusion in the package itself, however this is not really robust concerning updates and you would also have to look into the implementation of each package.

### STEP 4: Create groups that can individually be switched on/off by the user

Now that your page does not look broken anymore we can start setting up different groups an assign scripts and iframes for the user to active/deactivete.

```yaml
Sandstorm:
  CookiePunch:
    purposes:
      media: Media Embeds
    groups:
      media:
        title: Bar
        purposes:
          - media
        description: Some bar description
    elements:
      patterns:
	      "https://www.youtube.com/embed/":
	        type: iframe
	        block: true
	        group: media
        "https://player.vimeo.com/":
        	type: iframe
        	block: true
        	group: media
```

In the consent modal you should see a new switch "Media".

