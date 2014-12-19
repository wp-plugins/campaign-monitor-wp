<?php

function campaignmonitor_object( $settings ) {

	if ( ! class_exists( 'CS_REST_General ' ) ) {
		require_once $settings[ 'plugin_dir' ] . "providers/campaignmonitor/campaignmonitor/csrest_general.php";
	}
	if ( ! class_exists( 'CS_REST_Clients' ) ) {
		require_once $settings[ 'plugin_dir' ] . "providers/campaignmonitor/campaignmonitor/csrest_clients.php";
	}
	if ( ! class_exists( 'CS_REST_Subscribers' ) ) {
		require_once $settings[ 'plugin_dir' ] . "providers/campaignmonitor/campaignmonitor/csrest_subscribers.php";
	}

	$suggested = array();
	foreach ( $settings[ 'fca_eoi_last_3_forms' ] as $fca_eoi_previous_form ) {
		try {
			if( K::get_var( 'campaignmonitor_list_id', $fca_eoi_previous_form[ 'fca_eoi' ] ) ) {
				$suggested[ 'campaignmonitor_api_key' ] = $fca_eoi_previous_form[ 'fca_eoi' ][ 'campaignmonitor_api_key' ];
				$suggested[ 'campaignmonitor_client_id' ] = $fca_eoi_previous_form[ 'fca_eoi' ][ 'campaignmonitor_client_id' ];
				break;
			}
		} catch ( Exception $e ) {}
	}

	$eoi_form_meta = K::get_var( 'eoi_form_meta', $settings, array() );
	$api_key = K::get_var(
		'campaignmonitor_api_key'
		, $suggested
		, K::get_var( 'campaignmonitor_api_key', $eoi_form_meta, '' )
	);
	$client_id = K::get_var(
		'campaignmonitor_client_id'
		, $suggested
		, K::get_var( 'campaignmonitor_client_id', $eoi_form_meta, '' )
	);

	// return true if both api_key and client_id are provided
	return $api_key && $client_id;
}

function campaignmonitor_get_lists( $settings ) {

	// Return an empty array if the api_key or the client_id are missing
	if ( ! K::get_var( 'campaignmonitor_helper', $settings ) ) {
		return array();
	}

	$suggested = array();
	foreach ( $settings[ 'fca_eoi_last_3_forms' ] as $fca_eoi_previous_form ) {
		try {
			if( K::get_var( 'campaignmonitor_list_id', $fca_eoi_previous_form[ 'fca_eoi' ] ) ) {
				$suggested[ 'campaignmonitor_api_key' ] = $fca_eoi_previous_form[ 'fca_eoi' ][ 'campaignmonitor_api_key' ];
				$suggested[ 'campaignmonitor_client_id' ] = $fca_eoi_previous_form[ 'fca_eoi' ][ 'campaignmonitor_client_id' ];
				break;
			}
		} catch ( Exception $e ) {}
	}

	$eoi_form_meta = K::get_var( 'eoi_form_meta', $settings, array() );
	$api_key = K::get_var(
		'campaignmonitor_api_key'
		, $suggested
		, K::get_var( 'campaignmonitor_api_key', $eoi_form_meta, '' )
	);
	$client_id = K::get_var(
		'campaignmonitor_client_id'
		, $suggested
		, K::get_var( 'campaignmonitor_client_id', $eoi_form_meta, '' )
	);

	$lists = array();
	$auth = array( 'api_key' => $api_key );
	$wrap = new CS_REST_Clients( $client_id, $auth );
	$results = json_decode( json_encode( $wrap->get_lists() ), true );
	if ( isset( $results[ 'response' ] ) && $results[ 'http_status_code' ] == 200 ) {
		foreach ( $results[ 'response' ] as $result ) {
			$lists[] = array(
				'id' => $result['ListID'],
				'name' => $result['Name']
			);
		}
	}

	return $lists;
}

function campaignmonitor_add_user( $settings, $user_data, $list_id ) {

	if( empty( $settings[ 'campaignmonitor_helper' ] ) ) {
		return false;
	}

	$eoi_form_meta = K::get_var( 'eoi_form_meta', $settings, array() );
	$api_key = K::get_var( 'campaignmonitor_api_key', $eoi_form_meta );

	// Subscribe user
	$auth = array( 'api_key' => $api_key );
	$wrap = new CS_REST_Subscribers( $list_id, $auth );
	$result = $wrap->add( array(
		'EmailAddress' => K::get_var( 'email', $user_data ),
		'Name' => K::get_var( 'name', $user_data ),
		'Resubscribe' => true,
	) );
	
	return $result->was_successful() ? true : false;
}

function campaignmonitor_ajax_get_lists() {

	// Validate the API key
	$api_key = K::get_var( 'campaignmonitor_api_key', $_POST );
	$client_id = K::get_var( 'campaignmonitor_client_id', $_POST );
	$lists_formatted = array( '' => 'Not set' );

	// Make call and add lists if any
	if ( $api_key && $client_id ) {

		global $dh_easy_opt_ins_plugin;
		$settings = $dh_easy_opt_ins_plugin->settings;
		if ( ! class_exists( 'CS_REST_General ' ) ) {
			require_once $settings[ 'plugin_dir' ] . "providers/campaignmonitor/campaignmonitor/csrest_general.php";
		}
		if ( ! class_exists( 'CS_REST_Clients' ) ) {
			require_once $settings[ 'plugin_dir' ] . "providers/campaignmonitor/campaignmonitor/csrest_clients.php";
		}
		if ( ! class_exists( 'CS_REST_Subscribers' ) ) {
			require_once $settings[ 'plugin_dir' ] . "providers/campaignmonitor/campaignmonitor/csrest_subscribers.php";
		}

		$auth = array( 'api_key' => $api_key );
		$wrap = new CS_REST_Clients( $client_id, $auth );
		$results = json_decode( json_encode( $wrap->get_lists() ), true );
		if ( isset( $results[ 'response' ] ) && $results[ 'http_status_code' ] == 200 ) {
			foreach ( $results[ 'response' ] as $result ) {
				$lists_formatted[ $result['ListID'] ] = $result['Name'];
			}
		}
	}

	echo json_encode( $lists_formatted );
	exit;
}

function campaignmonitor_admin_notices( $errors ) {

	/* Provider errors can be added here */

	return $errors;
}

function campaignmonitor_string( $def_str ) {

	$strings = array(
		'Form integration' => __( 'Campaign Monitor Integration' ),
	);

	return K::get_var( $def_str, $strings, $def_str );
}

function campaignmonitor_integration( $settings ) {

	global $post;
	$fca_eoi = get_post_meta( $post->ID, 'fca_eoi', true );
	$screen = get_current_screen();

	// Hack for mailchimp upgrade
	$fca_eoi[ 'campaignmonitor_list_id' ] = K::get_var(
		'campaignmonitor_list_id'
		, $fca_eoi
		, K::get_var( 'list_id' , $fca_eoi )
	);
	if( strlen( K::get_var( 'campaignmonitor_list_id' , $fca_eoi ) ) == 32){
		$fca_eoi[ 'provider' ] = 'campaignmonitor';
	}
	// End of hack

	// Remember old Campaign Monitor settigns if we are in a new form
	$suggested = array();
	if ( 'add' === $screen->action ) {
		$fca_eoi_last_3_forms = $settings[ 'fca_eoi_last_3_forms' ];
		foreach ( $fca_eoi_last_3_forms as $fca_eoi_previous_form ) {
			try {
				if( K::get_var( 'campaignmonitor_list_id', $fca_eoi_previous_form[ 'fca_eoi' ] ) ) {
					$suggested[ 'campaignmonitor_api_key' ] = $fca_eoi_previous_form[ 'fca_eoi' ][ 'campaignmonitor_api_key' ];
					$suggested[ 'campaignmonitor_client_id' ] = $fca_eoi_previous_form[ 'fca_eoi' ][ 'campaignmonitor_client_id' ];
					break;
				}
			} catch ( Exception $e ) {}
		}
	}

	// Prepare lists for K
	$lists_formatted = array( '' => 'Not set' );
	foreach ( campaignmonitor_get_lists( $settings ) as $list ) {
		$lists_formatted[ $list[ 'id' ] ] = $list[ 'name' ];
	}

	K::fieldset( campaignmonitor_string( 'Form integration' ) ,
		array(
			array( 'input', 'fca_eoi[campaignmonitor_api_key]',
				array( 
					'class' => 'regular-text',
					'value' => K::get_var( 'campaignmonitor_api_key', $suggested, '' )
						? K::get_var( 'campaignmonitor_api_key', $suggested, '' )
						: K::get_var( 'campaignmonitor_api_key', $fca_eoi, '' )
					,
				),
				array( 'format' => '<p><label>API Key<br />:input</label><br /><em>Where can I find <a tabindex="-1" href="http://help.campaignmonitor.com/topic.aspx?t=206" target="_blank">my Campaign Monitor Api Key</a>?</em></p>' )
			),
			array( 'input', 'fca_eoi[campaignmonitor_client_id]',
				array( 
					'class' => 'regular-text',
					'value' => K::get_var( 'campaignmonitor_client_id', $suggested, '' )
						? K::get_var( 'campaignmonitor_client_id', $suggested, '' )
						: K::get_var( 'campaignmonitor_client_id', $fca_eoi, '' )
					,
				),
				array( 'format' => '<p><label>Client ID<br />:input</label><br /><em>Where can I find <a tabindex="-1" href="http://www.campaignmonitor.com/api/getting-started/#clientid" target="_blank">my Campaign Monitor Client ID</a>?</em></p>' )
			),
			array( 'select', 'fca_eoi[campaignmonitor_list_id]',
				array(
					'class' => 'select2',
					'style' => 'width: 27em;',
				),
				array(
					'format' => '<p id="campaignmonitor_list_id_wrapper"><label>List to subscribe to<br />:select</label></p>',
					'options' => $lists_formatted,
					'selected' => K::get_var( 'campaignmonitor_list_id', $suggested, '' )
						? K::get_var( 'campaignmonitor_list_id', $suggested, '' )
						: K::get_var( 'campaignmonitor_list_id', $fca_eoi, '' )
					,
				),
			),
		),
		array(
			'id' => 'fca_eoi_fieldset_form_campaignmonitor_integration',
		)
	);
}
