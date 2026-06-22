<?php
/**
 * Archive Products Elementor Widget.
 *
 * A responsive WooCommerce product grid that reproduces the Hyun Engines shop
 * card (image + sale badge, title, now/was price, shipping note, feature list,
 * AJAX add-to-cart + view button). It can follow the current archive query
 * (use it inside a Theme Builder / shop archive) or run its own query (use it
 * on any normal Elementor page), with optional pagination.
 *
 * Self-contained: all markup is scoped under .stl-ap-* and styled by this
 * widget's own style.css, so it does not depend on the theme stylesheet.
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
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;

class STL_Widget_Archive_Products extends Widget_Base {

	const ALLOWED_HEADING_TAGS = array( 'h2', 'h3', 'h4', 'h5', 'h6' );

	public function get_name()           { return 'stl_archive_products'; }
	public function get_title()          { return __( 'Archive Products', 'stl-addons' ); }
	public function get_icon()           { return 'eicon-products'; }
	public function get_categories()     { return array( 'stl-addons', 'general' ); }
	public function get_keywords()       { return array( 'woocommerce', 'products', 'shop', 'archive', 'grid', 'store', 'stl' ); }
	public function get_style_depends()  { return array( 'stl-archive-products' ); }
	public function get_script_depends() { return array( 'wc-add-to-cart', 'stl-archive-products' ); }

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	public function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' stl-widget stl-ap-section';
	}

	/** Standard unit set + ranges shared by the spacing/size sliders. */
	private function std_slider_args() {
		return array(
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'range'      => array(
				'px'  => array( 'min' => 0, 'max' => 1000 ),
				'em'  => array( 'min' => 0, 'max' => 100 ),
				'rem' => array( 'min' => 0, 'max' => 100 ),
				'%'   => array( 'min' => 0, 'max' => 100 ),
				'vh'  => array( 'min' => 0, 'max' => 100 ),
				'vw'  => array( 'min' => 0, 'max' => 100 ),
			),
		);
	}

	/** Product categories as [ term_id => name ] for a SELECT2. Bounded to 300. */
	private function category_options() {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return array();
		}
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'number'     => 300,
			)
		);
		$out = array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$out[ $term->term_id ] = $term->name;
			}
		}
		return $out;
	}

	/** Map a friendly "order by" preset to WP_Query orderby + order + meta_key. */
	private function resolve_order( $key ) {
		switch ( $key ) {
			case 'date':       return array( 'orderby' => 'date',           'order' => 'DESC' );
			case 'title-asc':  return array( 'orderby' => 'title',          'order' => 'ASC' );
			case 'title-desc': return array( 'orderby' => 'title',          'order' => 'DESC' );
			case 'price-asc':  return array( 'orderby' => 'meta_value_num', 'order' => 'ASC',  'meta_key' => '_price' );
			case 'price-desc': return array( 'orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => '_price' );
			case 'popularity': return array( 'orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => 'total_sales' );
			case 'rating':     return array( 'orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => '_wc_average_rating' );
			case 'rand':       return array( 'orderby' => 'rand',           'order' => 'DESC' );
			case 'menu_order':
			default:           return array( 'orderby' => 'menu_order title', 'order' => 'ASC' );
		}
	}

	protected function register_controls() {
		$this->controls_query();
		$this->controls_toolbar();
		$this->controls_display();
		$this->controls_layout();
		$this->controls_card();
		$this->controls_image_style();
		$this->controls_title_style();
		$this->controls_price_style();
		$this->controls_button_style();
		$this->controls_pagination_style();
	}

	/* ---------------------------------------------------------------- Content */

	private function controls_query() {
		$this->start_controls_section( 'sec_query', array(
			'label' => __( 'Query', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'source', array(
			'label'       => __( 'Source', 'stl-addons' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'current',
			'options'     => array(
				'current' => __( 'Current Query (shop / category archive)', 'stl-addons' ),
				'custom'  => __( 'Custom Query', 'stl-addons' ),
			),
			'description' => __( 'Use "Current Query" inside a shop/category archive template. Use "Custom Query" to show products on any normal page.', 'stl-addons' ),
		) );

		$this->add_control( 'categories', array(
			'label'       => __( 'Categories', 'stl-addons' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'options'     => $this->category_options(),
			'description' => __( 'Leave empty to include all categories.', 'stl-addons' ),
			'condition'   => array( 'source' => 'custom' ),
		) );

		$this->add_control( 'order_by', array(
			'label'     => __( 'Order By', 'stl-addons' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'menu_order',
			'options'   => array(
				'menu_order' => __( 'Default (menu order)', 'stl-addons' ),
				'date'       => __( 'Newest First', 'stl-addons' ),
				'price-asc'  => __( 'Price: Low to High', 'stl-addons' ),
				'price-desc' => __( 'Price: High to Low', 'stl-addons' ),
				'popularity' => __( 'Popularity', 'stl-addons' ),
				'rating'     => __( 'Average Rating', 'stl-addons' ),
				'title-asc'  => __( 'Title A → Z', 'stl-addons' ),
				'title-desc' => __( 'Title Z → A', 'stl-addons' ),
				'rand'       => __( 'Random', 'stl-addons' ),
			),
			'condition' => array( 'source' => 'custom' ),
		) );

		$this->add_control( 'on_sale_only', array(
			'label'        => __( 'On Sale Only', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
			'condition'    => array( 'source' => 'custom' ),
		) );

		$this->add_control( 'featured_only', array(
			'label'        => __( 'Featured Only', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
			'condition'    => array( 'source' => 'custom' ),
		) );

		$this->add_control( 'hide_out_of_stock', array(
			'label'        => __( 'Hide Out of Stock', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
			'condition'    => array( 'source' => 'custom' ),
		) );

		$this->add_control( 'products_per_page', array(
			'label'       => __( 'Products Per Page', 'stl-addons' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 12,
			'min'         => 1,
			'max'         => 60,
			'condition'   => array( 'source' => 'custom' ),
		) );

		$this->add_control( 'offset', array(
			'label'       => __( 'Offset', 'stl-addons' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'min'         => 0,
			'description' => __( 'Skip this many products from the start.', 'stl-addons' ),
			'condition'   => array( 'source' => 'custom', 'paginate' => '' ),
		) );

		$this->add_control( 'paginate', array(
			'label'        => __( 'Show Pagination', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'separator'    => 'before',
		) );

		$this->end_controls_section();
	}

	private function controls_display() {
		$this->start_controls_section( 'sec_display', array(
			'label' => __( 'Card Content', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'title_tag', array(
			'label'   => __( 'Title HTML Tag', 'stl-addons' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'h3',
			'options' => array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6' ),
		) );

		$this->add_control( 'show_sale_badge', array(
			'label'        => __( 'Show Sale Badge', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'sale_text', array(
			'label'     => __( 'Sale Badge Text', 'stl-addons' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => __( 'Sale', 'stl-addons' ),
			'condition' => array( 'show_sale_badge' => 'yes' ),
		) );

		$this->add_control( 'show_price', array(
			'label'        => __( 'Show Price', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_shipping', array(
			'label'        => __( 'Show Shipping Note', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'shipping_text', array(
			'label'     => __( 'Shipping Note', 'stl-addons' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => __( '+ Free shipping in Australia', 'stl-addons' ),
			'condition' => array( 'show_shipping' => 'yes' ),
		) );

		$this->add_control( 'show_features', array(
			'label'        => __( 'Show Feature List', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'features', array(
			'label'       => __( 'Features (one per line)', 'stl-addons' ),
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 3,
			'default'     => "12 months product warranty\nChangeover basis · fitting available",
			'description' => __( 'Static selling points shown on every card. One line each.', 'stl-addons' ),
			'condition'   => array( 'show_features' => 'yes' ),
		) );

		$this->add_control( 'show_add_to_cart', array(
			'label'        => __( 'Show Add to Cart', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_view', array(
			'label'        => __( 'Show View Button', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'view_text', array(
			'label'     => __( 'View Button Text', 'stl-addons' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => __( 'View', 'stl-addons' ),
			'condition' => array( 'show_view' => 'yes' ),
		) );

		$this->end_controls_section();
	}

	private function controls_toolbar() {
		$this->start_controls_section( 'sec_toolbar', array(
			'label' => __( 'Toolbar (sort + count)', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'show_ordering', array(
			'label'        => __( 'Show Sort Dropdown', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'description'  => __( 'Adds the WooCommerce "Sort by" dropdown above the grid.', 'stl-addons' ),
		) );

		$this->add_control( 'show_result_count', array(
			'label'        => __( 'Show Result Count', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ Style */

	private function controls_layout() {
		$this->start_controls_section( 'sec_layout', array(
			'label' => __( 'Layout', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'columns', array(
			'label'          => __( 'Columns', 'stl-addons' ),
			'type'           => Controls_Manager::SELECT,
			'default'        => '3',
			'tablet_default' => '2',
			'mobile_default' => '1',
			'options'        => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
			'selectors'      => array( '{{WRAPPER}} .stl-ap-grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ),
		) );

		$this->add_responsive_control( 'gap', array_merge(
			array(
				'label'     => __( 'Gap', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-ap-grid' => 'gap: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_control( 'section_bg', array(
			'label'     => __( 'Section Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}}' => 'background: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'section_padding', array(
			'label'      => __( 'Section Padding', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'selectors'  => array( '{{WRAPPER}}' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_card() {
		$this->start_controls_section( 'sec_card', array(
			'label' => __( 'Card', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'card_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ap-card' => 'background: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'card_border',
			'selector' => '{{WRAPPER}} .stl-ap-card',
		) );

		$this->add_responsive_control( 'card_radius', array_merge(
			array(
				'label'     => __( 'Border Radius', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-ap-card' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'card_body_padding', array(
			'label'      => __( 'Body Padding', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'selectors'  => array( '{{WRAPPER}} .stl-ap-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'card_shadow',
			'selector' => '{{WRAPPER}} .stl-ap-card',
		) );

		$this->add_control( 'card_hover_border', array(
			'label'     => __( 'Hover Border Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ap-card:hover' => 'border-color: {{VALUE}};' ),
		) );

		$this->add_control( 'enable_lift', array(
			'label'                => __( 'Lift on Hover', 'stl-addons' ),
			'type'                 => Controls_Manager::SWITCHER,
			'return_value'         => 'yes',
			'default'              => 'yes',
			'selectors_dictionary' => array( 'yes' => 'translateY(-6px)', '' => 'none' ),
			'selectors'            => array( '{{WRAPPER}} .stl-ap-card:hover' => 'transform: {{VALUE}};' ),
		) );

		$this->add_control( 'accent_color', array(
			'label'       => __( 'Accent Color', 'stl-addons' ),
			'type'        => Controls_Manager::COLOR,
			'description' => __( 'Sale badge, hover accents and the add-to-cart button base.', 'stl-addons' ),
			'selectors'   => array( '{{WRAPPER}} .stl-ap-section, {{WRAPPER}}.stl-ap-section' => '--stl-ap-accent: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_image_style() {
		$this->start_controls_section( 'sec_image', array(
			'label' => __( 'Image', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'image_ratio', array(
			'label'      => __( 'Aspect Ratio', 'stl-addons' ),
			'type'       => Controls_Manager::SLIDER,
			'range'      => array( 'px' => array( 'min' => 0.5, 'max' => 2, 'step' => 0.05 ) ),
			'selectors'  => array( '{{WRAPPER}} .stl-ap-imgbox' => 'aspect-ratio: {{SIZE}};' ),
		) );

		$this->add_control( 'image_fit', array(
			'label'     => __( 'Object Fit', 'stl-addons' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'cover',
			'options'   => array(
				'cover'   => __( 'Cover', 'stl-addons' ),
				'contain' => __( 'Contain', 'stl-addons' ),
			),
			'selectors' => array( '{{WRAPPER}} .stl-ap-imgbox img' => 'object-fit: {{VALUE}};' ),
		) );

		$this->add_control( 'enable_zoom', array(
			'label'                => __( 'Zoom on Hover', 'stl-addons' ),
			'type'                 => Controls_Manager::SWITCHER,
			'return_value'         => 'yes',
			'default'              => 'yes',
			'selectors_dictionary' => array( 'yes' => 'scale(1.05)', '' => 'scale(1)' ),
			'selectors'            => array( '{{WRAPPER}} .stl-ap-card:hover .stl-ap-imgbox img' => 'transform: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_title_style() {
		$this->start_controls_section( 'sec_title', array(
			'label' => __( 'Title', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'title_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ap-title a' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'title_hover_color', array(
			'label'     => __( 'Hover Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ap-title a:hover' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'title_typo',
			'selector' => '{{WRAPPER}} .stl-ap-title',
		) );

		$this->end_controls_section();
	}

	private function controls_price_style() {
		$this->start_controls_section( 'sec_price', array(
			'label'     => __( 'Price', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_price' => 'yes' ),
		) );

		$this->add_control( 'price_color', array(
			'label'     => __( 'Price Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ap-price .now' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'price_was_color', array(
			'label'     => __( 'Old Price Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ap-price .was' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'price_typo',
			'selector' => '{{WRAPPER}} .stl-ap-price .now',
		) );

		$this->end_controls_section();
	}

	private function controls_button_style() {
		$this->start_controls_section( 'sec_buttons', array(
			'label' => __( 'Buttons', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'add_heading', array(
			'label' => __( 'Add to Cart', 'stl-addons' ),
			'type'  => Controls_Manager::HEADING,
		) );

		$this->add_control( 'add_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ap-add' => 'background: {{VALUE}};' ),
		) );

		$this->add_control( 'add_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ap-add' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'add_bg_hover', array(
			'label'     => __( 'Hover Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ap-add:hover' => 'background: {{VALUE}};' ),
		) );

		$this->add_control( 'view_heading', array(
			'label'     => __( 'View Button', 'stl-addons' ),
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		) );

		$this->add_control( 'view_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ap-view' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'view_border', array(
			'label'     => __( 'Border Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ap-view' => 'border-color: {{VALUE}};' ),
		) );

		$this->add_control( 'view_hover', array(
			'label'     => __( 'Hover Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ap-view:hover' => 'color: {{VALUE}}; border-color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_pagination_style() {
		$this->start_controls_section( 'sec_pagination', array(
			'label'     => __( 'Pagination', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'paginate' => 'yes' ),
		) );

		$this->add_control( 'pag_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ap-pagination a, {{WRAPPER}} .stl-ap-pagination span' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'pag_active_bg', array(
			'label'     => __( 'Active Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ap-pagination .current' => 'background: {{VALUE}}; border-color: {{VALUE}}; color: #fff;' ),
		) );

		$this->add_control( 'pag_active_color', array(
			'label'     => __( 'Active Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ap-pagination .current' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	/* ----------------------------------------------------------------- Render */

	/** Current paged number across the common query vars. */
	private function current_paged() {
		$paged = (int) get_query_var( 'paged' );
		if ( ! $paged ) {
			$paged = (int) get_query_var( 'page' );
		}
		return max( 1, $paged );
	}

	/** WooCommerce catalog ordering options for the sort dropdown. */
	private function ordering_options() {
		return apply_filters(
			'woocommerce_catalog_orderby',
			array(
				'menu_order' => __( 'Default sorting', 'woocommerce' ),
				'popularity' => __( 'Sort by popularity', 'woocommerce' ),
				'rating'     => __( 'Sort by average rating', 'woocommerce' ),
				'date'       => __( 'Sort by latest', 'woocommerce' ),
				'price'      => __( 'Sort by price: low to high', 'woocommerce' ),
				'price-desc' => __( 'Sort by price: high to low', 'woocommerce' ),
			)
		);
	}

	/** The orderby value to pre-select in the dropdown (?orderby, else the widget/store default). */
	private function current_orderby( $s ) {
		if ( isset( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display sort, mirrors WooCommerce core.
			return sanitize_title( wp_unslash( $_GET['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( 'custom' === ( $s['source'] ?? 'current' ) ) {
			$map = array(
				'menu_order' => 'menu_order',
				'date'       => 'date',
				'price-asc'  => 'price',
				'price-desc' => 'price-desc',
				'popularity' => 'popularity',
				'rating'     => 'rating',
			);
			$ob = $s['order_by'] ?? 'menu_order';
			return isset( $map[ $ob ] ) ? $map[ $ob ] : 'menu_order';
		}
		return (string) get_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
	}

	/** "Showing X–Y of Z results" string. */
	private function result_count_text( $total, $paged, $per_page ) {
		$total = (int) $total;
		if ( $total < 1 ) {
			return '';
		}
		if ( $per_page < 1 || $total <= $per_page ) {
			/* translators: %d: total results */
			return sprintf( _n( 'Showing the single result', 'Showing all %d results', $total, 'woocommerce' ), $total );
		}
		$first = ( $per_page * ( $paged - 1 ) ) + 1;
		$last  = min( $total, $per_page * $paged );
		/* translators: 1: first result 2: last result 3: total results */
		return sprintf( _x( 'Showing %1$d&ndash;%2$d of %3$d results', 'with first and last result', 'woocommerce' ), $first, $last, $total );
	}

	/** Build a WP_Query for the "custom" source. */
	private function build_custom_query( $s, $paged ) {
		$order_key = $s['order_by'] ?? 'menu_order';

		// A ?orderby in the URL (from the sort dropdown) overrides the configured order.
		if ( isset( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display sort, mirrors WooCommerce core.
			$wc_orderby = sanitize_title( wp_unslash( $_GET['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$translate  = array(
				'menu_order' => 'menu_order',
				'date'       => 'date',
				'price'      => 'price-asc',
				'price-desc' => 'price-desc',
				'popularity' => 'popularity',
				'rating'     => 'rating',
				'rand'       => 'rand',
			);
			if ( isset( $translate[ $wc_orderby ] ) ) {
				$order_key = $translate[ $wc_orderby ];
			}
		}

		$order = $this->resolve_order( $order_key );

		$args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, (int) ( $s['products_per_page'] ?? 12 ) ),
			'orderby'             => $order['orderby'],
			'order'               => $order['order'],
			'ignore_sticky_posts' => true,
		);

		if ( isset( $order['meta_key'] ) ) {
			$args['meta_key'] = $order['meta_key']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		}

		$paginate = 'yes' === ( $s['paginate'] ?? 'yes' );
		if ( $paginate ) {
			$args['paged'] = $paged;
		} else {
			$args['offset']        = max( 0, (int) ( $s['offset'] ?? 0 ) );
			$args['no_found_rows'] = true;
		}

		$tax_query = array();

		$cats = array_filter( array_map( 'intval', (array) ( $s['categories'] ?? array() ) ) );
		if ( $cats ) {
			$tax_query[] = array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $cats );
		}

		if ( 'yes' === ( $s['featured_only'] ?? '' ) ) {
			$tax_query[] = array( 'taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => 'featured' );
		}

		if ( 'yes' === ( $s['hide_out_of_stock'] ?? '' ) ) {
			$tax_query[] = array( 'taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => 'outofstock', 'operator' => 'NOT IN' );
		}

		if ( 'yes' === ( $s['on_sale_only'] ?? '' ) ) {
			$on_sale = wc_get_product_ids_on_sale();
			$args['post__in'] = $on_sale ? $on_sale : array( 0 );
		}

		if ( $tax_query ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		return new WP_Query( $args );
	}

	/** Render the AJAX-capable add-to-cart link, mirroring WooCommerce loop output. */
	private function render_add_to_cart( $product ) {
		$classes = implode(
			' ',
			array_filter(
				array(
					'stl-ap-add',
					'product_type_' . $product->get_type(),
					$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
					$product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $product->is_in_stock() ? 'ajax_add_to_cart' : '',
				)
			)
		);

		$attrs = array(
			'data-quantity'    => '1',
			'data-product_id'  => $product->get_id(),
			'data-product_sku' => $product->get_sku(),
			'aria-label'       => $product->add_to_cart_description(),
			'rel'              => 'nofollow',
		);

		echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core filter, values escaped below.
			'woocommerce_loop_add_to_cart_link',
			sprintf(
				'<a href="%s" class="%s" %s>%s</a>',
				esc_url( $product->add_to_cart_url() ),
				esc_attr( $classes ),
				wc_implode_html_attributes( $attrs ),
				esc_html( $product->add_to_cart_text() )
			),
			$product,
			array( 'class' => $classes )
		);
	}

	protected function render() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<p class="stl-ap-empty">' . esc_html__( 'WooCommerce is not active.', 'stl-addons' ) . '</p>';
			return;
		}

		$s        = $this->get_settings_for_display();
		$source   = $s['source'] ?? 'current';
		$paginate = 'yes' === ( $s['paginate'] ?? 'yes' );
		$paged    = $this->current_paged();

		// Resolve the query object.
		$owns_query = false;
		if ( 'current' === $source && ( is_shop() || is_product_taxonomy() ) ) {
			$query = $GLOBALS['wp_query'];
		} else {
			// Custom source, OR "current" used off-archive (e.g. a normal page / the editor).
			$query      = $this->build_custom_query( $s, $paged );
			$owns_query = true;
		}

		if ( empty( $query->posts ) ) {
			echo '<p class="stl-ap-empty">' . esc_html__( 'No products found.', 'stl-addons' ) . '</p>';
			if ( $owns_query ) {
				wp_reset_postdata();
			}
			return;
		}

		$tag_raw      = $s['title_tag'] ?? 'h3';
		$title_tag    = in_array( $tag_raw, self::ALLOWED_HEADING_TAGS, true ) ? $tag_raw : 'h3';
		$show_sale    = 'yes' === ( $s['show_sale_badge'] ?? 'yes' );
		$show_price   = 'yes' === ( $s['show_price'] ?? 'yes' );
		$show_ship    = 'yes' === ( $s['show_shipping'] ?? 'yes' );
		$show_feat    = 'yes' === ( $s['show_features'] ?? 'yes' );
		$show_add     = 'yes' === ( $s['show_add_to_cart'] ?? 'yes' );
		$show_view    = 'yes' === ( $s['show_view'] ?? 'yes' );
		$sale_text    = $s['sale_text'] ?? __( 'Sale', 'stl-addons' );
		$ship_text    = $s['shipping_text'] ?? '';
		$view_text    = $s['view_text'] ?? __( 'View', 'stl-addons' );
		$features     = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) ( $s['features'] ?? '' ) ) ) );

		$show_ordering = 'yes' === ( $s['show_ordering'] ?? 'yes' );
		$show_count    = 'yes' === ( $s['show_result_count'] ?? 'yes' );
		$per_page      = ( 'current' === $source ) ? (int) $query->get( 'posts_per_page' ) : max( 1, (int) ( $s['products_per_page'] ?? 12 ) );
		$total_found   = (int) $query->found_posts;
		if ( $total_found < 1 ) {
			$total_found = count( $query->posts );
		}
		$current_orderby = $this->current_orderby( $s );
		$order_options   = $this->ordering_options();
		?>
		<div class="stl-ap">
			<?php if ( $show_count || $show_ordering ) : ?>
				<div class="stl-ap-toolbar">
					<?php if ( $show_count ) : ?>
						<p class="stl-ap-count"><?php echo esc_html( $this->result_count_text( $total_found, $paged, $per_page ) ); ?></p>
					<?php else : ?>
						<span></span>
					<?php endif; ?>

					<?php if ( $show_ordering ) : ?>
						<form class="woocommerce-ordering stl-ap-ordering" method="get">
							<select name="orderby" class="orderby" aria-label="<?php esc_attr_e( 'Shop order', 'woocommerce' ); ?>">
								<?php foreach ( $order_options as $id => $name ) : ?>
									<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $current_orderby, $id ); ?>><?php echo esc_html( $name ); ?></option>
								<?php endforeach; ?>
							</select>
							<input type="hidden" name="paged" value="1" />
							<?php
							if ( function_exists( 'wc_query_string_form_fields' ) ) {
								wc_query_string_form_fields( null, array( 'orderby', 'submit', 'paged', 'product-page' ) );
							}
							?>
						</form>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<ul class="stl-ap-grid products">
				<?php
				foreach ( $query->posts as $product_post ) :
					$product = wc_get_product( $product_post->ID );
					if ( ! $product || ! $product->is_visible() ) {
						continue;
					}
					$permalink = get_permalink( $product->get_id() );
					$on_sale   = $product->is_on_sale();
					?>
					<li class="stl-ap-card product">
						<a class="stl-ap-imgbox" href="<?php echo esc_url( $permalink ); ?>">
							<?php if ( $show_sale && $on_sale && '' !== $sale_text ) : ?>
								<span class="stl-ap-sale"><?php echo esc_html( $sale_text ); ?></span>
							<?php endif; ?>
							<?php echo $product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core returns escaped markup. ?>
						</a>

						<div class="stl-ap-body">
							<<?php echo $title_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelisted. ?> class="stl-ap-title">
								<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
							</<?php echo $title_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

							<?php if ( $show_price && '' !== $product->get_price_html() ) : ?>
								<div class="stl-ap-price">
									<span class="now"><?php echo wp_kses_post( wc_price( wc_get_price_to_display( $product ) ) ); ?></span>
									<?php if ( $on_sale && '' !== (string) $product->get_regular_price() ) : ?>
										<span class="was"><?php echo wp_kses_post( wc_price( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ) ) ); ?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<?php if ( $show_ship && '' !== $ship_text ) : ?>
								<div class="stl-ap-ship"><?php echo esc_html( $ship_text ); ?></div>
							<?php endif; ?>

							<?php if ( $show_feat && $features ) : ?>
								<ul class="stl-ap-feat">
									<?php foreach ( $features as $feat ) : ?>
										<li><?php echo esc_html( $feat ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>

							<?php if ( $show_add || $show_view ) : ?>
								<div class="stl-ap-actions">
									<?php if ( $show_add ) : ?>
										<?php $this->render_add_to_cart( $product ); ?>
									<?php endif; ?>
									<?php if ( $show_view ) : ?>
										<a class="stl-ap-view" href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $view_text ); ?></a>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php
			if ( $paginate ) {
				$total = (int) $query->max_num_pages;
				if ( $total > 1 ) {
					$links = paginate_links(
						array(
							'base'      => str_replace( PHP_INT_MAX, '%#%', esc_url( get_pagenum_link( PHP_INT_MAX ) ) ),
							'format'    => '',
							'current'   => $paged,
							'total'     => $total,
							'type'      => 'plain',
							'prev_text' => '&lsaquo;',
							'next_text' => '&rsaquo;',
						)
					);
					if ( $links ) {
						echo '<nav class="stl-ap-pagination" aria-label="' . esc_attr__( 'Products', 'stl-addons' ) . '">' . $links . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links returns escaped anchors.
					}
				}
			}
			?>
		</div>
		<?php
		if ( $owns_query ) {
			wp_reset_postdata();
		}
	}
}
