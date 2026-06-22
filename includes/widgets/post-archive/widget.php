<?php
/**
 * Post Archive Elementor Widget.
 *
 * A paginated archive grid of posts pulled live from the WordPress query,
 * rebuilding the Hyun Engines blog "pcard" design — gradient image placeholder
 * with a category tag badge, kicker, title, excerpt and a footer carrying the
 * date · reading-time meta plus a "Read" link. Editors control the source query
 * (post type, categories, tags), ordering, count, columns and server-side
 * pagination from the panel.
 *
 * Pagination is rendered with paginate_links() over a dedicated query-string
 * key (editor-set, default `pa_page` → ?pa_page=2), so it never collides with
 * the page's main query (which would 404 past the main query's last page). The
 * key is configurable for a readable URL; leaving it blank falls back to a
 * per-instance unique key so several archives on one page paginate
 * independently. It works on any page and stays fully crawlable.
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

class STL_Widget_Post_Archive extends Widget_Base {

	const ALLOWED_HEADING_TAGS = array( 'h2', 'h3', 'h4', 'h5', 'h6' );

	public function get_name()           { return 'stl_post_archive'; }
	public function get_title()          { return __( 'Post Archive', 'stl-addons' ); }
	public function get_icon()           { return 'eicon-archive-posts'; }
	public function get_categories()     { return array( 'stl-addons', 'general' ); }
	public function get_keywords()       { return array( 'posts', 'archive', 'blog', 'grid', 'articles', 'pagination', 'loop', 'query', 'stl' ); }
	public function get_style_depends()  { return array( 'stl-post-archive' ); }

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	public function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' stl-widget stl-pa-section';
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

	/**
	 * Pagination query-string key for this widget's loop.
	 *
	 * Defaults to the editor-set "Pagination URL Key" (e.g. `pa_page` → ?pa_page=2)
	 * for a clean, readable link. Falls back to a per-instance unique key
	 * (`pa_page_<id>`) when the field is blank or set to a reserved WordPress var,
	 * so multiple archives on one page still paginate independently and the page's
	 * main query is never touched.
	 */
	private function page_param() {
		$key      = sanitize_key( (string) $this->get_settings_for_display( 'pagination_key' ) );
		$reserved = array( 'page', 'paged', 'p', 'name', 'cat', 'tag', 'tag_id', 's', 'author', 'm', 'w', 'order', 'orderby', 'feed' );
		if ( '' === $key || in_array( $key, $reserved, true ) ) {
			return 'pa_page_' . $this->get_id();
		}
		return $key;
	}

	/** DOM id used as the scroll-to anchor after a pagination click. Matches the URL key. */
	private function anchor_id() {
		return 'stl-pa-' . $this->page_param();
	}

	/** Current page for this widget's loop, read from its own query-string key. */
	private function current_page() {
		$param = $this->page_param();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pagination arg.
		$paged = isset( $_GET[ $param ] ) ? (int) $_GET[ $param ] : 1;
		return max( 1, $paged );
	}

	/** The "Read" arrow icon, inline so it inherits currentColor. */
	private function arrow_svg() {
		return '<svg width="16" height="11" viewBox="0 0 18 12" fill="none" aria-hidden="true"><path d="M1 6h15M11 1l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}

	protected function register_controls() {
		$this->controls_query();
		$this->controls_display();
		$this->controls_header();
		$this->controls_pagination();
		$this->controls_layout();
		$this->controls_card();
		$this->controls_image_style();
		$this->controls_tag_style();
		$this->controls_kicker_style();
		$this->controls_title_style();
		$this->controls_excerpt_style();
		$this->controls_meta_style();
		$this->controls_more_style();
		$this->controls_pagination_style();
	}

	/* ---------------------------------------------------------------- Content */

	private function controls_query() {
		$this->start_controls_section( 'sec_query', array(
			'label' => __( 'Query', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'query_mode', array(
			'label'       => __( 'Query Mode', 'stl-addons' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'manual',
			'options'     => array(
				'manual'  => __( 'Manual query', 'stl-addons' ),
				'related' => __( 'Related to current post', 'stl-addons' ),
			),
			'description' => __( 'Related mode auto-matches the viewed post\'s terms and excludes it. Use it for a single-post related section; pagination is off in this mode.', 'stl-addons' ),
		) );

		$this->add_control( 'related_taxonomy', array(
			'label'       => __( 'Relate By', 'stl-addons' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'category',
			'options'     => array(
				'category' => __( 'Categories', 'stl-addons' ),
				'post_tag' => __( 'Tags', 'stl-addons' ),
				'both'     => __( 'Categories or Tags', 'stl-addons' ),
				'auto'     => __( 'All taxonomies (auto)', 'stl-addons' ),
			),
			'description' => __( 'Which of the current post terms to match on. Auto uses every taxonomy the post type has.', 'stl-addons' ),
			'condition'   => array( 'query_mode' => 'related' ),
		) );

		$this->add_control( 'post_type', array(
			'label'     => __( 'Source', 'stl-addons' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'post',
			'options'   => $this->post_type_options(),
			'condition' => array( 'query_mode' => 'manual' ),
		) );

		$this->add_control( 'categories', array(
			'label'       => __( 'Categories', 'stl-addons' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'options'     => $this->term_options( 'category' ),
			'description' => __( 'Leave empty to include all categories.', 'stl-addons' ),
			'condition'   => array( 'query_mode' => 'manual', 'post_type' => 'post' ),
		) );

		$this->add_control( 'tags', array(
			'label'       => __( 'Tags', 'stl-addons' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'options'     => $this->term_options( 'post_tag' ),
			'description' => __( 'Leave empty to include all tags.', 'stl-addons' ),
			'condition'   => array( 'query_mode' => 'manual', 'post_type' => 'post' ),
		) );

		$this->add_control( 'tax_relation', array(
			'label'     => __( 'Match', 'stl-addons' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'OR',
			'options'   => array(
				'OR'  => __( 'Any selected category / tag', 'stl-addons' ),
				'AND' => __( 'All selected categories & tags', 'stl-addons' ),
			),
			'condition' => array( 'query_mode' => 'manual', 'post_type' => 'post' ),
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
			'label'   => __( 'Posts Per Page', 'stl-addons' ),
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
			'description' => __( 'Skip this many posts. Ignored when pagination is on.', 'stl-addons' ),
			'condition'   => array( 'pagination!' => 'yes' ),
		) );

		$this->add_control( 'exclude_current', array(
			'label'        => __( 'Exclude Current Post', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
			'description'  => __( 'Avoids listing the post you are already viewing.', 'stl-addons' ),
			'condition'    => array( 'query_mode' => 'manual' ),
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
			'description'  => __( 'Falls back to a gradient placeholder when a post has no featured image.', 'stl-addons' ),
		) );

		$this->add_control( 'show_tag', array(
			'label'        => __( 'Show Category Tag Badge', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_kicker', array(
			'label'        => __( 'Show Category Kicker', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
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

		$this->add_control( 'show_meta', array(
			'label'        => __( 'Show Meta (date · read time)', 'stl-addons' ),
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
			'placeholder' => get_option( 'date_format' ) ? get_option( 'date_format' ) : 'F j, Y',
			'description' => __( 'PHP date format. Leave empty to use the site default.', 'stl-addons' ),
			'condition'   => array( 'show_meta' => 'yes', 'show_date' => 'yes' ),
		) );

		$this->add_control( 'show_readtime', array(
			'label'        => __( 'Show Reading Time', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'condition'    => array( 'show_meta' => 'yes' ),
		) );

		$this->add_control( 'meta_separator', array(
			'label'     => __( 'Meta Separator', 'stl-addons' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => '·',
			'condition' => array( 'show_meta' => 'yes' ),
		) );

		$this->add_control( 'show_more', array(
			'label'        => __( 'Show Read Link', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'more_text', array(
			'label'     => __( 'Read Link Text', 'stl-addons' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => __( 'Read', 'stl-addons' ),
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

	private function controls_header() {
		$this->start_controls_section( 'sec_header', array(
			'label' => __( 'Section Header', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'show_header', array(
			'label'        => __( 'Show Header', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
			'description'  => __( 'A title row above the grid (e.g. "Latest guides").', 'stl-addons' ),
		) );

		$this->add_control( 'header_title', array(
			'label'     => __( 'Title', 'stl-addons' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => __( 'Latest guides', 'stl-addons' ),
			'condition' => array( 'show_header' => 'yes' ),
		) );

		$this->add_control( 'header_title_tag', array(
			'label'     => __( 'Title HTML Tag', 'stl-addons' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'h2',
			'options'   => array(
				'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6',
			),
			'condition' => array( 'show_header' => 'yes' ),
		) );

		$this->add_control( 'header_subtitle', array(
			'label'       => __( 'Subtitle / Count', 'stl-addons' ),
			'type'        => Controls_Manager::TEXT,
			'placeholder' => __( 'Showing all articles', 'stl-addons' ),
			'condition'   => array( 'show_header' => 'yes' ),
		) );

		$this->end_controls_section();
	}

	private function controls_pagination() {
		$this->start_controls_section( 'sec_pagination', array(
			'label' => __( 'Pagination', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'pagination', array(
			'label'        => __( 'Enable Pagination', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'description'  => __( 'Numbered, crawlable pagination via a clean query-string link. Disabled in Related mode.', 'stl-addons' ),
			'condition'    => array( 'query_mode!' => 'related' ),
		) );

		$this->add_control( 'pagination_key', array(
			'label'       => __( 'Pagination URL Key', 'stl-addons' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'pa_page',
			'placeholder' => 'pa_page',
			'description'  => __( 'The query-string key in the page link, e.g. "pa_page" gives ?pa_page=2. Use a short word like "blog". Give each archive on the same page a different key. Avoid the reserved words page and paged.', 'stl-addons' ),
			'condition'   => array( 'pagination' => 'yes' ),
		) );

		$this->add_control( 'prev_text', array(
			'label'     => __( 'Previous Label', 'stl-addons' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => '‹',
			'condition' => array( 'pagination' => 'yes' ),
		) );

		$this->add_control( 'next_text', array(
			'label'     => __( 'Next Label', 'stl-addons' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => '›',
			'condition' => array( 'pagination' => 'yes' ),
		) );

		$this->add_control( 'mid_size', array(
			'label'       => __( 'Pages Around Current', 'stl-addons' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 2,
			'min'         => 0,
			'max'         => 6,
			'condition'   => array( 'pagination' => 'yes' ),
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
			'default'        => '2',
			'tablet_default' => '2',
			'mobile_default' => '1',
			'options'        => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
			'selectors'      => array( '{{WRAPPER}} .stl-pa-grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ),
		) );

		$this->add_responsive_control( 'gap', array_merge(
			array(
				'label'     => __( 'Gap', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-pa-grid' => 'gap: {{SIZE}}{{UNIT}};' ),
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

		$this->add_control( 'accent_color', array(
			'label'       => __( 'Accent Color', 'stl-addons' ),
			'type'        => Controls_Manager::COLOR,
			'description' => __( 'Tag badge, kicker, read link and active page.', 'stl-addons' ),
			'selectors'   => array(
				'{{WRAPPER}}.stl-pa-section, {{WRAPPER}} .stl-pa-section' => '--stl-pa-accent: {{VALUE}};',
			),
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
			'selectors' => array( '{{WRAPPER}} .stl-pa-card' => 'background: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'card_border',
			'selector' => '{{WRAPPER}} .stl-pa-card',
		) );

		$this->add_responsive_control( 'card_radius', array_merge(
			array(
				'label'     => __( 'Border Radius', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-pa-card' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'card_body_padding', array(
			'label'      => __( 'Body Padding', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'selectors'  => array( '{{WRAPPER}} .stl-pa-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'card_shadow',
			'selector' => '{{WRAPPER}} .stl-pa-card',
		) );

		$this->add_control( 'card_hover_border', array(
			'label'     => __( 'Hover Border Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pa-card:hover' => 'border-color: {{VALUE}};' ),
		) );

		$this->add_control( 'enable_lift', array(
			'label'                => __( 'Lift on Hover', 'stl-addons' ),
			'type'                 => Controls_Manager::SWITCHER,
			'return_value'         => 'yes',
			'default'              => 'yes',
			'selectors_dictionary' => array( 'yes' => 'translateY(-6px)', '' => 'none' ),
			'selectors'            => array( '{{WRAPPER}} .stl-pa-card:hover' => 'transform: {{VALUE}};' ),
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
			'selectors'  => array( '{{WRAPPER}} .stl-pa-ph' => 'height: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_control( 'image_fit', array(
			'label'     => __( 'Object Fit', 'stl-addons' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'cover',
			'options'   => array(
				'cover'   => __( 'Cover', 'stl-addons' ),
				'contain' => __( 'Contain', 'stl-addons' ),
			),
			'selectors' => array( '{{WRAPPER}} .stl-pa-ph img' => 'object-fit: {{VALUE}};' ),
		) );

		$this->add_control( 'enable_zoom', array(
			'label'                => __( 'Zoom on Hover', 'stl-addons' ),
			'type'                 => Controls_Manager::SWITCHER,
			'return_value'         => 'yes',
			'default'              => '',
			'selectors_dictionary' => array( 'yes' => 'scale(1.05)', '' => 'scale(1)' ),
			'selectors'            => array( '{{WRAPPER}} .stl-pa-card:hover .stl-pa-ph img' => 'transform: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_tag_style() {
		$this->start_controls_section( 'sec_tag', array(
			'label'     => __( 'Tag Badge', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_tag' => 'yes' ),
		) );

		$this->add_control( 'tag_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pa-tag' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'tag_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pa-tag' => 'background: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'tag_typo',
			'selector' => '{{WRAPPER}} .stl-pa-tag',
		) );

		$this->end_controls_section();
	}

	private function controls_kicker_style() {
		$this->start_controls_section( 'sec_kicker', array(
			'label'     => __( 'Kicker', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_kicker' => 'yes' ),
		) );

		$this->add_control( 'kicker_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pa-kicker' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'kicker_typo',
			'selector' => '{{WRAPPER}} .stl-pa-kicker',
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
			'selectors' => array( '{{WRAPPER}} .stl-pa-title' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'title_hover_color', array(
			'label'     => __( 'Hover Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pa-card:hover .stl-pa-title' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'title_typo',
			'selector' => '{{WRAPPER}} .stl-pa-title',
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
			'selectors' => array( '{{WRAPPER}} .stl-pa-excerpt' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'excerpt_typo',
			'selector' => '{{WRAPPER}} .stl-pa-excerpt',
		) );

		$this->end_controls_section();
	}

	private function controls_meta_style() {
		$this->start_controls_section( 'sec_meta', array(
			'label'     => __( 'Meta', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_meta' => 'yes' ),
		) );

		$this->add_control( 'meta_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pa-meta' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'foot_border_color', array(
			'label'     => __( 'Footer Divider Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pa-foot' => 'border-top-color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'meta_typo',
			'selector' => '{{WRAPPER}} .stl-pa-meta',
		) );

		$this->end_controls_section();
	}

	private function controls_more_style() {
		$this->start_controls_section( 'sec_more', array(
			'label'     => __( 'Read Link', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_more' => 'yes' ),
		) );

		$this->add_control( 'more_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pa-more' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'more_typo',
			'selector' => '{{WRAPPER}} .stl-pa-more',
		) );

		$this->end_controls_section();
	}

	private function controls_pagination_style() {
		$this->start_controls_section( 'sec_pagination_style', array(
			'label'     => __( 'Pagination', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'pagination' => 'yes' ),
		) );

		$this->add_responsive_control( 'pagination_align', array(
			'label'     => __( 'Alignment', 'stl-addons' ),
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => __( 'Left', 'stl-addons' ),   'icon' => 'eicon-text-align-left' ),
				'center'     => array( 'title' => __( 'Center', 'stl-addons' ), 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => __( 'Right', 'stl-addons' ),  'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .stl-pa-pagination' => 'justify-content: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'pagination_spacing', array_merge(
			array(
				'label'     => __( 'Spacing Above', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-pa-pagination' => 'margin-top: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_control( 'page_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pa-pagination .page-numbers' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'page_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pa-pagination .page-numbers' => 'background: {{VALUE}};' ),
		) );

		$this->add_control( 'page_border_color', array(
			'label'     => __( 'Border Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pa-pagination .page-numbers' => 'border-color: {{VALUE}};' ),
		) );

		$this->add_control( 'page_active_color', array(
			'label'     => __( 'Active Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pa-pagination .page-numbers.current' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'page_active_bg', array(
			'label'     => __( 'Active Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-pa-pagination .page-numbers.current' => 'background: {{VALUE}}; border-color: {{VALUE}};',
			),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'page_typo',
			'selector' => '{{WRAPPER}} .stl-pa-pagination .page-numbers',
		) );

		$this->end_controls_section();
	}

	/* ----------------------------------------------------------------- Render */

	/** Build the WP_Query args from the panel settings, resolved page and mode. */
	private function build_query_args( $s, $paginate, $paged, $mode ) {
		$order     = $this->resolve_order( $s['order_by'] ?? 'date-desc' );
		$post_type = $s['post_type'] ?? 'post';

		// In related mode the loop follows the post type of the post being viewed.
		if ( 'related' === $mode ) {
			$current = get_queried_object();
			if ( $current instanceof WP_Post ) {
				$post_type = $current->post_type;
			}
		}

		$args = array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, (int) ( $s['posts_per_page'] ?? 6 ) ),
			'orderby'             => $order['orderby'],
			'order'               => $order['order'],
			'ignore_sticky_posts' => true,
		);

		if ( $paginate ) {
			$args['paged']         = $paged;
			$args['no_found_rows'] = false;
		} else {
			$args['no_found_rows'] = true;
			if ( 'related' !== $mode ) {
				$offset = max( 0, (int) ( $s['offset'] ?? 0 ) );
				if ( $offset ) {
					$args['offset'] = $offset;
				}
			}
		}

		// Related: auto-match the viewed post's terms and drop the post itself.
		if ( 'related' === $mode ) {
			$cid = get_queried_object_id();
			if ( $cid ) {
				$args['post__not_in'] = array( $cid );
			}
			$rtq = $this->related_tax_query( $cid, $s['related_taxonomy'] ?? 'category' );
			if ( $rtq ) {
				$args['tax_query'] = $rtq; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			}
			return $args;
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

	/** tax_query built from the current post's own terms, for related mode. */
	private function related_tax_query( $post_id, $choice ) {
		if ( ! $post_id ) {
			return array();
		}

		switch ( $choice ) {
			case 'post_tag':
				$taxes = array( 'post_tag' );
				break;
			case 'both':
				$taxes = array( 'category', 'post_tag' );
				break;
			case 'auto':
				$taxes = get_object_taxonomies( get_post_type( $post_id ) );
				$taxes = array_diff( $taxes, array( 'post_format' ) );
				break;
			case 'category':
			default:
				$taxes = array( 'category' );
			}

		$tax_query = array();
		foreach ( $taxes as $tax ) {
			if ( ! taxonomy_exists( $tax ) ) {
				continue;
			}
			$term_ids = wp_get_post_terms( $post_id, $tax, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $term_ids ) || ! $term_ids ) {
				continue;
			}
			$tax_query[] = array( 'taxonomy' => $tax, 'field' => 'term_id', 'terms' => $term_ids );
		}
		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'OR';
		}

		return $tax_query;
	}

	/** Primary category-like term name for a post (tag badge + kicker). */
	private function primary_term_name( $post ) {
		$taxes = get_object_taxonomies( $post->post_type );
		// Prefer the standard category, else the first hierarchical taxonomy.
		$ordered = array_unique( array_merge( array( 'category' ), $taxes ) );
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

	protected function render() {
		$s = $this->get_settings_for_display();

		$mode     = ( 'related' === ( $s['query_mode'] ?? 'manual' ) ) ? 'related' : 'manual';
		$paginate = ( 'yes' === ( $s['pagination'] ?? 'yes' ) ) && 'related' !== $mode;
		$paged    = $paginate ? $this->current_page() : 1;

		$args  = $this->build_query_args( $s, $paginate, $paged, $mode );
		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			?>
			<p class="stl-pa-empty"><?php esc_html_e( 'No posts found for this query. Adjust the Query controls in the Content tab.', 'stl-addons' ); ?></p>
			<?php
			return;
		}

		$tag_raw      = $s['title_tag'] ?? 'h3';
		$title_tag    = in_array( $tag_raw, self::ALLOWED_HEADING_TAGS, true ) ? $tag_raw : 'h3';
		$show_image   = 'yes' === ( $s['show_image'] ?? 'yes' );
		$show_tag     = 'yes' === ( $s['show_tag'] ?? 'yes' );
		$show_kicker  = 'yes' === ( $s['show_kicker'] ?? 'yes' );
		$show_exc     = 'yes' === ( $s['show_excerpt'] ?? 'yes' );
		$show_meta    = 'yes' === ( $s['show_meta'] ?? 'yes' );
		$show_date    = 'yes' === ( $s['show_date'] ?? 'yes' );
		$show_rt      = 'yes' === ( $s['show_readtime'] ?? 'yes' );
		$show_more    = 'yes' === ( $s['show_more'] ?? 'yes' );
		$more_arrow   = 'yes' === ( $s['more_arrow'] ?? 'yes' );
		$schema       = 'yes' === ( $s['schema'] ?? '' );
		$date_fmt     = trim( (string) ( $s['date_format'] ?? '' ) );
		$exc_len      = (int) ( $s['excerpt_length'] ?? 22 );
		$more_text    = $s['more_text'] ?? __( 'Read', 'stl-addons' );
		$sep          = trim( (string) ( $s['meta_separator'] ?? '·' ) );
		$show_header  = 'yes' === ( $s['show_header'] ?? '' );
		$header_title = $s['header_title'] ?? '';
		$header_sub   = $s['header_subtitle'] ?? '';
		$htag_raw     = $s['header_title_tag'] ?? 'h2';
		$header_tag   = in_array( $htag_raw, self::ALLOWED_HEADING_TAGS, true ) ? $htag_raw : 'h2';
		?>
		<div class="stl-pa" id="<?php echo esc_attr( $this->anchor_id() ); ?>">
			<?php if ( $show_header && ( $header_title || $header_sub ) ) : ?>
				<div class="stl-pa-head">
					<?php if ( $header_title ) : ?>
						<<?php echo $header_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelisted. ?> class="stl-pa-head-title"><?php echo esc_html( $header_title ); ?></<?php echo $header_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php endif; ?>
					<?php if ( $header_sub ) : ?>
						<span class="stl-pa-count"><?php echo esc_html( $header_sub ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="stl-pa-grid"<?php echo $schema ? ' itemscope itemtype="https://schema.org/Blog"' : ''; ?>>
				<?php
				foreach ( $query->posts as $post ) :
					$pid       = $post->ID;
					$permalink = get_permalink( $post );
					$title     = get_the_title( $post );
					$term_name = ( $show_tag || $show_kicker ) ? $this->primary_term_name( $post ) : '';
					$thumb_id  = $show_image ? get_post_thumbnail_id( $pid ) : 0;

					$excerpt = '';
					if ( $show_exc ) {
						$excerpt = has_excerpt( $post )
							? get_the_excerpt( $post )
							: wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), $exc_len, '&hellip;' );
					}
					?>
					<a class="stl-pa-card"
						href="<?php echo esc_url( $permalink ); ?>"
						<?php echo $schema ? ' itemprop="blogPost" itemscope itemtype="https://schema.org/BlogPosting"' : ''; ?>>

						<?php if ( $schema ) : ?>
							<meta itemprop="mainEntityOfPage" content="<?php echo esc_url( $permalink ); ?>" />
						<?php endif; ?>

						<?php if ( $show_image ) : ?>
							<div class="stl-pa-ph<?php echo $thumb_id ? '' : ' is-empty'; ?>">
								<?php
								if ( $thumb_id ) {
									$img_settings = array_merge( $s, array( 'stl_pa_img' => array(
										'id'  => $thumb_id,
										'url' => wp_get_attachment_image_url( $thumb_id, 'full' ),
									) ) );
									echo Group_Control_Image_Size::get_attachment_image_html( $img_settings, 'image_size', 'stl_pa_img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor helper returns escaped markup.
									if ( $schema ) {
										$img_src = wp_get_attachment_image_url( $thumb_id, 'large' );
										if ( $img_src ) {
											echo '<meta itemprop="image" content="' . esc_url( $img_src ) . '" />';
										}
									}
								}
								if ( $show_tag && $term_name ) {
									echo '<span class="stl-pa-tag">' . esc_html( $term_name ) . '</span>';
								}
								?>
							</div>
						<?php endif; ?>

						<div class="stl-pa-body">
							<?php if ( $show_kicker && $term_name ) : ?>
								<div class="stl-pa-kicker"><?php echo esc_html( $term_name ); ?></div>
							<?php endif; ?>

							<?php if ( $title ) : ?>
								<<?php echo $title_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelisted above. ?> class="stl-pa-title"<?php echo $schema ? ' itemprop="headline"' : ''; ?>><?php echo esc_html( $title ); ?></<?php echo $title_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<?php endif; ?>

							<?php if ( $show_exc && $excerpt ) : ?>
								<p class="stl-pa-excerpt"<?php echo $schema ? ' itemprop="description"' : ''; ?>><?php echo esc_html( $excerpt ); ?></p>
							<?php endif; ?>

							<?php
							$meta_bits = array();
							if ( $show_meta && $show_date ) {
								$meta_bits[] = esc_html( get_the_date( $date_fmt, $post ) );
							}
							if ( $show_meta && $show_rt ) {
								/* translators: %d: estimated reading time in minutes. */
								$meta_bits[] = esc_html( sprintf( __( '%d min', 'stl-addons' ), $this->reading_time( $post ) ) );
							}
							$has_meta = $show_meta && $meta_bits;
							$has_more = $show_more && $more_text;
							if ( $has_meta || $has_more ) :
								?>
								<div class="stl-pa-foot">
									<?php if ( $has_meta ) : ?>
										<span class="stl-pa-meta"><?php echo wp_kses_post( implode( ' ' . $sep . ' ', $meta_bits ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bits escaped above, separator sanitized here. ?></span>
									<?php else : ?>
										<span></span>
									<?php endif; ?>

									<?php if ( $has_more ) : ?>
										<span class="stl-pa-more">
											<?php echo esc_html( $more_text ); ?>
											<?php
											if ( $more_arrow ) {
												echo $this->arrow_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG.
											}
											?>
										</span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					</a>
				<?php endforeach; ?>
			</div>

			<?php
			if ( $paginate && (int) $query->max_num_pages > 1 ) {
				$links = paginate_links( array(
					'base'         => add_query_arg( $this->page_param(), '%#%' ),
					'format'       => '',
					'current'      => $paged,
					'total'        => (int) $query->max_num_pages,
					'mid_size'     => (int) ( $s['mid_size'] ?? 2 ),
					'prev_text'    => $s['prev_text'] ?? '‹',
					'next_text'    => $s['next_text'] ?? '›',
				) );
				if ( $links ) {
					echo '<nav class="stl-pa-pagination" aria-label="' . esc_attr__( 'Posts navigation', 'stl-addons' ) . '">'
						. $links // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links() returns safe markup.
						. '</nav>';
				}
			}
			?>
		</div>
		<?php
		wp_reset_postdata();
	}
}