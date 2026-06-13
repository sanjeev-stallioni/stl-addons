<?php
/**
 * Post Grid Elementor Widget.
 *
 * A responsive grid of posts pulled live from the WordPress query — featured
 * image, date, title, excerpt and a "read more" link, matching the workshop
 * blog-card design. Editors control the source query (post type, categories,
 * tags), ordering, count and per-breakpoint columns from the panel.
 *
 * Optional, accessible client-side category filtering lets visitors narrow the
 * grid without a page reload — every post stays in the DOM, so it is fully
 * crawlable. Optional schema.org/BlogPosting microdata can be emitted per card
 * for richer search results.
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
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;

class STL_Widget_Post_Grid extends Widget_Base {

	const ALLOWED_HEADING_TAGS = array( 'h2', 'h3', 'h4', 'h5', 'h6' );

	public function get_name()           { return 'stl_post_grid'; }
	public function get_title()          { return __( 'Post Grid', 'stl-addons' ); }
	public function get_icon()           { return 'eicon-posts-grid'; }
	public function get_categories()     { return array( 'stl-addons', 'general' ); }
	public function get_keywords()       { return array( 'posts', 'blog', 'grid', 'articles', 'news', 'loop', 'query', 'stl' ); }
	public function get_style_depends()  { return array( 'stl-post-grid' ); }
	public function get_script_depends() { return array( 'stl-post-grid' ); }

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	public function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' stl-widget stl-pg-section';
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

	/** Public, non-attachment post types as [ name => label ] for a SELECT. */
	private function post_type_options() {
		$out = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
			if ( 'attachment' === $type->name ) {
				continue;
			}
			$out[ $type->name ] = $type->label;
		}
		return $out;
	}

	/** Terms of a taxonomy as [ term_id => name ] for a SELECT2. Bounded to 300. */
	private function term_options( $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}
		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'number'     => 300,
		) );
		$out = array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$out[ $term->term_id ] = $term->name;
			}
		}
		return $out;
	}

	/** Map a friendly "order by" preset to WP_Query orderby + order. */
	private function resolve_order( $key ) {
		switch ( $key ) {
			case 'date-asc':      return array( 'orderby' => 'date',          'order' => 'ASC' );
			case 'title-asc':     return array( 'orderby' => 'title',         'order' => 'ASC' );
			case 'title-desc':    return array( 'orderby' => 'title',         'order' => 'DESC' );
			case 'modified-desc': return array( 'orderby' => 'modified',      'order' => 'DESC' );
			case 'comment_count': return array( 'orderby' => 'comment_count', 'order' => 'DESC' );
			case 'menu_order':    return array( 'orderby' => 'menu_order',    'order' => 'ASC' );
			case 'rand':          return array( 'orderby' => 'rand',          'order' => 'DESC' );
			case 'date-desc':
			default:              return array( 'orderby' => 'date',          'order' => 'DESC' );
		}
	}

	protected function register_controls() {
		$this->controls_query();
		$this->controls_display();
		$this->controls_filter();
		$this->controls_layout();
		$this->controls_card();
		$this->controls_image_style();
		$this->controls_date_style();
		$this->controls_title_style();
		$this->controls_excerpt_style();
		$this->controls_more_style();
		$this->controls_filter_style();
	}

	/* ---------------------------------------------------------------- Content */

	private function controls_query() {
		$this->start_controls_section( 'sec_query', array(
			'label' => __( 'Query', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'post_type', array(
			'label'   => __( 'Source', 'stl-addons' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'post',
			'options' => $this->post_type_options(),
		) );

		$this->add_control( 'categories', array(
			'label'       => __( 'Categories', 'stl-addons' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'options'     => $this->term_options( 'category' ),
			'description' => __( 'Leave empty to include all categories.', 'stl-addons' ),
		) );

		$this->add_control( 'tags', array(
			'label'       => __( 'Tags', 'stl-addons' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'options'     => $this->term_options( 'post_tag' ),
			'description' => __( 'Leave empty to include all tags.', 'stl-addons' ),
		) );

		$this->add_control( 'tax_relation', array(
			'label'     => __( 'Match', 'stl-addons' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'OR',
			'options'   => array(
				'OR'  => __( 'Any selected category / tag', 'stl-addons' ),
				'AND' => __( 'All selected categories & tags', 'stl-addons' ),
			),
			'condition' => array( 'post_type' => 'post' ),
		) );

		$this->add_control( 'order_by', array(
			'label'   => __( 'Order By', 'stl-addons' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'date-desc',
			'options' => array(
				'date-desc'     => __( 'Newest First', 'stl-addons' ),
				'date-asc'      => __( 'Oldest First', 'stl-addons' ),
				'title-asc'     => __( 'Title A → Z', 'stl-addons' ),
				'title-desc'    => __( 'Title Z → A', 'stl-addons' ),
				'modified-desc' => __( 'Recently Updated', 'stl-addons' ),
				'comment_count' => __( 'Most Commented', 'stl-addons' ),
				'menu_order'    => __( 'Menu Order', 'stl-addons' ),
				'rand'          => __( 'Random', 'stl-addons' ),
			),
		) );

		$this->add_control( 'posts_per_page', array(
			'label'   => __( 'Number of Posts', 'stl-addons' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 6,
			'min'     => 1,
			'max'     => 48,
		) );

		$this->add_control( 'offset', array(
			'label'       => __( 'Offset', 'stl-addons' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'min'         => 0,
			'description' => __( 'Skip this many posts from the start of the result set.', 'stl-addons' ),
		) );

		$this->add_control( 'exclude_current', array(
			'label'        => __( 'Exclude Current Post', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'description'  => __( 'Avoids listing the post you are already viewing.', 'stl-addons' ),
		) );

		$this->add_control( 'ignore_sticky', array(
			'label'        => __( 'Ignore Sticky Posts', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'image_size',
				'default'   => 'large',
				'separator' => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function controls_display() {
		$this->start_controls_section( 'sec_display', array(
			'label' => __( 'Display', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'title_tag', array(
			'label'       => __( 'Title HTML Tag', 'stl-addons' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'h3',
			'options'     => array(
				'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6',
			),
			'description' => __( 'Pick the heading level that matches your page outline.', 'stl-addons' ),
		) );

		$this->add_control( 'show_image', array(
			'label'        => __( 'Show Featured Image', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_date', array(
			'label'        => __( 'Show Date', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'date_format', array(
			'label'       => __( 'Date Format', 'stl-addons' ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => get_option( 'date_format' ) ? get_option( 'date_format' ) : 'F j, Y',
			'description' => __( 'PHP date format. Leave empty to use the site default.', 'stl-addons' ),
			'condition'   => array( 'show_date' => 'yes' ),
		) );

		$this->add_control( 'show_excerpt', array(
			'label'        => __( 'Show Excerpt', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'excerpt_length', array(
			'label'     => __( 'Excerpt Length (words)', 'stl-addons' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 22,
			'min'       => 0,
			'max'       => 100,
			'condition' => array( 'show_excerpt' => 'yes' ),
		) );

		$this->add_control( 'show_more', array(
			'label'        => __( 'Show Read More', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'more_text', array(
			'label'     => __( 'Read More Text', 'stl-addons' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => __( 'Read article', 'stl-addons' ),
			'condition' => array( 'show_more' => 'yes' ),
		) );

		$this->add_control( 'more_arrow', array(
			'label'        => __( 'Show Arrow', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'condition'    => array( 'show_more' => 'yes' ),
		) );

		$this->add_control( 'schema', array(
			'label'        => __( 'Add BlogPosting Schema', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
			'separator'    => 'before',
			'description'  => __( 'Emits schema.org/BlogPosting microdata per card for richer search results.', 'stl-addons' ),
		) );

		$this->end_controls_section();
	}

	private function controls_filter() {
		$this->start_controls_section( 'sec_filter', array(
			'label' => __( 'Category Filter', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'show_filter', array(
			'label'        => __( 'Show Filter Bar', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
			'description'  => __( 'Adds accessible buttons that narrow the grid by category without a page reload. All posts stay in the page for SEO.', 'stl-addons' ),
		) );

		$this->add_control( 'filter_taxonomy', array(
			'label'     => __( 'Filter By', 'stl-addons' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'category',
			'options'   => array(
				'category' => __( 'Categories', 'stl-addons' ),
				'post_tag' => __( 'Tags', 'stl-addons' ),
			),
			'condition' => array( 'show_filter' => 'yes' ),
		) );

		$this->add_control( 'filter_all_label', array(
			'label'     => __( '"All" Button Label', 'stl-addons' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => __( 'All', 'stl-addons' ),
			'condition' => array( 'show_filter' => 'yes' ),
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
			'selectors'      => array( '{{WRAPPER}} .stl-pg-grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ),
		) );

		$this->add_responsive_control( 'gap', array_merge(
			array(
				'label'     => __( 'Gap', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-pg-grid' => 'gap: {{SIZE}}{{UNIT}};' ),
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
			'selectors' => array( '{{WRAPPER}} .stl-pg-post' => 'background: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'card_border',
			'selector' => '{{WRAPPER}} .stl-pg-post',
		) );

		$this->add_responsive_control( 'card_radius', array_merge(
			array(
				'label'     => __( 'Border Radius', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-pg-post' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'card_body_padding', array(
			'label'      => __( 'Body Padding', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'selectors'  => array( '{{WRAPPER}} .stl-pg-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'card_shadow',
			'selector' => '{{WRAPPER}} .stl-pg-post',
		) );

		$this->add_control( 'card_hover_border', array(
			'label'     => __( 'Hover Border Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pg-post:hover' => 'border-color: {{VALUE}};' ),
		) );

		$this->add_control( 'enable_lift', array(
			'label'                => __( 'Lift on Hover', 'stl-addons' ),
			'type'                 => Controls_Manager::SWITCHER,
			'return_value'         => 'yes',
			'default'              => 'yes',
			'selectors_dictionary' => array( 'yes' => 'translateY(-5px)', '' => 'none' ),
			'selectors'            => array( '{{WRAPPER}} .stl-pg-post:hover' => 'transform: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_image_style() {
		$this->start_controls_section( 'sec_image', array(
			'label'     => __( 'Image', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_image' => 'yes' ),
		) );

		$this->add_responsive_control( 'image_height', array(
			'label'      => __( 'Height', 'stl-addons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'vh', 'custom' ),
			'range'      => array(
				'px' => array( 'min' => 80, 'max' => 600 ),
				'vh' => array( 'min' => 5, 'max' => 80 ),
			),
			'selectors'  => array( '{{WRAPPER}} .stl-pg-ph' => 'height: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_control( 'image_fit', array(
			'label'     => __( 'Object Fit', 'stl-addons' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'cover',
			'options'   => array(
				'cover'   => __( 'Cover', 'stl-addons' ),
				'contain' => __( 'Contain', 'stl-addons' ),
			),
			'selectors' => array( '{{WRAPPER}} .stl-pg-ph img' => 'object-fit: {{VALUE}};' ),
		) );

		$this->add_control( 'enable_zoom', array(
			'label'                => __( 'Zoom on Hover', 'stl-addons' ),
			'type'                 => Controls_Manager::SWITCHER,
			'return_value'         => 'yes',
			'default'              => '',
			'selectors_dictionary' => array( 'yes' => 'scale(1.05)', '' => 'scale(1)' ),
			'selectors'            => array( '{{WRAPPER}} .stl-pg-post:hover .stl-pg-ph img' => 'transform: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_date_style() {
		$this->start_controls_section( 'sec_date', array(
			'label'     => __( 'Date', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_date' => 'yes' ),
		) );

		$this->add_control( 'date_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pg-date' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'date_typo',
			'selector' => '{{WRAPPER}} .stl-pg-date',
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
			'selectors' => array( '{{WRAPPER}} .stl-pg-title' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'title_hover_color', array(
			'label'     => __( 'Hover Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pg-post:hover .stl-pg-title' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'title_typo',
			'selector' => '{{WRAPPER}} .stl-pg-title',
		) );

		$this->end_controls_section();
	}

	private function controls_excerpt_style() {
		$this->start_controls_section( 'sec_excerpt', array(
			'label'     => __( 'Excerpt', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_excerpt' => 'yes' ),
		) );

		$this->add_control( 'excerpt_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pg-excerpt' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'excerpt_typo',
			'selector' => '{{WRAPPER}} .stl-pg-excerpt',
		) );

		$this->end_controls_section();
	}

	private function controls_more_style() {
		$this->start_controls_section( 'sec_more', array(
			'label'     => __( 'Read More', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_more' => 'yes' ),
		) );

		$this->add_control( 'more_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pg-more' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'more_underline_color', array(
			'label'     => __( 'Underline Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pg-more' => 'border-bottom-color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'more_typo',
			'selector' => '{{WRAPPER}} .stl-pg-more',
		) );

		$this->end_controls_section();
	}

	private function controls_filter_style() {
		$this->start_controls_section( 'sec_filter_style', array(
			'label'     => __( 'Filter Bar', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_filter' => 'yes' ),
		) );

		$this->add_responsive_control( 'filter_align', array(
			'label'     => __( 'Alignment', 'stl-addons' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => __( 'Left', 'stl-addons' ),   'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => __( 'Center', 'stl-addons' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => __( 'Right', 'stl-addons' ),  'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .stl-pg-filter' => 'justify-content: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'filter_spacing', array_merge(
			array(
				'label'     => __( 'Spacing Below', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-pg-filter' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_control( 'chip_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pg-chip' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'chip_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pg-chip' => 'background: {{VALUE}};' ),
		) );

		$this->add_control( 'chip_active_color', array(
			'label'     => __( 'Active Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pg-chip.is-active' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'chip_active_bg', array(
			'label'     => __( 'Active Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-pg-chip.is-active' => 'background: {{VALUE}}; border-color: {{VALUE}};',
			),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'chip_typo',
			'selector' => '{{WRAPPER}} .stl-pg-chip',
		) );

		$this->end_controls_section();
	}

	/* ----------------------------------------------------------------- Render */

	/** Build the WP_Query args from the panel settings. */
	private function build_query_args( $s ) {
		$post_type = $s['post_type'] ?? 'post';
		$order     = $this->resolve_order( $s['order_by'] ?? 'date-desc' );

		$args = array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, (int) ( $s['posts_per_page'] ?? 6 ) ),
			'offset'              => max( 0, (int) ( $s['offset'] ?? 0 ) ),
			'orderby'             => $order['orderby'],
			'order'               => $order['order'],
			'ignore_sticky_posts' => ( 'yes' === ( $s['ignore_sticky'] ?? '' ) ),
			'no_found_rows'       => true,
		);

		if ( 'yes' === ( $s['exclude_current'] ?? '' ) ) {
			$current = get_queried_object_id();
			if ( $current ) {
				$args['post__not_in'] = array( $current );
			}
		}

		if ( 'post' === $post_type ) {
			$tax_query = array();
			$cats = array_filter( array_map( 'intval', (array) ( $s['categories'] ?? array() ) ) );
			$tags = array_filter( array_map( 'intval', (array) ( $s['tags'] ?? array() ) ) );

			if ( $cats ) {
				$tax_query[] = array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => $cats );
			}
			if ( $tags ) {
				$tax_query[] = array( 'taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => $tags );
			}
			if ( count( $tax_query ) > 1 ) {
				$relation = ( 'AND' === ( $s['tax_relation'] ?? 'OR' ) ) ? 'AND' : 'OR';
				$tax_query['relation'] = $relation;
			}
			if ( $tax_query ) {
				$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			}
		}

		return $args;
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$args  = $this->build_query_args( $s );
		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			?>
			<p class="stl-pg-empty"><?php esc_html_e( 'No posts found for this query. Adjust the Query controls in the Content tab.', 'stl-addons' ); ?></p>
			<?php
			return;
		}

		$tag_raw     = $s['title_tag'] ?? 'h3';
		$title_tag   = in_array( $tag_raw, self::ALLOWED_HEADING_TAGS, true ) ? $tag_raw : 'h3';
		$show_image  = 'yes' === ( $s['show_image'] ?? 'yes' );
		$show_date   = 'yes' === ( $s['show_date'] ?? 'yes' );
		$show_exc    = 'yes' === ( $s['show_excerpt'] ?? 'yes' );
		$show_more   = 'yes' === ( $s['show_more'] ?? 'yes' );
		$more_arrow  = 'yes' === ( $s['more_arrow'] ?? 'yes' );
		$schema      = 'yes' === ( $s['schema'] ?? '' );
		$show_filter = 'yes' === ( $s['show_filter'] ?? '' );
		$filter_tax  = in_array( ( $s['filter_taxonomy'] ?? 'category' ), array( 'category', 'post_tag' ), true ) ? $s['filter_taxonomy'] : 'category';
		$date_fmt    = trim( (string) ( $s['date_format'] ?? '' ) );
		$exc_len     = (int) ( $s['excerpt_length'] ?? 22 );
		$more_text   = $s['more_text'] ?? __( 'Read article', 'stl-addons' );

		// First pass: collect post data and the set of filter terms actually present.
		$items     = array();
		$terms_set = array();
		foreach ( $query->posts as $post ) {
			$slugs = array();
			if ( $show_filter ) {
				$terms = get_the_terms( $post->ID, $filter_tax );
				if ( $terms && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $t ) {
						$slugs[] = $t->slug;
						$terms_set[ $t->slug ] = $t->name;
					}
				}
			}
			$items[] = array( 'post' => $post, 'slugs' => $slugs );
		}
		$has_filter = $show_filter && count( $terms_set ) > 1;
		?>
		<div class="stl-pg">
			<?php if ( $has_filter ) : ?>
				<div class="stl-pg-filter" role="group" aria-label="<?php esc_attr_e( 'Filter posts by category', 'stl-addons' ); ?>">
					<button type="button" class="stl-pg-chip is-active" data-filter="*" aria-pressed="true">
						<?php echo esc_html( $s['filter_all_label'] ?? __( 'All', 'stl-addons' ) ); ?>
					</button>
					<?php
					asort( $terms_set );
					foreach ( $terms_set as $slug => $name ) :
						?>
						<button type="button" class="stl-pg-chip" data-filter="<?php echo esc_attr( $slug ); ?>" aria-pressed="false">
							<?php echo esc_html( $name ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="stl-pg-grid"<?php echo $schema ? ' itemscope itemtype="https://schema.org/Blog"' : ''; ?>>
				<?php
				foreach ( $items as $item ) :
					$post      = $item['post'];
					$pid       = $post->ID;
					$permalink = get_permalink( $post );
					$title     = get_the_title( $post );
					$thumb_id  = $show_image ? get_post_thumbnail_id( $pid ) : 0;
					$data_cats = $has_filter ? implode( ' ', array_map( 'sanitize_html_class', $item['slugs'] ) ) : '';

					$excerpt = '';
					if ( $show_exc ) {
						$excerpt = has_excerpt( $post )
							? get_the_excerpt( $post )
							: wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), $exc_len, '&hellip;' );
					}
					?>
					<a class="stl-pg-post"
						href="<?php echo esc_url( $permalink ); ?>"
						<?php echo $has_filter ? ' data-cats="' . esc_attr( $data_cats ) . '"' : ''; ?>
						<?php echo $schema ? ' itemprop="blogPost" itemscope itemtype="https://schema.org/BlogPosting"' : ''; ?>>

						<?php if ( $schema ) : ?>
							<meta itemprop="mainEntityOfPage" content="<?php echo esc_url( $permalink ); ?>" />
						<?php endif; ?>

						<?php if ( $show_image && $thumb_id ) : ?>
							<div class="stl-pg-ph">
								<?php
								$img_settings = array_merge( $s, array( 'stl_pg_img' => array(
									'id'  => $thumb_id,
									'url' => wp_get_attachment_image_url( $thumb_id, 'full' ),
								) ) );
								echo Group_Control_Image_Size::get_attachment_image_html( $img_settings, 'image_size', 'stl_pg_img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor helper returns escaped markup.
								if ( $schema ) {
									$img_src = wp_get_attachment_image_url( $thumb_id, 'large' );
									if ( $img_src ) {
										echo '<meta itemprop="image" content="' . esc_url( $img_src ) . '" />';
									}
								}
								?>
							</div>
						<?php endif; ?>

						<div class="stl-pg-body">
							<?php if ( $show_date ) : ?>
								<time class="stl-pg-date" datetime="<?php echo esc_attr( get_the_date( 'c', $post ) ); ?>"<?php echo $schema ? ' itemprop="datePublished"' : ''; ?>>
									<?php echo esc_html( get_the_date( $date_fmt, $post ) ); ?>
								</time>
							<?php endif; ?>

							<?php if ( $title ) : ?>
								<<?php echo $title_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelisted above. ?> class="stl-pg-title"<?php echo $schema ? ' itemprop="headline"' : ''; ?>><?php echo esc_html( $title ); ?></<?php echo $title_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<?php endif; ?>

							<?php if ( $show_exc && $excerpt ) : ?>
								<p class="stl-pg-excerpt"<?php echo $schema ? ' itemprop="description"' : ''; ?>><?php echo esc_html( $excerpt ); ?></p>
							<?php endif; ?>

							<?php if ( $show_more && $more_text ) : ?>
								<span class="stl-pg-more">
									<?php echo esc_html( $more_text ); ?>
									<?php if ( $more_arrow ) : ?>
										<span class="stl-pg-arrow" aria-hidden="true">&rarr;</span>
									<?php endif; ?>
								</span>
							<?php endif; ?>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		wp_reset_postdata();
	}
}
