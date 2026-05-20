<?php
/**
 * Main plugin class — bootstraps Elementor integration and auto-loads widgets
 * from includes/widgets/<slug>/widget.php.
 *
 * @package StlAddons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class STL_Addons_Plugin {

	public static function init() {
		load_plugin_textdomain( 'stl-addons', false, dirname( plugin_basename( STL_FILE ) ) . '/languages' );

		if ( file_exists( STL_DIR . 'includes/admin/dashboard.php' ) ) {
			require_once STL_DIR . 'includes/admin/dashboard.php';
			if ( is_admin() && class_exists( 'STL_Addons_Admin' ) ) {
				STL_Addons_Admin::init();
			}
		}

		if ( ! self::is_compatible() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );

		// Inline widget CSS to drop render-blocking requests. Each widget CSS is
		// tiny (~2–4 KB) and only loads when its widget is on the page, so the
		// inline cost is bounded and the latency saving is real.
		add_filter( 'style_loader_tag', array( __CLASS__, 'maybe_inline_widget_css' ), 10, 2 );
	}

	/**
	 * Check Elementor, PHP, and WP version compatibility. Emits an admin notice on failure.
	 */
	private static function is_compatible() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'notice_missing_elementor' ) );
			return false;
		}

		if ( defined( 'ELEMENTOR_VERSION' ) && ! version_compare( ELEMENTOR_VERSION, STL_MIN_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'notice_minimum_elementor' ) );
			return false;
		}

		if ( version_compare( PHP_VERSION, STL_MIN_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'notice_minimum_php' ) );
			return false;
		}

		return true;
	}

	/**
	 * Discover all widget folders and return [ slug => absolute path to widget.php ].
	 * Memoized — the loader hooks call this multiple times per request.
	 */
	private static function discover_widgets() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}
		$cache = array();
		foreach ( (array) glob( STL_DIR . 'includes/widgets/*/widget.php' ) as $file ) {
			$slug = basename( dirname( $file ) );
			if ( preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
				$cache[ $slug ] = $file;
			}
		}
		return $cache;
	}

	/**
	 * Convert "team-grid" to "STL_Widget_Team_Grid".
	 */
	private static function class_name_for( $slug ) {
		return 'STL_Widget_' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $slug ) ) );
	}

	public static function register_assets() {
		if ( file_exists( STL_DIR . 'assets/common.css' ) ) {
			wp_register_style( 'stl-common', STL_URL . 'assets/common.css', array(), STL_VERSION );
		}

		foreach ( self::discover_widgets() as $slug => $file ) {
			$dir = dirname( $file );
			$rel = 'includes/widgets/' . $slug . '/assets/';

			if ( file_exists( $dir . '/assets/style.css' ) ) {
				wp_register_style( 'stl-' . $slug, STL_URL . $rel . 'style.css', array( 'stl-common' ), STL_VERSION );
			}

			if ( file_exists( $dir . '/assets/script.js' ) ) {
				wp_register_script( 'stl-' . $slug, STL_URL . $rel . 'script.js', array(), STL_VERSION, true );
			}
		}
	}

	public static function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'stl-addons',
			array(
				'title' => __( 'Stl Addons', 'stl-addons' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	public static function register_widgets( $widgets_manager ) {
		$disabled = class_exists( 'STL_Addons_Admin' ) ? STL_Addons_Admin::disabled_widgets() : array();

		foreach ( self::discover_widgets() as $slug => $file ) {
			if ( in_array( $slug, $disabled, true ) ) {
				continue;
			}

			require_once $file;

			$class = self::class_name_for( $slug );
			if ( class_exists( $class ) && is_subclass_of( $class, '\Elementor\Widget_Base' ) ) {
				$widgets_manager->register( new $class() );
			}
		}
	}

	/**
	 * Map a registered handle (e.g. "stl-button") to its absolute CSS path on disk.
	 * Returns null for handles that don't belong to this plugin or have no file.
	 */
	private static function path_for_handle( $handle ) {
		if ( 'stl-common' === $handle ) {
			$path = STL_DIR . 'assets/common.css';
			return file_exists( $path ) ? $path : null;
		}
		if ( 0 !== strpos( $handle, 'stl-' ) ) {
			return null;
		}
		$slug = substr( $handle, 4 );
		$widgets = self::discover_widgets();
		if ( ! isset( $widgets[ $slug ] ) ) {
			return null;
		}
		$path = STL_DIR . 'includes/widgets/' . $slug . '/assets/style.css';
		return file_exists( $path ) ? $path : null;
	}

	/**
	 * Replace `<link>` tags for our stl-* handles with inline `<style>` blocks.
	 * Skipped inside the Elementor editor (the editor reloads CSS on edits and
	 * needs the registered handle to remain a real stylesheet). Disable globally
	 * with: add_filter( 'stl_addons_inline_widget_css', '__return_false' );
	 */
	public static function maybe_inline_widget_css( $tag, $handle ) {
		if ( 0 !== strpos( (string) $handle, 'stl-' ) ) {
			return $tag;
		}
		if ( ! apply_filters( 'stl_addons_inline_widget_css', true, $handle ) ) {
			return $tag;
		}
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return $tag;
		}

		$path = self::path_for_handle( $handle );
		if ( null === $path ) {
			return $tag;
		}

		$css = @file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $css || '' === $css ) {
			return $tag;
		}

		return sprintf(
			"<style id=\"%s-inline-css\">%s</style>\n",
			esc_attr( $handle ),
			$css // CSS authored by us — already trusted; do not escape.
		);
	}

	public static function notice_missing_elementor() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-warning is-dismissible"><p><strong>%1$s</strong>: %2$s</p></div>',
			esc_html__( 'Stl Addons for Elementor', 'stl-addons' ),
			esc_html__( 'requires Elementor. Please install and activate Elementor.', 'stl-addons' )
		);
	}

	public static function notice_minimum_elementor() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-warning is-dismissible"><p><strong>%1$s</strong>: %2$s</p></div>',
			esc_html__( 'Stl Addons for Elementor', 'stl-addons' ),
			sprintf(
				/* translators: %s: minimum Elementor version */
				esc_html__( 'requires Elementor version %s or greater.', 'stl-addons' ),
				esc_html( STL_MIN_ELEMENTOR_VERSION )
			)
		);
	}

	public static function notice_minimum_php() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-warning is-dismissible"><p><strong>%1$s</strong>: %2$s</p></div>',
			esc_html__( 'Stl Addons for Elementor', 'stl-addons' ),
			sprintf(
				/* translators: %s: minimum PHP version */
				esc_html__( 'requires PHP version %s or greater.', 'stl-addons' ),
				esc_html( STL_MIN_PHP_VERSION )
			)
		);
	}
}
