<?php
/**
 * Plugin Name:       Stl Addons for Elementor
 * Plugin URI:        https://stallioni.com
 * Description:       Elementor widgets by Stallioni.
 * Version:           1.1.0
 * Requires at least: 5.8
 * Tested up to:      7.0
 * Requires PHP:      7.4
 * Author:            Stallioni Net Solutions
 * Author URI:        https://stallioni.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       stl-addons
 * Domain Path:       /languages
 * Requires Plugins:  elementor
 * Elementor tested up to: 4.0
 *
 * @package StlAddons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'STL_VERSION', '1.1.0' );
define( 'STL_FILE', __FILE__ );
define( 'STL_DIR', plugin_dir_path( __FILE__ ) );
define( 'STL_URL', plugin_dir_url( __FILE__ ) );
define( 'STL_MIN_PHP_VERSION', '7.4' );
define( 'STL_MIN_WP_VERSION', '5.8' );
define( 'STL_MIN_ELEMENTOR_VERSION', '3.5.0' );

require_once STL_DIR . 'includes/plugin.php';

add_action( 'plugins_loaded', array( 'STL_Addons_Plugin', 'init' ) );
