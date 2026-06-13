<?php
/**
 * Review Grid Elementor Widget.
 *
 * Responsive grid of customer review cards — star rating, review body, and an
 * author line with an avatar (image or auto-initial). The accent color used by
 * the stars and the avatar gradient is scoped to the widget wrapper via CSS
 * variables, so multiple instances on a page never bleed into each other.
 *
 * Optional schema.org/Review microdata can be emitted per card for richer SEO.
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
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;

class STL_Widget_Review_Grid extends Widget_Base {

	public function get_name()           { return 'stl_review_grid'; }
	public function get_title()          { return __( 'Review Grid', 'stl-addons' ); }
	public function get_icon()           { return 'eicon-review'; }
	public function get_categories()     { return array( 'stl-addons', 'general' ); }
	public function get_keywords()       { return array( 'review', 'testimonial', 'rating', 'stars', 'feedback', 'grid', 'stl' ); }
	public function get_style_depends()  { return array( 'stl-review-grid' ); }

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	public function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' stl-widget stl-review-section';
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

	protected function register_controls() {
		$this->controls_reviews();
		$this->controls_layout();
		$this->controls_card();
		$this->controls_stars();
		$this->controls_text();
		$this->controls_avatar();
		$this->controls_name();
		$this->controls_role();
	}

	private function controls_reviews() {
		$this->start_controls_section( 'sec_reviews', array(
			'label' => __( 'Reviews', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$rep = new Repeater();

		$rep->add_control( 'rating', array(
			'label'   => __( 'Star Rating', 'stl-addons' ),
			'type'    => Controls_Manager::SELECT,
			'default' => '5',
			'options' => array(
				'5' => __( '5 stars', 'stl-addons' ),
				'4' => __( '4 stars', 'stl-addons' ),
				'3' => __( '3 stars', 'stl-addons' ),
				'2' => __( '2 stars', 'stl-addons' ),
				'1' => __( '1 star', 'stl-addons' ),
			),
		) );

		$rep->add_control( 'text', array(
			'label'   => __( 'Review', 'stl-addons' ),
			'type'    => Controls_Manager::TEXTAREA,
			'rows'    => 4,
			'default' => __( 'A short, honest review from a happy customer goes here — what the job was and how it went.', 'stl-addons' ),
		) );

		$rep->add_control( 'name', array(
			'label'   => __( 'Author Name', 'stl-addons' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Customer name', 'stl-addons' ),
		) );

		$rep->add_control( 'role', array(
			'label'       => __( 'Author Meta', 'stl-addons' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => __( 'What they had done', 'stl-addons' ),
			'description' => __( 'A short line under the name, e.g. the service or product.', 'stl-addons' ),
		) );

		$rep->add_control( 'avatar', array(
			'label'       => __( 'Avatar (optional)', 'stl-addons' ),
			'type'        => Controls_Manager::MEDIA,
			'description' => __( 'Leave empty to show the first letter of the name on a colored circle.', 'stl-addons' ),
		) );

		$this->add_control( 'reviews', array(
			'label'       => __( 'Reviews', 'stl-addons' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'default'     => array(
				array(
					'rating' => '5',
					'text'   => __( 'Big thank you to the team for their professionalism and service. Replaced my engine quickly and it runs smoothly with no issues. Highly recommended.', 'stl-addons' ),
					'name'   => __( 'John S', 'stl-addons' ),
					'role'   => __( 'Engine replacement', 'stl-addons' ),
				),
				array(
					'rating' => '5',
					'text'   => __( 'Amazing customer service and very good prices. They rebuilt my engine in a short time — very happy, thank you so much.', 'stl-addons' ),
					'name'   => __( 'Sarah M', 'stl-addons' ),
					'role'   => __( 'Engine rebuild', 'stl-addons' ),
				),
				array(
					'rating' => '5',
					'text'   => __( 'Absolutely fantastic service. Purchased an engine from these guys — no issues at all, running very smooth.', 'stl-addons' ),
					'name'   => __( 'Jack C', 'stl-addons' ),
					'role'   => __( 'Engine supply', 'stl-addons' ),
				),
				array(
					'rating' => '5',
					'text'   => __( "I'd highly recommend them — quality job, great service and a good price.", 'stl-addons' ),
					'name'   => __( 'Alex K', 'stl-addons' ),
					'role'   => __( 'Timing chain', 'stl-addons' ),
				),
			),
			'title_field' => '{{{ name }}} — {{{ rating }}}★',
		) );

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'avatar_size',
				'default'   => 'thumbnail',
				'separator' => 'before',
			)
		);

		$this->add_control( 'schema', array(
			'label'        => __( 'Add Review Schema (microdata)', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
			'separator'    => 'before',
			'description'  => __( 'Emits schema.org/Review markup for richer search results. Best used inside a page that also marks up the business or product being reviewed.', 'stl-addons' ),
		) );

		$this->end_controls_section();
	}

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
			'selectors'      => array( '{{WRAPPER}} .stl-review-grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ),
		) );

		$this->add_responsive_control( 'gap', array_merge(
			array(
				'label'     => __( 'Gap', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-review-grid' => 'gap: {{SIZE}}{{UNIT}};' ),
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
			'selectors' => array( '{{WRAPPER}} .stl-review-card' => 'background: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'card_border',
			'selector' => '{{WRAPPER}} .stl-review-card',
		) );

		$this->add_responsive_control( 'card_radius', array_merge(
			array(
				'label'     => __( 'Border Radius', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-review-card' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'card_padding', array(
			'label'      => __( 'Padding', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'selectors'  => array( '{{WRAPPER}} .stl-review-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'card_shadow',
			'selector' => '{{WRAPPER}} .stl-review-card',
		) );

		$this->add_control( 'card_hover_border', array(
			'label'     => __( 'Hover Border Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-review-card:hover' => 'border-color: {{VALUE}};' ),
		) );

		$this->add_control( 'enable_lift', array(
			'label'                => __( 'Lift on Hover', 'stl-addons' ),
			'type'                 => Controls_Manager::SWITCHER,
			'return_value'         => 'yes',
			'default'              => 'yes',
			'selectors_dictionary' => array( 'yes' => 'translateY(-6px)', '' => 'none' ),
			'selectors'            => array( '{{WRAPPER}} .stl-review-card:hover' => 'transform: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_stars() {
		$this->start_controls_section( 'sec_stars', array(
			'label' => __( 'Stars', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'star_on_color', array(
			'label'     => __( 'Filled Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}}' => '--stl-review-accent: {{VALUE}};' ),
		) );

		$this->add_control( 'star_off_color', array(
			'label'     => __( 'Empty Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-review-star' => 'color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'star_size', array(
			'label'      => __( 'Size', 'stl-addons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'em', 'rem' ),
			'range'      => array(
				'px'  => array( 'min' => 8, 'max' => 64 ),
				'em'  => array( 'min' => 0.5, 'max' => 5 ),
				'rem' => array( 'min' => 0.5, 'max' => 5 ),
			),
			'selectors'  => array( '{{WRAPPER}} .stl-review-stars' => 'font-size: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'star_gap', array(
			'label'      => __( 'Letter Spacing', 'stl-addons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'em', 'rem' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 20 ) ),
			'selectors'  => array( '{{WRAPPER}} .stl-review-stars' => 'letter-spacing: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'stars_spacing', array_merge(
			array(
				'label'     => __( 'Spacing Below', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-review-stars' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->end_controls_section();
	}

	private function controls_text() {
		$this->start_controls_section( 'sec_text', array(
			'label' => __( 'Review Text', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'text_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-review-text' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'text_typo',
			'selector' => '{{WRAPPER}} .stl-review-text',
		) );

		$this->add_responsive_control( 'text_spacing', array_merge(
			array(
				'label'     => __( 'Spacing Below', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-review-text' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->end_controls_section();
	}

	private function controls_avatar() {
		$this->start_controls_section( 'sec_avatar', array(
			'label' => __( 'Avatar', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'avatar_grad_help', array(
			'type' => Controls_Manager::RAW_HTML,
			'raw'  => esc_html__( 'The gradient fills the initial-letter avatar. The first color also drives the filled stars.', 'stl-addons' ),
			'content_classes' => 'elementor-descriptor',
		) );

		$this->add_control( 'avatar_color_1', array(
			'label'     => __( 'Gradient Start', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}}' => '--stl-review-accent: {{VALUE}};' ),
		) );

		$this->add_control( 'avatar_color_2', array(
			'label'     => __( 'Gradient End', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}}' => '--stl-review-accent-2: {{VALUE}};' ),
		) );

		$this->add_control( 'avatar_text_color', array(
			'label'     => __( 'Initial Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-review-av' => 'color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'avatar_size_px', array(
			'label'      => __( 'Size', 'stl-addons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'em', 'rem' ),
			'range'      => array( 'px' => array( 'min' => 24, 'max' => 96 ) ),
			'selectors'  => array( '{{WRAPPER}} .stl-review-av' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'avatar_typo',
			'label'    => __( 'Initial Typography', 'stl-addons' ),
			'selector' => '{{WRAPPER}} .stl-review-av',
		) );

		$this->end_controls_section();
	}

	private function controls_name() {
		$this->start_controls_section( 'sec_name', array(
			'label' => __( 'Author Name', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'name_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-review-name' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'name_typo',
			'selector' => '{{WRAPPER}} .stl-review-name',
		) );

		$this->end_controls_section();
	}

	private function controls_role() {
		$this->start_controls_section( 'sec_role', array(
			'label' => __( 'Author Meta', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'role_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-review-role' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'role_typo',
			'selector' => '{{WRAPPER}} .stl-review-role',
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$s       = $this->get_settings_for_display();
		$reviews = ( isset( $s['reviews'] ) && is_array( $s['reviews'] ) ) ? $s['reviews'] : array();
		$schema  = ( 'yes' === ( $s['schema'] ?? '' ) );

		if ( empty( $reviews ) ) {
			?>
			<p class="stl-review-empty"><?php esc_html_e( 'Add reviews from the Content tab → Reviews.', 'stl-addons' ); ?></p>
			<?php
			return;
		}
		?>
		<div class="stl-review-grid">
			<?php
			foreach ( $reviews as $r ) :
				$rating = max( 0, min( 5, (int) ( $r['rating'] ?? 5 ) ) );
				$text   = $r['text'] ?? '';
				$name   = $r['name'] ?? '';
				$role   = $r['role'] ?? '';
				$avatar = ( isset( $r['avatar'] ) && is_array( $r['avatar'] ) ) ? $r['avatar'] : array();
				$has_img = ! empty( $avatar['id'] ) || ! empty( $avatar['url'] );
				$initial = $name ? mb_strtoupper( mb_substr( trim( $name ), 0, 1 ) ) : '';

				$rating_label = sprintf(
					/* translators: %d: star rating from 1 to 5 */
					_n( 'Rated %d out of 5 stars', 'Rated %d out of 5 stars', $rating, 'stl-addons' ),
					$rating
				);
				?>
				<article class="stl-review-card"<?php echo $schema ? ' itemscope itemtype="https://schema.org/Review"' : ''; ?>>
					<div class="stl-review-stars" role="img" aria-label="<?php echo esc_attr( $rating_label ); ?>"<?php echo $schema ? ' itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating"' : ''; ?>>
						<?php
						if ( $schema ) {
							echo '<meta itemprop="ratingValue" content="' . esc_attr( $rating ) . '" />';
							echo '<meta itemprop="bestRating" content="5" />';
							echo '<meta itemprop="worstRating" content="1" />';
						}
						for ( $i = 1; $i <= 5; $i++ ) :
							$on = $i <= $rating ? ' is-on' : '';
							?>
							<span class="stl-review-star<?php echo esc_attr( $on ); ?>" aria-hidden="true">&#9733;</span>
						<?php endfor; ?>
					</div>

					<?php if ( $text ) : ?>
						<blockquote class="stl-review-text"<?php echo $schema ? ' itemprop="reviewBody"' : ''; ?>><?php echo esc_html( $text ); ?></blockquote>
					<?php endif; ?>

					<?php if ( $name || $has_img || $initial ) : ?>
						<footer class="stl-review-who"<?php echo $schema ? ' itemprop="author" itemscope itemtype="https://schema.org/Person"' : ''; ?>>
							<?php if ( $has_img ) : ?>
								<span class="stl-review-av stl-review-av--img">
									<?php
									$img_settings = array_merge( $s, array( 'avatar' => $avatar ) );
									echo Group_Control_Image_Size::get_attachment_image_html( $img_settings, 'avatar_size', 'avatar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor helper returns escaped markup.
									?>
								</span>
							<?php elseif ( $initial ) : ?>
								<span class="stl-review-av" aria-hidden="true"><?php echo esc_html( $initial ); ?></span>
							<?php endif; ?>
							<span class="stl-review-meta">
								<?php if ( $name ) : ?>
									<cite class="stl-review-name"<?php echo $schema ? ' itemprop="name"' : ''; ?>><?php echo esc_html( $name ); ?></cite>
								<?php endif; ?>
								<?php if ( $role ) : ?>
									<small class="stl-review-role"><?php echo esc_html( $role ); ?></small>
								<?php endif; ?>
							</span>
						</footer>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
