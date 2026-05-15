# Stl Addons for Elementor

A growing collection of Elementor widgets by [Stallioni](https://stallioni.com) — drop-in, fully editable, brand-friendly.

---

## Widgets shipped

| Widget | Description |
|---|---|
| **Team Grid** | Responsive grid of team members with photo, name, role, bio, and tag pills. Columns adjust per device. Hover lift + image zoom. |
| **Founder Section** | Editorial cover layout — photo on the left, name / role / bio / quote / tags on the right, over a dark card with accent details. |

Both widgets live in the **Stl Addons** category of the Elementor panel and offer full control over typography, colors, spacing, and dividers via the Style tab.

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
4. Edit any page with Elementor → search the panel for **Team Grid** or **Founder Section** → drag in.

---

## Usage

Once activated, both widgets appear in Elementor's widget panel under the **Stl Addons** category.

- **Content tab** holds the data: team members (repeater), photo, name, role, bio, tags, quote.
- **Style tab** is split into focused sub-sections (e.g. *Card*, *Body*, *Badge*, *Vol Mark*, *Meta Row*, *Name*, *Role*, *Bio*, *Quote*, *Tags*). Each has typography, color, spacing, and (where relevant) divider controls.
- Fonts come from the active theme or Elementor's global fonts — the plugin ships **no Google Fonts** by default.

---

## Admin dashboard

The plugin adds a top-level **Stl Addons** menu in WP Admin with three tabs:

- **Dashboard** — welcome, widget summary, quick links, and the Stallioni brand card.
- **Widgets** — toggle each widget on or off. Disabled widgets are hidden from the Elementor panel sitewide (front-end and editor). Toggles save instantly via AJAX.
- **Get Help** — a getting-started checklist and a developer note for adding new widgets.

Storage:

- Disabled widgets are persisted in the WP option `stl_disabled_widgets` (array of slugs).
- The widget registration loop in `STL_Addons_Plugin::register_widgets()` skips any slug present in that list.
- All toggle requests run through `wp_ajax_stl_toggle_widget` with a nonce + `manage_options` capability check.

Theming:

- The dashboard uses the Stallioni palette — orange `#f26522` (primary), navy `#1a233d` (text & dark surfaces), and neutral grays.
- All admin styles live in `assets/admin.css` and load **only** on the plugin's admin pages.

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
    ├── plugin.php                ← STL_Addons_Plugin: auto-loads widgets, registers category & assets
    ├── admin/
    │   └── dashboard.php         ← STL_Addons_Admin: menu, tabs, AJAX
    └── widgets/
        ├── team-grid/
        │   ├── widget.php        ← class STL_Widget_Team_Grid
        │   └── assets/style.css  ← auto-registered as handle `stl-team-grid`
        └── founder-section/
            ├── widget.php        ← class STL_Widget_Founder_Section
            └── assets/style.css  ← auto-registered as handle `stl-founder-section`
```

### Adding a new widget

The plugin auto-loads anything under `includes/widgets/`. No edits to `plugin.php` ever needed:

1. Create `includes/widgets/<slug>/widget.php` (slug must be `kebab-case`).
2. Define `STL_Widget_<Studly_Snake>` extending `\Elementor\Widget_Base`.
3. Set `get_name()` → `stl_<snake>`, `get_categories()` → include `'stl-addons'`, `get_style_depends()` → `array( 'stl-<slug>' )` if you ship CSS.
4. Optionally drop `includes/widgets/<slug>/assets/style.css`.
5. Add `stl-widget` to the outermost element in `render()` so the shared box-sizing reset applies.

### Conventions

| Item | Format | Example |
|---|---|---|
| Folder slug | kebab-case | `team-grid` |
| PHP class | `STL_Widget_<Studly_Snake>` | `STL_Widget_Team_Grid` |
| Elementor widget name | `stl_<snake>` | `stl_team_grid` |
| Asset handle | `stl-<slug>` | `stl-team-grid` |
| CSS class prefix | `.stl-<short>-*` | `.stl-team-*` |
| Text domain | `'stl-addons'` | `__( 'Label', 'stl-addons' )` |

PHP constants available throughout: `STL_VERSION`, `STL_FILE`, `STL_DIR`, `STL_URL`.

---

## Security & best practices

- All output is escaped (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` for HTML-bearing fields)
- ABSPATH guards on every PHP file
- Widget registration verifies `is_subclass_of( $class, '\Elementor\Widget_Base' )` before instantiating
- Folder-slug allowlist (`/^[a-z0-9-]+$/`) on the auto-discovery loop
- Admin notices gated on `current_user_can( 'activate_plugins' )`
- PHP / WP / Elementor version checks at boot with distinct admin notices
- No raw superglobals, no direct DB access, no remote font loading

---

## License

GPL-2.0-or-later. See the header in `stl-addons.php`.
