<?php
/**
 * Product Tabs Elementor Widget.
 *
 * The dark tabbed section from the Hyun Engines single-product design. Tabs are
 * a repeater; each tab pulls from one of:
 *   - description   → the product description (dynamic)
 *   - specifications → the product's visible attributes, as a spec table (dynamic)
 *   - reviews        → WooCommerce reviews + live count (dynamic)
 *   - custom         → static WYSIWYG content AND/OR a shortcode (e.g. a per-product
 *                      FAQ plugin shortcode), for Shipping & Returns / FAQ.
 *
 * Self-contained: markup scoped under .stl-pt-* and styled by this widget's own
 * style.css. Drop it on a single-product Theme Builder template.
 *
 * @package StlAddons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;

class STL_Widget_Product_Tabs extends Widget_Base {

	/**
	 * Hide this widget from the Elementor panel when WooCommerce is inactive.
	 */
	public static function is_available() {
		return class_exists( 'WooCommerce' );
	}

	public function get_name()           { return 'stl_product_tabs'; }
	public function get_title()          { return __( 'Product Tabs', 'stl-addons' ); }
	public function get_icon()           { return 'eicon-product-tabs'; }
	public function get_categories()     { return array( 'stl-addons', 'general' ); }
	public function get_keywords()       { return array( 'woocommerce', 'product', 'tabs', 'description', 'specifications', 'faq', 'reviews', 'stl' ); }
	public function get_style_depends()  { return array( 'stl-product-tabs' ); }
	public function get_script_depends() { return array( 'stl-product-tabs' ); }

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	public function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' stl-widget stl-pt-section';
	}

	private function get_product() {
		global $product;
		if ( $product instanceof WC_Product ) {
			return $product;
		}
		$maybe = wc_get_product( get_the_ID() );
		return $maybe instanceof WC_Product ? $maybe : null;
	}

	private function is_editor() {
		return class_exists( '\Elementor\Plugin' )
			&& isset( \Elementor\Plugin::$instance->editor )
			&& \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	protected function register_controls() {
		$this->controls_tabs();
		$this->controls_nav_style();
		$this->controls_pane_style();
		$this->controls_spec_style();
		$this->controls_sctitle_style();
		$this->controls_section_style();
	}

	/* ---------------------------------------------------------------- Content */

	private function controls_tabs() {
		$this->start_controls_section( 'sec_tabs', array(
			'label' => __( 'Tabs', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$repeater = new Repeater();

		$repeater->add_control( 'title', array(
			'label'   => __( 'Tab Title', 'stl-addons' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Tab', 'stl-addons' ),
		) );

		$repeater->add_control( 'source', array(
			'label'   => __( 'Content Source', 'stl-addons' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'custom',
			'options' => array(
				'description'    => __( 'Product Description (dynamic)', 'stl-addons' ),
				'specifications' => __( 'Specifications — Attributes (dynamic)', 'stl-addons' ),
				'reviews'        => __( 'Reviews (dynamic)', 'stl-addons' ),
				'custom'         => __( 'Custom — static + shortcode', 'stl-addons' ),
			),
		) );

		$repeater->add_control( 'content', array(
			'label'       => __( 'Static Content', 'stl-addons' ),
			'type'        => Controls_Manager::WYSIWYG,
			'default'     => '',
			'condition'   => array( 'source' => 'custom' ),
		) );

		$repeater->add_control( 'shortcode_title', array(
			'label'       => __( 'Shortcode Title', 'stl-addons' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'placeholder' => __( 'e.g. Frequently Asked Questions', 'stl-addons' ),
			'description' => __( 'Optional heading shown above the shortcode output.', 'stl-addons' ),
			'condition'   => array( 'source' => 'custom' ),
		) );

		$repeater->add_control( 'shortcode', array(
			'label'       => __( 'Shortcode', 'stl-addons' ),
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 2,
			'placeholder' => '[your_faq_shortcode]',
			'description' => __( 'Rendered below the static content — e.g. your per-product FAQ plugin shortcode.', 'stl-addons' ),
			'condition'   => array( 'source' => 'custom' ),
		) );

		$this->add_control( 'tabs', array(
			'label'       => __( 'Tabs', 'stl-addons' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'title_field' => '{{{ title }}}',
			'default'     => array(
				array( 'title' => __( 'Description', 'stl-addons' ),        'source' => 'description' ),
				array( 'title' => __( 'Specifications', 'stl-addons' ),     'source' => 'specifications' ),
				array( 'title' => __( 'Reviews', 'stl-addons' ),            'source' => 'reviews' ),
				array(
					'title'   => __( 'Shipping & Returns', 'stl-addons' ),
					'source'  => 'custom',
					'content' => '<h2>Shipping &amp; Returns</h2><ul><li>Free shipping Australia-wide on all engine orders</li><li>Dispatch within 2–3 business days of order confirmation</li><li>Changeover core must be returned within 14 days of delivery</li><li>Fitting available at our Dandenong workshop</li></ul>',
				),
			),
		) );

		$this->add_control( 'auto_review_count', array(
			'label'        => __( 'Append review count to Reviews tab', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'description'  => __( 'Shows e.g. "Reviews (3)" automatically.', 'stl-addons' ),
		) );

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ Style */

	private function controls_nav_style() {
		$this->start_controls_section( 'sec_nav', array(
			'label' => __( 'Tab Nav', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'nav_color', array(
			'label'     => __( 'Tab Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pt-btn' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'nav_active_color', array(
			'label'     => __( 'Active Tab Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pt-btn.is-on' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'nav_active_border', array(
			'label'     => __( 'Active Underline Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pt-btn.is-on' => 'border-bottom-color: {{VALUE}};' ),
		) );

		$this->add_control( 'nav_border', array(
			'label'     => __( 'Divider Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pt-nav' => 'border-bottom-color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'nav_typo',
			'selector' => '{{WRAPPER}} .stl-pt-btn',
		) );

		$this->end_controls_section();
	}

	private function controls_pane_style() {
		$this->start_controls_section( 'sec_pane', array(
			'label' => __( 'Content', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'heading_heading', array(
			'label'     => __( 'Headings', 'stl-addons' ),
			'type'      => Controls_Manager::HEADING,
		) );

		$this->add_control( 'heading_color', array(
			'label'     => __( 'Heading Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pt-pane h2, {{WRAPPER}} .stl-pt-pane h3' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'heading_typo',
			'label'    => __( 'Heading Typography', 'stl-addons' ),
			'selector' => '{{WRAPPER}} .stl-pt-pane h2, {{WRAPPER}} .stl-pt-pane h3',
		) );

		$this->add_control( 'text_heading', array(
			'label'     => __( 'Text', 'stl-addons' ),
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		) );

		$this->add_control( 'text_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-pt-pane'    => 'color: {{VALUE}};',
				'{{WRAPPER}} .stl-pt-pane p'  => 'color: {{VALUE}};',
				'{{WRAPPER}} .stl-pt-pane li' => 'color: {{VALUE}};',
			),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'pane_typo',
			'label'    => __( 'Text Typography', 'stl-addons' ),
			'selector' => '{{WRAPPER}} .stl-pt-pane p, {{WRAPPER}} .stl-pt-pane li',
		) );

		$this->add_control( 'misc_heading', array(
			'label'     => __( 'Links & Markers', 'stl-addons' ),
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		) );

		$this->add_control( 'link_color', array(
			'label'     => __( 'Link Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pt-pane a' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'marker_color', array(
			'label'     => __( 'List Marker Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pt-pane ul li::before' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_spec_style() {
		$this->start_controls_section( 'sec_spec', array(
			'label' => __( 'Specifications Table', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'spec_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pt-spec' => 'background: {{VALUE}};' ),
		) );

		$this->add_control( 'spec_border', array(
			'label'     => __( 'Border / Divider Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-pt-spec'    => 'border-color: {{VALUE}};',
				'{{WRAPPER}} .stl-pt-spec td' => 'border-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'spec_label_color', array(
			'label'     => __( 'Label Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pt-spec td:first-child' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'spec_value_color', array(
			'label'     => __( 'Value Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pt-spec td:last-child' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_sctitle_style() {
		$this->start_controls_section( 'sec_sctitle', array(
			'label' => __( 'Shortcode Title', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'sctitle_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pt-sc-title' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'sctitle_typo',
			'selector' => '{{WRAPPER}} .stl-pt-sc-title',
		) );

		$this->add_responsive_control( 'sctitle_margin', array(
			'label'      => __( 'Margin', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%' ),
			'selectors'  => array( '{{WRAPPER}} .stl-pt-sc-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_section_style() {
		$this->start_controls_section( 'sec_box', array(
			'label' => __( 'Section', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'section_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}}' => 'background: {{VALUE}};' ),
		) );

		$this->add_control( 'accent_color', array(
			'label'     => __( 'Accent Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}}.stl-pt-section, {{WRAPPER}} .stl-pt-section' => '--stl-pt-accent: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	/* ----------------------------------------------------------------- Render */

	/** Build the visible-attributes spec table. */
	private function render_specs( $product ) {
		if ( ! $product ) {
			return '<p class="stl-pt-empty">' . esc_html__( 'Specifications appear here from the product attributes.', 'stl-addons' ) . '</p>';
		}

		$rows = '';
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! is_a( $attribute, 'WC_Product_Attribute' ) || ! $attribute->get_visible() ) {
				continue;
			}
			$label = wc_attribute_label( $attribute->get_name(), $product );
			if ( $attribute->is_taxonomy() ) {
				$values = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
				$value  = is_wp_error( $values ) ? '' : implode( ', ', $values );
			} else {
				$value = implode( ', ', $attribute->get_options() );
			}
			if ( '' === $value ) {
				continue;
			}
			$rows .= '<tr><td>' . esc_html( $label ) . '</td><td>' . esc_html( $value ) . '</td></tr>';
		}

		if ( '' === $rows ) {
			return '<p class="stl-pt-empty">' . esc_html__( 'No specifications yet — add them in the product\'s Attributes tab.', 'stl-addons' ) . '</p>';
		}

		return '<table class="stl-pt-spec"><tbody>' . $rows . '</tbody></table>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rows escaped above.
	}

	protected function render() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$s    = $this->get_settings_for_display();
		$tabs = $s['tabs'] ?? array();
		if ( empty( $tabs ) ) {
			return;
		}

		$product   = $this->get_product();
		$editor    = $this->is_editor();
		$auto_rev  = 'yes' === ( $s['auto_review_count'] ?? 'yes' );
		$uid       = $this->get_id();
		$valid_src = array( 'description', 'specifications', 'reviews', 'custom' );
		?>
		<div class="stl-pt">
			<div class="stl-pt-nav" role="tablist">
				<?php foreach ( $tabs as $i => $tab ) :
					$title  = $tab['title'] ?? '';
					$source = in_array( ( $tab['source'] ?? 'custom' ), $valid_src, true ) ? $tab['source'] : 'custom';
					if ( 'reviews' === $source && $auto_rev && $product ) {
						$title .= ' (' . (int) $product->get_review_count() . ')';
					}
					$pane_id = 'stl-pt-' . $uid . '-' . $i;
					?>
					<button type="button" class="stl-pt-btn<?php echo 0 === $i ? ' is-on' : ''; ?>"
						id="<?php echo esc_attr( $pane_id ); ?>-tab"
						role="tab" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"
						aria-controls="<?php echo esc_attr( $pane_id ); ?>"
						tabindex="<?php echo 0 === $i ? '0' : '-1'; ?>"
						data-tab="<?php echo esc_attr( $pane_id ); ?>">
						<?php echo esc_html( $title ); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<div class="stl-pt-panes">
				<?php foreach ( $tabs as $i => $tab ) :
					$source  = in_array( ( $tab['source'] ?? 'custom' ), $valid_src, true ) ? $tab['source'] : 'custom';
					$pane_id = 'stl-pt-' . $uid . '-' . $i;
					?>
					<div class="stl-pt-pane<?php echo 0 === $i ? ' is-on' : ''; ?>" id="<?php echo esc_attr( $pane_id ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $pane_id ); ?>-tab" tabindex="0">
						<?php
						switch ( $source ) {

							case 'description':
								$desc = $product ? $product->get_description() : '';
								if ( '' !== $desc ) {
									// wc_format_content() runs shortcodes + paragraphs WITHOUT the_content,
									// so Elementor cannot re-inject the whole builder page here (which would
									// recurse, re-rendering this very tab widget).
									echo function_exists( 'wc_format_content' )
										? wc_format_content( $desc ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce sanitizes.
										: wpautop( do_shortcode( wp_kses_post( $desc ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitized.
								} elseif ( $editor ) {
									echo '<p class="stl-pt-empty">' . esc_html__( 'The product description shows here.', 'stl-addons' ) . '</p>';
								}
								break;

							case 'specifications':
								echo $this->render_specs( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in method.
								break;

							case 'reviews':
								if ( $product && ! $editor ) {
									// Point the global post at this product so comments_template()
									// loads the right reviews, then restore the previous global so
									// anything rendered after this widget isn't corrupted (the widget
									// may be used outside the main product loop).
									$stl_prev_post   = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
									$GLOBALS['post'] = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
									setup_postdata( $GLOBALS['post'] );
									comments_template();
									$GLOBALS['post'] = $stl_prev_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
									wp_reset_postdata();
								} else {
									echo '<p class="stl-pt-empty">' . esc_html__( 'WooCommerce reviews show here.', 'stl-addons' ) . '</p>';
								}
								break;

							case 'custom':
							default:
								$content   = $tab['content'] ?? '';
								$sc_title  = trim( (string) ( $tab['shortcode_title'] ?? '' ) );
								$shortcode = trim( (string) ( $tab['shortcode'] ?? '' ) );
								if ( '' !== $content ) {
									echo do_shortcode( wp_kses_post( $content ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-sanitized.
								}
								if ( '' !== $shortcode ) {
									if ( '' !== $sc_title ) {
										echo '<h3 class="stl-pt-sc-title">' . esc_html( $sc_title ) . '</h3>';
									}
									echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output.
								}
								break;
						}
						?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
