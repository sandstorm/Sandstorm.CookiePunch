# Contributing

You need a running Neos distribution with this package installed.

## PHP Code -> Blocking

Run unit tests when changing the markup processing:

```bash
# bash — from the Neos/Flow application root
bin/phpunit -c <your-phpunit-unit-config.xml> Packages/Application/Sandstorm.CookiePunch/Tests/Unit/
```

Run functional tests when changing the conditional consent rendering:

```bash
# bash — from the Neos/Flow application root
bin/phpunit -c <your-phpunit-functional-config.xml> Packages/Application/Sandstorm.CookiePunch/Tests/Functional/
```

The exact `-c` config path depends on your distribution. In a stock Neos 9 setup the configs typically live in `Build/PhpUnit/`.

## Fusion, XLIFF and TypeScript

```bash
# bash — inside Packages/Application/Sandstorm.CookiePunch
nvm use && yarn          # install dependencies
yarn run watch           # rebuild TypeScript on change while developing
yarn run build           # full production build before committing
```

We use Node.js to automatically generate XLIFF and Fusion files from the original Klaro translations. Recompile them with:

```bash
# bash
yarn run build:translations
```

Check `package.json` for the full list of scripts.
