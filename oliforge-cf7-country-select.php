<?php
/**
 * Plugin Name: OliForge Country Select for Contact Form 7
 * Plugin URI: https://oliforge.io/plugins/country-select/
 * Description: Adds an accessible, searchable and multilingual country selector with locally bundled SVG flags, administrator-controlled country availability, automatic locale detection and developer filters to Contact Form 7. Part of the OliForge™ plugin suite.
 * Version: 3.2.12
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Requires Plugins: contact-form-7
 * Author: OliForge™
 * Author URI: https://oliforge.io/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: oliforge-cf7-country-select
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'OLIFORGE_CF7_COUNTRY_SELECT_VERSION', '3.2.12' );
define( 'OLIFORGE_CF7_COUNTRY_SELECT_FILE', __FILE__ );
define( 'OLIFORGE_CF7_COUNTRY_SELECT_DIR', plugin_dir_path( __FILE__ ) );
define( 'OLIFORGE_CF7_COUNTRY_SELECT_URL', plugin_dir_url( __FILE__ ) );

require_once OLIFORGE_CF7_COUNTRY_SELECT_DIR . 'includes/class-oliforge-cf7-country-select.php';

add_filter( 'plugin_action_links_' . plugin_basename( OLIFORGE_CF7_COUNTRY_SELECT_FILE ), static function ( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=oliforge-cf7-country-select' ) ) . '">' . esc_html__( 'Settings', 'oliforge-cf7-country-select' ) . '</a>';
	array_unshift( $links, $settings_link );
	if ( ! defined( 'OLIFORGE_CF7_COUNTRY_SELECT_PRO_VERSION' ) ) {
		$links[] = '<a href="' . esc_url( 'https://oliforge.io/plugins/country-select-pro/' ) . '" target="_blank" rel="noopener" style="color:#C25F0A;font-weight:600;">' . esc_html__( 'Upgrade to Pro', 'oliforge-cf7-country-select' ) . '</a>';
	}
	return $links;
} );

add_action( 'init', static function () {
	load_plugin_textdomain( 'oliforge-cf7-country-select', false, dirname( plugin_basename( OLIFORGE_CF7_COUNTRY_SELECT_FILE ) ) . '/languages' );
}, 1 );

add_action( 'plugins_loaded', static function () {
    if ( ! defined( 'WPCF7_VERSION' ) ) {
        add_action( 'admin_notices', static function () {
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'OliForge Country Select requires Contact Form 7 to be active.', 'oliforge-cf7-country-select' ) . '</p></div>';
        } );
        return;
    }
    OliForge_CF7_Country_Select::init();
} );
