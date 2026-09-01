<?php
/**
 * Uninstall cleanup for OliForge Country Select for Contact Form 7.
 *
 * @package OliForge_CF7_Country_Select
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'oliforge_cf7_country_select_allowed_countries' );
delete_option( 'oliforge_cf7_country_select_display_language' );

delete_option( 'oliforge_cf7_country_select_validation_border' );
