<?php

/**
 * Sage includes
 *
 * The $sage_includes array determines the code library included in your theme.
 * Add or remove files to the array as needed. Supports child theme overrides.
 *
 * Please note that missing files will produce a fatal error.
 *
 * @link https://github.com/roots/sage/pull/1042
 */
$sage_includes = [
	'lib/assets.php',         // Scripts and stylesheets
	'lib/setup.php',          // Theme setup
	'lib/wrapper.php',        // Theme wrapper class
	'lib/customizer.php',     // Theme customizer
	'lib/filter-data.php',    // Preparing filter
	'lib/gravity-forms.php',  // Gravity Forms hooks
	'lib/emails.php',         // Email functions
	'lib/helpers.php',        // Helper functions
	'lib/hooks.php',          // General hooks
    'lib/defuse-crypto.phar', // Defuse Crypto Library
    'lib/ratings.php',        // Defuse Crypto Library
];

// Only load the all update library file when the flag is set.
if (is_admin() && defined('VAZQUEZ_API_ALL_UPDATE') && VAZQUEZ_API_ALL_UPDATE === true) {
    $sage_includes[] = 'lib/api_all_update.php';
}

foreach ( $sage_includes as $file ) {
	if ( ! $filepath = locate_template( $file ) ) {
		trigger_error( sprintf( __( 'Error locating %s for inclusion', 'sage' ), $file ), E_USER_ERROR );
	}
	require_once $filepath;
}
unset( $file, $filepath );
