<?php
/**
 * Product Price Elementor Widget.
 *
 * Single-product price block (Hyun Engines design): current price, the
 * strikethrough "was" price, and a "You save $X" badge when the product is on
 * sale. Reads the current product, so drop it on a single-product Theme Builder
 * template.
 *
 * Self-contained: markup scoped under .stl-pp-* and styled by this widget's own
 * style.css.
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
use Elementor\Group_Control_Typography;

class STL_Widget_Product_Price extends Widget_Base {

	/**
	 * Hide this widget from the Elementor panel when WooCommerce is inactive.
	 */
	public static function is_available() {
		return class_exists( 'WooCommerce' );
	}

	public function get_name()          { return 'stl_product_price'; }
	public function get_title()         { return __( 'Product Price', 'stl-addons' ); }
	public function get_icon()          { return 'eicon-product-price'; }
	public function get_categories()    { return array( 'stl-addons', 'general' ); }
	public function get_keywords()      { return array( 'woocommerce', 'product', 'price', 'sale', 'save', 'single', 'stl' ); }
	public function get_style_depends() { return array( 'stl-product-price' ); }

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	public function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' stl-widget stl-pp-section';
	}

	/** Resolve the product to display: current product, or null. */
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

		$this->start_controls_section( 'sec_content', array(
			'label' => __( 'Price', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'show_was', array(
			'label'        => __( 'Show Old Price', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_save', array(
			'label'        => __( 'Show "You save" Badge', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'save_label', array(
			'label'     => __( 'Save Label', 'stl-addons' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => __( 'You save', 'stl-addons' ),
			'condition' => array( 'show_save' => 'yes' ),
		) );

		$this->end_controls_section();

		/* ---- Style ---- */

		$this->start_controls_section( 'sec_style', array(
			'label' => __( 'Style', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'align', array(
			'label'     => __( 'Alignment', 'stl-addons' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => __( 'Left', 'stl-addons' ),   'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => __( 'Center', 'stl-addons' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => __( 'Right', 'stl-addons' ),  'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .stl-pp' => 'justify-content: {{VALUE}};' ),
		) );

		$this->add_control( 'now_color', array(
			'label'     => __( 'Price Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pp .now' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'now_typo',
			'selector' => '{{WRAPPER}} .stl-pp .now',
		) );

		$this->add_control( 'was_color', array(
			'label'     => __( 'Old Price Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'separator' => 'before',
			'selectors' => array( '{{WRAPPER}} .stl-pp .was' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'save_color', array(
			'label'     => __( 'Save Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'separator' => 'before',
			'selectors' => array( '{{WRAPPER}} .stl-pp .save' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'save_bg', array(
			'label'     => __( 'Save Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pp .save' => 'background: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$s          = $this->get_settings_for_display();
		$show_was   = 'yes' === ( $s['show_was'] ?? 'yes' );
		$show_save  = 'yes' === ( $s['show_save'] ?? 'yes' );
		$save_label = $s['save_label'] ?? __( 'You save', 'stl-addons' );

		$product = $this->get_product();

		// Editor fallback so the widget isn't blank off a product context.
		if ( ! $product ) {
			if ( $this->is_editor() ) {
				echo '<div class="stl-pp"><span class="now">' . wp_kses_post( wc_price( 5999 ) ) . '</span>';
				if ( $show_was ) {
					echo '<span class="was">' . wp_kses_post( wc_price( 7000 ) ) . '</span>';
				}
				if ( $show_save ) {
					echo '<span class="save">' . esc_html( $save_label ) . ' ' . wp_kses_post( wc_price( 1001 ) ) . '</span>';
				}
				echo '</div>';
			}
			return;
		}

		$on_sale     = $product->is_on_sale();
		$regular_raw = $product->get_regular_price();
		$now_amount  = wc_get_price_to_display( $product );
		$was_amount  = ( '' !== (string) $regular_raw ) ? wc_get_price_to_display( $product, array( 'price' => $regular_raw ) ) : 0;
		$save_amount = ( $was_amount > $now_amount ) ? ( $was_amount - $now_amount ) : 0;
		?>
		<div class="stl-pp">
			<?php if ( '' !== $product->get_price_html() ) : ?>
				<span class="now"><?php echo wp_kses_post( wc_price( $now_amount ) ); ?></span>

				<?php if ( $show_was && $on_sale && $was_amount > 0 ) : ?>
					<span class="was"><?php echo wp_kses_post( wc_price( $was_amount ) ); ?></span>
				<?php endif; ?>

				<?php if ( $show_save && $on_sale && $save_amount > 0 ) : ?>
					<span class="save"><?php echo esc_html( $save_label ) . ' ' . wp_kses_post( wc_price( $save_amount ) ); ?></span>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}
}
