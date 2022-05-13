<?php
/**
 * Plugin Name: Stashed Out - Customized Plugin
 * Plugin URI: https://thulanimatshoba.co.za
 * Description: Custom Code for the Stashed Out Theme
 * Version: 1.0.0
 * Author: Thulani Matshoba
 * Requires PHP: 7.0
 */

// Plugin Directory
define( 'PLUG_DIR', dirname( __FILE__ ) );
define( 'PLUG_DIR_JS_URL', plugin_dir_url( __FILE__ ) . 'admin/' );
/**
 * Admin Functions
 * These functions affect the admin area of WordPress, and not the live site.
 */

require_once PLUG_DIR . '/admin/post-types.php';
require_once PLUG_DIR . '/admin/taxonomies.php';

require_once PLUG_DIR . '/admin/functions.php';
// Fact of The day
//require_once PLUG_DIR . '/admin/stashed-factoftheday.php';

// Active all existing users.
function stashed_cp_activation() {
	$user_ids = get_users(
		[
			'fields' => 'ID',
		]
	);

	foreach ( $user_ids as $user_id ) {
		if ( ! add_user_meta( $user_id, 'stashed-status-user', true, true ) ) {
			update_user_meta( $user_id, 'stashed-status-user', true );
		}
	}
}
register_activation_hook( __FILE__, 'stashed_cp_activation' );
