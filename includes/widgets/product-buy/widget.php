<?php
/**
 * Product Buy Elementor Widget.
 *
 * Single-product "buy row" (Hyun Engines design): a quantity stepper (− input +)
 * plus the Add to cart button. Reads the current product, so drop it on a
 * single-product Theme Builder template.
 *
 * For simple, purchasable, in-stock products it renders WooCommerce's native
 * add-to-cart form (so quantity posts correctly). For other product types it
 * falls back to a link to the product page.
 *
 * Self-contained: markup scoped under .stl-buy-* and styled by this widget's own
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

class STL_Widget_Product_Buy extends Widget_Base {

	/**
	 * Hide this widget from the Elementor panel when WooCommerce is inactive.
	 */
	public static function is_available() {
		return class_exists( 'WooCommerce' );
	}

	public function get_name()           { return 'stl_product_buy'; }
	public function get_title()          { return __( 'Product Buy (Add to Cart)', 'stl-addons' ); }
	public function get_icon()           { return 'eicon-product-add-to-cart'; }
	public function get_categories()     { return array( 'stl-addons', 'general' ); }
	public function get_keywords()       { return array( 'woocommerce', 'product', 'add to cart', 'buy', 'quantity', 'single', 'stl' ); }
	public function get_style_depends()  { return array( 'stl-product-buy' ); }
	public function get_script_depends()  { return array( 'stl-product-buy' ); }

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	public function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' stl-widget stl-buy-section';
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
			'label' => __( 'Buy Row', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'show_qty', array(
			'label'        => __( 'Show Quantity Stepper', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'button_text', array(
			'label'       => __( 'Button Text', 'stl-addons' ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => __( 'Add to cart', 'stl-addons' ),
			'description' => __( 'Leave empty to use the product default.', 'stl-addons' ),
		) );

		$this->end_controls_section();

		/* ---- Style ---- */

		$this->start_controls_section( 'sec_style', array(
			'label' => __( 'Style', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'qty_heading', array(
			'label'     => __( 'Quantity', 'stl-addons' ),
			'type'      => Controls_Manager::HEADING,
			'condition' => array( 'show_qty' => 'yes' ),
		) );

		$this->add_control( 'qty_border', array(
			'label'     => __( 'Border Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'condition' => array( 'show_qty' => 'yes' ),
			'selectors' => array( '{{WRAPPER}} .stl-buy-qty' => 'border-color: {{VALUE}};' ),
		) );

		$this->add_control( 'qty_text', array(
			'label'     => __( 'Text / Button Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'condition' => array( 'show_qty' => 'yes' ),
			'selectors' => array(
				'{{WRAPPER}} .stl-buy-qty button' => 'color: {{VALUE}};',
				'{{WRAPPER}} .stl-buy-input'      => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'btn_heading', array(
			'label'     => __( 'Add to Cart Button', 'stl-addons' ),
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		) );

		$this->add_control( 'btn_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-buy-add' => 'background: {{VALUE}}; box-shadow: none;' ),
		) );

		$this->add_control( 'btn_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-buy-add' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'btn_bg_hover', array(
			'label'     => __( 'Hover Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-buy-add:hover' => 'background: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'btn_min_width', array(
			'label'      => __( 'Button Min Width', 'stl-addons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'range'      => array( 'px' => array( 'min' => 80, 'max' => 600 ) ),
			'selectors'  => array( '{{WRAPPER}} .stl-buy-add' => 'min-width: {{SIZE}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}

	/** Render the quantity stepper input group. */
	private function render_qty( $product ) {
		$max = $product->get_max_purchase_quantity();
		?>
		<div class="stl-buy-qty">
			<button type="button" class="stl-buy-minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'stl-addons' ); ?>">&minus;</button>
			<input type="number" class="stl-buy-input qty" name="quantity"
				value="<?php echo esc_attr( $product->get_min_purchase_quantity() ); ?>"
				min="<?php echo esc_attr( $product->get_min_purchase_quantity() ); ?>"
				<?php echo ( $max > 0 ) ? 'max="' . esc_attr( $max ) . '"' : ''; ?>
				step="1" inputmode="numeric" autocomplete="off" />
			<button type="button" class="stl-buy-plus" aria-label="<?php esc_attr_e( 'Increase quantity', 'stl-addons' ); ?>">+</button>
		</div>
		<?php
	}

	protected function render() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$s        = $this->get_settings_for_display();
		$show_qty = 'yes' === ( $s['show_qty'] ?? 'yes' );
		$btn_text = trim( (string) ( $s['button_text'] ?? '' ) );

		$product = $this->get_product();

		// Editor fallback so the widget isn't blank off a product context.
		if ( ! $product ) {
			if ( $this->is_editor() ) {
				$label = '' !== $btn_text ? $btn_text : __( 'Add to cart', 'stl-addons' );
				echo '<div class="stl-buy">';
				if ( $show_qty ) {
					echo '<div class="stl-buy-qty"><button type="button" class="stl-buy-minus">&minus;</button><input type="number" class="stl-buy-input" value="1" min="1"><button type="button" class="stl-buy-plus">+</button></div>';
				}
				echo '<button type="button" class="stl-buy-add">' . esc_html( $label ) . '</button></div>';
			}
			return;
		}

		$label = '' !== $btn_text ? $btn_text : $product->add_to_cart_text();

		// Simple, purchasable, in-stock → native add-to-cart form with quantity.
		if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
			?>
			<form class="stl-buy cart"
				action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>"
				method="post" enctype="multipart/form-data">
				<?php if ( $show_qty ) : ?>
					<?php $this->render_qty( $product ); ?>
				<?php endif; ?>
				<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="stl-buy-add">
					<?php echo esc_html( $label ); ?>
				</button>
			</form>
			<?php
			return;
		}

		// Other types / unpurchasable → link to the product page.
		?>
		<div class="stl-buy">
			<a class="stl-buy-add stl-buy-link" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" rel="nofollow">
				<?php echo esc_html( $label ); ?>
			</a>
		</div>
		<?php
	}
}
