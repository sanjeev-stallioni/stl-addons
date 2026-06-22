# Stl Addons for Elementor

A growing collection of Elementor widgets by [Stallioni](https://stallioni.com) — drop-in, fully editable, brand-friendly.

---

## Widgets shipped

| Widget | Description |
|---|---|
| **Team Grid** | Responsive grid of team members with photo, name, role, bio, and tag pills. Columns adjust per device. Hover lift + image zoom. |
| **Founder Section** | Editorial cover layout — photo on the left, name / role / bio / quote / tags on the right, over a dark card with accent details. |
| **Button** | Two animated button styles: a layered "push" effect (Style 1) and a bordered "frame draw" effect (Style 2). Renders as `<a>` or `<button>` based on whether a link is supplied. |
| **Timeline** | Vertical alternating timeline with year, title, description, and circular media per milestone. Accent color is scoped per-widget so multiple timelines on a page can have different colors. |
| **Form Tabs** | Tabbed selector that swaps between forms rendered by another plugin (Contact Form 7, WPForms, Gravity Forms, etc.) via shortcode. Each tab is a card with icon, title, meta line, and description; clicking reveals its matching form panel. Per-tab On-Click JavaScript hook for custom loaders. |
| **Social Links** | Floating social-icon block in two styles: a sliding edge **rail** (left or right) with hover slide-out, or a **radial FAB** at a screen corner that fans icons out in a quarter arc on click. Accessible (aria-expanded, outside-click + Escape close). |
| **Marquee** | Seamless horizontally-scrolling keyword strip (ticker). Pure-CSS animation (no JS), perfectly looped via a duplicated aria-hidden copy. Per-item optional links, configurable direction, speed, separator, pause-on-hover, and an opt-in full-viewport-width breakout. Honours `prefers-reduced-motion`. |
| **Review Grid** | Responsive grid of customer review cards — star rating, review body (`<blockquote>`), and an author line with an avatar (image or auto-generated initial on a gradient circle). Accessible star rating (`role="img"` + aria-label), semantic `<cite>` author, and an opt-in schema.org/Review microdata switch for richer SEO. Star color and avatar gradient are scoped per-widget. No JS. |
| **Post Grid** | Live blog-card grid pulled from `WP_Query` — featured image, date (`<time>`), title, excerpt, and "read article" link. Full query controls (post type, categories, tags with AND/OR match), friendly ordering presets (Newest/Oldest First, A→Z, Most Commented, Random…), post count, offset, exclude-current and ignore-sticky. Per-breakpoint column control, selectable title heading level, and an optional accessible client-side **category filter bar** (all posts stay in the DOM for SEO). Opt-in schema.org/BlogPosting microdata. |
| **Post Archive** | Paginated archive grid from `WP_Query`, rebuilding the "pcard" blog-card design — dark gradient image placeholder (with a decorative fallback when a post has no featured image), category **tag badge**, kicker, title, excerpt, and a footer with `date · reading-time` meta + a Read link. Full query controls (post type, categories, tags with AND/OR), ordering presets, per-page count, and selectable heading level. **Server-side numbered pagination** via a crawlable, editor-configurable query-string key (default `?pa_page=`). A **Related-to-current-post** query mode auto-matches the viewed post's terms (category / tag / both / all taxonomies) and excludes it — for a single-post related section. Reading time is estimated at ~200 wpm. Opt-in schema.org/BlogPosting microdata. |
| **Featured Post** | Large editorial "featured" card(s) from `WP_Query` — a split layout with a media panel (featured image or a decorative gradient placeholder + **"Featured" tag**) beside a body holding a kicker (post category or custom text), title, excerpt, a `date · reading-time` meta line, and a "Read the guide" link. Query controls (post type, categories, tags with AND/OR), ordering presets, post count (cards stack), offset, exclude-current. Media side left/right, selectable heading level, reading time ~200 wpm. Whole card is one crawlable `<a>`. Opt-in schema.org/BlogPosting microdata. |

### WooCommerce widgets

| Widget | Description |
|---|---|
| **Archive Products** | Responsive WooCommerce product grid reproducing a shop card — image + sale badge, title, now/was price, shipping note, feature list, and an **AJAX add-to-cart** + view button. Can **follow the current archive query** (drop it in a Theme Builder / shop archive) or run its **own query** on any Elementor page, with optional pagination. Selectable heading level and full style controls. Self-contained, scoped `.stl-ap-*` (depends on the `wc-add-to-cart` script). |
| **Product Categories** | Lists WooCommerce product categories as a sidebar-style **panel of rows with product-count badges**, or as a **thumbnail grid**. The category matching the current archive is highlighted automatically. Self-contained, scoped `.stl-pc-*`. |
| **Product Price** | Single-product price block — current price, strikethrough "was" price, and a **"You save \$X" badge** when on sale. Reads the current product, so drop it on a single-product Theme Builder template. Scoped `.stl-pp-*`. |
| **Product Buy (Add to Cart)** | Single-product **buy row** — a quantity stepper (− input +) plus Add to cart. Renders WooCommerce's **native add-to-cart form** for simple, purchasable, in-stock products (so quantity posts correctly), falling back to a product-page link for other product types. Scoped `.stl-buy-*`. |
| **Product Tabs** | Tabbed single-product section. Each tab (a repeater) pulls from **Product Description**, **Specifications** (built from the product's visible attributes as a spec table), **Reviews** (live WooCommerce reviews + count), or **Custom** — a WYSIWYG plus an optional shortcode with its own styled title (e.g. a per-product FAQ). Dark design with per-part style controls (nav, content, spec table, shortcode title, section). Scoped `.stl-pt-*`. |

All widgets live in the **Stl Addons** category of the Elementor panel and offer full control over typography, colors, spacing, and dividers via the Style tab.

---

## Requirements

- WordPress **5.8+**
- PHP **7.4+**
- Elementor **3.5.0+** (free version is sufficient)

---

## Installation

1. Copy the `stl-addons` folder into `wp-content/plugins/`.
2. In WP Admin → **Plugins**, activate **Stl Addons for Elementor**.
3. Make sure Elementor is also activated (the plugin won't load otherwise — you'll see an admin notice).
4. Edit any page with Elementor → search the panel for any **Stl Addons** widget → drag in.

---

## Usage

Once activated, all widgets appear in Elementor's widget panel under the **Stl Addons** category.

- **Content tab** holds the data: text, repeater items, photos, links.
- **Style tab** is split into focused sub-sections (e.g. *Card*, *Body*, *Badge*, *Name*, *Role*, *Bio*, *Quote*, *Tags*). Each has typography, color, spacing, and (where relevant) divider controls.
- **Heading HTML tag** is configurable on most content widgets (Team Grid, Timeline, Founder Section, Post Grid, Post Archive, Featured Post, and the WooCommerce widgets) — pick the level (h2–h6, or h1–h6 on Featured Post) that matches your page's outline for better SEO.
- Fonts come from the active theme or Elementor's global fonts — the plugin ships **no Google Fonts** by default.

---

## Admin dashboard

The plugin adds a top-level **Stl Addons** menu in WP Admin with three independent pages:

- **Dashboard** — welcome, widget summary, quick links, and the Stallioni brand card.
- **Widgets** — toggle each widget on or off. Disabled widgets are hidden from the Elementor panel sitewide (front-end and editor). Toggles save instantly via AJAX.
- **Get Help** — a getting-started checklist, a "How styles & scripts load" explainer (inline-CSS behavior + the opt-out filter), and a developer note for adding new widgets.

Each page is a separate WP admin route (`admin.php?page=stl-addons`, `…-widgets`, `…-help`) — no shared tab dispatcher, so the page you're on only loads its own callback.

Storage:

- Disabled widgets are persisted in the WP option `stl_disabled_widgets` (array of slugs).
- The widget registration loop in `STL_Addons_Plugin::register_widgets()` skips any slug present in that list.
- All toggle requests run through `wp_ajax_stl_toggle_widget` with a nonce + `manage_options` capability check.

Theming & icons:

- The dashboard uses the Stallioni palette — orange `#f26522` (primary), navy `#1a233d` (text & dark surfaces), and neutral grays.
- Each widget can ship an `icon.svg` next to its `widget.php` — the dashboard inlines it (with `wp_kses` sanitization) and falls back to a two-letter badge if absent.
- All admin styles live in `assets/admin.css` and load **only** on the plugin's admin pages.

---

## Performance

- **Per-widget CSS, only when used.** Each widget declares `get_style_depends()`, so Elementor enqueues a widget's stylesheet only on pages where that widget actually renders.
- **Inline CSS — no render-blocking requests.** A `style_loader_tag` filter rewrites every `stl-*` `<link>` tag into an inline `<style>` block at print time. Each widget CSS is 2–4 KB, so the inline cost is bounded and the LCP win from skipping the HTTP roundtrip is real.

  This is why "View Source" on a published page shows the widget styles printed in the page (e.g. `<style id="stl-post-grid-inline-css">…</style>`) instead of an external `<link>` — that is the intended, optimized output, and it applies to every plugin widget on the page. Inlining is **skipped inside the Elementor editor** (the editor reloads CSS on edits and needs the registered handle to stay a real stylesheet), so to see the difference, view-source the published/preview page, not the editor.

  **Don't want inlining?** Serve the widget CSS as normal external files instead. Globally:

  ```php
  // Turn off inlining for all stl-addons widgets — emits <link> tags as usual.
  add_filter( 'stl_addons_inline_widget_css', '__return_false' );
  ```

  Or per handle, to keep inlining for everything except one widget (the filter receives the registered handle as its 2nd argument):

  ```php
  add_filter( 'stl_addons_inline_widget_css', function ( $inline, $handle ) {
      return 'stl-post-grid' === $handle ? false : $inline; // external file for Post Grid only
  }, 10, 2 );
  ```

- **Memoized widget discovery.** `STL_Addons_Plugin::discover_widgets()` runs `glob()` once per request and caches the result in a static — multiple hook callbacks (`register_assets`, `register_widgets`, the inline-CSS filter) share the same list.
- **Image attributes.** Attachment images use Elementor's `Group_Control_Image_Size::get_attachment_image_html()` (responsive `srcset` + intrinsic `width`/`height`). Fallback `<img>` tags carry `loading="lazy"` + `decoding="async"`.
- **CSS variables scoped per widget.** The Timeline accent color (`--stl-timeline-accent`) and the Button's offset (`--stl-btn-offset-*`) live on the widget wrapper, not on `:root`, so multiple instances coexist without color or spacing leaks.
- **Per-widget JS, only when used.** Widgets that need behavior — Form Tabs (card switching), Social Links (radial trigger), Post Grid (category filter), Product Tabs (tabs + keyboard nav), Product Buy (quantity stepper), and Archive Products (AJAX add-to-cart) — declare `get_script_depends()` so their tiny `script.js` only loads on pages that render the widget, and re-init under the Elementor editor/AJAX lifecycle. The remaining widgets (Button, Timeline, Team Grid, Founder Section, Marquee, Review Grid, Post Archive, Featured Post, Product Categories, Product Price) ship no JS at all.

---

## Plugin structure

```
stl-addons/
├── stl-addons.php                ← entry: defines STL_* constants, boots STL_Addons_Plugin
├── README.md
├── assets/
│   ├── common.css                ← reset shared by every widget (auto-enqueued)
│   ├── admin.css                 ← Stallioni-themed admin dashboard styles
│   └── admin.js                  ← AJAX handler for widget toggles
└── includes/
    ├── plugin.php                ← STL_Addons_Plugin: auto-loads widgets, registers category & assets, inline-CSS filter
    ├── admin/
    │   └── dashboard.php         ← STL_Addons_Admin: 3 admin pages, AJAX toggle, SVG icon rendering
    └── widgets/
        ├── button/
        │   ├── widget.php        ← class STL_Widget_Button
        │   ├── icon.svg          ← rendered inline in the admin dashboard
        │   └── assets/style.css  ← auto-registered as handle `stl-button`
        ├── timeline/
        │   ├── widget.php        ← class STL_Widget_Timeline
        │   ├── icon.svg
        │   └── assets/style.css
        ├── team-grid/
        │   ├── widget.php        ← class STL_Widget_Team_Grid
        │   ├── icon.svg
        │   └── assets/style.css
        ├── founder-section/
        │   ├── widget.php        ← class STL_Widget_Founder_Section
        │   ├── icon.svg
        │   └── assets/style.css
        ├── form-tabs/
        │   ├── widget.php        ← class STL_Widget_Form_Tabs
        │   ├── icon.svg
        │   └── assets/
        │       ├── style.css
        │       └── script.js     ← tab switching, also auto-registered as `stl-form-tabs`
        ├── social-links/
        │   ├── widget.php        ← class STL_Widget_Social_Links
        │   ├── icon.svg
        │   └── assets/
        │       ├── style.css
        │       └── script.js     ← radial trigger open/close
        ├── marquee/
        │   ├── widget.php        ← class STL_Widget_Marquee
        │   ├── icon.svg
        │   └── assets/style.css  ← seamless CSS ticker (no JS)
        ├── review-grid/
        │   ├── widget.php        ← class STL_Widget_Review_Grid
        │   ├── icon.svg
        │   └── assets/style.css
        ├── post-grid/
        │   ├── widget.php        ← class STL_Widget_Post_Grid
        │   ├── icon.svg
        │   └── assets/
        │       ├── style.css
        │       └── script.js
        ├── post-archive/
        │   ├── widget.php        ← class STL_Widget_Post_Archive
        │   ├── icon.svg
        │   └── assets/style.css
        ├── featured-post/
        │   ├── widget.php        ← class STL_Widget_Featured_Post
        │   ├── icon.svg
        │   └── assets/style.css
        ├── archive-products/     ← WooCommerce product grid (AJAX add-to-cart)
        │   ├── widget.php        ← class STL_Widget_Archive_Products
        │   ├── icon.svg
        │   └── assets/
        │       ├── style.css
        │       └── script.js
        ├── product-categories/
        │   ├── widget.php        ← class STL_Widget_Product_Categories
        │   ├── icon.svg
        │   └── assets/style.css
        ├── product-price/
        │   ├── widget.php        ← class STL_Widget_Product_Price
        │   ├── icon.svg
        │   └── assets/style.css
        ├── product-buy/
        │   ├── widget.php        ← class STL_Widget_Product_Buy
        │   ├── icon.svg
        │   └── assets/
        │       ├── style.css
        │       └── script.js
        └── product-tabs/
            ├── widget.php        ← class STL_Widget_Product_Tabs
            ├── icon.svg
            └── assets/
                ├── style.css
                └── script.js
```

### Adding a new widget

The plugin auto-loads anything under `includes/widgets/`. No edits to `plugin.php` ever needed:

1. Create `includes/widgets/<slug>/widget.php` (slug must be `kebab-case`).
2. Define `STL_Widget_<Studly_Snake>` extending `\Elementor\Widget_Base`.
3. Set `get_name()` → `stl_<snake>`, `get_categories()` → include `'stl-addons'`, `get_style_depends()` → `array( 'stl-<slug>' )` if you ship CSS.
4. Optionally drop `includes/widgets/<slug>/assets/style.css` (auto-registered as `stl-<slug>`).
5. Optionally drop `includes/widgets/<slug>/assets/script.js` (auto-registered as `stl-<slug>`) and declare `get_script_depends() → array( 'stl-<slug>' )` so it loads only when the widget is on the page.
6. Optionally drop `includes/widgets/<slug>/icon.svg` — the admin dashboard will inline it with sanitization.
7. Add `stl-widget` to the outermost element in `render()` (or via `get_html_wrapper_class()`) so the shared box-sizing reset applies.

For deeper patterns — optimized DOM (`has_widget_inner_wrapper` / `get_html_wrapper_class`), controls organization, the "no defaults on style controls" rule, render hygiene, and i18n — see [CONVENTIONS.md](CONVENTIONS.md).

### Conventions

| Item | Format | Example |
|---|---|---|
| Folder slug | kebab-case | `team-grid` |
| PHP class | `STL_Widget_<Studly_Snake>` | `STL_Widget_Team_Grid` |
| Elementor widget name | `stl_<snake>` | `stl_team_grid` |
| Asset handle | `stl-<slug>` | `stl-team-grid` |
| CSS class prefix | `.stl-<short>-*` | `.stl-team-*` |
| Dashboard icon | `<slug>/icon.svg` | `team-grid/icon.svg` |
| Text domain | `'stl-addons'` | `__( 'Label', 'stl-addons' )` |

PHP constants available throughout: `STL_VERSION`, `STL_FILE`, `STL_DIR`, `STL_URL`.

---

## Security & best practices

- All output is escaped (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` for HTML-bearing fields).
- Heading-tag selects (h2–h6, or h1–h6 on Featured Post) are whitelist-validated on render — a tampered setting can't inject arbitrary markup.
- Widget `icon.svg` files are sanitized through `wp_kses()` with an explicit element + attribute allowlist (no `<script>`, no `on*` handlers).
- ABSPATH guards on every PHP file.
- Widget registration verifies `is_subclass_of( $class, '\Elementor\Widget_Base' )` before instantiating.
- Folder-slug allowlist (`/^[a-z0-9-]+$/`) on the auto-discovery loop.
- AJAX endpoint requires `manage_options` + `check_ajax_referer` + slug validation against the discovered list.
- `$_GET` / `$_POST` reads use `wp_unslash()` + `sanitize_key()`.
- Admin notices gated on `current_user_can( 'activate_plugins' )`.
- PHP / WP / Elementor version checks at boot with distinct admin notices.
- No raw superglobals, no direct DB access, no remote font loading.

---

## Changelog

### 1.1.0

**Added**

- **Post Grid** — live blog-card grid from `WP_Query` with query controls, ordering presets, per-breakpoint columns, and an optional client-side category filter bar.
- **Post Archive** — paginated archive grid with server-side numbered pagination (crawlable, editor-configurable query-string key), a Related-to-current-post mode, and reading-time meta.
- **Featured Post** — large split "featured" card(s) from `WP_Query` with a media panel, kicker, `date · reading-time` meta, and selectable media side.
- **WooCommerce widgets** — Archive Products (AJAX add-to-cart), Product Categories, Product Price, Product Buy (quantity stepper + native add-to-cart), and Product Tabs (Description / Specifications / Reviews / Custom).
- **Get Help page** — a "How styles & scripts load" explainer documenting inline-CSS behavior and the `stl_addons_inline_widget_css` opt-out filter (global or per-handle).

**Changed**

- **Review Cards → Review Grid** — renamed the widget (folder, class, name, handle, title). ⚠️ The internal widget name changed (`stl_review_cards` → `stl_review_grid`), so any **Review Cards instance already placed on a page will show "widget not found" and must be re-added** as Review Grid.
- **Post Archive pagination** — links use a clean, configurable key (default `?pa_page=`) and no longer append a `#…` scroll fragment.
- Bumped headers: *Tested up to* 6.5 → 7.0, *Elementor tested up to* 3.21 → 4.0.

**Fixed**

- Product Tabs: restore the global `$post` after the Reviews tab; added full ARIA tablist wiring + arrow-key navigation.
- Product Categories: precise current-category highlight (`is_tax('product_cat')` + integer term-id compare).
- Product Price: "You save" badge now only shows when actually on sale; removed an empty-price fallback span.
- Product Buy: quantity stepper re-initializes under the Elementor editor / AJAX lifecycle.
- Post Archive: single-escape the meta separator; Archive Products: removed dead query code.

### 1.0.0

- Initial release: Team Grid, Founder Section, Button, Timeline, Form Tabs, Social Links, Marquee, Review Cards. Auto-loading widget architecture, admin dashboard with per-widget toggles, and inline-CSS performance pipeline.

---

## License

GPL-2.0-or-later. See the header in `stl-addons.php`.
