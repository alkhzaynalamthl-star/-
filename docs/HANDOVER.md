# Handover — Optimum Closets store (theme v2.0.0)

## ملخص بالعربية

- **ما تم:** متجر ووكومرس عربي افتراضيًا (من اليمين لليسار) بنسخة إنجليزية كاملة تحت `/en/`. يعمل محليًا ببيانات تجريبية في «وضع المعاينة». يشمل: صفحة رئيسية، ومتجر بفلاتر (نوع الأبواب، عرض الجدار، اللون، السعر) وبحث وترتيب، وصفحة منتج فيها معرض صور مع تكبير ومعاينة من الداخل بنقاط تفاعلية وتبديل للتشطيب مع الصورة المطابقة وسعر يُحسب على الخادم، والسلة، وإتمام الطلب، والتأكيد، وحسابي، ونموذج تصميم بالمقاس مع رفع الصور، وأعمالنا، والمعارض، والتواصل.
- **التصاميم الثلاثة** متاحة للمعاينة، والموصى به «أتيليه» هو الافتراضي، ويمكن تغييره من المخصّص.
- **الصور** مجسّمات ثلاثية الأبعاد توضيحية أنشأناها بالكامل، ومعلَّمة «صورة توضيحية». لم يكن موقع الشركة ولا صورها متاحة من بيئة العمل، ويجب استبدالها بصور منتجاتكم الفعلية.
- **ما زال مطلوبًا:** بوابة دفع حقيقية، وأسعار ومنتجات فعلية، والشعار وألوان الهوية، وأوقات العمل، ورقم واتساب، وبريد الإشعارات. القائمة الكاملة في «Integrations still required» أدناه.
- **تنبيه أمني:** محركات البحث تفهرس صفحات غريبة على نطاق optimum-closets.com (حساسات إطارات وشواحن بطاريات). هذا مؤشر محتمل على اختراق من نوع SEO spam، ونوصي بفحص الموقع الحالي.

---

## 1. What was delivered

| Deliverable | Where |
|---|---|
| WordPress / WooCommerce theme, bilingual, three switchable design directions | `optimum-closets/` |
| Three concept homepages (AR/EN, mobile and desktop, product card preview) plus a comparison page with the recommendation | `optimum-closets/concepts/index.html` (published preview link in the delivery message) |
| 48 original renders, optimised to WebP in 2–3 sizes plus a JPEG fallback, with Arabic and English alt text | `optimum-closets/assets/img/renders/` |
| Render generator (Blender) and optimiser | `tools/renders/` |
| Arabic translation of all theme strings (`.po`, `.mo`, `.l10n.php`) | `optimum-closets/languages/`, source in `tools/i18n/ar.py` |
| One-command dev store and browser tests | `tools/dev/`, `tools/qa/` |
| Screenshots | `docs/screenshots/` |
| Prompts for AI image generation (not connected, see §6) | `docs/IMAGE-PROMPTS.md` |

## 2. Architecture (and why)

The repository already contained a custom WooCommerce theme, and the live site runs WordPress / WooCommerce. Its URLs look like `product-category/...`, with English under `/en/`. So the work continues **inside that structure**, as a theme:

- **No page builder and no new runtime.** It is PHP templates, one CSS file with design tokens, and small vanilla JS files. jQuery is used only where WooCommerce's own cart and checkout scripts need it.
- **Languages.** Arabic is the default and English lives under `/en/`, the same scheme the live site uses. If Polylang or WPML is active, the theme defers to it. Otherwise a built-in layer strips and restores `/en/`, switches the locale, prefixes every generated link, and reads English fields that the theme adds to products, pages, categories and attributes. The language switcher keeps the current page, the query string (filters, product options) and the WooCommerce session, so the cart is kept.
- **Pricing on the server.** Each configurable product stores its options and price deltas in `_oc_config`. The browser only sends *choices*. The price is computed in PHP by the live price endpoint (`POST /wp-json/optimum/v1/price`), when adding to the cart, and again at every cart total calculation. Tampered requests are rejected (HTTP 422) or re-priced.
- **Preview mode** (Customizer → Optimum Closets → Design & preview mode, on by default): a site-wide banner, demo data, and a "Preview — no payment" checkout method that records orders as *preview orders*. The confirmation page only says "payment received" when `WC_Order::is_paid()` is true, which requires a real gateway to verify the payment on the server.
- **Secrets stay out of the front end.** The optional request webhook is signed with `OPTIMUM_WEBHOOK_SECRET`, which is defined in `wp-config.php`, never in the theme or the page.

### Product configuration

Edit it in the product screen, in the "Wardrobe options & pricing" box. It is validated on save:

```json
{
  "door": "hinged-wood", "height": 255, "depth": 60,
  "pricing": "table",
  "widths": [[160, 4900], [200, 5900], [240, 6900], [280, 7900]],
  "default": {"width": 240, "finish": "oak", "layout": "classic"},
  "groups": {"finish": {"oak": 0, "walnut": 900, "cashmere": 400},
             "layout": {"classic": 0, "long": 250, "drawers": 750}},
  "extras": {"led": 690, "install": 350},
  "image_group": "finish",
  "images": {"oak": {"closed": 101, "open": 102, "open_by": {"long": 103}, "gallery": [104]}},
  "hotspots": {"102": {"rail_double": [35.9, 57.1]}},
  "shown": {"width": 240}
}
```

Use `"pricing": "per_metre"` with `"per_metre": {"rate": 2950, "min": 150, "max": 600, "step": 10}` for made-to-measure products. Option labels (Arabic and English) live in `optimum_option_registry()` in `inc/catalog.php`.

## 3. Brand: what was verified, and what is temporary

The brand site (optimum-closets.com), Instagram and the Facebook page **could not be opened from this environment**: the network policy blocked them, so the logo, colours, fonts and photos could not be inspected. Only facts visible in search-engine results were used.

**Confirmed (search-indexed pages of optimum-closets.com, 25 Sep 2026):**

| Fact | Source |
|---|---|
| Showroom, Jeddah: Prince Sultan Street, Al-Naeem District | optimum-closets.com/en/contact-us |
| Showroom, Makkah: Third Ring Road | optimum-closets.com/en/contact-us; Yango Maps listing |
| Phone +966 54 120 0434 · info@optimum-closets.com | optimum-closets.com/en/contact-us |
| Free initial consultation and measurement | optimum-closets.com/en/home |
| 2–4 weeks from final design approval to installation | optimum-closets.com/en/home |
| Instagram @optimum_closests | instagram.com/optimum_closests |

**Temporary choices (replace once the originals are available):**

| Item | Temporary choice | Where to change it |
|---|---|---|
| Logo | Text wordmark "الخزائن الأمثل / OPTIMUM CLOSETS" | Appearance → Customize → Site Identity → Logo (replaces the wordmark automatically) |
| Colours (Atelier) | Ivory `#fbf9f5`, espresso `#1f1a16`, bronze `#8a6a4a`, brass `#a9855e` | `--bg`, `--ink`, `--accent` tokens at the top of `assets/css/store.css` |
| Typefaces | Markazi Text + IBM Plex Sans Arabic (Atelier). The other directions use Alexandria + Readex Pro and Almarai + Tajawal. All are self-hosted, SIL OFL. | `--f-display` / `--f-text` tokens |
| Product names and prices | Sidra, Noor, Rimal, Diwan, Marsam, and two accessories: **all demo** | Remove with `wp optimum demo remove` |
| Opening hours, WhatsApp number | Left empty ("please call to confirm hours") | Customizer → Contact & showrooms |
| Delivery cost, VAT registration | Dev store: "Delivery & installation" at 0 SAR, 15% VAT included | WooCommerce → Settings → Shipping / Tax |

## 4. Design references

The two sites named in the brief could not be browsed: **motionsites.ai** and **21st.dev** were both blocked by the environment's network policy. motionsites.ai is also mostly a paid prompt library. What each offers was checked through search results only. The references below were used for specific ideas, and no code or assets were copied.

| # | Reference | Element that inspired us | Used in |
|---|---|---|---|
| 1 | [Rimadesio — Walk-in closet / Cover](https://www.rimadesio.it/en/product/walk-in-closet/) | Slim aluminium frames with glass, and interior lighting shown as the product itself | Noor glass wardrobe renders; the Showroom direction |
| 2 | [Poliform — Senzafine walk-in](https://www.poliform.it/en-us/senzafine-night-system/) | Architectural calm, generous white space, open volumes with LED | Atelier direction: spacing, editorial type, walk-in scene |
| 3 | [Molteni&C — Gliss Master](https://www.molteni.it/en/us/product/gliss-master) | Interior fittings (shirt, shoe and drawer modules) presented as the reason to buy | "See inside" view and hotspots on the product page |
| 4 | [IKEA — PAX planner](https://www.ikea.com/us/en/planners/pax-planner/) | Size → interior → doors → price flow with a live total | Configurator order and the price breakdown |
| 5 | [21st.dev — Image comparison slider (community components)](https://21st.dev/community/components/bundui/image-comparison) | Toggling two states of one image in place | "Doors closed / See inside" toggle and card hover (own implementation, no code copied) |
| 6 | [motionsites.ai](https://motionsites.ai/) (search listings only) | Hero sections with a slow, purposeful reveal | Atelier's "sliding-door" hero reveal; Showroom's scroll-driven door opening |

## 5. The three directions and the recommendation

See `optimum-closets/concepts/index.html`.

- **Atelier (recommended, default):** quiet architectural luxury, with large imagery, an editorial serif and slow reveals.
- **Showroom:** a dark, interactive showroom where the doors open on scroll and hotspots explain the interior.
- **Bayt:** warm and compact, with fast browsing, prominent search, category chips and quick add.

The store uses Atelier's identity with Showroom's interior interactions (on the product page) and Bayt's compact listing patterns. Switch the default in Customizer → Optimum Closets → Design, or preview any direction with `?concept=showroom` / `?concept=bayt`.

## 6. Images: sources and licences

- **Company photos:** none were available (the site and social accounts were blocked from the environment). Nothing on the store claims to be a company photo.
- **AI image generation:** no Codex or other image-generation tool was connected. I checked the tool list and the network, and neither had one. How to connect one, plus ready prompts: `docs/IMAGE-PROMPTS.md`.
- **What was used:** 48 original **procedural 3D renders**, made for this project with `tools/renders/wardrobes.py` (Blender 5.0 Cycles via the `bpy` package, GPL tool; the output images belong to the project owner). No stock photos and no third-party images. Lighting, materials, camera height and colour grading are shared by every shot, and door counts, hinge sides, handles, shelves and perspective (vertical lines kept vertical) all come from one parametric model. Hotspot coordinates are projected from the 3D objects, so the product page points at the real rail, shelf or drawer.
- **Labelling:** every render is flagged `_oc_render` in the media library and shows an "Illustrative render / صورة توضيحية" tag. The *Our Work* page shows an honest empty state for real projects, and puts renders only under "Inspiration — not completed projects".
- **Fonts:** Markazi Text, IBM Plex Sans Arabic, Alexandria, Readex Pro, Almarai and Tajawal, all SIL Open Font License 1.1, via @fontsource, self-hosted (`assets/fonts/LICENSE-OFL.txt`).

## 7. What works (tested)

Tested in Chromium at 375, 768 and 1440 px in Arabic and English (results in `docs/screenshots/`, and the scripts in `tools/qa/` reproduce them):

- Every page listed below renders with no PHP notices, no JS errors, no broken images and no horizontal overflow: home, shop, category, filtered shop, search, product (table-priced and per-metre), cart, checkout, confirmation, account, made to measure, our work, showrooms, contact, 404, and the four concept pages.
- **Order journey** (`tools/qa/flow.js`), all checks passing in Arabic and English:
  - The default price, changing finish (the price and the matching image update), width, layout and extras.
  - The interior view follows finish and layout, and the hotspots show.
  - The server rejects invalid options.
  - AJAX add to cart updates the header count.
  - The cart lists the chosen options, priced by the server.
  - The language switch keeps both the page and the cart.
  - The quantity stepper updates the totals.
  - Checkout offers only the preview method, and the confirmation says "Preview order recorded" and makes no payment claim.
- The request form validates on the server (Saudi mobile format, dimensions, photo type, size and count, consent, honeypot and timing trap). It stores the request privately with the photos and tells the customer exactly what happened.
- Keyboard: the skip link, menus, mega menu (Esc), drawer (focus trap), filter sheet, tabs (arrow keys), the closed/open toggle (arrow keys), hotspots (Enter), and zoom (Esc, +/−). `prefers-reduced-motion` turns off reveals and the hero curtain.

## 8. Integrations still required

1. **Payment gateway** with server-side verification (webhook or callback), for example Moyasar, HyperPay, Tap or PayTabs for mada, Visa/Mastercard and Apple Pay, plus Tabby or Tamara if instalments are wanted. After it is live, turn **off** preview mode.
2. **Real catalogue:** product names, photos, sizes, finishes and **prices** confirmed by the company (enter them in the product configuration). Then remove the demo data.
3. **Brand assets:** logo (SVG), colour codes, and ideally brand fonts.
4. **Business details:** opening hours per showroom, the WhatsApp number, precise map pins, delivery and installation fees or zones, return, warranty and privacy policy pages, and the VAT number (if VAT registered, it must appear on invoices).
5. **Request handling:** an e-mail recipient (Customizer → Requests & integrations) and working SMTP (for example WP Mail SMTP with the company mailbox). Optionally, a CRM or ticketing webhook URL plus `OPTIMUM_WEBHOOK_SECRET` in `wp-config.php`.
6. **Arabic language packs** on the live site (Settings → General → Site language: العربية, then Dashboard → Updates). The theme's fallback covers WooCommerce's customer-facing strings until then.
7. **Multilingual plugin decision:** if the live site already uses Polylang or WPML for `/en/`, keep it (the theme detects it). Otherwise the built-in layer is used and English content is entered in the "English version" boxes.
8. **nginx only:** deny `/wp-content/uploads/oc-requests/` (Apache is covered by the bundled `.htaccess`).
9. **Security review** of the current live site: search engines index spam pages on the domain (for example "Schrader … TPMS … Tire Pressure Sensor", "LiTime … Lithium Battery Charger"). Check for injected content before migrating.

## 9. Moving to the live site without breaking anything

- Work on a staging copy. The theme changes presentation only: product, category and page URLs stay as they are. Existing orders are untouched.
- Do not run the demo importer on the live site. `--store-settings` changes store settings and is for development only.
- Switch Cart, Checkout and My Account to the classic shortcodes. The theme styles the classic templates, and the block-based cart and checkout ignore theme templates.
- Assign the page templates (Made-to-measure request, Our work, Showrooms, Contact) to the existing pages, or create them.
