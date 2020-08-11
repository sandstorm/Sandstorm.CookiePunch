# Sandstorm.CookieCutter

This Neos package provides a general approach for blocking elements like script tags and iframes before the markup reaches the browser and therefore provides a general approach for blocking cookies or other concepts of tracking user behaviour. It also provides a UI in the browser for displaying a cookie-consent and partially unblocking groups of elements.

**This is what you can do so far:**

* verbosely block elements on the server before the markup is send to the client
* choose between two blocking approaches:
    * block everything and whitelist exceptions (recommended)
    * blacklist elements to be blocked
* block elements from all packages, without the need to customize or override
* block content created by editors, e.g. if they copy pasted HTML snippetss
* enable/disable groups of elements through a cookie consent in the frontend
* localize the frontend using Settings.yaml and Xliff or directly use content translated by your editors
* start with useful defaults concerning configuration and styling
* easily customize when needed


WIP
