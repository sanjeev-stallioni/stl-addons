<?php
/**
 * Team Grid Elementor Widget.
 *
 * @package StlAddons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

class STL_Widget_Team_Grid extends \Elementor\Widget_Base {

	public function get_name()           { return 'stl_team_grid'; }
	public function get_title()          { return __( 'Team Grid', 'stl-addons' ); }
	public function get_icon()           { return 'eicon-person'; }
	public function get_categories()     { return array( 'stl-addons', 'general' ); }
	public function get_keywords()       { return array( 'team', 'staff', 'people', 'members', 'about', 'crew', 'stl' ); }
	public function get_style_depends()  { return array( 'stl-team-grid' ); }

	protected function register_controls() {
		$this->controls_members();
		$this->controls_layout();
		$this->controls_card();
		$this->controls_image();
		$this->controls_name();
		$this->controls_role();
		$this->controls_bio();
		$this->controls_tags();
		$this->controls_hover();
	}

	private function controls_members() {
		$this->start_controls_section( 'sec_members', array(
			'label' => __( 'Team Members', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		) );

		$rep = new \Elementor\Repeater();

		$rep->add_control( 'photo', array(
			'label'   => __( 'Photo', 'stl-addons' ),
			'type'    => \Elementor\Controls_Manager::MEDIA,
			'default' => array( 'url' => \Elementor\Utils::get_placeholder_image_src() ),
		) );

		$rep->add_control( 'name', array(
			'label'   => __( 'Name', 'stl-addons' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Team member name', 'stl-addons' ),
		) );

		$rep->add_control( 'role', array(
			'label'   => __( 'Role / Title', 'stl-addons' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Role at company', 'stl-addons' ),
		) );

		$rep->add_control( 'bio', array(
			'label'   => __( 'Short Bio', 'stl-addons' ),
			'type'    => \Elementor\Controls_Manager::WYSIWYG,
			'default' => __( 'A short, two-line description that introduces this person and what they do.', 'stl-addons' ),
		) );

		$rep->add_control( 'tags', array(
			'label'       => __( 'Tag Pills', 'stl-addons' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'Tag 1, Tag 2',
			'description' => __( 'Comma-separated short labels (e.g. credentials, skills).', 'stl-addons' ),
		) );

		$this->add_group_control(
			\Elementor\Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'image_size',
				'default'   => 'large',
				'separator' => 'before',
			)
		);

		$this->add_control( 'members', array(
			'label'       => __( 'Members', 'stl-addons' ),
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'default'     => array(
				array(
					'name' => 'Person One', 'role' => 'Role One',
					'bio'  => 'A short two-line description introducing this person.',
					'tags' => 'Tag 1, Tag 2',
				),
				array(
					'name' => 'Person Two', 'role' => 'Role Two',
					'bio'  => 'A short two-line description introducing this person.',
					'tags' => 'Tag 1, Tag 2',
				),
				array(
					'name' => 'Person Three', 'role' => 'Role Three',
					'bio'  => 'A short two-line description introducing this person.',
					'tags' => 'Tag 1, Tag 2',
				),
			),
			'title_field' => '{{{ name }}}',
		) );

		$this->end_controls_section();
	}

	private function controls_layout() {
		$this->start_controls_section( 'sec_layout', array(
			'label' => __( 'Layout', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'columns', array(
			'label'   => __( 'Columns', 'stl-addons' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => '3',
			'tablet_default' => '2',
			'mobile_default' => '1',
			'options' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
			'selectors' => array( '{{WRAPPER}} .stl-team-grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ),
		) );

		$this->add_responsive_control( 'gap', array(
			'label'      => __( 'Column / Row Gap', 'stl-addons' ),
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
			'selectors'  => array( '{{WRAPPER}} .stl-team-grid' => 'gap: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_control( 'bg_color', array(
			'label'     => __( 'Section Background', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-team-section' => 'background: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'section_padding', array(
			'label'      => __( 'Section Padding', 'stl-addons' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'selectors'  => array( '{{WRAPPER}} .stl-team-section' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'max_width', array(
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
			'selectors'  => array( '{{WRAPPER}} .stl-team-wrap' => 'max-width: {{SIZE}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_card() {
		$this->start_controls_section( 'sec_card', array(
			'label' => __( 'Card', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'card_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-team-card' => 'background: {{VALUE}};' ),
		) );

		$this->add_control( 'card_border_color', array(
			'label'     => __( 'Border Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-team-card' => 'border-color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'card_radius', array(
			'label'      => __( 'Border Radius', 'stl-addons' ),
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
			'selectors'  => array( '{{WRAPPER}} .stl-team-card' => 'border-radius: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'card_padding', array(
			'label'      => __( 'Body Padding', 'stl-addons' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'selectors'  => array( '{{WRAPPER}} .stl-team-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'card_shadow',
			'selector' => '{{WRAPPER}} .stl-team-card',
		) );

		$this->end_controls_section();
	}

	private function controls_image() {
		$this->start_controls_section( 'sec_image', array(
			'label' => __( 'Image', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'image_height', array(
			'label'      => __( 'Height', 'stl-addons' ),
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
			'selectors'  => array( '{{WRAPPER}} .stl-team-image' => 'height: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_control( 'image_fit', array(
			'label'   => __( 'Object Fit', 'stl-addons' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'cover',
			'options' => array(
				'cover'   => __( 'Cover', 'stl-addons' ),
				'contain' => __( 'Contain', 'stl-addons' ),
			),
			'selectors' => array( '{{WRAPPER}} .stl-team-image img' => 'object-fit: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	private function controls_name() {
		$this->start_controls_section( 'sec_name', array(
			'label' => __( 'Name', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );
		$this->add_control( 'name_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-team-name' => 'color: {{VALUE}};' ),
		) );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'name_typo',
			'selector' => '{{WRAPPER}} .stl-team-name',
		) );
		$this->end_controls_section();
	}

	private function controls_role() {
		$this->start_controls_section( 'sec_role', array(
			'label' => __( 'Role', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );
		$this->add_control( 'role_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-team-role' => 'color: {{VALUE}};' ),
		) );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'role_typo',
			'selector' => '{{WRAPPER}} .stl-team-role',
		) );
		$this->end_controls_section();
	}

	private function controls_bio() {
		$this->start_controls_section( 'sec_bio', array(
			'label' => __( 'Bio', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );
		$this->add_control( 'bio_color', array(
			'label'     => __( 'Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-team-bio' => 'color: {{VALUE}};' ),
		) );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'bio_typo',
			'selector' => '{{WRAPPER}} .stl-team-bio',
		) );
		$this->end_controls_section();
	}

	private function controls_tags() {
		$this->start_controls_section( 'sec_tags', array(
			'label' => __( 'Tag Pills', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );
		$this->add_control( 'tag_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-team-tag' => 'background: {{VALUE}};' ),
		) );
		$this->add_control( 'tag_border', array(
			'label'     => __( 'Border', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-team-tag' => 'border-color: {{VALUE}};' ),
		) );
		$this->add_control( 'tag_color', array(
			'label'     => __( 'Text Color', 'stl-addons' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-team-tag' => 'color: {{VALUE}};' ),
		) );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'tag_typo',
			'selector' => '{{WRAPPER}} .stl-team-tag',
		) );
		$this->add_responsive_control( 'tag_radius', array(
			'label'      => __( 'Radius', 'stl-addons' ),
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
			'selectors'  => array( '{{WRAPPER}} .stl-team-tag' => 'border-radius: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();
	}

	private function controls_hover() {
		$this->start_controls_section( 'sec_hover', array(
			'label' => __( 'Hover', 'stl-addons' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'enable_lift', array(
			'label'        => __( 'Lift Card on Hover', 'stl-addons' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'selectors_dictionary' => array( 'yes' => 'translateY(-4px)', '' => 'none' ),
			'selectors'    => array( '{{WRAPPER}} .stl-team-card:hover' => 'transform: {{VALUE}};' ),
		) );

		$this->add_control( 'enable_zoom', array(
			'label'        => __( 'Zoom Image on Hover', 'stl-addons' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'selectors_dictionary' => array( 'yes' => 'scale(1.04)', '' => 'scale(1)' ),
			'selectors'    => array( '{{WRAPPER}} .stl-team-card:hover .stl-team-image img' => 'transform: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$s       = $this->get_settings_for_display();
		$members = ( isset( $s['members'] ) && is_array( $s['members'] ) ) ? $s['members'] : array();
		?>
		<section class="stl-widget stl-team-section">
			<div class="stl-team-wrap">
				<?php if ( empty( $members ) ) : ?>
					<p class="stl-team-empty"><?php esc_html_e( 'Add members from the Content tab → Team Members.', 'stl-addons' ); ?></p>
				<?php else : ?>
					<div class="stl-team-grid">
						<?php foreach ( $members as $m ) :
							$photo = isset( $m['photo'] ) && is_array( $m['photo'] ) ? $m['photo'] : array();
							$name  = $m['name'] ?? '';
							$role  = $m['role'] ?? '';
							$bio   = $m['bio']  ?? '';
							$tags  = isset( $m['tags'] ) ? $m['tags'] : '';
							$tag_list = array_filter( array_map( 'trim', explode( ',', $tags ) ) );
							$image_settings = array_merge( $s, array( 'photo' => $photo ) );
							?>
							<article class="stl-team-card">
								<div class="stl-team-image">
									<?php
									if ( ! empty( $photo['id'] ) || ! empty( $photo['url'] ) ) {
										echo \Elementor\Group_Control_Image_Size::get_attachment_image_html( $image_settings, 'image_size', 'photo' );
									} else {
										printf(
											'<img src="%s" alt="%s" loading="lazy" />',
											esc_url( \Elementor\Utils::get_placeholder_image_src() ),
											esc_attr( $name )
										);
									}
									?>
								</div>
								<div class="stl-team-body">
									<?php if ( $name ) : ?>
										<h3 class="stl-team-name"><?php echo esc_html( $name ); ?></h3>
									<?php endif; ?>
									<?php if ( $role ) : ?>
										<div class="stl-team-role"><?php echo esc_html( $role ); ?></div>
									<?php endif; ?>
									<?php if ( $bio ) : ?>
										<div class="stl-team-bio stl-wysiwyg-editor"><?php echo wp_kses_post( wpautop( $bio ) ); ?></div>
									<?php endif; ?>
									<?php if ( ! empty( $tag_list ) ) : ?>
										<div class="stl-team-tags">
											<?php foreach ( $tag_list as $tag ) : ?>
												<span class="stl-team-tag"><?php echo esc_html( $tag ); ?></span>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
