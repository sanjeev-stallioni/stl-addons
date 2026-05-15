<?php
/**
 * Founder Section Elementor Widget.
 *
 * Editorial "cover" layout: photo on the left, name/role/bio/quote/tags on the right,
 * over a dark card with gold accents. Ported from the [citta_leadership] shortcode.
 *
 * @package StlAddons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

class STL_Widget_Founder_Section extends \Elementor\Widget_Base {

	public function get_name()           { return 'stl_founder_section'; }
	public function get_title()          { return __( 'Founder Section', 'stl-addons' ); }
	public function get_icon()           { return 'eicon-testimonial'; }
	public function get_categories()     { return array( 'stl-addons', 'general' ); }
	public function get_keywords()       { return array( 'founder', 'leadership', 'about', 'profile', 'bio', 'stl' ); }
	public function get_style_depends()  { return array( 'stl-founder-section' ); }

	protected function register_controls() {
		$this->controls_photo();
		$this->controls_text();
		$this->controls_card_style();
		$this->controls_body_style();
		$this->controls_badge_style();
		$this->controls_vol_style();
		$this->controls_meta_style();
		$this->controls_name_style();
		$this->controls_role_style();
		$this->controls_bio_style();
		$this->controls_quote_style();
		$this->controls_tags_style();
	}

	private function controls_photo() {
		$this->start_controls_section( 'sec_photo', array(
			'label' => __( 'Photo & Badge', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'photo', array(
			'label'   => __( 'Founder Photo', 'stl-addons' ),
			'type'    => \Elementor\Controls_Manager::MEDIA,
			'default' => array( 'url' => \Elementor\Utils::get_placeholder_image_src() ),
		) );

		$this->add_control( 'badge_text', array(
			'label'   => __( 'Top-Left Badge', 'stl-addons' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Leadership', 'stl-addons' ),
		) );

		$this->add_control( 'vol_text', array(
			'label'       => __( 'Bottom-Right Label', 'stl-addons' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => __( 'Vol. 01', 'stl-addons' ),
			'description' => __( 'Small italic mark over the photo (e.g. "Vol. 01"). Leave blank to hide.', 'stl-addons' ),
		) );

		$this->end_controls_section();
	}

	private function controls_text() {
		$this->start_controls_section( 'sec_text', array(
			'label' => __( 'Founder Info', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'meta_left', array(
			'label'   => __( 'Meta — Left', 'stl-addons' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Founder, Company', 'stl-addons' ),
		) );

		$this->add_control( 'meta_accent', array(
			'label'   => __( 'Meta — Right (gold accent)', 'stl-addons' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Featured Profile', 'stl-addons' ),
		) );

		$this->add_control( 'name_first', array(
			'label'   => __( 'Name (regular)', 'stl-addons' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'First', 'stl-addons' ),
		) );

		$this->add_control( 'name_italic', array(
			'label'       => __( 'Name (italic accent)', 'stl-addons' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => __( 'Last', 'stl-addons' ),
			'description' => __( 'Rendered in italic accent style after the regular name.', 'stl-addons' ),
		) );

		$this->add_control( 'role', array(
			'label'   => __( 'Role', 'stl-addons' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Founder & CEO', 'stl-addons' ),
		) );

		$this->add_control( 'bio', array(
			'label'   => __( 'Bio', 'stl-addons' ),
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'default' => __( 'A short paragraph introducing this person — their background, focus areas, and what they bring to the team. Keep it three to four lines for the best visual balance.', 'stl-addons' ),
		) );

		$this->add_control( 'quote', array(
			'label'   => __( 'Quote', 'stl-addons' ),
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'default' => __( 'A single memorable line — a guiding principle, a belief, or a perspective that defines how this person approaches their work.', 'stl-addons' ),
		) );

		$this->add_control( 'tags', array(
			'label'       => __( 'Tags', 'stl-addons' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => __( 'Tag 1, Tag 2, Tag 3, Tag 4', 'stl-addons' ),
			'description' => __( 'Comma-separated. Each becomes a pill.', 'stl-addons' ),
		) );

		$this->end_controls_section();
	}

	private function controls_card_style() {
		$this->start_controls_section( 'sec_card_style', array(
			'label' => __( 'Card', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'card_bg', array(
			'label'     => __( 'Card Background', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-founder-cover'       => 'background: {{VALUE}};',
				'{{WRAPPER}} .stl-founder-photo'       => 'background: linear-gradient(180deg, {{VALUE}}, {{VALUE}});',
			),
		) );

		$this->add_responsive_control( 'card_max_width', array(
			'label'      => __( 'Max Width', 'stl-addons' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'range'      => array(
				'px'  => array( 'min' => 0, 'max' => 1000 ),
				'em'  => array( 'min' => 0, 'max' => 100 ),
				'rem' => array( 'min' => 0, 'max' => 100 ),
				'%'   => array( 'min' => 0, 'max' => 100 ),
				'vh'  => array( 'min' => 0, 'max' => 100 ),
				'vw'  => array( 'min' => 0, 'max' => 100 ),
			),
			'selectors'  => array( '{{WRAPPER}} .stl-founder-cover' => 'max-width: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'photo_frame_height', array(
			'label'      => __( 'Photo Frame Height', 'stl-addons' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'range'      => array(
				'px'  => array( 'min' => 0, 'max' => 1000 ),
				'em'  => array( 'min' => 0, 'max' => 100 ),
				'rem' => array( 'min' => 0, 'max' => 100 ),
				'%'   => array( 'min' => 0, 'max' => 100 ),
				'vh'  => array( 'min' => 0, 'max' => 100 ),
				'vw'  => array( 'min' => 0, 'max' => 100 ),
			),
			'selectors'  => array( '{{WRAPPER}} .stl-founder-photo' => 'min-height: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'card_radius', array(
			'label'      => __( 'Card Border Radius', 'stl-addons' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'range'      => array(
				'px'  => array( 'min' => 0, 'max' => 1000 ),
				'em'  => array( 'min' => 0, 'max' => 100 ),
				'rem' => array( 'min' => 0, 'max' => 100 ),
				'%'   => array( 'min' => 0, 'max' => 100 ),
				'vh'  => array( 'min' => 0, 'max' => 100 ),
				'vw'  => array( 'min' => 0, 'max' => 100 ),
			),
			'selectors'  => array( '{{WRAPPER}} .stl-founder-cover' => 'border-radius: {{SIZE}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}

	/**
	 * Standard slider unit set + ranges (matches the plugin-wide convention).
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

	private function controls_body_style() {
		$this->start_controls_section( 'sec_body_style', array(
			'label' => __( 'Body', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'body_padding', array(
			'label'      => __( 'Body Padding', 'stl-addons' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-founder-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'body_items_gap', array_merge(
			array(
				'label'     => __( 'Items Spacing', 'stl-addons' ),
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'selectors' => array(
					'{{WRAPPER}} .stl-founder-body'   => 'gap: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .stl-founder-body > *' => 'margin-top: 0; margin-bottom: 0;',
					'{{WRAPPER}} .stl-founder-meta'   => 'padding-bottom: 0;',
				),
			),
			$this->std_slider_args()
		) );

		$this->add_control( 'body_top_divider_color', array(
			'label'     => __( 'Top Divider Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-founder-body::before' => 'background: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_badge_style() {
		$this->start_controls_section( 'sec_badge_style', array(
			'label' => __( 'Badge', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'badge_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-founder-badge' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'badge_line_color', array(
			'label'     => __( 'Line Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-founder-badge::before' => 'background: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'badge_typo',
			'selector' => '{{WRAPPER}} .stl-founder-badge',
		) );

		$this->end_controls_section();
	}

	private function controls_vol_style() {
		$this->start_controls_section( 'sec_vol_style', array(
			'label' => __( 'Vol Mark', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'vol_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-founder-vol' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'vol_typo',
			'selector' => '{{WRAPPER}} .stl-founder-vol',
		) );

		$this->end_controls_section();
	}

	private function controls_meta_style() {
		$this->start_controls_section( 'sec_meta_style', array(
			'label' => __( 'Meta Row', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'meta_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-founder-meta' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'meta_accent_color', array(
			'label'     => __( 'Accent Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-founder-meta .stl-founder-accent' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'meta_typo',
			'selector' => '{{WRAPPER}} .stl-founder-meta',
		) );

		$this->end_controls_section();
	}

	private function controls_name_style() {
		$this->start_controls_section( 'sec_name_style', array(
			'label' => __( 'Name', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'name_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-founder-name' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'name_accent_color', array(
			'label'     => __( 'Italic Accent Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-founder-name .stl-founder-it' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'name_typo',
			'selector' => '{{WRAPPER}} .stl-founder-name',
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'name_accent_typo',
			'label'    => __( 'Italic Accent Typography', 'stl-addons' ),
			'selector' => '{{WRAPPER}} .stl-founder-name .stl-founder-it',
		) );

		$this->end_controls_section();
	}

	private function controls_role_style() {
		$this->start_controls_section( 'sec_role_style', array(
			'label' => __( 'Role', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'role_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-founder-role' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'role_typo',
			'selector' => '{{WRAPPER}} .stl-founder-role',
		) );

		$this->end_controls_section();
	}

	private function controls_bio_style() {
		$this->start_controls_section( 'sec_bio_style', array(
			'label' => __( 'Bio', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'bio_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-founder-bio' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'bio_typo',
			'selector' => '{{WRAPPER}} .stl-founder-bio',
		) );

		$this->end_controls_section();
	}

	private function controls_quote_style() {
		$this->start_controls_section( 'sec_quote_style', array(
			'label' => __( 'Quote', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'quote_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-founder-quote' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'quote_mark_color', array(
			'label'     => __( 'Quote Mark Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-founder-quote::before' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'quote_divider_color', array(
			'label'     => __( 'Top Divider Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-founder-quote' => 'border-top-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'quote_typo',
			'selector' => '{{WRAPPER}} .stl-founder-quote',
		) );

		$this->add_responsive_control( 'quote_padding', array(
			'label'      => __( 'Padding', 'stl-addons' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-founder-quote' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();
	}

	private function controls_tags_style() {
		$this->start_controls_section( 'sec_tags_style', array(
			'label' => __( 'Tags', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'tag_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-founder-tag' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'tag_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-founder-tag' => 'background: {{VALUE}};' ),
		) );

		$this->add_control( 'tag_border_color', array(
			'label'     => __( 'Border Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-founder-tag' => 'border-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'tag_typo',
			'selector' => '{{WRAPPER}} .stl-founder-tag',
		) );

		$this->add_responsive_control( 'tag_radius', array_merge(
			array(
				'label'     => __( 'Border Radius', 'stl-addons' ),
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-founder-tag' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'tag_padding', array(
			'label'      => __( 'Padding', 'stl-addons' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-founder-tag' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'tags_gap', array_merge(
			array(
				'label'     => __( 'Gap Between Tags', 'stl-addons' ),
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-founder-tags' => 'gap: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$s            = $this->get_settings_for_display();
		$photo_url    = ! empty( $s['photo']['url'] ) ? $s['photo']['url'] : '';
		$badge_text   = $s['badge_text']  ?? '';
		$vol_text     = $s['vol_text']    ?? '';
		$meta_left    = $s['meta_left']   ?? '';
		$meta_accent  = $s['meta_accent'] ?? '';
		$name_first   = $s['name_first']  ?? '';
		$name_italic  = $s['name_italic'] ?? '';
		$role         = $s['role']        ?? '';
		$bio          = $s['bio']         ?? '';
		$quote        = $s['quote']       ?? '';
		$tags_raw     = $s['tags']        ?? '';
		$tags         = array_filter( array_map( 'trim', explode( ',', $tags_raw ) ) );
		$alt          = trim( $name_first . ' ' . $name_italic );
		?>
		<div class="stl-widget stl-founder-cover">
			<div class="stl-founder-photo">
				<?php if ( $photo_url ) : ?>
					<img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" />
				<?php endif; ?>
				<?php if ( $badge_text ) : ?>
					<span class="stl-founder-badge"><?php echo esc_html( $badge_text ); ?></span>
				<?php endif; ?>
				<?php if ( $vol_text ) : ?>
					<span class="stl-founder-vol"><?php echo esc_html( $vol_text ); ?></span>
				<?php endif; ?>
			</div>
			<div class="stl-founder-body">
				<?php if ( $meta_left || $meta_accent ) : ?>
					<div class="stl-founder-meta">
						<span><?php echo esc_html( $meta_left ); ?></span>
						<?php if ( $meta_accent ) : ?>
							<span class="stl-founder-accent"><?php echo esc_html( $meta_accent ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $name_first || $name_italic ) : ?>
					<h3 class="stl-founder-name">
						<?php echo esc_html( $name_first ); ?>
						<?php if ( $name_italic ) : ?>
							<span class="stl-founder-it"><?php echo esc_html( $name_italic ); ?></span>
						<?php endif; ?>
					</h3>
				<?php endif; ?>

				<?php if ( $role ) : ?>
					<p class="stl-founder-role"><?php echo esc_html( $role ); ?></p>
				<?php endif; ?>

				<?php if ( $bio ) : ?>
					<p class="stl-founder-bio"><?php echo wp_kses_post( $bio ); ?></p>
				<?php endif; ?>

				<?php if ( $quote ) : ?>
					<p class="stl-founder-quote"><?php echo wp_kses_post( $quote ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $tags ) ) : ?>
					<div class="stl-founder-tags">
						<?php foreach ( $tags as $tag ) : ?>
							<span class="stl-founder-tag"><?php echo esc_html( $tag ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}

