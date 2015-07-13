<?php

function campaignmonitor_require_library( $settings ) {
	$base_class = 'CS_REST_Wrapper_Base';

	$classes = array(
		'CS_REST_Administrators' => 'csrest_administrators',
		'CS_REST_Campaigns'      => 'csrest_campaigns',
		'CS_REST_Clients'        => 'csrest_clients',
		'CS_REST_General'        => 'csrest_general',
		'CS_REST_Lists'          => 'csrest_lists',
		'CS_REST_People'         => 'csrest_people',
		'CS_REST_Segments'       => 'csrest_segments',
		'CS_REST_Subscribers'    => 'csrest_subscribers',
		'CS_REST_Templates'      => 'csrest_templates'
	);

	if ( class_exists( $base_class ) ) {
		$base_class = new ReflectionClass( $base_class );
		$base_dir   = realpath( dirname( $base_class->getFileName() ) . '/..' );

		foreach ( array( 'CS_REST_General', 'CS_REST_Clients', 'CS_REST_Subscribers' ) as $class ) {
			if ( ! class_exists( $class ) ) {
				require_once $base_dir . '/' . $classes[ $class ] . '.php';
			}
		}
	} else {
		foreach ( $classes as $class => $file ) {
			if ( ! class_exists( $class ) ) {
				require_once $settings['plugin_dir'] . 'providers/campaignmonitor/campaignmonitor/' . $file . '.php';
			}
		}
	}
}

function campaignmonitor_object( $settings ) {
	static $object = null;

	if ( is_null( $object ) ) {
		$object = campaignmonitor_object_create( $settings );
	}

	return $object;
}

function campaignmonitor_object_create( $settings ) {

	campaignmonitor_require_library( $settings );

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

	$helper = campaignmonitor_object( $settings );

	// Return an empty array if the api_key or the client_id are missing
	if ( empty( $helper ) ) {
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

	$helper = campaignmonitor_object( $settings );

	if ( empty( $helper ) ) {
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

		campaignmonitor_require_library( $settings );

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
