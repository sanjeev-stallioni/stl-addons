<?php
/**
 * Timeline Elementor Widget.
 *
 * Vertical alternating timeline with year / title / description and circular
 * media. The accent color is scoped to the widget wrapper via a CSS variable.
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
use Elementor\Utils;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;

class STL_Widget_Timeline extends Widget_Base {

	const ALLOWED_HEADING_TAGS = array( 'h2', 'h3', 'h4', 'h5', 'h6' );

	public function get_name()           { return 'stl_timeline'; }
	public function get_title()          { return __( 'Timeline', 'stl-addons' ); }
	public function get_icon()           { return 'eicon-time-line'; }
	public function get_categories()     { return array( 'stl-addons', 'general' ); }
	public function get_keywords()       { return array( 'timeline', 'history', 'milestone', 'story', 'stl' ); }
	public function get_style_depends()  { return array( 'stl-timeline' ); }

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	public function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' stl-widget stl-timeline';
	}

	protected function register_controls() {
		$this->controls_items();
		$this->controls_layout();
		$this->controls_year_style();
		$this->controls_title_style();
		$this->controls_description_style();
		$this->controls_media_style();
	}

	private function controls_items() {
		$this->start_controls_section( 'sec_items', array(
			'label' => __( 'Timeline', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'title_tag', array(
			'label'       => __( 'Title HTML Tag', 'stl-addons' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'h3',
			'options'     => array(
				'h2' => 'H2',
				'h3' => 'H3',
				'h4' => 'H4',
				'h5' => 'H5',
				'h6' => 'H6',
			),
			'description' => __( 'Pick the heading level that matches your page outline.', 'stl-addons' ),
		) );

		$rep = new Repeater();

		$rep->add_control( 'year', array(
			'label'       => __( 'Year', 'stl-addons' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => '2018',
			'label_block' => true,
		) );

		$rep->add_control( 'title', array(
			'label'       => __( 'Title', 'stl-addons' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => __( 'Milestone title', 'stl-addons' ),
			'label_block' => true,
		) );

		$rep->add_control( 'description', array(
			'label'   => __( 'Description', 'stl-addons' ),
			'type'    => Controls_Manager::TEXTAREA,
			'default' => __( 'A short description of what happened at this point in time.', 'stl-addons' ),
		) );

		$rep->add_control( 'image', array(
			'label'   => __( 'Image', 'stl-addons' ),
			'type'    => Controls_Manager::MEDIA,
			'default' => array( 'url' => Utils::get_placeholder_image_src() ),
		) );

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'image_size',
				'default'   => 'medium',
				'separator' => 'before',
			)
		);

		$this->add_control( 'items', array(
			'label'       => __( 'Items', 'stl-addons' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'default'     => array(
				array(
					'year'        => '2018',
					'title'       => __( 'The beginning', 'stl-addons' ),
					'description' => __( 'A short description of what happened at this point in time.', 'stl-addons' ),
				),
				array(
					'year'        => '2020',
					'title'       => __( 'Growing up', 'stl-addons' ),
					'description' => __( 'A short description of what happened at this point in time.', 'stl-addons' ),
				),
				array(
					'year'        => '2023',
					'title'       => __( 'New chapter', 'stl-addons' ),
					'description' => __( 'A short description of what happened at this point in time.', 'stl-addons' ),
				),
			),
			'title_field' => '{{{ year }}} — {{{ title }}}',
		) );

		$this->end_controls_section();
	}

	private function controls_layout() {
		$this->start_controls_section( 'sec_layout', array(
			'label' => __( 'Layout', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'theme_color', array(
			'label'     => __( 'Accent Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}}' => '--stl-timeline-accent: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'item_gap', array(
			'label'      => __( 'Gap Between Items', 'stl-addons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'em', 'rem' ),
			'range'      => array(
				'px'  => array( 'min' => 0, 'max' => 200, 'step' => 1 ),
				'em'  => array( 'min' => 0, 'max' => 20 ),
				'rem' => array( 'min' => 0, 'max' => 20 ),
			),
			'selectors'  => array(
				'{{WRAPPER}} .stl-timeline-item:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'wrapper_padding', array(
			'label'      => __( 'Inner Padding', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-timeline-items' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();
	}

	private function controls_year_style() {
		$this->start_controls_section( 'sec_year', array(
			'label' => __( 'Year', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'year_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-timeline-year' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'year_typography',
			'selector' => '{{WRAPPER}} .stl-timeline-year',
		) );

		$this->add_responsive_control( 'year_margin', array(
			'label'      => __( 'Margin', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-timeline-year' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
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
			'selectors' => array( '{{WRAPPER}} .stl-timeline-title' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'title_typography',
			'selector' => '{{WRAPPER}} .stl-timeline-title',
		) );

		$this->add_responsive_control( 'title_margin', array(
			'label'      => __( 'Margin', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-timeline-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();
	}

	private function controls_description_style() {
		$this->start_controls_section( 'sec_desc', array(
			'label' => __( 'Description', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'desc_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-timeline-desc' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'desc_typography',
			'selector' => '{{WRAPPER}} .stl-timeline-desc',
		) );

		$this->add_responsive_control( 'desc_margin', array(
			'label'      => __( 'Margin', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-timeline-desc' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();
	}

	private function controls_media_style() {
		$this->start_controls_section( 'sec_media', array(
			'label' => __( 'Media', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'media_size', array(
			'label'      => __( 'Circle Size', 'stl-addons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 80, 'max' => 400, 'step' => 1 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-timeline-media > div' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->add_control( 'media_border_color', array(
			'label'     => __( 'Border Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-timeline-media > div' => 'border-color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$s        = $this->get_settings_for_display();
		$items    = ( isset( $s['items'] ) && is_array( $s['items'] ) ) ? $s['items'] : array();
		$tag_raw  = $s['title_tag'] ?? 'h3';
		$title_tag = in_array( $tag_raw, self::ALLOWED_HEADING_TAGS, true ) ? $tag_raw : 'h3';
		?>
		<?php if ( empty( $items ) ) : ?>
			<p class="stl-timeline-empty"><?php esc_html_e( 'Add timeline items from the Content tab → Timeline.', 'stl-addons' ); ?></p>
		<?php else : ?>
			<div class="stl-timeline-inner">
				<span class="stl-timeline-cap stl-timeline-cap-top" aria-hidden="true"></span>
				<div class="stl-timeline-items">
					<?php foreach ( $items as $item ) :
						$year  = $item['year']        ?? '';
						$title = $item['title']       ?? '';
						$desc  = $item['description'] ?? '';
						$image = isset( $item['image'] ) && is_array( $item['image'] ) ? $item['image'] : array();
						$image_settings = array_merge( $s, array( 'image' => $image ) );
						?>
						<div class="stl-timeline-item">
							<div class="stl-timeline-content">
								<?php if ( $year ) : ?>
									<div class="stl-timeline-year"><?php echo esc_html( $year ); ?></div>
								<?php endif; ?>
								<?php if ( $title ) : ?>
									<<?php echo $title_tag; ?> class="stl-timeline-title"><?php echo esc_html( $title ); ?></<?php echo $title_tag; ?>>
								<?php endif; ?>
								<?php if ( $desc ) : ?>
									<p class="stl-timeline-desc"><?php echo esc_html( $desc ); ?></p>
								<?php endif; ?>
							</div>
							<span class="stl-timeline-pointer" aria-hidden="true"></span>
							<div class="stl-timeline-media">
								<?php if ( ! empty( $image['id'] ) || ! empty( $image['url'] ) ) : ?>
									<div>
										<?php
										if ( ! empty( $image['id'] ) ) {
											echo Group_Control_Image_Size::get_attachment_image_html( $image_settings, 'image_size', 'image' );
										} else {
											printf(
												'<img src="%s" alt="%s" loading="lazy" decoding="async" />',
												esc_url( $image['url'] ),
												esc_attr( $title )
											);
										}
										?>
									</div>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<span class="stl-timeline-cap stl-timeline-cap-bottom" aria-hidden="true"></span>
			</div>
		<?php endif; ?>
		<?php
	}
}
