
jQuery(document).ready(function(){

	//last notified id.
	var last_notified = sbp_noti.last_notified;

	//set interval to 5s. 
	wp.heartbeat.interval( 'fast' );

	// Set notification drop down.
	jQuery('.sbp_noti_container').on('click','.sbp-noti-pending-notifications',function(){
		jQuery('.sbp_noti .notifications_lists_container').stop().slideToggle();
	});

	jQuery(".sbp_noti").mouseleave(function(){
		jQuery('.sbp_noti .notifications_lists_container').slideUp();
	});

	jQuery(document).on( 'heartbeat-tick', function( event, data ) {
		if ( data.hasOwnProperty( 'sbp-noti-data' ) ) {
			var sbp_noti_data = data['sbp-noti-data'] ;
			update_last_notified( sbp_noti_data.last_notified );
			var messages = sbp_noti_data.messages;
			
			if( messages == undefined || messages.length == 0 )
				return ;
			
			//fire custom event sbp_noti:new_notifications.
			jQuery( document ).trigger( "sbp_noti:new_notifications", [{count: messages.length, messages: messages}] );
		}
	});

	jQuery(document).on( 'heartbeat-send', function( e, data ) {	
		data['sbp-noti-data'] = {last_notified: get_last_notified()};
	});


	jQuery( document ).on('sbp_noti:new_notifications', function(evt, data ){
			
		if( data.count && data.count>0 ){
			
			update_count_text( jq('#ab-pending-notifications'), data.count );
			update_count_text( jq('.sbp-noti-pending-notifications span'), data.count );
			jQuery('#notifierr').get(0).play();									

			var my_act_notification_menu = jq('#wp-admin-bar-my-account-notifications > a span');
			//if the count menu does not exist.
			if(  ! my_act_notification_menu.get(0 ) ) {
			
				
				if( jq('#wp-admin-bar-my-account-notifications').get(0) ) { 
					jq('#wp-admin-bar-my-account-notifications > a').append(' <span class="count">'+data.count+" </span>");
					jq('#wp-admin-bar-my-account-notifications-unread a').append(' <span class="count">'+data.count+" </span>");
				}
			}else{
				
				update_count_text( my_act_notification_menu, data.count );
				update_count_text( jq('#wp-admin-bar-my-account-notifications-unread span'), data.count );
				
			}
			var list_parent = jq('#wp-admin-bar-bp-notifications-default');
			
			if( list_parent.get(0) ) {
				if(list_parent.has('wp-admin-bar-no-notifications')){
					list_parent.find('#wp-admin-bar-no-notifications').remove();
				}
				list_parent.append("<li class='0'>"+data.messages.join("</li><li>") + "</li>" );
				list_parent.find("li > a").addClass("ab-item");
			}
			var notifier_parent_list = jq('.notifications_lists_container .notifications_lists');
			if( notifier_parent_list.get(0) ) {
				
				notifier_parent_list.append("<li  class='0'>"+data.messages.join("</li><li>") + "</li>"  );
					
			}
		}
		
	});

	function update_count_text( elements, count) {
		//don't do anything if the element does not exist or the count is zero.
		
		if( ! elements.get(0) || ! count  )
			return;
		
		elements.each( function() {
			var element = jq(this);
			var current_count = parseInt( element.text() );
			
			current_count = current_count + parseInt(count) - 0;
		
			element.text( '' + current_count );
		});
		
		
	}


	/**
	 * Set last notified time
	 * 
	 * @param time String datetime.
	 * @returns null
	 */	
	function update_last_notified( time ) {

		last_notified = time;
	}

	//private functions 
	/**
	 * Get last notified time.
	 * 
	 * @returns string
	 */
	function get_last_notified() {
		//last notified is accessible in this scope but not outside.
		return last_notified;
	}

});	
