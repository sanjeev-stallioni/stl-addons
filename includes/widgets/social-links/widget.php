<?php
/**
 * Social Links Elementor Widget.
 *
 * Two presentation styles for a list of social links:
 *
 *   Style 1 (Rail)   — vertical fixed rail of icons + labels on the left or
 *                      right edge of the viewport. Items slide on hover.
 *   Style 2 (Radial) — floating action button at a screen corner; clicking
 *                      it fans the icons out in a quarter arc.
 *
 * Ported from the MyKodeHub Social Icon widget and adapted to the stl-addons
 * conventions (scoped classes, no style-tab defaults, std_slider_args, etc.).
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
use Elementor\Group_Control_Box_Shadow;

class STL_Widget_Social_Links extends Widget_Base {

	public function get_name()           { return 'stl_social_links'; }
	public function get_title()          { return __( 'Social Links', 'stl-addons' ); }
	public function get_icon()           { return 'eicon-social-icons'; }
	public function get_categories()     { return array( 'stl-addons', 'general' ); }
	public function get_keywords()       { return array( 'social', 'icons', 'links', 'rail', 'radial', 'floating', 'stl' ); }
	public function get_style_depends()  { return array( 'stl-social-links' ); }
	public function get_script_depends() { return array( 'stl-social-links' ); }

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	public function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' stl-widget stl-social-links';
	}

	/**
	 * Standard slider unit set + ranges (plugin-wide convention).
	 */
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

	protected function register_controls() {
		$this->controls_content();
		$this->controls_rail_layout();
		$this->controls_rail_item();
		$this->controls_rail_label();
		$this->controls_rail_icon();
		$this->controls_radial_layout();
		$this->controls_radial_trigger();
		$this->controls_radial_item();
	}

	/* ============================================================
	 * Content
	 * ============================================================ */
	private function controls_content() {
		$this->start_controls_section( 'sec_content', array(
			'label' => __( 'Content', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'style_variant', array(
			'label'   => __( 'Style', 'stl-addons' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'rail',
			'options' => array(
				'rail'   => __( 'Style 1 — Sliding Rail', 'stl-addons' ),
				'radial' => __( 'Style 2 — Radial FAB', 'stl-addons' ),
			),
		) );

		$this->add_control( 'rail_side', array(
			'label'     => __( 'Rail Side', 'stl-addons' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'right',
			'options'   => array(
				'right' => __( 'Right edge', 'stl-addons' ),
				'left'  => __( 'Left edge', 'stl-addons' ),
			),
			'condition' => array( 'style_variant' => 'rail' ),
		) );

		$this->add_control( 'radial_corner', array(
			'label'     => __( 'Radial Corner', 'stl-addons' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'bottom-right',
			'options'   => array(
				'bottom-right' => __( 'Bottom right', 'stl-addons' ),
				'bottom-left'  => __( 'Bottom left', 'stl-addons' ),
			),
			'condition' => array( 'style_variant' => 'radial' ),
		) );

		$this->add_control( 'radial_btn_icon_kind', array(
			'label'     => __( 'Trigger Icon', 'stl-addons' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'bubble',
			'options'   => array(
				'bubble' => __( 'Animated chat bubble', 'stl-addons' ),
				'custom' => __( 'Custom icon', 'stl-addons' ),
			),
			'condition' => array( 'style_variant' => 'radial' ),
		) );

		$this->add_control( 'radial_btn_icon', array(
			'label'     => __( 'Trigger Icon (custom)', 'stl-addons' ),
			'type'      => Controls_Manager::ICONS,
			'default'   => array(
				'value'   => 'fas fa-plus',
				'library' => 'fa-solid',
			),
			'condition' => array(
				'style_variant'        => 'radial',
				'radial_btn_icon_kind' => 'custom',
			),
		) );

		// Repeater of social items (shared across both styles).
		$rep = new Repeater();

		$rep->add_control( 'title', array(
			'label'   => __( 'Label', 'stl-addons' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Facebook', 'stl-addons' ),
		) );

		$rep->add_control( 'icon', array(
			'label'   => __( 'Icon', 'stl-addons' ),
			'type'    => Controls_Manager::ICONS,
			'default' => array(
				'value'   => 'fab fa-facebook-f',
				'library' => 'fa-brands',
			),
		) );

		$rep->add_control( 'link', array(
			'label'       => __( 'Link', 'stl-addons' ),
			'type'        => Controls_Manager::URL,
			'options'     => array( 'url', 'is_external', 'nofollow' ),
			'default'     => array( 'url' => 'https://www.facebook.com/' ),
			'label_block' => true,
		) );

		$rep->add_control( 'item_bg', array(
			'label'     => __( 'Item Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} {{CURRENT_ITEM}}.stl-sl-rail-item'   => 'background-color: {{VALUE}};',
				'{{WRAPPER}} {{CURRENT_ITEM}}.stl-sl-radial-item a' => 'background-color: {{VALUE}};',
			),
		) );

		$rep->add_control( 'item_bg_hover', array(
			'label'     => __( 'Item Hover Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} {{CURRENT_ITEM}}.stl-sl-rail-item:hover'                   => 'background-color: {{VALUE}};',
				'{{WRAPPER}} {{CURRENT_ITEM}}.stl-sl-rail-item:hover .stl-sl-rail-icon svg' => 'fill: {{VALUE}};',
				'{{WRAPPER}} {{CURRENT_ITEM}}.stl-sl-rail-item:hover .stl-sl-rail-icon i'   => 'color: {{VALUE}};',
				'{{WRAPPER}} {{CURRENT_ITEM}}.stl-sl-radial-item a:hover'              => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'items', array(
			'label'       => __( 'Items', 'stl-addons' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ title }}}',
			'default'     => array(
				array(
					'title' => __( 'Facebook', 'stl-addons' ),
					'icon'  => array( 'value' => 'fab fa-facebook-f', 'library' => 'fa-brands' ),
					'link'  => array( 'url' => 'https://www.facebook.com/' ),
				),
				array(
					'title' => __( 'X', 'stl-addons' ),
					'icon'  => array( 'value' => 'fab fa-twitter', 'library' => 'fa-brands' ),
					'link'  => array( 'url' => 'https://twitter.com/' ),
				),
				array(
					'title' => __( 'Instagram', 'stl-addons' ),
					'icon'  => array( 'value' => 'fab fa-instagram', 'library' => 'fa-brands' ),
					'link'  => array( 'url' => 'https://www.instagram.com/' ),
				),
				array(
					'title' => __( 'LinkedIn', 'stl-addons' ),
					'icon'  => array( 'value' => 'fab fa-linkedin-in', 'library' => 'fa-brands' ),
					'link'  => array( 'url' => 'https://www.linkedin.com/' ),
				),
			),
		) );

		$this->end_controls_section();
	}

	/* ============================================================
	 * Rail — layout
	 * ============================================================ */
	private function controls_rail_layout() {
		$this->start_controls_section( 'sec_rail_layout', array(
			'label'     => __( 'Rail — Layout', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'style_variant' => 'rail' ),
		) );

		$this->add_responsive_control( 'rail_offset_x', array_merge(
			array(
				'label'      => __( 'Edge Offset (X)', 'stl-addons' ),
				'type'       => Controls_Manager::SLIDER,
				'description' => __( 'How far the rail peeks out from the edge before hover.', 'stl-addons' ),
				'selectors'  => array(
					'{{WRAPPER}} .stl-sl-rail .stl-sl-rail-list' => 'transform: translateX({{SIZE}}{{UNIT}});',
				),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'rail_z_index', array(
			'label'     => __( 'Z-index', 'stl-addons' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 0,
			'max'       => 9999,
			'selectors' => array( '{{WRAPPER}} .stl-sl-rail' => 'z-index: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	/* ============================================================
	 * Rail — item
	 * ============================================================ */
	private function controls_rail_item() {
		$this->start_controls_section( 'sec_rail_item', array(
			'label'     => __( 'Rail — Item', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'style_variant' => 'rail' ),
		) );

		$this->add_responsive_control( 'rail_item_width', array_merge(
			array(
				'label'     => __( 'Width', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-sl-rail-item' => 'width: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'rail_item_padding', array(
			'label'      => __( 'Padding', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-sl-rail-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'rail_item_margin', array(
			'label'      => __( 'Margin', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-sl-rail-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'rail_item_radius', array(
			'label'      => __( 'Border Radius', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-sl-rail-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'rail_item_hover_x', array_merge(
			array(
				'label'     => __( 'Hover Slide (X)', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'description' => __( 'How far the item slides out on hover. Use a negative value for the right rail.', 'stl-addons' ),
				'selectors' => array(
					'{{WRAPPER}} .stl-sl-rail-item:hover' => 'transform: translateX({{SIZE}}{{UNIT}});',
				),
			),
			$this->std_slider_args()
		) );

		$this->end_controls_section();
	}

	/* ============================================================
	 * Rail — label
	 * ============================================================ */
	private function controls_rail_label() {
		$this->start_controls_section( 'sec_rail_label', array(
			'label'     => __( 'Rail — Label', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'style_variant' => 'rail' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'rail_label_typography',
			'selector' => '{{WRAPPER}} .stl-sl-rail-item a',
		) );

		$this->add_control( 'rail_label_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-sl-rail-item a' => 'color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'rail_label_gap', array_merge(
			array(
				'label'     => __( 'Gap', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array(
					'{{WRAPPER}} .stl-sl-rail-item a' => 'gap: {{SIZE}}{{UNIT}};',
				),
			),
			$this->std_slider_args()
		) );

		$this->end_controls_section();
	}

	/* ============================================================
	 * Rail — icon
	 * ============================================================ */
	private function controls_rail_icon() {
		$this->start_controls_section( 'sec_rail_icon', array(
			'label'     => __( 'Rail — Icon', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'style_variant' => 'rail' ),
		) );

		$this->add_responsive_control( 'rail_icon_size', array_merge(
			array(
				'label'     => __( 'Icon Size', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array(
					'{{WRAPPER}} .stl-sl-rail-icon i'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .stl-sl-rail-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'rail_icon_width', array_merge(
			array(
				'label'     => __( 'Icon Box Width', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-sl-rail-icon' => 'width: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'rail_icon_height', array_merge(
			array(
				'label'     => __( 'Icon Box Height', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-sl-rail-icon' => 'height: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'rail_icon_padding', array(
			'label'      => __( 'Icon Box Padding', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-sl-rail-icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'rail_icon_radius', array(
			'label'      => __( 'Icon Box Radius', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-sl-rail-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_control( 'rail_icon_color', array(
			'label'     => __( 'Icon Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-sl-rail-icon i'   => 'color: {{VALUE}};',
				'{{WRAPPER}} .stl-sl-rail-icon svg' => 'fill: {{VALUE}};',
			),
		) );

		$this->add_control( 'rail_icon_bg', array(
			'label'     => __( 'Icon Box Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-sl-rail-icon' => 'background-color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	/* ============================================================
	 * Radial — layout
	 * ============================================================ */
	private function controls_radial_layout() {
		$this->start_controls_section( 'sec_radial_layout', array(
			'label'     => __( 'Radial — Layout', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'style_variant' => 'radial' ),
		) );

		$this->add_control( 'radial_hide_last', array(
			'label'        => __( 'Show only 3 items (bottom-right)', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'description'  => __( 'Hides the 4th item — handy when the corner is too close to the page edge.', 'stl-addons' ),
			'return_value' => 'yes',
			'default'      => '',
			'condition'    => array( 'radial_corner' => 'bottom-right' ),
		) );

		$this->add_control( 'radial_hide_first', array(
			'label'        => __( 'Show only 3 items (bottom-left)', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
			'condition'    => array( 'radial_corner' => 'bottom-left' ),
		) );

		$this->add_responsive_control( 'radial_offset_x_right', array_merge(
			array(
				'label'     => __( 'Distance from Left Edge', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'condition' => array( 'radial_corner' => 'bottom-right' ),
				'selectors' => array(
					'{{WRAPPER}} .stl-sl-radial.is-bottom-right' => 'left: {{SIZE}}{{UNIT}};',
				),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'radial_offset_x_left', array_merge(
			array(
				'label'     => __( 'Distance from Right Edge', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'condition' => array( 'radial_corner' => 'bottom-left' ),
				'selectors' => array(
					'{{WRAPPER}} .stl-sl-radial.is-bottom-left' => 'right: {{SIZE}}{{UNIT}};',
				),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'radial_offset_y', array_merge(
			array(
				'label'     => __( 'Distance from Bottom', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-sl-radial' => 'bottom: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'radial_z_index', array(
			'label'     => __( 'Z-index', 'stl-addons' ),
			'type'      => Controls_Manager::NUMBER,
			'min'       => 0,
			'max'       => 9999,
			'selectors' => array( '{{WRAPPER}} .stl-sl-radial' => 'z-index: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	/* ============================================================
	 * Radial — trigger button
	 * ============================================================ */
	private function controls_radial_trigger() {
		$this->start_controls_section( 'sec_radial_trigger', array(
			'label'     => __( 'Radial — Trigger', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'style_variant' => 'radial' ),
		) );

		$this->add_responsive_control( 'radial_btn_size', array_merge(
			array(
				'label'     => __( 'Button Size', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array(
					'{{WRAPPER}} .stl-sl-radial-trigger' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'radial_btn_icon_size', array_merge(
			array(
				'label'     => __( 'Icon Size', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array(
					'{{WRAPPER}} .stl-sl-radial-trigger .stl-sl-bubble'      => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .stl-sl-radial-trigger .stl-sl-btn-icon i'  => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .stl-sl-radial-trigger .stl-sl-btn-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			),
			$this->std_slider_args()
		) );

		$this->start_controls_tabs( 'radial_btn_state' );

		// Normal
		$this->start_controls_tab( 'radial_btn_normal', array( 'label' => __( 'Normal', 'stl-addons' ) ) );

		$this->add_control( 'radial_btn_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-sl-radial-trigger' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'radial_btn_color', array(
			'label'     => __( 'Icon Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-sl-radial-trigger svg'                          => 'fill: {{VALUE}};',
				'{{WRAPPER}} .stl-sl-radial-trigger i'                            => 'color: {{VALUE}};',
				'{{WRAPPER}} .stl-sl-radial-trigger .stl-sl-bubble .line'         => 'stroke: {{VALUE}};',
				'{{WRAPPER}} .stl-sl-radial-trigger .stl-sl-bubble .stl-sl-dot'   => 'fill: {{VALUE}};',
			),
		) );

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'radial_btn_shadow',
			'selector' => '{{WRAPPER}} .stl-sl-radial-trigger',
		) );

		$this->end_controls_tab();

		// Active
		$this->start_controls_tab( 'radial_btn_active', array( 'label' => __( 'Active', 'stl-addons' ) ) );

		$this->add_control( 'radial_btn_bg_active', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-sl-radial.is-active .stl-sl-radial-trigger' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'radial_btn_color_active', array(
			'label'     => __( 'Icon Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-sl-radial.is-active .stl-sl-radial-trigger svg'                          => 'fill: {{VALUE}};',
				'{{WRAPPER}} .stl-sl-radial.is-active .stl-sl-radial-trigger i'                            => 'color: {{VALUE}};',
				'{{WRAPPER}} .stl-sl-radial.is-active .stl-sl-radial-trigger .stl-sl-bubble .line'         => 'stroke: {{VALUE}};',
				'{{WRAPPER}} .stl-sl-radial.is-active .stl-sl-radial-trigger .stl-sl-bubble .stl-sl-dot'   => 'fill: {{VALUE}};',
			),
		) );

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'radial_btn_shadow_active',
			'selector' => '{{WRAPPER}} .stl-sl-radial.is-active .stl-sl-radial-trigger',
		) );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/* ============================================================
	 * Radial — item
	 * ============================================================ */
	private function controls_radial_item() {
		$this->start_controls_section( 'sec_radial_item', array(
			'label'     => __( 'Radial — Item', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'style_variant' => 'radial' ),
		) );

		$this->add_responsive_control( 'radial_item_size', array_merge(
			array(
				'label'     => __( 'Item Button Size', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array(
					'{{WRAPPER}} .stl-sl-radial .stl-sl-radial-item'           => '--stl-sl-point-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .stl-sl-radial.is-active .stl-sl-radial-item' => '--stl-sl-point-size: {{SIZE}}{{UNIT}};',
				),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'radial_icon_size', array_merge(
			array(
				'label'     => __( 'Icon Size', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array(
					'{{WRAPPER}} .stl-sl-radial .stl-sl-radial-icon i'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .stl-sl-radial .stl-sl-radial-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			),
			$this->std_slider_args()
		) );

		$this->add_control( 'radial_item_color', array(
			'label'     => __( 'Icon Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-sl-radial .stl-sl-radial-icon i'   => 'color: {{VALUE}};',
				'{{WRAPPER}} .stl-sl-radial .stl-sl-radial-icon svg' => 'fill: {{VALUE}};',
			),
		) );

		$this->end_controls_section();
	}

	/* ============================================================
	 * Render
	 * ============================================================ */
	protected function render() {
		$s       = $this->get_settings_for_display();
		$variant = ( 'radial' === ( $s['style_variant'] ?? 'rail' ) ) ? 'radial' : 'rail';
		$items   = ( isset( $s['items'] ) && is_array( $s['items'] ) ) ? $s['items'] : array();

		if ( empty( $items ) ) {
			printf(
				'<p class="stl-sl-empty">%s</p>',
				esc_html__( 'Add a social item from the Content tab → Items.', 'stl-addons' )
			);
			return;
		}

		if ( 'rail' === $variant ) {
			$side = ( 'left' === ( $s['rail_side'] ?? 'right' ) ) ? 'left' : 'right';
			?>
			<div class="stl-sl-rail is-<?php echo esc_attr( $side ); ?>">
				<ul class="stl-sl-rail-list">
					<?php foreach ( $items as $item ) :
						$label = $item['title'] ?? '';
						$icon  = $item['icon']  ?? array();
						$link  = isset( $item['link'] ) && is_array( $item['link'] ) ? $item['link'] : array();
						$id    = $item['_id'] ?? '';
						$this->add_link_attributes( 'link_' . $id, $link );
						?>
						<li class="stl-sl-rail-item elementor-repeater-item-<?php echo esc_attr( $id ); ?>">
							<a <?php echo $this->get_render_attribute_string( 'link_' . $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
								<?php if ( 'left' === $side ) : ?>
									<span class="stl-sl-rail-label"><?php echo esc_html( $label ); ?></span>
									<span class="stl-sl-rail-icon" aria-hidden="true"><?php \Elementor\Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) ); ?></span>
								<?php else : ?>
									<span class="stl-sl-rail-icon" aria-hidden="true"><?php \Elementor\Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) ); ?></span>
									<span class="stl-sl-rail-label"><?php echo esc_html( $label ); ?></span>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
			return;
		}

		// Radial.
		$corner   = ( 'bottom-left' === ( $s['radial_corner'] ?? 'bottom-right' ) ) ? 'bottom-left' : 'bottom-right';
		$btn_kind = ( 'custom' === ( $s['radial_btn_icon_kind'] ?? 'bubble' ) ) ? 'custom' : 'bubble';
		$hide_idx = '';
		if ( 'bottom-right' === $corner && 'yes' === ( $s['radial_hide_last'] ?? '' ) ) {
			$hide_idx = 'hide-last';
		} elseif ( 'bottom-left' === $corner && 'yes' === ( $s['radial_hide_first'] ?? '' ) ) {
			$hide_idx = 'hide-first';
		}
		?>
		<div class="stl-sl-radial is-<?php echo esc_attr( $corner ); ?><?php if ( $hide_idx ) : ?> <?php echo esc_attr( $hide_idx ); ?><?php endif; ?>">
			<a class="stl-sl-radial-trigger" href="#" role="button" aria-expanded="false" aria-label="<?php esc_attr_e( 'Toggle social links', 'stl-addons' ); ?>">
				<?php if ( 'bubble' === $btn_kind ) : ?>
					<svg class="stl-sl-bubble" width="80" height="80" viewBox="0 0 100 100" aria-hidden="true">
						<g class="stl-sl-bubble-shape">
							<path class="line line1" d="M 30.7873,85.113394 30.7873,46.556405 C 30.7873,41.101961 36.826342,35.342 40.898074,35.342 H 59.113981 C 63.73287,35.342 69.29995,40.103201 69.29995,46.784744"/>
							<path class="line line2" d="M 13.461999,65.039335 H 58.028684 C 63.483128,65.039335 69.243089,59.000293 69.243089,54.928561 V 45.605853 C 69.243089,40.986964 65.02087,35.419884 58.339327,35.419884"/>
						</g>
						<circle class="stl-sl-dot" r="1.9" cy="50.7" cx="42.5"/>
						<circle class="stl-sl-dot" cx="49.9" cy="50.7" r="1.9"/>
						<circle class="stl-sl-dot" r="1.9" cy="50.7" cx="57.3"/>
					</svg>
				<?php else :
					$btn_icon = $s['radial_btn_icon'] ?? array(); ?>
					<span class="stl-sl-btn-icon" aria-hidden="true"><?php \Elementor\Icons_Manager::render_icon( $btn_icon, array( 'aria-hidden' => 'true' ) ); ?></span>
				<?php endif; ?>
			</a>
			<ul class="stl-sl-radial-menu">
				<?php foreach ( $items as $item ) :
					$icon = $item['icon'] ?? array();
					$link = isset( $item['link'] ) && is_array( $item['link'] ) ? $item['link'] : array();
					$id   = $item['_id'] ?? '';
					$this->add_link_attributes( 'rlink_' . $id, $link );
					?>
					<li class="stl-sl-radial-item elementor-repeater-item-<?php echo esc_attr( $id ); ?>">
						<a <?php echo $this->get_render_attribute_string( 'rlink_' . $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( $item['title'] ?? '' ); ?>">
							<span class="stl-sl-radial-icon" aria-hidden="true"><?php \Elementor\Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}
