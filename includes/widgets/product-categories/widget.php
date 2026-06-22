<?php
/**
 * Product Categories Elementor Widget.
 *
 * Lists WooCommerce product categories in the Hyun Engines sidebar style (a
 * panel of rows, each with a product count badge) or as a thumbnail grid. The
 * category matching the current archive is highlighted automatically.
 *
 * Self-contained: all markup is scoped under .stl-pc-* and styled by this
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

class STL_Widget_Product_Categories extends Widget_Base {

	const ALLOWED_HEADING_TAGS = array( 'h2', 'h3', 'h4', 'h5', 'h6' );

	public function get_name()          { return 'stl_product_categories'; }
	public function get_title()         { return __( 'Product Categories', 'stl-addons' ); }
	public function get_icon()          { return 'eicon-product-categories'; }
	public function get_categories()    { return array( 'stl-addons', 'general' ); }
	public function get_keywords()      { return array( 'woocommerce', 'product', 'categories', 'sidebar', 'filter', 'taxonomy', 'stl' ); }
	public function get_style_depends() { return array( 'stl-product-categories' ); }

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	public function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' stl-widget stl-pc-section';
	}

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

	/** Product categories as [ term_id => name ] for SELECT2. Bounded to 300. */
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

	protected function register_controls() {
		$this->controls_content();
		$this->controls_panel_style();
		$this->controls_item_style();
		$this->controls_count_style();
		$this->controls_grid_style();
	}

	/* ---------------------------------------------------------------- Content */

	private function controls_content() {
		$this->start_controls_section( 'sec_content', array(
			'label' => __( 'Categories', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'layout', array(
			'label'   => __( 'Layout', 'stl-addons' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'list',
			'options' => array(
				'list' => __( 'List (sidebar)', 'stl-addons' ),
				'grid' => __( 'Grid (with thumbnails)', 'stl-addons' ),
			),
		) );

		$this->add_control( 'heading', array(
			'label'       => __( 'Panel Heading', 'stl-addons' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => __( 'Product Categories', 'stl-addons' ),
			'description' => __( 'Leave empty to hide the heading.', 'stl-addons' ),
		) );

		$this->add_control( 'heading_tag', array(
			'label'     => __( 'Heading HTML Tag', 'stl-addons' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'h4',
			'options'   => array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6' ),
			'condition' => array( 'heading!' => '' ),
		) );

		$this->add_control( 'parent', array(
			'label'   => __( 'Show', 'stl-addons' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'top',
			'options' => array(
				'top' => __( 'Top-level categories only', 'stl-addons' ),
				'all' => __( 'All categories (flat)', 'stl-addons' ),
			),
		) );

		$this->add_control( 'include', array(
			'label'       => __( 'Include Only', 'stl-addons' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'options'     => $this->category_options(),
			'description' => __( 'Leave empty to include all. Overrides the "Show" option.', 'stl-addons' ),
		) );

		$this->add_control( 'exclude', array(
			'label'       => __( 'Exclude', 'stl-addons' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'label_block' => true,
			'options'     => $this->category_options(),
		) );

		$this->add_control( 'hide_empty', array(
			'label'        => __( 'Hide Empty', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'orderby', array(
			'label'   => __( 'Order By', 'stl-addons' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'name',
			'options' => array(
				'name'       => __( 'Name', 'stl-addons' ),
				'count'      => __( 'Product Count', 'stl-addons' ),
				'menu_order' => __( 'Menu Order', 'stl-addons' ),
			),
		) );

		$this->add_control( 'order', array(
			'label'   => __( 'Order', 'stl-addons' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'ASC',
			'options' => array(
				'ASC'  => __( 'Ascending', 'stl-addons' ),
				'DESC' => __( 'Descending', 'stl-addons' ),
			),
		) );

		$this->add_control( 'limit', array(
			'label'       => __( 'Max Categories', 'stl-addons' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'min'         => 0,
			'description' => __( '0 = no limit.', 'stl-addons' ),
		) );

		$this->add_control( 'show_count', array(
			'label'        => __( 'Show Product Count', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'highlight_current', array(
			'label'        => __( 'Highlight Current Category', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'description'  => __( 'Marks the active category when viewing a category archive.', 'stl-addons' ),
		) );

		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ Style */

	private function controls_panel_style() {
		$this->start_controls_section( 'sec_panel', array(
			'label' => __( 'Panel', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'panel_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pc-panel' => 'background: {{VALUE}};' ),
		) );

		$this->add_control( 'panel_border', array(
			'label'     => __( 'Border Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pc-panel' => 'border-color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'panel_radius', array_merge(
			array(
				'label'     => __( 'Border Radius', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-pc-panel' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'panel_padding', array(
			'label'      => __( 'Padding', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%', 'custom' ),
			'selectors'  => array( '{{WRAPPER}} .stl-pc-panel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_control( 'heading_color', array(
			'label'     => __( 'Heading Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pc-heading' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'heading_typo',
			'selector' => '{{WRAPPER}} .stl-pc-heading',
		) );

		$this->add_control( 'accent_color', array(
			'label'       => __( 'Accent Color', 'stl-addons' ),
			'type'        => Controls_Manager::COLOR,
			'description' => __( 'Active category text + grid hover.', 'stl-addons' ),
			'selectors'   => array( '{{WRAPPER}} .stl-pc-section, {{WRAPPER}}.stl-pc-section' => '--stl-pc-accent: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_item_style() {
		$this->start_controls_section( 'sec_item', array(
			'label'     => __( 'List Item', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'list' ),
		) );

		$this->add_control( 'item_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pc-item' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'item_hover_bg', array(
			'label'     => __( 'Hover / Active Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-pc-item:hover'    => 'background: {{VALUE}};',
				'{{WRAPPER}} .stl-pc-item.is-active' => 'background: {{VALUE}};',
			),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'item_typo',
			'selector' => '{{WRAPPER}} .stl-pc-item',
		) );

		$this->end_controls_section();
	}

	private function controls_count_style() {
		$this->start_controls_section( 'sec_count', array(
			'label'     => __( 'Count Badge', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_count' => 'yes' ),
		) );

		$this->add_control( 'count_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pc-count' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'count_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pc-count' => 'background: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_grid_style() {
		$this->start_controls_section( 'sec_grid', array(
			'label'     => __( 'Grid', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'layout' => 'grid' ),
		) );

		$this->add_responsive_control( 'grid_columns', array(
			'label'          => __( 'Columns', 'stl-addons' ),
			'type'           => Controls_Manager::SELECT,
			'default'        => '3',
			'tablet_default' => '2',
			'mobile_default' => '2',
			'options'        => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
			'selectors'      => array( '{{WRAPPER}} .stl-pc-grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ),
		) );

		$this->add_responsive_control( 'grid_gap', array_merge(
			array(
				'label'     => __( 'Gap', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-pc-grid' => 'gap: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_control( 'tile_radius', array_merge(
			array(
				'label'     => __( 'Tile Radius', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array(
					'{{WRAPPER}} .stl-pc-tile'     => 'border-radius: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .stl-pc-tile-img' => 'border-radius: {{SIZE}}{{UNIT}} {{SIZE}}{{UNIT}} 0 0;',
				),
			),
			$this->std_slider_args()
		) );

		$this->add_control( 'tile_text_color', array(
			'label'     => __( 'Tile Text Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-pc-tile-name' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	/* ----------------------------------------------------------------- Render */

	/** Build get_terms() args from the panel settings. */
	private function build_term_args( $s ) {
		$args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => ( 'yes' === ( $s['hide_empty'] ?? 'yes' ) ),
			'orderby'    => in_array( ( $s['orderby'] ?? 'name' ), array( 'name', 'count', 'menu_order' ), true ) ? $s['orderby'] : 'name',
			'order'      => ( 'DESC' === ( $s['order'] ?? 'ASC' ) ) ? 'DESC' : 'ASC',
		);

		$include = array_filter( array_map( 'intval', (array) ( $s['include'] ?? array() ) ) );
		$exclude = array_filter( array_map( 'intval', (array) ( $s['exclude'] ?? array() ) ) );

		if ( $include ) {
			$args['include'] = $include;
		} elseif ( 'top' === ( $s['parent'] ?? 'top' ) ) {
			$args['parent'] = 0;
		}

		if ( $exclude ) {
			$args['exclude'] = $exclude;
		}

		$limit = (int) ( $s['limit'] ?? 0 );
		if ( $limit > 0 ) {
			$args['number'] = $limit;
		}

		return $args;
	}

	protected function render() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<p class="stl-pc-empty">' . esc_html__( 'WooCommerce is not active.', 'stl-addons' ) . '</p>';
			return;
		}

		$s     = $this->get_settings_for_display();
		$terms = get_terms( $this->build_term_args( $s ) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			echo '<p class="stl-pc-empty">' . esc_html__( 'No product categories found.', 'stl-addons' ) . '</p>';
			return;
		}

		$layout      = in_array( ( $s['layout'] ?? 'list' ), array( 'list', 'grid' ), true ) ? $s['layout'] : 'list';
		$heading     = $s['heading'] ?? '';
		$h_tag_raw   = $s['heading_tag'] ?? 'h4';
		$h_tag       = in_array( $h_tag_raw, self::ALLOWED_HEADING_TAGS, true ) ? $h_tag_raw : 'h4';
		$show_count  = 'yes' === ( $s['show_count'] ?? 'yes' );
		$highlight   = 'yes' === ( $s['highlight_current'] ?? 'yes' );
		$current_id  = ( $highlight && is_tax( 'product_cat' ) ) ? (int) get_queried_object_id() : 0;
		?>
		<div class="stl-pc-panel stl-pc-<?php echo esc_attr( $layout ); ?>-wrap">
			<?php if ( '' !== $heading ) : ?>
				<<?php echo $h_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelisted. ?> class="stl-pc-heading"><?php echo esc_html( $heading ); ?></<?php echo $h_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php endif; ?>

			<?php if ( 'grid' === $layout ) : ?>
				<div class="stl-pc-grid">
					<?php foreach ( $terms as $term ) :
						$is_active = ( (int) $term->term_id === $current_id );
						$thumb_id  = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
						$img       = $thumb_id ? wp_get_attachment_image( $thumb_id, 'woocommerce_thumbnail', false, array( 'loading' => 'lazy', 'alt' => $term->name ) ) : '';
						?>
						<a class="stl-pc-tile<?php echo $is_active ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
							<span class="stl-pc-tile-img">
								<?php
								if ( $img ) {
									echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core returns escaped markup.
								} elseif ( function_exists( 'wc_placeholder_img' ) ) {
									echo wc_placeholder_img( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core returns escaped markup.
								}
								?>
							</span>
							<span class="stl-pc-tile-name"><?php echo esc_html( $term->name ); ?></span>
							<?php if ( $show_count ) : ?>
								<span class="stl-pc-tile-count"><?php echo esc_html( number_format_i18n( $term->count ) ); ?></span>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<nav class="stl-pc-list" aria-label="<?php esc_attr_e( 'Product categories', 'stl-addons' ); ?>">
					<?php foreach ( $terms as $term ) :
						$is_active = ( (int) $term->term_id === $current_id );
						?>
						<a class="stl-pc-item<?php echo $is_active ? ' is-active' : ''; ?>"
							href="<?php echo esc_url( get_term_link( $term ) ); ?>"
							<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
							<span class="stl-pc-name"><?php echo esc_html( $term->name ); ?></span>
							<?php if ( $show_count ) : ?>
								<b class="stl-pc-count"><?php echo esc_html( number_format_i18n( $term->count ) ); ?></b>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		</div>
		<?php
	}
}
