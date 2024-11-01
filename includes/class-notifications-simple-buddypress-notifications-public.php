<?php

/**
 * The frontend functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Forumpress_Notifications
 * @subpackage Forumpress_Notifications/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version
 *
 * @package    Forumpress_Notifications
 * @subpackage Forumpress_Notifications/public
 * @author     agarwalmohit <agarwal29ster@gmail.com>
 */
class Forumpress_Notifications_Public {


	/**
	 * Single ton pattern instance reuse.
	 *
	 * @access  private
	 *
	 * @var object  $_instance class instance.
	 */
	private static $_instance;

	/**
	 * GET Instance
	 *
	 * Function help to create class instance as per singleton pattern.
	 *
	 * @return object  $_instance
	 */
	public static function get_instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_shortcode( 'simple_buddypress_notifications', array( $this, 'audio_notifications_toolbar_menu' ) );
		add_action( 'wp_enqueue_scripts',array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts',array( $this, 'enqueue_scripts' ) );
		add_filter( 'heartbeat_received', array( $this, 'sbp_noti_process_notification_request' ), 10, 3 );
		add_action( 'wp_head', array( $this, 'add_js_globally' ) );
	}

	/**
	 * Register the stylesheets for the frontend of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'notifier-style-fonts', FORUMPRESS_NOTIFICATIONS_URL . 'assets/css/elusive-icons.min.css' );
		wp_enqueue_style( 'notifier-style', FORUMPRESS_NOTIFICATIONS_URL . 'assets/css/style.css' );
	}

	/**
	 * Register the JavaScript for the frontend of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'notifier-script',FORUMPRESS_NOTIFICATIONS_URL . 'assets/js/script.js',array( 'jquery', 'heartbeat' ) );
	}

	public function get_js_settings() {

		return apply_filters( 'sbp_noti_get_js_settings', array(
				'last_notified' => $this->sbp_noti_get_latest_notification_id(),
		));
	}


	/**
	 * Add global object
	 */
	public function add_js_globally() {
		?>
		<script type="text/javascript">
			var sbp_noti = <?php echo json_encode( $this->get_js_settings() );?>;
		</script>
		<audio src="<?php echo FORUMPRESS_NOTIFICATIONS_URL . 'assets/audio/Jinja.mp3';?>" id="notifierr" type="audio/mp3" ></audio>
	<?php
	}

	/**
	 * Get the last notification ID for the user
	 *
	 * @global type $wpdb
	 * @param type $user_id
	 * @return type
	 */
	function sbp_noti_get_latest_notification_id( $user_id = false ) {

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		global $wpdb;

		$bp = buddypress();

		$table = $bp->notifications->table_name;

		$registered_components = bp_notifications_get_registered_components();

		$components_list = array();

		foreach ( $registered_components as $component ) {
			$components_list[] = $wpdb->prepare( '%s', $component );
		}

		$components_list = implode( ',', $components_list );

		$query = "SELECT MAX(id) FROM {$table} WHERE user_id = %d AND component_name IN ({$components_list}) AND is_new = %d ";

		$query = $wpdb->prepare( $query, $user_id, 1 );

		return (int) $wpdb->get_var( $query );
	}


	/**
	 * Function to show notification bell with notification count.
	 */
	public  function audio_notifications_toolbar_menu() {

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$notifications = bp_notifications_get_notifications_for_user( bp_loggedin_user_id(), 'object' );
		$count         = ! empty( $notifications ) ? count( $notifications ) : 0;
		$alert_class   = (int) $count > 0 ? 'sbp-noti-pending-count sbp-noti-alert' : 'sbp-noti-count sbp-noti-no-alert';
		$menu_title    = '<div class="sbp-noti-pending-notifications ' . $alert_class . '"><i class="el el-bell el-4x"></i><span>' . number_format_i18n( $count ) . '</span></div>';
		$menu_link     = trailingslashit( bp_loggedin_user_domain() . bp_get_notifications_slug() );
		ob_start();?>
			<div class='sbp_noti'>
				<div class='sbp_noti_container'><?php echo $menu_title;?></div>
				<div class='notifications_lists_container'>
					<ul class='notifications_lists'>
					<?php if ( ! empty( $notifications ) ) {?>
						<?php foreach ( (array) $notifications as $notification ) { ?>
							<li>
								<a href='<?php echo $notification->href ;?>' class='sbp-noti-notification-text'><?php echo $notification->content;?></a>
							</li>
						<?php }?>
					<?php }?>
					</ul>
				</div>
			</div>
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}



	/**
	 * Filter on the heartbeat recieved data and inject the new notifications data
	 *
	 * @param type $response
	 * @param type $data
	 * @param type $screen_id
	 * @return type
	 */
	function sbp_noti_process_notification_request( $response, $data, $screen_id ) {
        
		if ( isset( $data['sbp-noti-data'] ) ) {

			$notifications = array();
			$notification_ids = array();

			$request = $data['sbp-noti-data'];

			$last_notification_id = absint( $request['last_notified'] );

			if ( ! empty( $request ) ) {

				$notifications = $this->sbp_noti_get_new_notifications( get_current_user_id(),  $last_notification_id );

				$notification_ids = wp_list_pluck( $notifications, 'id' );

				$notifications = $this->sbp_noti_get_notification_messages( $notifications );

			}
			$notification_ids[] = $last_notification_id;
			$last_notification_id = max( $notification_ids );

			$response['sbp-noti-data'] = array( 'messages' => $notifications, 'last_notified' => $last_notification_id );

	    }
	    return $response;
	}


	/**
	 * Get all new notifications after a given time for the current user
	 *
	 * @global type $wpdb
	 * @param type $user_id
	 * @param int $last_notified
	 * @return type
	 */

	function sbp_noti_get_new_notifications( $user_id, $last_notified ) {

		global $wpdb;

		$bp = buddypress();

		$table = $bp->notifications->table_name;

		$registered_components = bp_notifications_get_registered_components();

		$components_list = array();

		foreach ( $registered_components as $component ) {
			$components_list[] = $wpdb->prepare( '%s', $component );
		}

		$components_list = implode( ',', $components_list );

		$query = "SELECT * FROM {$table} WHERE user_id = %d AND component_name IN ({$components_list}) AND id > %d AND is_new = %d ";

		$query = $wpdb->prepare( $query, $user_id, $last_notified, 1 );

		return $wpdb->get_results( $query );
	}

	/**
	 * Get a list of processed messages
	 *
	 */
	function sbp_noti_get_notification_messages( $notifications ) {

		$messages = array();

		if ( empty( $notifications ) ) {
			return $messages;
		}

		$total_notifications = count( $notifications );

		for ( $i = 0; $i < $total_notifications; $i++ ) {

			$notification = $notifications[ $i ];

			$messages[] = $this->sbp_noti_get_the_notification_description( $notification );

		}

		return $messages;
	}

	/**
	 * Parsing notification to extract message
	 *
	 * @see bp_get_the_notification_description
	 * @param type $notification
	 * @return type
	 */

	function sbp_noti_get_the_notification_description( $notification ) {

		$bp = buddypress();

		// Callback function exists
		if ( isset( $bp->{ $notification->component_name }->notification_callback ) && is_callable( $bp->{ $notification->component_name }->notification_callback ) ) {
			$description = call_user_func( $bp->{ $notification->component_name }->notification_callback, $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1 );

		} elseif ( isset( $bp->{ $notification->component_name }->format_notification_function ) && function_exists( $bp->{ $notification->component_name }->format_notification_function ) ) {
			$description = call_user_func( $bp->{ $notification->component_name }->format_notification_function, $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1 );

			// Allow non BuddyPress components to hook in
		} else {

			/** This filter is documented in bp-notifications/bp-notifications-functions.php */
			$description = apply_filters_ref_array( 'bp_notifications_get_notifications_for_user', array( $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1 ) );
		}

		/**
		 * Filters the full-text description for a specific notification.
		 *
		 * @since BuddyPress (1.9.0)
		 *
		 * @param string $description Full-text description for a specific notification.
		 */
		return apply_filters( 'bp_get_the_notification_description', $description );
	}

}
