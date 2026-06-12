<?php
/**
 * Marquee Elementor Widget.
 *
 * A horizontally scrolling keyword strip ("ticker"). The animation is pure CSS
 * — no JavaScript is enqueued — so it costs nothing at runtime beyond a single
 * GPU-composited transform. The item list is rendered twice (the second copy is
 * aria-hidden) so the loop is perfectly seamless: the track is exactly two equal
 * groups wide and animates by translateX(-50%).
 *
 * SEO: every item is real, crawlable text (the visible first copy is not hidden
 * from assistive tech or search engines); the duplicate is marked aria-hidden so
 * screen readers announce the content only once.
 *
 * Ported from the "marquee" band in hyun-engines-redesign.html and adapted to the
 * stl-addons conventions (scoped classes, visual baselines in CSS, no style-tab
 * defaults, optimized DOM).
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
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

class STL_Widget_Marquee extends Widget_Base {

	public function get_name()          { return 'stl_marquee'; }
	public function get_title()         { return __( 'Marquee', 'stl-addons' ); }
	public function get_icon()          { return 'eicon-slider-push'; }
	public function get_categories()    { return array( 'stl-addons', 'general' ); }
	public function get_keywords()      { return array( 'marquee', 'ticker', 'scroll', 'slider', 'banner', 'strip', 'stl' ); }
	public function get_style_depends() { return array( 'stl-marquee' ); }

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	public function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' stl-widget stl-marquee';
	}

	protected function register_controls() {
		$this->controls_content();
		$this->controls_settings();
		$this->controls_band_style();
		$this->controls_text_style();
		$this->controls_separator_style();
	}

	private function controls_content() {
		$this->start_controls_section( 'sec_content', array(
			'label' => __( 'Marquee', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$rep = new Repeater();

		$rep->add_control( 'text', array(
			'label'       => __( 'Text', 'stl-addons' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => __( 'New item', 'stl-addons' ),
			'label_block' => true,
			'dynamic'     => array( 'active' => true ),
		) );

		$rep->add_control( 'link', array(
			'label'         => __( 'Link (optional)', 'stl-addons' ),
			'type'          => Controls_Manager::URL,
			'placeholder'   => 'https://example.com',
			'show_external' => true,
			'dynamic'       => array( 'active' => true ),
		) );

		$this->add_control( 'items', array(
			'label'       => __( 'Items', 'stl-addons' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'default'     => array(
				array( 'text' => __( 'Reconditioned Engines', 'stl-addons' ) ),
				array( 'text' => __( 'Engine Rebuilds', 'stl-addons' ) ),
				array( 'text' => __( 'Timing Chains', 'stl-addons' ) ),
				array( 'text' => __( 'Supply & Fit', 'stl-addons' ) ),
				array( 'text' => __( 'Workshop Service', 'stl-addons' ) ),
			),
			'title_field' => '{{{ text }}}',
		) );

		$this->add_control( 'show_separator', array(
			'label'        => __( 'Show Separator', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Yes', 'stl-addons' ),
			'label_off'    => __( 'No', 'stl-addons' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'separator_char', array(
			'label'     => __( 'Separator Character', 'stl-addons' ),
			'type'      => Controls_Manager::TEXT,
			'default'   => '●',
			'condition' => array( 'show_separator' => 'yes' ),
			'selectors' => array(
				'{{WRAPPER}} .stl-mq' => '--stl-mq-sep: "{{VALUE}}";',
			),
		) );

		$this->end_controls_section();
	}

	private function controls_settings() {
		$this->start_controls_section( 'sec_settings', array(
			'label' => __( 'Settings', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'direction', array(
			'label'   => __( 'Direction', 'stl-addons' ),
			'type'    => Controls_Manager::CHOOSE,
			'options' => array(
				'left'  => array(
					'title' => __( 'Left', 'stl-addons' ),
					'icon'  => 'eicon-arrow-left',
				),
				'right' => array(
					'title' => __( 'Right', 'stl-addons' ),
					'icon'  => 'eicon-arrow-right',
				),
			),
			'default' => 'left',
			'toggle'  => false,
		) );

		// Speed: no default — the CSS baseline (26s) applies until the user sets one.
		$this->add_responsive_control( 'duration', array(
			'label'       => __( 'Animation Duration (seconds)', 'stl-addons' ),
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => array( 's' ),
			'range'       => array( 's' => array( 'min' => 4, 'max' => 120, 'step' => 1 ) ),
			'description'  => __( 'Lower = faster scroll. Leave blank for the default (26s).', 'stl-addons' ),
			'selectors'   => array(
				'{{WRAPPER}} .stl-mq-track' => 'animation-duration: {{SIZE}}s;',
			),
		) );

		$this->add_control( 'pause_on_hover', array(
			'label'        => __( 'Pause on Hover', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Yes', 'stl-addons' ),
			'label_off'    => __( 'No', 'stl-addons' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'full_width', array(
			'label'        => __( 'Stretch to Full Viewport Width', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Yes', 'stl-addons' ),
			'label_off'    => __( 'No', 'stl-addons' ),
			'return_value' => 'yes',
			'default'      => '',
			'description'  => __( 'Breaks the band out of its column to span the screen edge-to-edge. Best used in a container with no horizontal padding.', 'stl-addons' ),
		) );

		$this->end_controls_section();
	}

	private function controls_band_style() {
		$this->start_controls_section( 'sec_band', array(
			'label' => __( 'Band', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'band_background', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-mq' => 'background: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'band_border',
			'selector' => '{{WRAPPER}} .stl-mq',
		) );

		$this->add_responsive_control( 'band_radius', array(
			'label'      => __( 'Border Radius', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-mq' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
			),
		) );

		$this->add_responsive_control( 'band_padding', array(
			'label'      => __( 'Vertical Padding', 'stl-addons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'em', 'rem' ),
			'range'      => array(
				'px'  => array( 'min' => 0, 'max' => 80, 'step' => 1 ),
				'em'  => array( 'min' => 0, 'max' => 6 ),
				'rem' => array( 'min' => 0, 'max' => 6 ),
			),
			'selectors'  => array(
				'{{WRAPPER}} .stl-mq-track' => 'padding-block: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'item_gap', array(
			'label'      => __( 'Gap Between Items', 'stl-addons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'em', 'rem' ),
			'range'      => array(
				'px'  => array( 'min' => 0, 'max' => 160, 'step' => 1 ),
				'em'  => array( 'min' => 0, 'max' => 12 ),
				'rem' => array( 'min' => 0, 'max' => 12 ),
			),
			'selectors'  => array(
				'{{WRAPPER}} .stl-mq' => '--stl-mq-gap: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();
	}

	private function controls_text_style() {
		$this->start_controls_section( 'sec_text', array(
			'label' => __( 'Text', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'text_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-mq-item' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'text_hover_color', array(
			'label'     => __( 'Link Hover Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} a.stl-mq-item:hover' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'text_typography',
			'selector' => '{{WRAPPER}} .stl-mq-item',
		) );

		$this->end_controls_section();
	}

	private function controls_separator_style() {
		$this->start_controls_section( 'sec_separator', array(
			'label'     => __( 'Separator', 'stl-addons' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_separator' => 'yes' ),
		) );

		$this->add_control( 'separator_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-mq' => '--stl-mq-sep-color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'separator_size', array(
			'label'      => __( 'Size', 'stl-addons' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'em', 'rem' ),
			'range'      => array(
				'px'  => array( 'min' => 2, 'max' => 40, 'step' => 1 ),
				'em'  => array( 'min' => 0.2, 'max' => 3, 'step' => 0.05 ),
				'rem' => array( 'min' => 0.2, 'max' => 3, 'step' => 0.05 ),
			),
			'selectors'  => array(
				'{{WRAPPER}} .stl-mq-item::after' => 'font-size: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = ( isset( $s['items'] ) && is_array( $s['items'] ) ) ? $s['items'] : array();

		// Drop empty rows up front so both copies render an identical, gap-free set.
		$items = array_values( array_filter( $items, static function ( $i ) {
			return isset( $i['text'] ) && '' !== trim( (string) $i['text'] );
		} ) );

		if ( empty( $items ) ) {
			?>
			<p class="stl-mq-empty"><?php esc_html_e( 'Add marquee items from the Content tab → Marquee.', 'stl-addons' ); ?></p>
			<?php
			return;
		}

		$direction = ( ( $s['direction'] ?? 'left' ) === 'right' ) ? 'right' : 'left';

		$classes = array( 'stl-mq', 'stl-mq--' . $direction );
		if ( ( $s['pause_on_hover'] ?? '' ) === 'yes' ) {
			$classes[] = 'stl-mq--pause';
		}
		if ( ( $s['full_width'] ?? '' ) === 'yes' ) {
			$classes[] = 'stl-mq--full';
		}
		if ( ( $s['show_separator'] ?? 'yes' ) !== 'yes' ) {
			$classes[] = 'stl-mq--no-sep';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<div class="stl-mq-track">
				<?php for ( $copy = 0; $copy < 2; $copy++ ) : ?>
					<div class="stl-mq-group"<?php echo 0 === $copy ? '' : ' aria-hidden="true"'; ?>>
						<?php foreach ( $items as $item ) :
							$text = $item['text'] ?? '';
							$url  = $item['link']['url'] ?? '';
							if ( $url ) :
								$rel    = ! empty( $item['link']['nofollow'] ) ? 'nofollow' : '';
								$target = ! empty( $item['link']['is_external'] ) ? '_blank' : '';
								if ( '_blank' === $target ) {
									$rel = trim( $rel . ' noopener' );
								}
								?>
								<a class="stl-mq-item" href="<?php echo esc_url( $url ); ?>"<?php
									echo $target ? ' target="' . esc_attr( $target ) . '"' : '';
									echo $rel ? ' rel="' . esc_attr( $rel ) . '"' : '';
								?>><?php echo esc_html( $text ); ?></a>
							<?php else : ?>
								<span class="stl-mq-item"><?php echo esc_html( $text ); ?></span>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php endfor; ?>
			</div>
		</div>
		<?php
	}
}
