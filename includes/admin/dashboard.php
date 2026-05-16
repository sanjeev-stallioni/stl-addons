<?php
/**
 * Admin dashboard for Stl Addons for Elementor.
 *
 * Renders the top-level admin menu, the dashboard / widgets / help tabs,
 * and handles the AJAX endpoint that toggles widgets on and off.
 *
 * @package StlAddons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class STL_Addons_Admin {

	const MENU_SLUG   = 'stl-addons';
	const OPTION_KEY  = 'stl_disabled_widgets';
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
			array( __CLASS__, 'render_page' ),
			$icon,
			58
		);

		add_submenu_page( self::MENU_SLUG, __( 'Dashboard', 'stl-addons' ), __( 'Dashboard', 'stl-addons' ), 'manage_options', self::MENU_SLUG, array( __CLASS__, 'render_page' ) );
		add_submenu_page( self::MENU_SLUG, __( 'Widgets', 'stl-addons' ), __( 'Widgets', 'stl-addons' ),     'manage_options', self::MENU_SLUG . '&tab=widgets', '__return_null' );
		add_submenu_page( self::MENU_SLUG, __( 'Get Help', 'stl-addons' ), __( 'Get Help', 'stl-addons' ),   'manage_options', self::MENU_SLUG . '&tab=help',    '__return_null' );
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

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard';
		$allowed_tabs = array( 'dashboard', 'widgets', 'help' );
		if ( ! in_array( $tab, $allowed_tabs, true ) ) {
			$tab = 'dashboard';
		}
		?>
		<div class="stl-admin">
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

			<nav class="stl-admin-tabs">
				<?php
				$tabs = array(
					'dashboard' => __( 'Dashboard', 'stl-addons' ),
					'widgets'   => __( 'Widgets', 'stl-addons' ),
					'help'      => __( 'Get Help', 'stl-addons' ),
				);
				foreach ( $tabs as $key => $label ) {
					$url    = add_query_arg( array( 'page' => self::MENU_SLUG, 'tab' => $key ), admin_url( 'admin.php' ) );
					$active = $tab === $key ? ' is-active' : '';
					printf(
						'<a class="stl-admin-tab%s" href="%s">%s</a>',
						esc_attr( $active ),
						esc_url( $url ),
						esc_html( $label )
					);
				}
				?>
			</nav>

			<div class="stl-admin-body">
				<?php
				if ( 'widgets' === $tab ) {
					self::render_tab_widgets();
				} elseif ( 'help' === $tab ) {
					self::render_tab_help();
				} else {
					self::render_tab_dashboard();
				}
				?>
			</div>
		</div>
		<?php
	}

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
						<a class="stl-btn stl-btn-ghost" href="<?php echo esc_url( add_query_arg( array( 'page' => self::MENU_SLUG, 'tab' => 'widgets' ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Manage Widgets', 'stl-addons' ); ?></a>
					</div>
				</div>

				<div class="stl-card">
					<div class="stl-card-head">
						<h3><?php esc_html_e( 'Widgets', 'stl-addons' ); ?></h3>
						<span class="stl-pill"><?php echo esc_html( sprintf( /* translators: 1: enabled count, 2: total count */ __( '%1$d of %2$d enabled', 'stl-addons' ), $enabled_count, count( $widgets ) ) ); ?></span>
					</div>
					<div class="stl-widget-grid">
						<?php foreach ( $widgets as $slug => $title ) :
							$is_on = ! in_array( $slug, $disabled, true );
							?>
							<div class="stl-widget-tile">
								<div class="stl-widget-tile-icon"><span class="dashicons <?php echo esc_attr( self::widget_icon( $slug ) ); ?>"></span></div>
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
						<li><a href="<?php echo esc_url( add_query_arg( array( 'page' => self::MENU_SLUG, 'tab' => 'help' ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Get Help', 'stl-addons' ); ?> →</a></li>
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
			<div class="stl-widget-grid">
				<?php foreach ( $widgets as $slug => $title ) :
					$is_on = ! in_array( $slug, $disabled, true );
					?>
					<div class="stl-widget-tile">
						<div class="stl-widget-tile-icon"><span class="dashicons <?php echo esc_attr( self::widget_icon( $slug ) ); ?>"></span></div>
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
					<p><?php esc_html_e( 'The plugin auto-loads anything under includes/widgets/. Folder slug must be kebab-case and the PHP class must follow the STL_Widget_<Studly_Snake> convention.', 'stl-addons' ); ?></p>
					<pre class="stl-code">includes/widgets/&lt;slug&gt;/widget.php
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

	/**
	 * Return the Dashicons class used to represent a widget on the dashboard tile.
	 */
	private static function widget_icon( $slug ) {
		$map = array(
			'team-grid'       => 'dashicons-groups',
			'founder-section' => 'dashicons-id-alt',
		);

		/**
		 * Allow themes/plugins to customise the icon per widget slug.
		 *
		 * @param array $map slug => 'dashicons-XYZ'
		 */
		$map = apply_filters( 'stl_addons_widget_icons', $map );

		return isset( $map[ $slug ] ) ? $map[ $slug ] : 'dashicons-screenoptions';
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
