<?php
/**
 * Admin dashboard for Stl Addons for Elementor.
 *
 * Registers three independent admin pages (Dashboard, Widgets, Get Help) under
 * a shared top-level menu, plus the AJAX endpoint that toggles widgets on/off.
 *
 * @package StlAddons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class STL_Addons_Admin {

	const MENU_SLUG    = 'stl-addons';
	const PAGE_WIDGETS = 'stl-addons-widgets';
	const PAGE_HELP    = 'stl-addons-help';
	const OPTION_KEY   = 'stl_disabled_widgets';
	const NONCE_ACTION = 'stl_admin_toggle';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_stl_toggle_widget', array( __CLASS__, 'ajax_toggle_widget' ) );
	}

	public static function register_menu() {
		$icon = 'data:image/svg+xml;base64,' . base64_encode(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#a7aaad" d="M10 1.5a8.5 8.5 0 1 0 0 17 8.5 8.5 0 0 0 0-17Zm-.6 4.2h1.3v3.5h3.5v1.3h-3.5v3.5H9.4v-3.5H5.9v-1.3h3.5V5.7Z"/></svg>'
		);

		add_menu_page(
			__( 'Stl Addons', 'stl-addons' ),
			__( 'Stl Addons', 'stl-addons' ),
			'manage_options',
			self::MENU_SLUG,
			array( __CLASS__, 'render_dashboard_page' ),
			$icon,
			58
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Dashboard', 'stl-addons' ),
			__( 'Dashboard', 'stl-addons' ),
			'manage_options',
			self::MENU_SLUG,
			array( __CLASS__, 'render_dashboard_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Widgets', 'stl-addons' ),
			__( 'Widgets', 'stl-addons' ),
			'manage_options',
			self::PAGE_WIDGETS,
			array( __CLASS__, 'render_widgets_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Get Help', 'stl-addons' ),
			__( 'Get Help', 'stl-addons' ),
			'manage_options',
			self::PAGE_HELP,
			array( __CLASS__, 'render_help_page' )
		);
	}

	public static function enqueue_assets( $hook ) {
		if ( false === strpos( (string) $hook, self::MENU_SLUG ) ) {
			return;
		}

		wp_enqueue_style( 'stl-admin', STL_URL . 'assets/admin.css', array(), STL_VERSION );
		wp_enqueue_script( 'stl-admin', STL_URL . 'assets/admin.js', array(), STL_VERSION, true );
		wp_localize_script(
			'stl-admin',
			'stlAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Page entry points
	// -------------------------------------------------------------------------

	public static function render_dashboard_page() {
		self::render_shell( 'dashboard', array( __CLASS__, 'render_tab_dashboard' ) );
	}

	public static function render_widgets_page() {
		self::render_shell( 'widgets', array( __CLASS__, 'render_tab_widgets' ) );
	}

	public static function render_help_page() {
		self::render_shell( 'help', array( __CLASS__, 'render_tab_help' ) );
	}

	/**
	 * Shared page shell: capability check, header, nav, body.
	 */
	private static function render_shell( $active, $callback ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="stl-admin">
			<?php self::render_header(); ?>
			<?php self::render_nav( $active ); ?>
			<div class="stl-admin-body">
				<?php call_user_func( $callback ); ?>
			</div>
		</div>
		<?php
	}

	private static function render_header() {
		?>
		<div class="stl-admin-header">
			<div class="stl-admin-brand">
				<span class="stl-admin-logo">STL</span>
				<div>
					<h1><?php esc_html_e( 'Stl Addons for Elementor', 'stl-addons' ); ?></h1>
					<p class="stl-admin-tagline"><?php esc_html_e( 'Elementor widgets by Stallioni — drop-in, fully editable, brand-friendly.', 'stl-addons' ); ?></p>
				</div>
			</div>
			<div class="stl-admin-meta">
				<span class="stl-admin-version">v<?php echo esc_html( STL_VERSION ); ?></span>
				<a class="stl-admin-link" href="https://stallioni.com" target="_blank" rel="noopener"><?php esc_html_e( 'stallioni.com', 'stl-addons' ); ?> ↗</a>
			</div>
		</div>
		<?php
	}

	private static function render_nav( $active ) {
		$tabs = array(
			'dashboard' => array( 'label' => __( 'Dashboard', 'stl-addons' ), 'page' => self::MENU_SLUG ),
			'widgets'   => array( 'label' => __( 'Widgets', 'stl-addons' ),   'page' => self::PAGE_WIDGETS ),
			'help'      => array( 'label' => __( 'Get Help', 'stl-addons' ),  'page' => self::PAGE_HELP ),
		);
		?>
		<nav class="stl-admin-tabs">
			<?php foreach ( $tabs as $key => $tab ) :
				$url   = add_query_arg( 'page', $tab['page'], admin_url( 'admin.php' ) );
				$class = 'stl-admin-tab' . ( $active === $key ? ' is-active' : '' );
				?>
				<a class="<?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $tab['label'] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	// -------------------------------------------------------------------------
	// Tab content
	// -------------------------------------------------------------------------

	private static function render_tab_dashboard() {
		$widgets       = self::discovered_widgets();
		$disabled      = self::disabled_widgets();
		$enabled_count = count( $widgets ) - count( array_intersect( $disabled, array_keys( $widgets ) ) );
		?>
		<div class="stl-admin-grid">
			<div class="stl-admin-main">
				<div class="stl-card stl-card-welcome">
					<h2><?php esc_html_e( 'Welcome to Stl Addons', 'stl-addons' ); ?></h2>
					<p><?php esc_html_e( 'A growing collection of Elementor widgets built and maintained by Stallioni. Drop a widget into any page, customise typography, colours and spacing from the Style tab, and keep your design system consistent across the site.', 'stl-addons' ); ?></p>
					<div class="stl-admin-cta-row">
						<a class="stl-btn stl-btn-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>"><?php esc_html_e( 'Edit a Page', 'stl-addons' ); ?></a>
						<a class="stl-btn stl-btn-ghost" href="<?php echo esc_url( add_query_arg( 'page', self::PAGE_WIDGETS, admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Manage Widgets', 'stl-addons' ); ?></a>
					</div>
				</div>

				<div class="stl-card">
					<div class="stl-card-head">
						<h3><?php esc_html_e( 'Widgets', 'stl-addons' ); ?></h3>
						<span class="stl-pill"><?php echo esc_html( sprintf( /* translators: 1: enabled count, 2: total count */ __( '%1$d of %2$d enabled', 'stl-addons' ), $enabled_count, count( $widgets ) ) ); ?></span>
					</div>
					<?php self::render_widget_grid( $widgets, $disabled ); ?>
				</div>
			</div>

			<aside class="stl-admin-side">
				<div class="stl-card stl-card-brand">
					<h3><?php esc_html_e( 'By Stallioni', 'stl-addons' ); ?></h3>
					<p><?php esc_html_e( 'Stallioni is a product studio building website tools, design systems, and WordPress add-ons.', 'stl-addons' ); ?></p>
					<ul class="stl-list">
						<li><span class="stl-dot"></span> <?php esc_html_e( 'Custom Elementor widgets', 'stl-addons' ); ?></li>
						<li><span class="stl-dot"></span> <?php esc_html_e( 'No Google Fonts pulled in', 'stl-addons' ); ?></li>
						<li><span class="stl-dot"></span> <?php esc_html_e( 'Brand-friendly defaults', 'stl-addons' ); ?></li>
						<li><span class="stl-dot"></span> <?php esc_html_e( 'Secure-by-default code', 'stl-addons' ); ?></li>
					</ul>
					<a class="stl-btn stl-btn-primary stl-btn-block" href="https://stallioni.com" target="_blank" rel="noopener"><?php esc_html_e( 'Visit Stallioni', 'stl-addons' ); ?> ↗</a>
				</div>

				<div class="stl-card">
					<h3><?php esc_html_e( 'Quick Links', 'stl-addons' ); ?></h3>
					<ul class="stl-link-list">
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=elementor' ) ); ?>"><?php esc_html_e( 'Elementor Settings', 'stl-addons' ); ?> ↗</a></li>
						<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=elementor_library' ) ); ?>"><?php esc_html_e( 'Elementor Templates', 'stl-addons' ); ?> ↗</a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'page', self::PAGE_HELP, admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Get Help', 'stl-addons' ); ?> →</a></li>
					</ul>
				</div>
			</aside>
		</div>
		<?php
	}

	private static function render_tab_widgets() {
		$widgets  = self::discovered_widgets();
		$disabled = self::disabled_widgets();
		?>
		<div class="stl-card">
			<div class="stl-card-head">
				<h3><?php esc_html_e( 'Manage Widgets', 'stl-addons' ); ?></h3>
				<p class="stl-card-sub"><?php esc_html_e( 'Toggle individual widgets on or off. Disabled widgets are hidden from the Elementor panel sitewide.', 'stl-addons' ); ?></p>
			</div>
			<?php self::render_widget_grid( $widgets, $disabled ); ?>
			<?php if ( empty( $widgets ) ) : ?>
				<p class="stl-empty"><?php esc_html_e( 'No widgets discovered. Drop a widget folder under includes/widgets/ to get started.', 'stl-addons' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_tab_help() {
		?>
		<div class="stl-admin-grid">
			<div class="stl-admin-main">
				<div class="stl-card">
					<h3><?php esc_html_e( 'Getting started', 'stl-addons' ); ?></h3>
					<ol class="stl-numbered">
						<li><?php esc_html_e( 'Activate Elementor (free is sufficient).', 'stl-addons' ); ?></li>
						<li><?php esc_html_e( 'Edit any page or post with Elementor.', 'stl-addons' ); ?></li>
						<li><?php esc_html_e( 'In the widget panel, search for the "Stl Addons" category.', 'stl-addons' ); ?></li>
						<li><?php esc_html_e( 'Drag any widget into a section and use the Style tab to customise.', 'stl-addons' ); ?></li>
					</ol>
				</div>

				<div class="stl-card">
					<h3><?php esc_html_e( 'Adding a new widget (for developers)', 'stl-addons' ); ?></h3>
					<p><?php esc_html_e( 'The plugin auto-loads anything under includes/widgets/. Folder slug must be kebab-case and the PHP class must follow the STL_Widget_<Studly_Snake> convention. Drop an icon.svg in the same folder to give it a custom dashboard icon.', 'stl-addons' ); ?></p>
					<pre class="stl-code">includes/widgets/&lt;slug&gt;/widget.php
includes/widgets/&lt;slug&gt;/icon.svg
includes/widgets/&lt;slug&gt;/assets/style.css</pre>
				</div>
			</div>

			<aside class="stl-admin-side">
				<div class="stl-card stl-card-brand">
					<h3><?php esc_html_e( 'Need help?', 'stl-addons' ); ?></h3>
					<p><?php esc_html_e( 'Contact the Stallioni team directly for support, custom widgets, or full-site design work.', 'stl-addons' ); ?></p>
					<a class="stl-btn stl-btn-primary stl-btn-block" href="https://stallioni.com" target="_blank" rel="noopener"><?php esc_html_e( 'Visit Stallioni', 'stl-addons' ); ?> ↗</a>
				</div>
			</aside>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Reusable bits
	// -------------------------------------------------------------------------

	private static function render_widget_grid( $widgets, $disabled ) {
		?>
		<div class="stl-widget-grid">
			<?php foreach ( $widgets as $slug => $title ) :
				$is_on = ! in_array( $slug, $disabled, true );
				?>
				<div class="stl-widget-tile">
					<div class="stl-widget-tile-icon"><?php self::render_widget_icon( $slug, $title ); ?></div>
					<div class="stl-widget-tile-body">
						<strong><?php echo esc_html( $title ); ?></strong>
						<span class="stl-widget-tile-slug"><?php echo esc_html( $slug ); ?></span>
					</div>
					<label class="stl-switch">
						<input type="checkbox" data-slug="<?php echo esc_attr( $slug ); ?>" <?php checked( $is_on ); ?> />
						<span class="stl-switch-track"></span>
					</label>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render the widget icon: prefers includes/widgets/<slug>/icon.svg, falls
	 * back to the first two letters of the title.
	 */
	private static function render_widget_icon( $slug, $title ) {
		$svg = self::widget_icon_svg( $slug );
		if ( null !== $svg ) {
			echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized below.
			return;
		}
		echo '<span>' . esc_html( strtoupper( substr( $title, 0, 2 ) ) ) . '</span>';
	}

	/**
	 * Read and sanitize a widget's icon.svg. Returns null if the file is
	 * missing, unreadable, or empty. The result is safe to echo.
	 */
	private static function widget_icon_svg( $slug ) {
		static $cache = array();
		if ( array_key_exists( $slug, $cache ) ) {
			return $cache[ $slug ];
		}

		$cache[ $slug ] = null;

		if ( ! preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
			return null;
		}

		$path = STL_DIR . 'includes/widgets/' . $slug . '/icon.svg';
		if ( ! is_readable( $path ) ) {
			return null;
		}

		$raw = @file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $raw || '' === $raw ) {
			return null;
		}

		$cache[ $slug ] = self::sanitize_svg( $raw );
		return $cache[ $slug ];
	}

	/**
	 * Allow <svg> + the shape/path elements we use. Strips scripts and event
	 * handler attributes so a tampered icon.svg can't inject markup or JS.
	 */
	private static function sanitize_svg( $svg ) {
		$allowed = array(
			'svg'      => array( 'xmlns' => true, 'viewbox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'aria-hidden' => true, 'role' => true, 'width' => true, 'height' => true, 'class' => true ),
			'path'     => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ),
			'rect'     => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true ),
			'circle'   => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true ),
			'line'     => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true ),
			'polyline' => array( 'points' => true, 'fill' => true, 'stroke' => true ),
			'polygon'  => array( 'points' => true, 'fill' => true, 'stroke' => true ),
			'g'        => array( 'fill' => true, 'stroke' => true, 'transform' => true ),
		);
		return wp_kses( $svg, $allowed );
	}

	// -------------------------------------------------------------------------
	// Data
	// -------------------------------------------------------------------------

	/**
	 * Discover widgets and return [ slug => pretty title ]. Mirrors the loader.
	 */
	private static function discovered_widgets() {
		$found = array();
		foreach ( (array) glob( STL_DIR . 'includes/widgets/*/widget.php' ) as $file ) {
			$slug = basename( dirname( $file ) );
			if ( preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
				$found[ $slug ] = ucwords( str_replace( '-', ' ', $slug ) );
			}
		}
		ksort( $found );
		return $found;
	}

	public static function disabled_widgets() {
		$raw = get_option( self::OPTION_KEY, array() );
		return is_array( $raw ) ? array_values( array_filter( array_map( 'sanitize_key', $raw ) ) ) : array();
	}

	public static function ajax_toggle_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'stl-addons' ) ), 403 );
		}
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$slug    = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$enable  = isset( $_POST['enable'] ) ? (bool) intval( $_POST['enable'] ) : false;

		$discovered = self::discovered_widgets();
		if ( ! isset( $discovered[ $slug ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown widget.', 'stl-addons' ) ), 400 );
		}

		$disabled = self::disabled_widgets();

		if ( $enable ) {
			$disabled = array_values( array_diff( $disabled, array( $slug ) ) );
		} elseif ( ! in_array( $slug, $disabled, true ) ) {
			$disabled[] = $slug;
		}

		update_option( self::OPTION_KEY, $disabled );

		wp_send_json_success( array(
			'slug'    => $slug,
			'enabled' => $enable,
		) );
	}
}
