<?php
/**
 * Featured Post Elementor Widget.
 *
 * One (or more) large editorial "featured" cards pulled live from WP_Query —
 * a split layout with a media panel (featured image or a decorative gradient
 * placeholder + "Featured" tag) beside a body holding a kicker, title, excerpt,
 * a date · reading-time meta line, and a "Read the guide" link.
 *
 * Editors control the source query (post type, categories, tags), ordering and
 * how many cards show. The media side can sit left or right, and the whole card
 * is one crawlable <a>. Optional schema.org/BlogPosting microdata per card.
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
use Elementor\Group_Control_Border;

class STL_Widget_Featured_Post extends Widget_Base {

	const ALLOWED_HEADING_TAGS = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );

	public function get_name()           { return 'stl_featured_post'; }
	public function get_title()          { return __( 'Featured Post', 'stl-addons' ); }
	public function get_icon()           { return 'eicon-featured-image'; }
	public function get_categories()     { return array( 'stl-addons', 'general' ); }
	public function get_keywords()       { return array( 'featured', 'post', 'blog', 'highlight', 'article', 'query', 'stl' ); }
	public function get_style_depends()  { return array( 'stl-featured-post' ); }

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	public function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' stl-widget stl-fp-section';
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

	/** Primary category-like term name for a post (used for the kicker). */
	private function primary_term_name( $post ) {
		$ordered = array_unique( array_merge( array( 'category' ), get_object_taxonomies( $post->post_type ) ) );
		foreach ( $ordered as $tax ) {
			if ( ! taxonomy_exists( $tax ) || 'post_tag' === $tax ) {
				continue;
			}
			$terms = get_the_terms( $post->ID, $tax );
			if ( $terms && ! is_wp_error( $terms ) ) {
				return $terms[0]->name;
			}
		}
		return '';
	}

	/** Estimated reading time in whole minutes (≈200 wpm), min 1. */
	private function reading_time( $post ) {
		$words = str_word_count( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) );
		return max( 1, (int) ceil( $words / 200 ) );
	}

	/** The "Read" arrow icon, inline so it inherits currentColor. */
	private function arrow_svg() {
		return '<svg width="18" height="12" viewBox="0 0 18 12" fill="none" aria-hidden="true"><path d="M1 6h15M11 1l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}

	protected function register_controls() {
		$this->controls_query();
		$this->controls_display();
		$this->controls_layout();
		$this->controls_card();
		$this->controls_media();
		$this->controls_tag();
		$this->controls_kicker();
		$this->controls_title();
		$this->controls_excerpt();
		$this->controls_meta();
		$this->controls_more();
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
			'condition'   => array( 'post_type' => 'post' ),
		) );

		$this->add_control( 'tags', array(
			'label'       => __( 'Tags', 'stl-addons' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'options'     => $this->term_options( 'post_tag' ),
			'description' => __( 'Leave empty to include all tags.', 'stl-addons' ),
			'condition'   => array( 'post_type' => 'post' ),
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
			'label'       => __( 'Number of Posts', 'stl-addons' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 1,
			'min'         => 1,
			'max'         => 12,
			'description' => __( 'How many featured cards to show (they stack vertically).', 'stl-addons' ),
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

		$this->add_control( 'media_position', array(
			'label'   => __( 'Media Position', 'stl-addons' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'left',
			'options' => array(
				'left'  => __( 'Left', 'stl-addons' ),
				'right' => __( 'Right', 'stl-addons' ),
			),
		) );

		$this->add_control( 'show_image', array(
			'label'        => __( 'Show Featured Image', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'description'  => __( 'When a post has no image, a decorative gradient panel is shown instead.', 'stl-addons' ),
		) );

		$this->add_control( 'show_tag', array(
			'label'        => __( 'Show Tag', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'tag_text', array(
			'label'     => __( 'Tag Label', 'stl-addons' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => __( 'Featured', 'stl-addons' ),
			'condition' => array( 'show_tag' => 'yes' ),
		) );

		$this->add_control( 'kicker_source', array(
			'label'   => __( 'Kicker', 'stl-addons' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'category',
			'options' => array(
				'category' => __( 'Post category', 'stl-addons' ),
				'custom'   => __( 'Custom text', 'stl-addons' ),
				'none'     => __( 'Hidden', 'stl-addons' ),
			),
		) );

		$this->add_control( 'kicker_custom', array(
			'label'     => __( 'Custom Kicker', 'stl-addons' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => __( 'Featured guide', 'stl-addons' ),
			'condition' => array( 'kicker_source' => 'custom' ),
		) );

		$this->add_control( 'title_tag', array(
			'label'       => __( 'Title HTML Tag', 'stl-addons' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'h2',
			'options'     => array(
				'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6',
			),
			'description' => __( 'Pick the heading level that matches your page outline.', 'stl-addons' ),
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
			'default'   => 32,
			'min'       => 0,
			'max'       => 120,
			'condition' => array( 'show_excerpt' => 'yes' ),
		) );

		$this->add_control( 'show_meta', array(
			'label'        => __( 'Show Meta Row', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_date', array(
			'label'        => __( 'Show Date', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'condition'    => array( 'show_meta' => 'yes' ),
		) );

		$this->add_control( 'date_format', array(
			'label'       => __( 'Date Format', 'stl-addons' ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => get_option( 'date_format' ) ? get_option( 'date_format' ) : 'j F Y',
			'description' => __( 'PHP date format. Leave empty to use the site default.', 'stl-addons' ),
			'condition'   => array( 'show_meta' => 'yes', 'show_date' => 'yes' ),
		) );

		$this->add_control( 'show_reading_time', array(
			'label'        => __( 'Show Reading Time', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'condition'    => array( 'show_meta' => 'yes' ),
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
			'default'   => __( 'Read the guide', 'stl-addons' ),
			'condition' => array( 'show_more' => 'yes' ),
		) );

		$this->add_control( 'show_arrow', array(
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

	/* ------------------------------------------------------------------ Style */

	private function controls_layout() {
		$this->start_controls_section( 'sec_layout', array(
			'label' => __( 'Layout', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'media_ratio', array(
			'label'      => __( 'Media / Body Split', 'stl-addons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( '%' ),
			'range'      => array( '%' => array( 'min' => 25, 'max' => 75 ) ),
			'selectors'  => array( '{{WRAPPER}} .stl-fp' => 'grid-template-columns: {{SIZE}}% 1fr;' ),
			'description' => __( 'Width of the media panel as a share of the card.', 'stl-addons' ),
		) );

		$this->add_responsive_control( 'cards_gap', array_merge(
			array(
				'label'     => __( 'Gap Between Cards', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-fp-list' => 'gap: {{SIZE}}{{UNIT}};' ),
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
			'selectors' => array( '{{WRAPPER}} .stl-fp' => 'background: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'card_border',
			'selector' => '{{WRAPPER}} .stl-fp',
		) );

		$this->add_responsive_control( 'card_radius', array_merge(
			array(
				'label'     => __( 'Border Radius', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-fp' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'body_padding', array(
			'label'      => __( 'Body Padding', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'selectors'  => array( '{{WRAPPER}} .stl-fp-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_control( 'card_hover_border', array(
			'label'     => __( 'Hover Border Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-fp:hover' => 'border-color: {{VALUE}};' ),
		) );

		$this->add_control( 'enable_lift', array(
			'label'                => __( 'Lift on Hover', 'stl-addons' ),
			'type'                 => Controls_Manager::SWITCHER,
			'return_value'         => 'yes',
			'default'              => 'yes',
			'selectors_dictionary' => array( 'yes' => 'translateY(-4px)', '' => 'none' ),
			'selectors'            => array( '{{WRAPPER}} .stl-fp:hover' => 'transform: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_media() {
		$this->start_controls_section( 'sec_media', array(
			'label'     => __( 'Media', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_image' => 'yes' ),
		) );

		$this->add_responsive_control( 'media_min_height', array(
			'label'      => __( 'Min Height', 'stl-addons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'vh' ),
			'range'      => array(
				'px' => array( 'min' => 120, 'max' => 700 ),
				'vh' => array( 'min' => 10, 'max' => 90 ),
			),
			'selectors'  => array( '{{WRAPPER}} .stl-fp-ph' => 'min-height: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_control( 'media_fit', array(
			'label'     => __( 'Object Fit', 'stl-addons' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'cover',
			'options'   => array(
				'cover'   => __( 'Cover', 'stl-addons' ),
				'contain' => __( 'Contain', 'stl-addons' ),
			),
			'selectors' => array( '{{WRAPPER}} .stl-fp-ph img' => 'object-fit: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_tag() {
		$this->start_controls_section( 'sec_tag', array(
			'label'     => __( 'Tag', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_tag' => 'yes' ),
		) );

		$this->add_control( 'tag_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-fp-tag' => 'background: {{VALUE}};' ),
		) );

		$this->add_control( 'tag_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-fp-tag' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'tag_typo',
			'selector' => '{{WRAPPER}} .stl-fp-tag',
		) );

		$this->end_controls_section();
	}

	private function controls_kicker() {
		$this->start_controls_section( 'sec_kicker', array(
			'label'     => __( 'Kicker', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'kicker_source!' => 'none' ),
		) );

		$this->add_control( 'kicker_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-fp-kicker' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'kicker_typo',
			'selector' => '{{WRAPPER}} .stl-fp-kicker',
		) );

		$this->end_controls_section();
	}

	private function controls_title() {
		$this->start_controls_section( 'sec_title', array(
			'label' => __( 'Title', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'title_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-fp-title' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'title_hover_color', array(
			'label'     => __( 'Hover Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-fp:hover .stl-fp-title' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'title_typo',
			'selector' => '{{WRAPPER}} .stl-fp-title',
		) );

		$this->end_controls_section();
	}

	private function controls_excerpt() {
		$this->start_controls_section( 'sec_excerpt', array(
			'label'     => __( 'Excerpt', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_excerpt' => 'yes' ),
		) );

		$this->add_control( 'excerpt_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-fp-excerpt' => 'color: {{VALUE}}; opacity: 1;' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'excerpt_typo',
			'selector' => '{{WRAPPER}} .stl-fp-excerpt',
		) );

		$this->end_controls_section();
	}

	private function controls_meta() {
		$this->start_controls_section( 'sec_meta', array(
			'label'     => __( 'Meta', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_meta' => 'yes' ),
		) );

		$this->add_control( 'meta_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-fp-meta' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'meta_dot_color', array(
			'label'     => __( 'Reading-time Dot', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-fp-rt::before' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'meta_typo',
			'selector' => '{{WRAPPER}} .stl-fp-meta',
		) );

		$this->end_controls_section();
	}

	private function controls_more() {
		$this->start_controls_section( 'sec_more', array(
			'label'     => __( 'Read More', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_more' => 'yes' ),
		) );

		$this->add_control( 'more_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-fp-more' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'more_typo',
			'selector' => '{{WRAPPER}} .stl-fp-more',
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
			'posts_per_page'      => max( 1, (int) ( $s['posts_per_page'] ?? 1 ) ),
			'orderby'             => $order['orderby'],
			'order'               => $order['order'],
			'ignore_sticky_posts' => ( 'yes' === ( $s['ignore_sticky'] ?? '' ) ),
			'no_found_rows'       => true,
		);

		$offset = max( 0, (int) ( $s['offset'] ?? 0 ) );
		if ( $offset ) {
			$args['offset'] = $offset;
		}

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
				$tax_query['relation'] = ( 'AND' === ( $s['tax_relation'] ?? 'OR' ) ) ? 'AND' : 'OR';
			}
			if ( $tax_query ) {
				$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			}
		}

		return $args;
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$query = new WP_Query( $this->build_query_args( $s ) );

		if ( ! $query->have_posts() ) {
			?>
			<p class="stl-fp-empty"><?php esc_html_e( 'No posts found for this query. Adjust the Query controls in the Content tab.', 'stl-addons' ); ?></p>
			<?php
			return;
		}

		$tag_raw    = $s['title_tag'] ?? 'h2';
		$title_tag  = in_array( $tag_raw, self::ALLOWED_HEADING_TAGS, true ) ? $tag_raw : 'h2';
		$media_pos  = ( 'right' === ( $s['media_position'] ?? 'left' ) ) ? 'right' : 'left';
		$show_image = 'yes' === ( $s['show_image'] ?? 'yes' );
		$show_tag   = 'yes' === ( $s['show_tag'] ?? 'yes' );
		$tag_text   = $s['tag_text'] ?? '';
		$kicker_src = $s['kicker_source'] ?? 'category';
		$show_exc   = 'yes' === ( $s['show_excerpt'] ?? 'yes' );
		$exc_len    = (int) ( $s['excerpt_length'] ?? 32 );
		$show_meta  = 'yes' === ( $s['show_meta'] ?? 'yes' );
		$show_date  = 'yes' === ( $s['show_date'] ?? 'yes' );
		$show_rt    = 'yes' === ( $s['show_reading_time'] ?? 'yes' );
		$date_fmt   = trim( (string) ( $s['date_format'] ?? '' ) );
		$show_more  = 'yes' === ( $s['show_more'] ?? 'yes' );
		$show_arrow = 'yes' === ( $s['show_arrow'] ?? 'yes' );
		$more_text  = $s['more_text'] ?? __( 'Read the guide', 'stl-addons' );
		$schema     = 'yes' === ( $s['schema'] ?? '' );
		?>
		<div class="stl-fp-list">
			<?php
			foreach ( $query->posts as $post ) :
				$permalink = get_permalink( $post );
				$title     = get_the_title( $post );
				$thumb_id  = $show_image ? get_post_thumbnail_id( $post->ID ) : 0;
				$has_img   = $show_image && $thumb_id;

				$kicker = '';
				if ( 'custom' === $kicker_src ) {
					$kicker = $s['kicker_custom'] ?? '';
				} elseif ( 'category' === $kicker_src ) {
					$kicker = $this->primary_term_name( $post );
				}

				$excerpt = '';
				if ( $show_exc ) {
					$excerpt = has_excerpt( $post )
						? get_the_excerpt( $post )
						: wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), $exc_len, '&hellip;' );
				}
				?>
				<a class="stl-fp stl-fp--media-<?php echo esc_attr( $media_pos ); ?>"
					href="<?php echo esc_url( $permalink ); ?>"
					<?php echo $schema ? ' itemscope itemtype="https://schema.org/BlogPosting"' : ''; ?>>

					<?php if ( $schema ) : ?>
						<meta itemprop="mainEntityOfPage" content="<?php echo esc_url( $permalink ); ?>" />
					<?php endif; ?>

					<div class="stl-fp-ph<?php echo $has_img ? ' has-img' : ''; ?>">
						<?php
						if ( $has_img ) {
							$img_settings = array_merge( $s, array( 'stl_fp_img' => array(
								'id'  => $thumb_id,
								'url' => wp_get_attachment_image_url( $thumb_id, 'full' ),
							) ) );
							echo Group_Control_Image_Size::get_attachment_image_html( $img_settings, 'image_size', 'stl_fp_img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor helper returns escaped markup.
							if ( $schema ) {
								$img_src = wp_get_attachment_image_url( $thumb_id, 'large' );
								if ( $img_src ) {
									echo '<meta itemprop="image" content="' . esc_url( $img_src ) . '" />';
								}
							}
						}
						?>
						<?php if ( $show_tag && $tag_text ) : ?>
							<span class="stl-fp-tag"><?php echo esc_html( $tag_text ); ?></span>
						<?php endif; ?>
					</div>

					<div class="stl-fp-body">
						<?php if ( 'none' !== $kicker_src && $kicker ) : ?>
							<div class="stl-fp-kicker"><?php echo esc_html( $kicker ); ?></div>
						<?php endif; ?>

						<?php if ( $title ) : ?>
							<<?php echo $title_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelisted. ?> class="stl-fp-title"<?php echo $schema ? ' itemprop="headline"' : ''; ?>><?php echo esc_html( $title ); ?></<?php echo $title_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<?php endif; ?>

						<?php if ( $show_exc && $excerpt ) : ?>
							<p class="stl-fp-excerpt"<?php echo $schema ? ' itemprop="description"' : ''; ?>><?php echo esc_html( $excerpt ); ?></p>
						<?php endif; ?>

						<?php if ( $show_meta && ( $show_date || $show_rt ) ) : ?>
							<div class="stl-fp-meta">
								<?php if ( $show_date ) : ?>
									<time class="stl-fp-date" datetime="<?php echo esc_attr( get_the_date( 'c', $post ) ); ?>"<?php echo $schema ? ' itemprop="datePublished"' : ''; ?>><?php echo esc_html( get_the_date( $date_fmt, $post ) ); ?></time>
								<?php endif; ?>
								<?php if ( $show_rt ) : ?>
									<span class="stl-fp-rt">
										<?php
										/* translators: %d: estimated reading time in minutes. */
										echo esc_html( sprintf( __( '%d min read', 'stl-addons' ), $this->reading_time( $post ) ) );
										?>
									</span>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<?php if ( $show_more && $more_text ) : ?>
							<span class="stl-fp-more">
								<?php echo esc_html( $more_text ); ?>
								<?php
								if ( $show_arrow ) {
									echo $this->arrow_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG.
								}
								?>
							</span>
						<?php endif; ?>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
		wp_reset_postdata();
	}
}
