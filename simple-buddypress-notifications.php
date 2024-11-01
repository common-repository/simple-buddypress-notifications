<?php
/**
Plugin Name: Simple Buddypress Notifications
Description: This plugin shows buddypress a notification bell instead of default buddypress notification. It shows all notification with an audio and visual alert. Can be used anywhere using this shortcode: [simple_buddypress_notifications].
Author: Mohit Agarwal
Version: 1.3.2
Author URI: http://agarwalmohit.com
Stable tag: "trunk"
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple Buddypress Notifications is a free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Simple Buddypress Notifications is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Simple Buddypress Notifications . If not, see http://www.gnu.org/licenses/gpl-2.0.html.

*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/* Only load code that needs BuddyPress to run once BP is loaded and initialized. */
function load_sbp_noti_component_init() {

	if ( ! defined( 'FORUMPRESS_NOTIFICATIONS_URL' ) ) {
		define( 'FORUMPRESS_NOTIFICATIONS_URL',  plugin_dir_url( __FILE__ ) );
	}

	if ( ! defined( 'FORUMPRESS_NOTIFICATIONS_PATH' ) ) {
		define( 'FORUMPRESS_NOTIFICATIONS_PATH',  plugin_dir_path( __FILE__ ) );
	}

	$bp = buddypress();
	// Allow plugin functionality to work only when Notification module is enabled.
	if( isset( $bp->notifications ) && !empty( $bp->notifications )){
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-notifications-simple-buddypress-notifications-public.php';
		$instance = Forumpress_Notifications_Public::get_instance();
	}
}
add_action( 'bp_include', 'load_sbp_noti_component_init' );

/**
 * Notice when buddpress is missing.
 */
function sbp_noti_admin_notice() {
	if( ! is_plugin_active('buddypress/bp-loader.php') ){ ?>
		<div class="notice notice-warning is-dismissible">
			<p><?php _e( 'Simple Buddypress Notifications requires BuddyPress to be activated.', 'simple-buddypress-notifications' ); ?></p>
		</div>
	<?php }
}
add_action( 'admin_notices', 'sbp_noti_admin_notice' );