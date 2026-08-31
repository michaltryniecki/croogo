# Vendored Tabler assets

Prebuilt distribution files, committed rather than built, so `composer install` is
the only step needed to get a working admin panel — no node toolchain.

| File | Package | Version | Licence |
| --- | --- | --- | --- |
| `tabler.min.css` | [`@tabler/core`](https://github.com/tabler/tabler) | 1.4.0 | MIT |
| `tabler-icons.min.css`, `fonts/tabler-icons.*` | [`@tabler/icons-webfont`](https://github.com/tabler/tabler-icons) | 3.46.0 | MIT |
| `../../js/tabler/bootstrap.bundle.min.js` | [`bootstrap`](https://github.com/twbs/bootstrap) | 5.3.8 | MIT |
| `../../js/tabler/tabler-theme.min.js` | [`@tabler/core`](https://github.com/tabler/tabler) | 1.4.0 | MIT |

`tabler.min.css` already bundles Bootstrap 5.3's CSS, so the admin panel does **not**
load `Core/webroot/css/bootstrap.min.css` (Bootstrap 4) any more. That file stays for
the front-end theme, which still uses it.

Bootstrap's **JavaScript** comes from `../../js/tabler/bootstrap.bundle.min.js`
(Bootstrap + Popper), vendored separately.

## The light/dark switch

`tabler-theme.min.js` is `@tabler/core`'s own `dist/js/tabler-theme.min.js`. It reads
`?theme=<name>` off the URL or, failing that, `localStorage['tabler-theme']`, and sets
`data-bs-theme` on `<html>` - the attribute the whole of `tabler.min.css` is keyed off.
It is loaded **first and synchronously** in `Core/templates/element/admin/javascripts.php`
so the theme is applied before the first paint; deferring it makes a dark admin flash
white on every page load.

The switch itself is two links in `Core/templates/element/admin/header.php`. Their
`?theme=` hrefs are what this script consumes, so the switch works with JavaScript
otherwise disabled; `Admin.themeToggle()` intercepts the click, writes the same
`tabler-theme` key and flips the attribute in place, so the usual case is not a page
load.

Two things follow the theme rather than being part of it:

* the **sidebar** is pinned to `data-bs-theme="dark"` in
  `Core/templates/element/admin/navigation.php` and stays dark in both themes - see
  the note there;
* the **native date controls** get their calendar glyph and dropdown from the browser,
  which follows the `color-scheme` property. `tabler.min.css` already sets
  `color-scheme: dark` under `[data-bs-theme=dark]`, so there is nothing to do here.

Tabler's script also handles `theme-base`, `theme-font`, `theme-primary` and
`theme-radius`. Those need `tabler-themes.css`, which is **not** vendored, so passing
them does nothing but set an attribute no stylesheet reads.

`@tabler/core`'s own `dist/js/tabler.min.js` is deliberately **not** vendored. It
bundles a second private copy of Bootstrap and registers its own data-api handlers,
so loading it alongside the Bootstrap bundle makes every dropdown toggle twice on a
single click - it opens and closes again. What it adds on top of Bootstrap is glue
for Tabler's demo widgets (autosize, countup, charts), none of which Croogo uses.

The `sourceMappingURL` footers were stripped on the way in: the `.map` files are not
vendored, and Cake's `AssetMiddleware` logs a `MissingRouteException` for every
request it cannot serve.

## Refreshing

```
npm pack @tabler/core@<version> @tabler/icons-webfont@<version>
```

then copy `dist/css/tabler.min.css`, `dist/tabler-icons.min.css`,
`dist/fonts/tabler-icons.{woff2,woff,ttf}` and `dist/js/tabler-theme.min.js` over the
files here, strip the `sourceMappingURL` comments again, and update the versions in the
table above.

Bootstrap comes from `npm pack bootstrap@<version>` the same way - check Tabler's own
`package.json` for the version it was built against before bumping it independently.

Croogo's own overrides live in `../core/croogo-tabler.css` — keep them there rather
than editing these files, so a refresh stays a straight copy.
