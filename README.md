# الخزائن الأمثل — Optimum Closets

A bilingual WooCommerce storefront for Optimum Closets: Arabic by default (RTL), plus a complete English version under `/en/` (LTR). It is built as a WordPress theme, the same structure as the live site, and runs locally with demo data in **preview mode**.

> **Preview mode:** product names and prices are demo placeholders, the images are illustrative 3D renders, and no payment is taken. Everything real that is still needed is listed in [`docs/HANDOVER.md`](docs/HANDOVER.md#integrations-still-required).

## What's here

| Path | What it is |
|---|---|
| `optimum-closets/` | The WordPress / WooCommerce theme (v2.0.0) |
| `optimum-closets/concepts/` | The three design concepts plus the comparison page (static, bilingual) |
| `optimum-closets/assets/img/renders/` | Optimised images (WebP in several sizes, JPEG fallback), with a manifest of Arabic and English alt text and the hotspot data |
| `tools/renders/` | The Blender scene generator that produces every render, and the optimiser |
| `tools/i18n/` | Arabic translations and the build script for `.po`, `.mo` and `.l10n.php` |
| `tools/dev/` | One-command local store (WordPress + WooCommerce + SQLite) |
| `tools/qa/` | Browser checks: screenshots, overflow, console errors, and the full order journey |
| `docs/` | Handover notes, references, image prompts, screenshots |

## Run it locally

Requirements: PHP 8.1+ (pdo_sqlite, gd, intl, zip), Composer, Node 18+, git. MySQL and Docker are not needed.

```bash
tools/dev/setup.sh     # WordPress 6.8 + WooCommerce 9.8 + theme + demo data (≈5 min, first run only)
tools/dev/serve.sh     # http://localhost:8080   (admin / admin)
```

- Arabic: `http://localhost:8080/` · English: `http://localhost:8080/en/`
- Concepts: `http://localhost:8080/wp-content/themes/optimum-closets/concepts/`
- Preview a direction across the whole store: `?concept=showroom`, `?concept=bayt` (reset with `?concept=default`)

## Tests

```bash
cd .dev/node && npm i playwright            # once; uses the pre-installed Chromium if present
NODE_PATH=.dev/node/node_modules node tools/qa/flow.js --out /tmp/flow        # order journey, ar
NODE_PATH=.dev/node/node_modules node tools/qa/flow.js --lang en --width 375   # order journey, en, mobile
NODE_PATH=.dev/node/node_modules node tools/qa/shots.js --pages home,shop,product,cart --widths 375,768,1440 --langs ar,en --out /tmp/shots
```

## Install on the live site (after staging)

1. Back up the site. Build the zip with `./build.sh`, then upload it on a **staging copy** under Appearance → Themes.
2. Do **not** run the demo importer on the live store. Add real products and use the "Wardrobe options & pricing" box on each product (format in [`docs/HANDOVER.md`](docs/HANDOVER.md#product-configuration)).
3. Cart, Checkout and My Account pages must use the classic shortcodes (`[woocommerce_cart]`, `[woocommerce_checkout]`, `[woocommerce_my_account]`).
4. Go through the checklist in [`docs/HANDOVER.md`](docs/HANDOVER.md).

## Regenerate images and translations

```bash
python -m venv .venv && .venv/bin/pip install bpy==5.0.1 pillow
.venv/bin/python tools/renders/wardrobes.py --out tools/renders/out --samples 24       # --list to see all shots
.venv/bin/python tools/renders/optimize.py --src tools/renders/out --dest optimum-closets/assets/img/renders
WP=".dev/wpcli/vendor/bin/wp --path=.dev/wp" tools/i18n/rebuild.sh
```
