<?php
/**
 * Button Elementor Widget.
 *
 * Two animated button styles: a layered "push" button (Style 1) and a
 * bordered "frame draw" button (Style 2).
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
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

class STL_Widget_Button extends Widget_Base {

	public function get_name()           { return 'stl_button'; }
	public function get_title()          { return __( 'Button', 'stl-addons' ); }
	public function get_icon()           { return 'eicon-button'; }
	public function get_categories()     { return array( 'stl-addons', 'general' ); }
	public function get_keywords()       { return array( 'button', 'cta', 'link', 'stl' ); }
	public function get_style_depends()  { return array( 'stl-button' ); }

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	public function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' stl-widget';
	}

	protected function register_controls() {
		$this->controls_content();
		$this->controls_layout();
		$this->controls_style();
	}

	private function controls_content() {
		$this->start_controls_section( 'sec_content', array(
			'label' => __( 'Button', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'style_variant', array(
			'label'   => __( 'Style', 'stl-addons' ),
			'type'    => Controls_Manager::SELECT,
			'default' => '1',
			'options' => array(
				'1' => __( 'Style 1 — Layered Push', 'stl-addons' ),
				'2' => __( 'Style 2 — Frame Draw', 'stl-addons' ),
			),
		) );

		$this->add_control( 'title', array(
			'label'       => __( 'Button Text', 'stl-addons' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => __( 'Click Here', 'stl-addons' ),
			'label_block' => true,
		) );

		$this->add_control( 'link', array(
			'label'       => __( 'Link', 'stl-addons' ),
			'type'        => Controls_Manager::URL,
			'options'     => array( 'url', 'is_external', 'nofollow' ),
			'default'     => array( 'url' => '#' ),
			'label_block' => true,
		) );

		$this->end_controls_section();
	}

	private function controls_layout() {
		$this->start_controls_section( 'sec_layout', array(
			'label' => __( 'Layout', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'align', array(
			'label'     => __( 'Alignment', 'stl-addons' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'left'   => array( 'title' => __( 'Left', 'stl-addons' ),   'icon' => 'eicon-text-align-left' ),
				'center' => array( 'title' => __( 'Center', 'stl-addons' ), 'icon' => 'eicon-text-align-center' ),
				'right'  => array( 'title' => __( 'Right', 'stl-addons' ),  'icon' => 'eicon-text-align-right' ),
			),
			'default'   => 'left',
			'selectors' => array( '{{WRAPPER}}' => 'text-align: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'typography',
			'selector' => '{{WRAPPER}} .stl-btn',
		) );

		$this->add_responsive_control( 'padding', array(
			'label'      => __( 'Padding', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'margin', array(
			'label'      => __( 'Margin', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-btn' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'offset', array(
			'label'      => __( 'Style 1 — Layer Offset', 'stl-addons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'em', 'rem' ),
			'range'      => array(
				'px'  => array( 'min' => 0, 'max' => 40, 'step' => 1 ),
				'em'  => array( 'min' => 0, 'max' => 5,  'step' => 0.1 ),
				'rem' => array( 'min' => 0, 'max' => 5,  'step' => 0.1 ),
			),
			'condition'  => array( 'style_variant' => '1' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-btn-style-1 .stl-btn-layer-back' => '--stl-btn-offset-x: {{SIZE}}{{UNIT}}; --stl-btn-offset-y: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'border_thickness', array(
			'label'      => __( 'Style 2 — Frame Thickness', 'stl-addons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 1, 'max' => 8, 'step' => 1 ) ),
			'condition'  => array( 'style_variant' => '2' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-btn-style-2::after, {{WRAPPER}} .stl-btn-style-2::before' => 'height: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} .stl-btn-style-2 .stl-btn-text::after, {{WRAPPER}} .stl-btn-style-2 .stl-btn-text::before' => 'width: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();
	}

	private function controls_style() {
		$this->start_controls_section( 'sec_style', array(
			'label' => __( 'Colors', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->start_controls_tabs( 'tabs_state' );

		// Normal.
		$this->start_controls_tab( 'tab_normal', array( 'label' => __( 'Normal', 'stl-addons' ) ) );

		$this->add_control( 'text_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-btn'                       => 'color: {{VALUE}};',
				'{{WRAPPER}} .stl-btn-style-2 .stl-btn-text' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'face_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-btn-style-1 .stl-btn-layer-front' => 'background: {{VALUE}};',
				'{{WRAPPER}} .stl-btn-style-2'                      => 'background: {{VALUE}};',
			),
		) );

		$this->add_control( 'back_bg', array(
			'label'     => __( 'Shadow Layer', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'condition' => array( 'style_variant' => '1' ),
			'selectors' => array(
				'{{WRAPPER}} .stl-btn-style-1 .stl-btn-layer-back' => 'background: {{VALUE}};',
			),
		) );

		$this->add_control( 'frame_color', array(
			'label'     => __( 'Frame Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'condition' => array( 'style_variant' => '2' ),
			'selectors' => array(
				'{{WRAPPER}} .stl-btn-style-2::after, {{WRAPPER}} .stl-btn-style-2::before, {{WRAPPER}} .stl-btn-style-2 .stl-btn-text::after, {{WRAPPER}} .stl-btn-style-2 .stl-btn-text::before' => 'background: {{VALUE}};',
			),
		) );

		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'      => 'border',
			'selector'  => '{{WRAPPER}} .stl-btn-style-1 .stl-btn-layer-front',
			'condition' => array( 'style_variant' => '1' ),
		) );

		$this->end_controls_tab();

		// Hover.
		$this->start_controls_tab( 'tab_hover', array( 'label' => __( 'Hover', 'stl-addons' ) ) );

		$this->add_control( 'text_color_hover', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-btn:hover'                       => 'color: {{VALUE}};',
				'{{WRAPPER}} .stl-btn-style-2:hover .stl-btn-text' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'face_bg_hover', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-btn-style-1:hover .stl-btn-layer-front' => 'background: {{VALUE}};',
				'{{WRAPPER}} .stl-btn-style-2:hover'                      => 'background: {{VALUE}};',
			),
		) );

		$this->add_control( 'frame_color_hover', array(
			'label'     => __( 'Frame Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'condition' => array( 'style_variant' => '2' ),
			'selectors' => array(
				'{{WRAPPER}} .stl-btn-style-2:hover::after, {{WRAPPER}} .stl-btn-style-2:hover::before, {{WRAPPER}} .stl-btn-style-2:hover .stl-btn-text::after, {{WRAPPER}} .stl-btn-style-2:hover .stl-btn-text::before' => 'background: {{VALUE}};',
			),
		) );

		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'      => 'border_hover',
			'selector'  => '{{WRAPPER}} .stl-btn-style-1:hover .stl-btn-layer-front',
			'condition' => array( 'style_variant' => '1' ),
		) );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function render() {
		$s        = $this->get_settings_for_display();
		$title    = $s['title'] ?? '';
		$variant  = ( '2' === ( $s['style_variant'] ?? '1' ) ) ? '2' : '1';
		$link     = isset( $s['link'] ) && is_array( $s['link'] ) ? $s['link'] : array();
		$has_link = ! empty( $link['url'] );

		$this->add_render_attribute( 'btn', 'class', array( 'stl-btn', 'stl-btn-style-' . $variant ) );

		if ( $has_link ) {
			$this->add_link_attributes( 'btn', $link );
			$tag = 'a';
		} else {
			$this->add_render_attribute( 'btn', 'type', 'button' );
			$tag = 'button';
		}

		printf( '<%1$s %2$s>', $tag, $this->get_render_attribute_string( 'btn' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( '1' === $variant ) {
			echo '<span class="stl-btn-layer-back" aria-hidden="true"></span>';
			echo '<span class="stl-btn-layer-front" aria-hidden="true"></span>';
		}

		printf( '<span class="stl-btn-text">%s</span>', esc_html( $title ) );

		printf( '</%s>', $tag ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
