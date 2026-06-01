<?php
/**
 * Form Tabs Elementor Widget.
 *
 * A two-or-more tab selector that switches between forms rendered by another
 * plugin (Contact Form 7, WPForms, Gravity Forms, etc.) via shortcode. Each
 * tab is a card with an icon, title, meta line and short description; clicking
 * a card reveals the matching form panel below.
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
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class STL_Widget_Form_Tabs extends Widget_Base {

	const ALLOWED_HEADING_TAGS = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );

	public function get_name()           { return 'stl_form_tabs'; }
	public function get_title()          { return __( 'Form Tabs', 'stl-addons' ); }
	public function get_icon()           { return 'eicon-form-horizontal'; }
	public function get_categories()     { return array( 'stl-addons', 'general' ); }
	public function get_keywords()       { return array( 'form', 'tabs', 'shortcode', 'cf7', 'contact form 7', 'wpforms', 'gravity', 'stl' ); }
	public function get_style_depends()  { return array( 'stl-form-tabs' ); }
	public function get_script_depends() { return array( 'stl-form-tabs' ); }

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	public function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' stl-widget stl-form-tabs';
	}

	private static function is_editor() {
		return class_exists( '\Elementor\Plugin' )
			&& isset( \Elementor\Plugin::$instance->editor )
			&& \Elementor\Plugin::$instance->editor->is_edit_mode();
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

	protected function register_controls() {
		$this->controls_tabs();
		$this->controls_behavior();
		$this->controls_layout();
		$this->controls_card_style();
		$this->controls_panel_style();
	}

	private function controls_tabs() {
		$this->start_controls_section( 'sec_tabs', array(
			'label' => __( 'Tabs', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'panel_heading_tag', array(
			'label'       => __( 'Form Heading Tag', 'stl-addons' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'h2',
			'options'     => array(
				'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6',
			),
			'description' => __( 'Pick the heading level that matches your page outline.', 'stl-addons' ),
		) );

		$rep = new Repeater();

		$rep->add_control( 'icon', array(
			'label'   => __( 'Card Icon', 'stl-addons' ),
			'type'    => Controls_Manager::ICONS,
			'default' => array(
				'value'   => 'far fa-user',
				'library' => 'fa-regular',
			),
		) );

		$rep->add_control( 'title', array(
			'label'       => __( 'Card Title', 'stl-addons' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => __( "I'm a Support Coordinator", 'stl-addons' ),
			'label_block' => true,
		) );

		$rep->add_control( 'meta', array(
			'label'       => __( 'Card Meta', 'stl-addons' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => __( '13 questions · about 6 minutes', 'stl-addons' ),
			'label_block' => true,
			'description' => __( 'Short subtitle shown under the card title.', 'stl-addons' ),
		) );

		$rep->add_control( 'desc', array(
			'label'   => __( 'Card Description', 'stl-addons' ),
			'type'    => Controls_Manager::TEXTAREA,
			'rows'    => 3,
			'default' => __( "Help us shape a pathway that's clear and easy to recommend — sector context, feedback, and a no-obligation conversation if it's useful.", 'stl-addons' ),
		) );

		$rep->add_control( 'panel_heading', array(
			'label'       => __( 'Form Heading', 'stl-addons' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => __( 'Support Coordinator Feedback', 'stl-addons' ),
			'label_block' => true,
			'separator'   => 'before',
		) );

		$rep->add_control( 'panel_subhead', array(
			'label'       => __( 'Form Subheading', 'stl-addons' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => __( 'Help shape a structured trauma recovery pathway', 'stl-addons' ),
			'label_block' => true,
		) );

		$rep->add_control( 'shortcode', array(
			'label'       => __( 'Form Shortcode', 'stl-addons' ),
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 3,
			'placeholder' => '[contact-form-7 id="123" title="Coordinator"]',
			'description' => __( 'Paste a shortcode from Contact Form 7, WPForms, Gravity Forms, etc.', 'stl-addons' ),
			'dynamic'     => array( 'active' => true ),
		) );

		$rep->add_control( 'onclick_js', array(
			'label'       => __( 'On-Click JavaScript', 'stl-addons' ),
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 3,
			'separator'   => 'before',
			'placeholder' => "gtag('event','tab_click',{tab:'sc'});",
			'description' => __( 'Optional JS to run when this tab is clicked. The tab still switches to its form afterwards. Inside the snippet: <code>this</code> is the button element and <code>event</code> is the click event.', 'stl-addons' ),
		) );

		$this->add_control( 'tabs', array(
			'label'       => __( 'Tabs', 'stl-addons' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'default'     => array(
				array(
					'icon'          => array( 'value' => 'far fa-user', 'library' => 'fa-regular' ),
					'title'         => __( "I'm a Support Coordinator", 'stl-addons' ),
					'meta'          => __( '13 questions · about 6 minutes', 'stl-addons' ),
					'desc'          => __( "Help us shape a pathway that's clear and easy to recommend — sector context, feedback, and a no-obligation conversation if it's useful.", 'stl-addons' ),
					'panel_heading' => __( 'Support Coordinator Feedback', 'stl-addons' ),
					'panel_subhead' => __( 'Help shape a structured trauma recovery pathway', 'stl-addons' ),
					'shortcode'     => '',
				),
				array(
					'icon'          => array( 'value' => 'fas fa-heart', 'library' => 'fa-solid' ),
					'title'         => __( "I'm a Participant, Family Member, or Carer", 'stl-addons' ),
					'meta'          => __( '11 questions · about 5 minutes', 'stl-addons' ),
					'desc'          => __( "Tell us what matters to you about recovery and how Citta lands — no pressure, no assessment, no sign-up required.", 'stl-addons' ),
					'panel_heading' => __( 'Participant Feedback', 'stl-addons' ),
					'panel_subhead' => __( 'For participants, family members, and carers', 'stl-addons' ),
					'shortcode'     => '',
				),
			),
			'title_field' => '{{{ title }}}',
		) );

		$this->end_controls_section();
	}

	private function controls_behavior() {
		$this->start_controls_section( 'sec_behavior', array(
			'label' => __( 'Behavior', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'default_open', array(
			'label'       => __( 'Default Open Tab', 'stl-addons' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => 'first',
			'options'     => array(
				'none'  => __( 'None (collapsed)', 'stl-addons' ),
				'first' => __( 'First tab', 'stl-addons' ),
			),
			'description' => __( 'Whether a form is visible before the visitor picks a card.', 'stl-addons' ),
		) );

		$this->add_control( 'smooth_scroll', array(
			'label'        => __( 'Smooth Scroll To Form', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'On', 'stl-addons' ),
			'label_off'    => __( 'Off', 'stl-addons' ),
			'return_value' => 'yes',
			'default'      => '',
			'description'  => __( 'Scroll the page to the form panel after a tab is picked.', 'stl-addons' ),
		) );

		$this->add_control( 'show_arrow', array(
			'label'        => __( 'Show Card Arrow', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->add_control( 'show_panel_head', array(
			'label'        => __( 'Show Form Heading Bar', 'stl-addons' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		) );

		$this->end_controls_section();
	}

	private function controls_layout() {
		$this->start_controls_section( 'sec_layout', array(
			'label' => __( 'Layout', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'cards_columns', array(
			'label'     => __( 'Card Columns', 'stl-addons' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '2',
			'options'   => array( '1' => '1', '2' => '2', '3' => '3' ),
			'selectors' => array(
				'{{WRAPPER}} .stl-ft-selector' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
			),
		) );

		$this->add_responsive_control( 'cards_gap', array_merge(
			array(
				'label'     => __( 'Card Gap', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-ft-selector' => 'gap: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'selector_max_width', array_merge(
			array(
				'label'     => __( 'Selector Max Width', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array(
					'{{WRAPPER}} .stl-ft-selector' => 'max-width: {{SIZE}}{{UNIT}}; margin-left: auto; margin-right: auto;',
				),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'panel_max_width', array_merge(
			array(
				'label'     => __( 'Form Panel Max Width', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array(
					'{{WRAPPER}} .stl-ft-panel' => 'max-width: {{SIZE}}{{UNIT}}; margin-left: auto; margin-right: auto;',
				),
			),
			$this->std_slider_args()
		) );

		$this->add_responsive_control( 'panel_gap', array_merge(
			array(
				'label'     => __( 'Gap Above Panel', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-ft-panel' => 'margin-top: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->end_controls_section();
	}

	private function controls_card_style() {
		$this->start_controls_section( 'sec_card', array(
			'label' => __( 'Cards', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'card_padding', array(
			'label'      => __( 'Padding', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-ft-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'card_radius', array_merge(
			array(
				'label'     => __( 'Border Radius', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-ft-card' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'card_title_typography',
			'label'    => __( 'Title Typography', 'stl-addons' ),
			'selector' => '{{WRAPPER}} .stl-ft-card-title',
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'card_meta_typography',
			'label'    => __( 'Meta Typography', 'stl-addons' ),
			'selector' => '{{WRAPPER}} .stl-ft-card-meta',
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'card_desc_typography',
			'label'    => __( 'Description Typography', 'stl-addons' ),
			'selector' => '{{WRAPPER}} .stl-ft-card-desc',
		) );

		$this->start_controls_tabs( 'card_state_tabs' );

		// Normal.
		$this->start_controls_tab( 'card_state_normal', array( 'label' => __( 'Normal', 'stl-addons' ) ) );

		$this->add_control( 'card_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ft-card' => 'background: {{VALUE}};' ),
		) );

		$this->add_control( 'card_text', array(
			'label'     => __( 'Title Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ft-card-title' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'card_meta_color', array(
			'label'     => __( 'Meta Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ft-card-meta' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'card_desc_color', array(
			'label'     => __( 'Description Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ft-card-desc' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'card_icon_color', array(
			'label'     => __( 'Icon Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ft-card-icon' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'card_icon_bg', array(
			'label'     => __( 'Icon Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ft-card-icon' => 'background: {{VALUE}};' ),
		) );

		$this->add_control( 'card_icon_border', array(
			'label'     => __( 'Icon Border Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ft-card-icon' => 'border-color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'card_border',
			'selector' => '{{WRAPPER}} .stl-ft-card',
		) );

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'card_shadow',
			'selector' => '{{WRAPPER}} .stl-ft-card',
		) );

		$this->end_controls_tab();

		// Active.
		$this->start_controls_tab( 'card_state_active', array( 'label' => __( 'Active', 'stl-addons' ) ) );

		$this->add_control( 'card_bg_active', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-ft-card[aria-pressed="true"]' => 'background: {{VALUE}}; border-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'card_text_active', array(
			'label'     => __( 'Title Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-ft-card[aria-pressed="true"] .stl-ft-card-title' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'card_meta_active', array(
			'label'     => __( 'Meta Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-ft-card[aria-pressed="true"] .stl-ft-card-meta' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'card_desc_active', array(
			'label'     => __( 'Description Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-ft-card[aria-pressed="true"] .stl-ft-card-desc' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'card_icon_color_active', array(
			'label'     => __( 'Icon Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-ft-card[aria-pressed="true"] .stl-ft-card-icon' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'card_icon_bg_active', array(
			'label'     => __( 'Icon Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-ft-card[aria-pressed="true"] .stl-ft-card-icon' => 'background: {{VALUE}};',
			),
		) );

		$this->add_control( 'card_icon_border_active', array(
			'label'     => __( 'Icon Border Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .stl-ft-card[aria-pressed="true"] .stl-ft-card-icon' => 'border-color: {{VALUE}};',
			),
		) );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	private function controls_panel_style() {
		$this->start_controls_section( 'sec_panel', array(
			'label' => __( 'Form Panel', 'stl-addons' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'panel_bg', array(
			'label'     => __( 'Background', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ft-panel' => 'background: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'panel_padding', array(
			'label'      => __( 'Padding', 'stl-addons' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%', 'vh', 'vw', 'custom' ),
			'selectors'  => array(
				'{{WRAPPER}} .stl-ft-panel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'panel_radius', array_merge(
			array(
				'label'     => __( 'Border Radius', 'stl-addons' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array( '{{WRAPPER}} .stl-ft-panel' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			),
			$this->std_slider_args()
		) );

		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'panel_border',
			'selector' => '{{WRAPPER}} .stl-ft-panel',
		) );

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'panel_shadow',
			'selector' => '{{WRAPPER}} .stl-ft-panel',
		) );

		$this->add_control( 'panel_heading_color', array(
			'label'     => __( 'Heading Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ft-panel-heading' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'panel_heading_typography',
			'label'    => __( 'Heading Typography', 'stl-addons' ),
			'selector' => '{{WRAPPER}} .stl-ft-panel-heading',
		) );

		$this->add_control( 'panel_subhead_color', array(
			'label'     => __( 'Subheading Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ft-panel-subhead' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'panel_subhead_typography',
			'label'    => __( 'Subheading Typography', 'stl-addons' ),
			'selector' => '{{WRAPPER}} .stl-ft-panel-subhead',
		) );

		$this->add_control( 'panel_divider_color', array(
			'label'     => __( 'Heading Divider Color', 'stl-addons' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .stl-ft-panel-head' => 'border-bottom-color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$tabs  = ( isset( $s['tabs'] ) && is_array( $s['tabs'] ) ) ? $s['tabs'] : array();

		if ( empty( $tabs ) ) {
			printf(
				'<p class="stl-ft-empty">%s</p>',
				esc_html__( 'Add a tab from the Content tab → Tabs.', 'stl-addons' )
			);
			return;
		}

		$default_open  = ( 'first' === ( $s['default_open'] ?? 'none' ) ) ? 0 : -1;
		$smooth_scroll = ( 'yes' === ( $s['smooth_scroll'] ?? '' ) ) ? '1' : '0';
		$show_arrow    = 'yes' === ( $s['show_arrow'] ?? 'yes' );
		$show_head     = 'yes' === ( $s['show_panel_head'] ?? 'yes' );

		$tag_raw    = $s['panel_heading_tag'] ?? 'h2';
		$heading_tag = in_array( $tag_raw, self::ALLOWED_HEADING_TAGS, true ) ? $tag_raw : 'h2';

		$widget_id = 'stl-ft-' . $this->get_id();

		$this->add_render_attribute( 'root', 'class', 'stl-ft-root' );
		$this->add_render_attribute( 'root', 'data-smooth', $smooth_scroll );
		$this->add_render_attribute( 'root', 'data-open', (string) $default_open );
		?>
		<div <?php echo $this->get_render_attribute_string( 'root' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="stl-ft-selector" role="tablist" aria-label="<?php esc_attr_e( 'Choose form', 'stl-addons' ); ?>">
				<?php foreach ( $tabs as $i => $tab ) :
					$pressed    = ( $i === $default_open ) ? 'true' : 'false';
					$tab_id     = $widget_id . '-tab-' . $i;
					$panel_id   = $widget_id . '-panel-' . $i;
					$title      = $tab['title']      ?? '';
					$meta       = $tab['meta']       ?? '';
					$desc       = $tab['desc']       ?? '';
					$icon       = $tab['icon']       ?? array();
					$onclick_js = trim( (string) ( $tab['onclick_js'] ?? '' ) );
					?>
					<button
						type="button"
						class="stl-ft-card"
						role="tab"
						id="<?php echo esc_attr( $tab_id ); ?>"
						aria-pressed="<?php echo esc_attr( $pressed ); ?>"
						aria-controls="<?php echo esc_attr( $panel_id ); ?>"
						data-index="<?php echo esc_attr( $i ); ?>"
						<?php if ( '' !== $onclick_js ) : ?>onclick="<?php echo esc_attr( $onclick_js ); ?>"<?php endif; ?>
					>
						<div class="stl-ft-card-row">
							<?php if ( ! empty( $icon['value'] ) ) : ?>
								<span class="stl-ft-card-icon" aria-hidden="true">
									<?php \Elementor\Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) ); ?>
								</span>
							<?php endif; ?>
							<div class="stl-ft-card-body">
								<?php if ( $title ) : ?>
									<div class="stl-ft-card-title"><?php echo wp_kses_post( $title ); ?></div>
								<?php endif; ?>
								<?php if ( $meta ) : ?>
									<div class="stl-ft-card-meta"><?php echo wp_kses_post( $meta ); ?></div>
								<?php endif; ?>
							</div>
							<?php if ( $show_arrow ) : ?>
								<span class="stl-ft-card-arrow" aria-hidden="true">
									<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
								</span>
							<?php endif; ?>
						</div>
						<?php if ( $desc ) : ?>
							<div class="stl-ft-card-desc"><?php echo wp_kses_post( $desc ); ?></div>
						<?php endif; ?>
					</button>
				<?php endforeach; ?>
			</div>

			<?php foreach ( $tabs as $i => $tab ) :
				$tab_id    = $widget_id . '-tab-' . $i;
				$panel_id  = $widget_id . '-panel-' . $i;
				$is_open   = ( $i === $default_open );
				$heading   = $tab['panel_heading'] ?? '';
				$subhead   = $tab['panel_subhead'] ?? '';
				$shortcode = $tab['shortcode']     ?? '';
				?>
				<div
					id="<?php echo esc_attr( $panel_id ); ?>"
					class="stl-ft-panel"
					role="tabpanel"
					aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"
					data-index="<?php echo esc_attr( $i ); ?>"
					<?php if ( ! $is_open ) : ?>hidden<?php endif; ?>
				>
					<?php if ( $show_head && ( $heading || $subhead ) ) : ?>
						<div class="stl-ft-panel-head">
							<?php if ( $heading ) : ?>
								<<?php echo $heading_tag; ?> class="stl-ft-panel-heading"><?php echo wp_kses_post( $heading ); ?></<?php echo $heading_tag; ?>>
							<?php endif; ?>
							<?php if ( $subhead ) : ?>
								<div class="stl-ft-panel-subhead"><?php echo wp_kses_post( $subhead ); ?></div>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<div class="stl-ft-panel-body">
						<?php
						$has_shortcode = '' !== trim( (string) $shortcode );
						if ( $has_shortcode ) {
							echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} elseif ( self::is_editor() ) {
							printf(
								'<p class="stl-ft-placeholder">%s</p>',
								esc_html__( 'Paste a form shortcode (Contact Form 7, WPForms, Gravity Forms, etc.) into this tab to render it here.', 'stl-addons' )
							);
						}
						?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
