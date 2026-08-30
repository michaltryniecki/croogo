# Vendored Tabler assets

Prebuilt distribution files, committed rather than built, so `composer install` is
the only step needed to get a working admin panel — no node toolchain.

| File | Package | Version | Licence |
| --- | --- | --- | --- |
| `tabler.min.css` | [`@tabler/core`](https://github.com/tabler/tabler) | 1.4.0 | MIT |
| `tabler-icons.min.css`, `fonts/tabler-icons.*` | [`@tabler/icons-webfont`](https://github.com/tabler/tabler-icons) | 3.46.0 | MIT |
| `../../js/tabler/bootstrap.bundle.min.js` | [`bootstrap`](https://github.com/twbs/bootstrap) | 5.3.8 | MIT |

`tabler.min.css` already bundles Bootstrap 5.3's CSS, so the admin panel does **not**
load `Core/webroot/css/bootstrap.min.css` (Bootstrap 4) any more. That file stays for
the front-end theme, which still uses it.

Bootstrap's **JavaScript** comes from `../../js/tabler/bootstrap.bundle.min.js`
(Bootstrap + Popper), vendored separately.

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

then copy `dist/css/tabler.min.css`, `dist/tabler-icons.min.css` and
`dist/fonts/tabler-icons.{woff2,woff,ttf}` over the files here, strip the
`sourceMappingURL` comments again, and update the versions in the table above.

Bootstrap comes from `npm pack bootstrap@<version>` the same way - check Tabler's own
`package.json` for the version it was built against before bumping it independently.

Croogo's own overrides live in `../core/croogo-tabler.css` — keep them there rather
than editing these files, so a refresh stays a straight copy.
