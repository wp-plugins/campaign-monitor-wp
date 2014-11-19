jQuery( document ).ready( function( $ ) {

	var $api_key = $( '[name="fca_eoi[campaignmonitor_api_key]"]' );
	var $client_id = $( '[name="fca_eoi[campaignmonitor_client_id]"]' );
	var $api_settings = $().add( $api_key ).add( $client_id );
	var $lists = $( '[name="fca_eoi[campaignmonitor_list_id]"]' );
	var $lists_wrapper = $( '#campaignmonitor_list_id_wrapper' );

	// Track values to prevent duplicate AJAX request
	$api_key.data( 'value', $api_key.val() );
	$client_id.data( 'value', $client_id.val() );

	campaignmonitor_toggle_fields();

	$api_settings.change( function() {

		var $this = $( this );
		var data = {
			'action': 'fca_eoi_campaignmonitor_get_lists' /* API action name, do not change */
			, 'campaignmonitor_api_key' : $api_key.val()
			, 'campaignmonitor_client_id' : $client_id.val()
		};

		// Did the value really change
		var api_key_changed = $api_key.val() == $api_key.data( 'value' )
		var client_id_changed = $client_id.val() == $client_id.data( 'value' )
		if ( api_key_changed && client_id_changed ) {
			return;
		} else {
			$api_key.data( 'value', $api_key.val() );
			$client_id.data( 'value', $client_id.val() );
		}

		$.post( ajaxurl, data, function( response ) {

			var lists = JSON.parse( response );
			var $lists = $( '<select class="select2" style="width: 27em;" name="fca_eoi[campaignmonitor_list_id]" >' );

			for ( list_id in lists ) {
				$lists.append( '<option value="' + list_id + '">' + lists[ list_id ] + '</option>' );
			}

			// Replace dropdown with new list of lists, apply Select2 then show
			$( '[name="fca_eoi[campaignmonitor_list_id]"]' ).select2( 'destroy' );
			$( '[name="fca_eoi[campaignmonitor_list_id]"]' ).replaceWith( $lists );
			$( '[name="fca_eoi[campaignmonitor_list_id]"]' ).select2();
			campaignmonitor_toggle_fields();
		} );
	} );

	/**
	 * Show/hide some fields if there are/aren't list options
	 *
	 * Don't forget that there is always the option "Not Set", 
	 * so take it into consideration when cheking the number of options
	 */
	function campaignmonitor_toggle_fields() {

		var options = $( 'option', '[name="fca_eoi[campaignmonitor_list_id]"]' );

		if( options.length > 1 ) {
			$lists_wrapper.show( 'fast' );
		} else {
			$lists_wrapper.hide();
		}
	}
} );
