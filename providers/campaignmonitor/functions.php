<?php

function campaignmonitor_object( $settings ) {

	require_once $settings[ 'plugin_dir' ] . "providers/campaignmonitor/campaignmonitor/csrest_general.php";
	require_once $settings[ 'plugin_dir' ] . "providers/campaignmonitor/campaignmonitor/csrest_clients.php";
	require_once $settings[ 'plugin_dir' ] . "providers/campaignmonitor/campaignmonitor/csrest_subscribers.php";

	$eoi_form_meta = K::get_var( 'eoi_form_meta', $settings, array() );
	$api_key = K::get_var( 'campaignmonitor_api_key', $eoi_form_meta );
	$client_id = K::get_var( 'campaignmonitor_client_id', $eoi_form_meta );

	// return true if both api_key and client_id are provided
	return $api_key && $client_id;
}

function campaignmonitor_get_lists( $settings ) {

	// Return an empty array if the api_key or the client_id are missing
	if ( ! K::get_var( 'helper', $settings ) ) {
		return array();
	}

	$eoi_form_meta = K::get_var( 'eoi_form_meta', $settings, array() );
	$api_key = K::get_var( 'campaignmonitor_api_key', $eoi_form_meta );
	$client_id = K::get_var( 'campaignmonitor_client_id', $eoi_form_meta );

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

	if( empty( $settings[ 'helper' ] ) ) {
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
		require_once $settings[ 'plugin_dir' ] . "providers/campaignmonitor/campaignmonitor/csrest_general.php";
		require_once $settings[ 'plugin_dir' ] . "providers/campaignmonitor/campaignmonitor/csrest_clients.php";
		require_once $settings[ 'plugin_dir' ] . "providers/campaignmonitor/campaignmonitor/csrest_subscribers.php";

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
	$eoi_form_meta = K::get_var( 'eoi_form_meta', $settings, array() );

	// Get lists from post cache $fca_eoi['_lists']
	if( FCA_EOI_CACHE_LISTS ) {
		$lists = K::get_var( '_lists', $eoi_form_meta );
	}
	if ( ! FCA_EOI_CACHE_LISTS || ! $lists || 'update' === K::get_var( 'cache', $_GET ) ) {
		$lists = campaignmonitor_get_lists ( $settings );
		$eoi_form_meta[ '_lists' ] = $lists;
		delete_post_meta($post->ID, 'fca_eoi' );
		add_post_meta( $post->ID, 'fca_eoi', $eoi_form_meta );
	}

	$lists_formatted = array( '' => 'Not set' );
	foreach ( $lists as $list ) {
		$lists_formatted[ $list[ 'id' ] ] = $list[ 'name' ];
	}

	K::fieldset( campaignmonitor_string( 'Form integration' ) ,
		array(
			array( 'input', 'fca_eoi[campaignmonitor_api_key]',
				array( 
					'class' => 'large-text',
					'value' => K::get_var( 'campaignmonitor_api_key', $eoi_form_meta ) 
						? K::get_var( 'campaignmonitor_api_key', $eoi_form_meta ) 
						: ''
					,
				),
				array( 'format' => '<p><label>API Key :input</label><br /><em>Where can I find <a tabindex="-1" href="http://help.campaignmonitor.com/topic.aspx?t=206" target="_blank">my Campaign Monitor Api Key</a>?</em></p>' )
			),
			array( 'input', 'fca_eoi[campaignmonitor_client_id]',
				array( 
					'class' => 'large-text',
					'value' => K::get_var( 'campaignmonitor_client_id', $eoi_form_meta ) 
						? K::get_var( 'campaignmonitor_client_id', $eoi_form_meta ) 
						: ''
					,
				),
				array( 'format' => '<p><label>Client ID :input</label><em>Where can I find <a tabindex="-1" href="http://www.campaignmonitor.com/api/getting-started/#clientid" target="_blank">my Campaign Monitor Client ID</a>?</em></p>' )
			),
			array( 'select', 'fca_eoi[list_id]',
				array(
					'class' => 'select2',
					'style' => 'width: 100%;',
				),
				array(
					'format' => '<p id="list_id_wrapper"><label>List to subscribe to :select</label></p>',
					'options' => $lists_formatted,
					'selected' => K::get_var( 'list_id', $eoi_form_meta ),
				),
			),
		),
		array(
			'class' => 'k collapsible collapsed',
			'id' => 'fca_eoi_fieldset_form_integration',
		)
	);
}
