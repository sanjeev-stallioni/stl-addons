# Stl Addons — Widget Development Conventions

Read this before adding a new widget. The quick-start ("Adding a new widget") lives in [README.md](README.md); this file covers the deeper patterns that keep the plugin consistent.

---

## Widget class skeleton

```php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( '\Elementor\Widget_Base' ) ) return;

class STL_Widget_Foo extends \Elementor\Widget_Base {

    public function get_name()          { return 'stl_foo'; }
    public function get_title()         { return __( 'Foo', 'stl-addons' ); }
    public function get_icon()          { return 'eicon-…'; }
    public function get_categories()    { return array( 'stl-addons', 'general' ); }
    public function get_keywords()      { return array( 'foo', 'stl' ); }
    public function get_style_depends() { return array( 'stl-foo' ); }

    // DOM optimization — see "Optimized DOM" below.
    public function has_widget_inner_wrapper(): bool { return false; }
    public function get_html_wrapper_class() {
        return parent::get_html_wrapper_class() . ' stl-widget stl-foo';
    }

    protected function register_controls() {
        $this->controls_content();
        $this->controls_card_style();
        // …one private method per controls section.
    }

    protected function render() {
        $s = $this->get_settings_for_display();
        // emit children directly — no outer wrapper element here.
    }
}
```

---

## Optimized DOM

All new widgets must opt out of the legacy double-wrapper ([Elementor docs](https://developers.elementor.com/docs/widgets/widget-inner-wrapper/)).

- `has_widget_inner_wrapper()` returns `false` — drops Elementor's `.elementor-widget-container`.
- `get_html_wrapper_class()` appends `stl-widget` (for the shared box-sizing reset) plus the widget's own scope class to the outer `.elementor-widget` element.
- `render()` does **not** emit its own outer wrapper — start with the first real child. Otherwise the widget gains an unnecessary extra div.
- Controls that style the wrapper itself use the selector `'{{WRAPPER}}'` with no descendant (e.g. `'{{WRAPPER}}' => 'background: {{VALUE}};'`). Never reference `.elementor-widget-container` in any selector — it no longer exists.

---

## Controls

**Organization**

- Split `register_controls()` into one private `controls_*()` method per section so the class stays scannable.
- Reuse `std_slider_args()` (see `founder-section/widget.php`) for any slider that uses the standard unit set (`px / em / rem / % / vh / vw / custom`) — keeps ranges consistent across widgets.
- Heading levels that appear in markup should be backed by a tag select (h1–h6) with a whitelist-validated render.

**Defaults — content yes, style no**

Set `'default'` only on **content** controls (text fields, image source, repeater seed rows, switcher on/off where the widget is unusable without it).

Do **not** set `'default'` on **style-tab** controls — sliders for spacing/size, colors, typography, padding, max-width, etc. Leaving them blank means:

- Elementor's theme styles and global typography / colors apply
- The site stays visually coherent across widgets
- Users editing the widget see "Default" instead of an opinionated value they have to undo

Anti-pattern (don't do this):

```php
$this->add_responsive_control( 'selector_max_width', array(
    'type'       => Controls_Manager::SLIDER,
    'range'      => array( 'px' => array( 'min' => 320, 'max' => 1400 ) ),
    'default'    => array( 'size' => 960, 'unit' => 'px' ), // ← remove this
    'selectors'  => array( '{{WRAPPER}} .stl-ft-selector' => 'max-width: {{SIZE}}{{UNIT}};' ),
) );
```

If a sensible visual baseline is genuinely needed without any control input, put it in the widget's `style.css` (e.g. `.stl-ft-selector { max-width: 960px; }`) — the user can still override it through the control because Elementor injects the control CSS with higher specificity.

---

## Render hygiene

- `$s = $this->get_settings_for_display();` then `$x = $s['x'] ?? '';` for every field — never index a setting without a default.
- Wrap optional fields in `<?php if ( $x ) : ?>` so an empty setting doesn't produce an empty tag.
- Escape on output: `esc_html`, `esc_attr`, `esc_url`, and `wp_kses_post( wpautop( $wysiwyg ) )` for WYSIWYG fields.
- Images: prefer `\Elementor\Group_Control_Image_Size::get_attachment_image_html( $s, 'image_size', 'photo' )`. Fallback `<img>` tags carry `loading="lazy"`.
- Repeater images: pass `array_merge( $s, array( 'photo' => $item_photo ) )` to the image-size helper so the per-item photo is picked up (see `team-grid/widget.php`).
- CSS variables that vary per instance (accent color, offsets) belong on the widget wrapper via a `selectors` control, not in `:root` — multiple instances on a page must not bleed into each other.

---

## Styles

- One `style.css` per widget under `assets/`. Auto-registered as handle `stl-<slug>`; declare it in `get_style_depends()` so it loads only on pages that use the widget.
- Scope every class with a unique prefix (`.stl-foo-*`) — no generic names like `.card` or `.row`.
- Don't put a box-sizing reset in your widget CSS; the `.stl-widget` class on the wrapper pulls it from `assets/common.css`.
- Visual baselines for things you intentionally left out of Elementor controls belong here (see the defaults rule above).

---

## i18n

Text domain is `'stl-addons'` for every `__()` / `esc_html__()` call. No exceptions.
