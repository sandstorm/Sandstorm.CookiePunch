# Sandstorm.CookiePunch

This Neos package provides a general approach for blocking elements like script tags and iframes before the markup reaches the browser and therefore provides a general approach for
blocking cookies or other concepts of tracking user behaviour. It integrates [Klaro](https://heyklaro.com/docs/) as UI for displaying a cookie-consent and unblocking groups of elements
after the user consented.

## Features

- eel helpers to block elements (scripts and iframes) before the markup is sent to the client
- eel helper to place contextual consents for any part of the markup
- a easy way to configure blocking via yaml config supporting patterns to target elements in markup
- localization support via Yaml and/or Fusion
- data source providing all services e.g. as a dropdown in the inspector
- **an awesome cookie-consent provided by [Klaro](https://heyklaro.com/docs/)** :heart: directly bundled with this package
  - supports unblocking of elements
  - supports contextual consents to temporarily/permanently unblock content by consenting directly on the element without the need to open the consent modal
  - You definitely need to check out their project on GitHub ;)

## Installation

`composer require sandstorm/cookiepunch`

## Basic Configuration and Usages

### Step 1: Adding the consent-modal

Create a new fusion file `CookiePunch.fusion` in `.../Resources/Private/Fusion` with the following content:

```neosfusion
prototype(Neos.Neos:Page) {
    head.javascripts.cookiepunchConsent = Sandstorm.CookiePunch:Consent
    # Block Global
    @process.blockTags = ${CookiePunch.blockTags(["iframe","script"], value, !node.context.inBackend)}
}
```

This will add the needed js and css to your page. If you reload the page you should see the consent-modal. Now all `<iframe>` and `<script>` tags will be
blocked. Supported tags are `["iframe", "script", "audio", "video", "source", "track", "img", "embed", "input"]`

`!node.context.inBackend` disables blocking in the Neos backend.

Open or reload your page. If blocking works your page should look broken, sorry for that ;)

You can open the console of your browser inspector and type `klaro.show()` to verify that klaro is working. In the next steps we will configure `purposes` and `services` that will
show up in the consent modal.

Let's start unbreaking your page;)

### Step 2: Never block your own javascript

You might have some scripts that you never want to be blocked because your page would not be usable at all. They are often called `main.js`, `app.js`, `index.js`, ...

```neosfusion
renderer = Neos.Fusion:Tag {
    tagName = "script"
    attributes.src = "resource://Vendor.Example/Public/JavaScript/index.js"
    @process.neverBlockTags = ${CookiePunch.neverBlockTags(["script"],value)}
}
```

```neosfusion
renderer = afx`
    <script src={props.src} type="application/javascript" @process.neverBlockTags={CookiePunch.neverBlockTags(["script"], value)}></script>
`
```

You could also use the technique described in the next step to never block script however for scripts that are technically needed by the site the eel helper can be used in a more
flexible way and adds more semantics to your code. "Hey, I checked this part of the code and it should not be blocked!".

### Step 3: Blocking via yaml config

In `Configuration/` create a `Settings.CookiePunch.yaml`.

**HINT:** Add the `schema.json` file from this package to your IDE and select it for `Settings.CookiePunch.yaml`. This will make it easier and give you auto-completion when configuring CookiePunch.

```yaml
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
          "Packages/Unikka.Slick":
            block: false
        iframe:
          "https://anchor.fm":
            service: anchor
```

**This config is split in two parts.**

**`consent`** will directly be used to configure what is shown in the klaro consent. Klaro differentiates between `purposes` and `services`. A `purpose` is a group for
multiple `services`. Check the consent to see how changes to the config are reflected in the browser.

**`blocking`** is used to find tags by a pattern, e.g. `"Packages/Unikka.Slick"` and then block them in the backend by "breaking" the tag. Instead of `block: ...` you can define a
service `service: myservice`. This way klaro can unblock the content once the user gives his consent.

#### Matching tags with patterns

Blocking patterns are configured for a tag. We currently support bocking `<script>` and `<iframe>` tags.

Let's look at the `Packages/Neos.Neos` pattern from the example above. This pattern will match all the following tags:

```
<script src="/foo/bar/Packages/Neos.Neos/baz/index.js"/>
<script data-foo="Neos.Neos"/>
<script Neos.Neosisawesome src="/some/source/main.js"/>
```

The tag is a string that matches if it contains the given pattern. You could also match for `foo` or anything else in this string. Internally `strpos()` is used.

#### This pattern will never be blocked:

```yaml
"Packages/Neos.Neos":
  block: false
```

#### This pattern will always be blocked and cannot be unblocked by the consent:

Might be useful if the editor can place any html content and you want to always block certain tags.

```yaml
"https://really-stuff.bad":
  block: true
```

#### This pattern will be blocked and can be unblocked by the user using the consent:

```yaml
"https://anchor.fm":
  service: anchor
```

### Step 4: Providing a link to your privacy statement

The default url is `/privacy`. This can be changed.

#### via the config as a string

```yaml
Sandstorm:
  CookiePunch:
    consent:
      privacyPolicyUrl: /different-privacy
```

#### Via the config using an XLF file

```yaml
Sandstorm:
  CookiePunch:
    consent:
      privacyPolicyUrl: Vendor.Site:Main:privacyPolicyUrl
```

#### Via fusion overriding the config prototype

```neosfusion
prototype(Sandstorm.CookiePunch:Config) {
    consent.privacyPolicyUrl = Neos.Neos:NodeUri {
        node = ${q(site).find('[instanceof Vendor.Site:PrivacyPage]').get(0)}
    }
}
```

```neosfusion
prototype(Sandstorm.CookiePunch:Config) {
    consent.privacyPolicyUrl = ${q(site).find('[instanceof Vendor.Site:Homepage]').property("privacyPolicyUrl")}
    consent.privacyPolicyUrl.@process.convert = Neos.Neos:ConvertUris
}
```

Check the consent modal in the browser if the link is present!

### Step 5: Styling

#### Via yaml config (klaro css variables)

see [supported Klaro css vars](./Examples/Settings.CookiePunch.Styling.yaml).

A full yaml config can be found in `Examples/Settings.CookiePunch.Styling.yaml`

```yaml
Sandstorm:
  CookiePunch:
    consent:
      styling:
        font-family: "Work Sans,Helvetica Neue,Helvetica,Arial,sans-serif"
        green1: "red"
        green2: "green"
        green3: "blue"
        border-radius: "0"
```

#### Manual styling

Keep in mind that this might break when updating CookiePunch (which will also update klaro.js).

First we have to disable css completely `noCSS = true`

```neosfusion
prototype(Neos.Neos:Page) {
    head.javascripts.cookiepunchConsent = Sandstorm.CookiePunch:Consent {
        noCSS = true
    }
    # Block Global
    @process.blockTags = ${CookiePunch.blockTags(["iframe","script"], value, !node.context.inBackend)}
}
```

Now no styling is applied to the consent UI. All styling has to be done by you. To make this easier you can find the original klaro styles
here: `Resources/Private/KlaroCss/klaro.css`.

### Step 6: Let the user open the consent modal later

You can place a link in Neos, e.g. somewhere in your privacy statement with the `href` pointing to `#open_cookie_punch_modal`.
This will be picked up by a click event listener and open the modal. The browser will not reload the page as we use `event.preventDefault()`
internally.

Alternatively you can call `klaro.show()` in your JavaScript.

## Advanced Usages

### Full list of consent options

**see [annotated yaml of consent options](./Examples/Settings.CookiePunch.FullConsentConfig.yaml)**

Most of the inline comments are directly copied from the [annotated config.js](https://github.com/kiprotect/klaro/blob/ec6e36934db10afdac0183721ddfbcb9c79e7dc3/dist/config.js) of klaro for your convenience ;)

### Full list of service options

**see [annotated yaml of service options](./Examples/Settings.CookiePunch.FullServiceConfig.yaml)**

Most of the inline comments are directly copied from the [annotated config.js](https://github.com/kiprotect/klaro/blob/ec6e36934db10afdac0183721ddfbcb9c79e7dc3/dist/config.js) of klaro for your convenience ;)

### Blocking a rendered fusion subtree

An already blocked markup will not be blocked again when running the eel-helpers for `Neos.Neos:Page`.
This means we can hook into e.g. plugins to block them and attach them to a service in the consent modal.

This technique especially comes in handy for blocking inline `<script>...</script>` tags that cannot be matched by a pattern.

```neosfusion
// Plugin Implementation Example
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
// CookiePunch.fusion
prototype(Vendor.Plugin.FooTube:Embed) {
  // tags in this part of the tree will be blocked first
  @process.blockTags = ${CookiePunch.blockTags(["iframe","script"], value, !node.context.inBackend, "footube")}
}

prototype(Neos.Neos:Page) {
  head.javascripts.cookiepunchConsent = Sandstorm.CookiePunch:Consent
  // at last all remaining tags will be blocked according to the config
  // already blocked tags will be ignored
  @process.blockTags = ${CookiePunch.blockTags(["iframe","script"], value, !node.context.inBackend)}
}
```

### Adding a contextual consent for non-iframe element

When blocking a `<script>` tag you might end up with a broken UI as some styles might not be applied and some markup might not be created.
You can use the following eel-helper to wrap parts of the rendered fusion tree so klaro can "replace" the broken content and add
a contextual consent.

```neosfusion
// CookiePunch.fusion
prototype(Vendor.Plugin.FooTube:Embed) {
  @process.addContextualConsent = ${CookiePunch.addContextualConsent("footube", value, !node.context.inBackend)}
}
```

Another use case are `<audio>` or `<video>` tags with or without nested `<source>` tags.
You might want to block them so that a visitors IP address is not send to a third party server without a consent.

```neosfusion
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

If you allow your editors to place HTML (e.g. if you are using `Neos.NodeTypes.Html:Html` node type) the editor
can place markup that potentially sets cookies. With the default configuration CookiePunch will block this content.
This content cannot be unblocked if the markup is not matched by any pattern in the yaml config.

You can add `Sandstorm.CookiePunch:Mixin.ConsentServices` to your NodeTypes.yaml to get a dropdown in the inspector.

```yaml
"Neos.NodeTypes.Html:Html":
  superTypes:
    "Sandstorm.CookiePunch:Mixin.ConsentServices": true
```

You also need to add this for the actual blocking.

```neosfusion
prototype(Neos.NodeTypes.Html:Html) {
  @process.blockTags = ${CookiePunch.blockTags(["iframe","script"], value, !node.context.inBackend, q(node).property("consentServices"))}
  // you can wrap the html element with a `<div data-name="myservice">...</div>` to make sure 
  // the contextual consent is displayed correctly 
  @process.contextualConsent = ${CookiePunch.addContextualConsent(q(node).property("consentServices"))}
}
```

### Let the editor change the text of the consent

You can override the corresponding path in the fusion prototype `Sandstorm.CookiePunch:Config.Translations` with
the text property of a content node. If the property contains markup you need to change the config of the consent.

```yaml
Sandstorm:
  CookiePunch:
    consent:
      # Setting this to true will render the descriptions of the consent
      # modal and consent notice are HTML. Use with care.
      htmlTexts: true
```

### Translations

Klaro already provides translations for many languages. These are made available as XLIFF files in `Resources/Private/Translations`.

You can override translations

- by creating you own XLIFF files overriding the default ones
- in the yaml config by providing/overriding a translation key instead of the actual text ( e.g. for the title of service )
- by overriding the corresponding path in the fusion prototypes `Sandstorm.CookiePunch:Config.Translations` or `Sandstorm.CookiePunch:Config`

**Example: Translating service labels**

Labels of services in your Settings.CookiePunch.yml can be translated like this:

```yaml
services:
  youtube:
    title: Youtube
    description: Vendor.Site:CookiePunch:services.youtube.description
```

Where 
- `Vendor:Site` is your site package key
- `CookiePunch` is the name of the xml file containing the translations (you can choose any name here, just needs to match the file name). See this:

![Screenshot 2022-06-07 at 14 37 57](https://user-images.githubusercontent.com/9661367/172380821-9c374cb4-35ab-4892-afe3-f6cd09885981.png)

- And inside the files you need to use the key following the colon `:` (here: `services.youtube.description`).

```
            <trans-unit id="services.youtube.description">
                <source>Erlaubt die Einbindung von Youtube-Videos.</source>
            </trans-unit>
```

### Conditional Rendering of Services in Consent Modal

You can evaluate if a switch in the cookie modal should be rendered at runtime like this:

```yaml
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

This is useful for multi-site setups and to prevent unnecessary consent switches from being rendered if e.g. no youtube video has ever been added to the content.

**Note:** 

1. You need to use an eel expression that evaluates to boolean.
2. If you do not add a when condition the default is `when: ${true}`, meaning there will always be a consent switch rendered for this service
3. When querying the content repository with `q(...)`, only `site` is allowed (`documentNode` and `node` are not available)
4. Klaro saves in a cookie, if a consent was given by the user in the past, so when e.g. removing and readding a youtube video, users are not asked again for cookie approval

**Important:**

You will need to adapt you cache configuration for `Sandstorm.CookiePunch:Consent` like this (uses the config example from above, adapt to your usecase):

```neosfusion
prototype(Sandstorm.CookiePunch:Consent) {
    @cache {
        mode = 'cached'
        entryIdentifier {
            node = ${node}
        }
        entryTags {
            1 = ${Neos.Caching.nodeTag(node)}
            // RootPage being the nodetype of the site node (used as `q(site)` in the `when` settings key example above)
            2 = ${Neos.Caching.nodeTypeTag('Vendor.Site:RootPage')}  // flush when the googleAnalyticsAccountKey changes
            3 = ${Neos.Caching.nodeTypeTag('Vendor.Site:YouTube')} // flush when a youtube video is added or removed
        }
    }
}
```

**Preventing an empty cookie modal**

If you want to prevent an empty CookieConsent modal for your users if all 'when' config keys evaluate to false,
override the prototype like this in your project:

```neosfusion
prototype(Sandstorm.CookiePunch:Consent) {
    // only render if there is at least one service that has not been filtered out by its 'when' config key
    @if.hasServices = ${Array.length(this.servicesRemainingAfterWhenConditions) > 0}
}
```

## Troubleshooting

### iframes work after unblocking but are the wrong size or in the wrong place

**Please check**

- Do you have an iframe that you blocked because it adds cookies?
- Do you have a Js that manipulates this iframe?
- Is this Js not blocked while the iframe is?
- Does a reload of the page fix the problem after you consented?

**This could be the problem**

- The Js runs once when the page loads, but the iframe is still "broken" (maybe having the wrong size).
- The Js does some styling magic to extend the iframe to the available width of the page.
- The Js needs to run when the iframe is in an unblocked state otherwise the calculation fails.

**How to fix**

- Also block the Js, although it does not add any cookies.
- Attach it to the same service as the iframe.
- This way the Js will run after the iframe was unblocked.

## Migrating from version 3 to 4

You can now block more tags. This is why we generalized the Eel helpers.

**Old**
```neosfusion
    @process.blockIframes = ${CookiePunch.blockIframes(value, !node.context.inBackend)}
    @process.blockScripts = ${CookiePunch.blockScripts(value, !node.context.inBackend)}
    @process.neverBlockScripts = ${CookiePunch.neverBlockScripts(value)}
    @process.neverBlockIframes = ${CookiePunch.neverBlockIframes(value)}
```

**New**
```neosfusion
    @process.blockTags = ${CookiePunch.blockTags(["iframe", "script"],value, !node.context.inBackend)}
    @process.neverBlockTags = ${CookiePunch.neverBlockTags(["iframe", "script"],value, !node.context.inBackend)}
```

## Migrating from version 2 to 3

We changed the format for configuring the purposes to provide more functionality provided by Klaro.js. We now support `title` and `description` for each purpose.

**Old**

```yaml
Sandstorm:
  CookiePunch:
    consent:
      purposes:
        mediaembeds: Media Embeds
```

**New**

```yaml
Sandstorm:
  CookiePunch:
    consent:
      purposes:
        title: Media Embeds
        description: Some Description
```

## Migrating from version 1 to 2

**HINT:** Add the `schema.json` file from this package to your IDE and select it for your yaml files. This will make it easier and give you auto-completion and validation when migrating.

Everything concerning the actual blocking of tags by changing the markup on the server
is moved to `Sandstorm/CookiePunch/blocking/...` in the config

Everything concerning the rendering of the consent and therefore the configuration of klaro
is moved to `Sandstorm/CookiePunch/consent/...` in the config

### Blocking Mode

**Old**

```yaml
Sandstorm:
  CookiePunch:
    mode:
      blockAllScripts: true
      blockAllIframes: true
```

**New**

```yaml
Sandstorm:
  CookiePunch:
    blocking:
      tagPatterns:
        script:
          "*":
            block: true
        iframe:
          "*":
            block: true
```

### Blocking Patterns

**Old**

```yaml
Sandstorm:
  CookiePunch:
    elements:
      block: true # default blocking mode for all tags
      group: default # default group for all blocked tags
      patterns:
        "Packages/Neos.Neos":
          type: script
          block: false
        "https://anchor.fm":
          type: iframe
          block: true
```

**New**

```yaml
Sandstorm:
  CookiePunch:
    blocking:
      tagPatterns:
        script:
          # "*":
          #   service: default -> see explanation
          "Packages/Neos.Neos":
            block: false
        iframe:
          # "*":
          #   service: default -> see explanation
          "https://anchor.fm":
            service: mediaembeds
```

IMPORTANT: The wildcard pattern `*` should only be used in rare situations. Think about if you really need
to change the default blocking behaviour and create a generic service `default` as this defies the whole purpose
of documenting the services used for this page.

A pattern schould eigther have `block: true|false` OR `service: "nameofservice"`

### Groups -> Services

We changed the naming to match the api of klaro. Everything concerning the consent can be configured here:

`Sandstorm/CookiePunch/consent/...`

**Old**

```yaml
Sandstorm:
  CookiePunch:
    groups:
      anchor:
        title: Anchor FM
        description: Podcast Player
```

**New**

```yaml
Sandstorm:
  CookiePunch:
    consent:
      services:
        anchor:
          title: Anchor FM
          description: Podcast Player
```

### Consent Options for each group -> service

As we are already in `Sandstorm/CookiePunch/consent/...` we drop the additional
`consent` nesting level. All options of a service concern the consent.

**Old**

```yaml
anchor:
  title: Anchor FM
  description: Podcast Player
  purposes:
    - mediaembeds
  consent:
    required: true
```

**New**

```yaml
anchor:
  title: Anchor FM
  description: Podcast Player
  purposes:
    - mediaembeds
  required: true
```

For more consent options of a service see the docs for advanced configuration.

### Styling

If you have custom styling it will most likely break depending on what was changed in klaro.js bundled
with this package. This package does not provide a SCSS file anymore. Check the section on styling for
more information.

## Contributing

You need a running Neos distribution and install this package.

### PHP Code -> Blocking

You can run tests when making changes to the processing of the markup. 

run `./bin/phpunit -c Build/BuildEssentials/PhpUnit/UnitTests.xml DistributionPackages/Sandstorm.CookiePunch/Tests/Unit/`

Or functional tests, when changing the conditional consent rendering:

run `./bin/phpunit -c Build/BuildEssentials/PhpUnit/FunctionalTests.xml DistributionPackages/Sandstorm.CookiePunch/Tests/Functional/`


### Fusion, XLF and Typescript

run `nvm use && yarn` to install all dependencies
run `yarn run watch` to start developing Typescript.

We use NodeJs to automatically generate XLS and Fusion files based on the original Klaro translations.
You can recompile these files by running `build:translations`.

Remember to run `yarn run build` when you are finished.

Check out the `package.json` for more useful scripts.

