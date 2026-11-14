# Fonts

| File | Face | Licence | Use |
|---|---|---|---|
| `geist-latin-wght-normal.woff2` | Geist, variable weight 100-900, Latin | SIL OFL 1.1 (`GEIST-LICENSE.txt`) | Headlines and text |
| `geist-mono-latin-{400,500}-normal.woff2` | Geist Mono, Latin | SIL OFL 1.1 (`GEIST-MONO-LICENSE.txt`) | Mono labels |

All files are self-hosted (no visitor depends on a system font or a third-party
request) and come from the official `@fontsource-variable/geist` and
`@fontsource/geist-mono` packages.

## Changing the typeface

1. Add the `woff2` files here, with their licence.
2. Point the `@font-face` blocks in `resources/css/fonts.css` at them.
3. Put the family first in `--font-sans` (`resources/css/app.css`).
4. Update the preload in `resources/views/components/layouts/app.blade.php`.
5. Run `npm run build` and check the largest headlines: display tracking
   (`.t-hero`, `.t-display`) is tuned for Geist.
