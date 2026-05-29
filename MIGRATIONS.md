# Migration Guide

Migrations are listed newest-first. Skip the sections older than your current version.

- [Migrating from version 4 to 5](#migrating-from-version-4-to-5)
- [Migrating from version 3 to 4](#migrating-from-version-3-to-4)
- [Migrating from version 2 to 3](#migrating-from-version-2-to-3)
- [Migrating from version 1 to 2](#migrating-from-version-1-to-2)

## Migrating from version 4 to 5

Version 5 rewrites the Klaro callback bridge so that strict Content Security Policies (CSP) work out of the box. Previously, callbacks were registered via `eval()`. They are now serialised into `window.cookiePunchCallbacks` by a script tag that runs *before* the main Klaro bundle.

**No action is required for most setups** — `onInit`, `onAccept`, and `onDecline` configured per-service in `Settings.CookiePunch.yaml` continue to work.

**You may need to adapt** if you wired custom JavaScript into Klaro callbacks outside the YAML config, e.g. by patching the inline `<script>` written by `Sandstorm.CookiePunch:Consent`. Move those callback bodies into the per-service `onInit`/`onAccept`/`onDecline` keys instead — see the README "Per-service lifecycle callbacks" section.

## Migrating from version 3 to 4

You can now block more tags. This is why we generalized the Eel helpers.

**Old**

```neosfusion
// Resources/Private/Fusion/CookiePunch.fusion
@process.blockIframes = ${CookiePunch.blockIframes(value, !node.context.inBackend)}
@process.blockScripts = ${CookiePunch.blockScripts(value, !node.context.inBackend)}
@process.neverBlockScripts = ${CookiePunch.neverBlockScripts(value)}
@process.neverBlockIframes = ${CookiePunch.neverBlockIframes(value)}
```

**New**

```neosfusion
// Resources/Private/Fusion/CookiePunch.fusion
@process.blockTags = ${CookiePunch.blockTags(["iframe", "script"], value, !node.context.inBackend)}
@process.neverBlockTags = ${CookiePunch.neverBlockTags(["iframe", "script"], value)}
```

## Migrating from version 2 to 3

We changed the format for configuring purposes to expose more functionality from Klaro.js. Each purpose now supports `title` and `description`.

**Old**

```yaml
# Configuration/Settings.CookiePunch.yaml
Sandstorm:
  CookiePunch:
    consent:
      purposes:
        mediaembeds: Media Embeds
```

**New**

```yaml
# Configuration/Settings.CookiePunch.yaml
Sandstorm:
  CookiePunch:
    consent:
      purposes:
        mediaembeds:
          title: Media Embeds
          description: Some Description
```

## Migrating from version 1 to 2

**HINT:** Add the `schema.json` file from this package to your IDE and select it for your YAML files. This will give you auto-completion and validation while migrating.

Everything concerning the actual blocking of tags by changing the markup on the server is moved to `Sandstorm/CookiePunch/blocking/...` in the config.

Everything concerning the rendering of the consent (and therefore the configuration of Klaro) is moved to `Sandstorm/CookiePunch/consent/...` in the config.

### Blocking Mode

**Old**

```yaml
# Configuration/Settings.CookiePunch.yaml
Sandstorm:
  CookiePunch:
    mode:
      blockAllScripts: true
      blockAllIframes: true
```

**New**

```yaml
# Configuration/Settings.CookiePunch.yaml
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
# Configuration/Settings.CookiePunch.yaml
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
# Configuration/Settings.CookiePunch.yaml
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

IMPORTANT: The wildcard pattern `*` should only be used in rare situations. Think about whether you really need to change the default blocking behaviour and create a generic service `default` — doing so defeats the purpose of documenting the services used by this page.

A pattern should either have `block: true|false` OR `service: "nameofservice"`.

### Groups -> Services

We changed the naming to match the Klaro API. Everything concerning the consent can be configured under `Sandstorm/CookiePunch/consent/...`.

**Old**

```yaml
# Configuration/Settings.CookiePunch.yaml
Sandstorm:
  CookiePunch:
    groups:
      anchor:
        title: Anchor FM
        description: Podcast Player
```

**New**

```yaml
# Configuration/Settings.CookiePunch.yaml
Sandstorm:
  CookiePunch:
    consent:
      services:
        anchor:
          title: Anchor FM
          description: Podcast Player
```

### Consent Options for each group -> service

Since we are already inside `Sandstorm/CookiePunch/consent/...`, we drop the additional `consent` nesting level — all options on a service concern the consent.

**Old**

```yaml
# Configuration/Settings.CookiePunch.yaml
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
# Configuration/Settings.CookiePunch.yaml
anchor:
  title: Anchor FM
  description: Podcast Player
  purposes:
    - mediaembeds
  required: true
```

For more consent options of a service see the docs for advanced configuration.

### Styling

If you have custom styling it will most likely break depending on what changed in the bundled `klaro.js`. This package no longer ships an SCSS file. See the "Styling" section in the README for details.
