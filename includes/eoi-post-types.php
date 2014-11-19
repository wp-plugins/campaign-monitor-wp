<?php

class EasyOptInsPostTypes {

	public $settings;

	public function __construct( $settings ) {

		$this->settings = $settings;

		$providers_available = array_keys( $this->settings[ 'providers' ] );

		// Register custom post type
		add_action( 'init', array( $this, 'register_custom_post_type' ) );

		// Add provider object and post settings to settings
		add_action( 'init', array( $this, 'more_settings' ) );

		// Handle AJAX submission (unused)
		add_action( 'init', array( $this, 'handle_submission' ) );

		// Initiate action hooks
		add_action( 'save_post', array( $this, 'save_meta_box_content' ), 1, 2 );

		// Live preview
		add_filter( 'the_content', array( $this, 'live_preview' ) );

		// Scripts and styles
		add_filter( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );

		add_action( 'admin_head', array( $this, 'hide_minor_publishing' ) );

		add_filter( 'admin_body_class', array( $this, 'add_body_class' ) );

		add_filter( 'wp_insert_post_data', array( $this, 'force_published' ) );

		// add_action( 'admin_footer', array( $this, 'disable_metabox_toggle' ) );

		add_action( 'wp_ajax_fca_eoi_subscribe', array( $this, 'ajax_subscribe' ) );

		add_filter( 'get_user_option_screen_layout_easy-opt-ins', array( $this, 'force_one_column' ) );

		add_filter( 'get_user_option_meta-box-order_easy-opt-ins', array( $this, 'order_columns' ) );

		add_filter( 'gettext', array( $this, 'override_text' ) );

		add_filter( 'bulk_actions-edit-easy-opt-ins', array( $this, 'disable_bulk_edit' ) );

		add_filter( 'post_row_actions', array( $this, 'remove_quick_edit' ) );

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		add_filter( 'enter_title_here', array( $this, 'change_default_title' ) );

		add_filter( 'init', array( $this, 'bind_content_filter' ), 10 );

		foreach ( $providers_available as $provider ) {
			add_action( 'wp_ajax_fca_eoi_' . $provider . '_get_lists', $provider . '_ajax_get_lists' );
		}

		// Hook provder callback functions
		foreach ( $providers_available as $provider ) {
			add_filter( 'fca_eoi_alter_admin_notices', $provider . '_admin_notices', 10, 1 );
		} 
	}

	public function more_settings() {

		$providers_available = array_keys( $this->settings[ 'providers' ] );

		// Get the post id 
		if( is_admin() ) {
			$form_id = K::get_var( 'post', $_GET );
		} else {
			$protocol = is_ssl() ? 'https' : 'http';
			$form_id = K::get_var( 'fca_eoi_form_id', $_POST );
		}

		// Save form meta into object settings
		$this->settings[ 'eoi_form_meta' ] = empty ( $form_id )
			? false
			: get_post_meta( $form_id, 'fca_eoi', true )
		;

		// Add last 3 posts and there meta
		// We need to prepare the previous posts
		$fca_eoi_last_3_forms = array();
		foreach (query_posts( 'posts_per_page=3&post_type=easy-opt-ins' ) as $i => $f ) {
			$fca_eoi_last_3_forms[ $i ][ 'post' ] = $f;
			$fca_eoi_last_3_forms[ $i ][ 'fca_eoi' ] = get_post_meta( $f->ID, 'fca_eoi', true );
		}
		// reset query after the loop
		wp_reset_query();
		$this->settings[ 'fca_eoi_last_3_forms' ] = $fca_eoi_last_3_forms;

		// Initialize provider(s) instance(s)
		foreach ( $providers_available as $provider ) {
			$this->settings[ $provider . '_helper' ] = empty ( $form_id )
				? call_user_func( $provider . '_object', $this->settings )
				: call_user_func( $provider . '_object', $this->settings );
			;
		}
	}

	public function register_custom_post_type() {

		$labels = array(
			'name' => __('Opt-in Forms') ,
			'singular_name' => __('Opt-in Form') ,
			'add_new' => __('Add New') ,
			'add_new_item' => __('Add New Opt-in Form') ,
			'edit_item' => __('Edit Opt-in Form') ,
			'new_item' => __('New Opt-in Form') ,
			'all_items' => __('Opt-in Forms') ,
			'view_item' => __('View Opt-in Form') ,
			'search_items' => __('Search Opt-in Form') ,
			'not_found' => __('No Opt-in Form Found') ,
			'not_found_in_trash' => __('No Opt-in Form Found in Trash') ,
			'parent_item_colon' => '',
			'menu_name' => __('Opt-in Forms')
		);
		$args = array(
			'menu_icon' => $this->settings[ 'provider' ] 
				? "{$this->settings['plugin_url']}/providers/{$this->settings['provider']}/icon.png"
				: "{$this->settings['plugin_url']}/icon.png"
			,
			'labels' => $labels,
			'public' => false,
			'exclude_from_search' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'query_var' => true,
			'rewrite' => array(
				'slug' => 'easy-opt-ins',
			) ,
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => false,
			'menu_position' => 105,
			'supports' => array(
				'title',
			) ,
			'register_meta_box_cb' => array(
				$this,
				'add_meta_boxes'
			)
		);
		register_post_type('easy-opt-ins', $args);
	}

	public function add_meta_boxes() {
		add_meta_box(
			'fca_eoi_meta_box_nav',
			__( 'Navigation' ),
			array( &$this, 'meta_box_content_nav' ),
			'easy-opt-ins',
			'side',
			'high'
		);
		add_meta_box(
			'fca_eoi_meta_box_setup',
			__( 'Setup' ),
			array( &$this, 'meta_box_content_setup' ),
			'easy-opt-ins',
			'side',
			'high'
		);
		add_meta_box(
			'fca_eoi_meta_box_build',
			__( 'Form Builder' ),
			array( &$this, 'meta_box_content_build' ),
			'easy-opt-ins',
			'side',
			'high'
		);
		add_meta_box(
			'fca_eoi_meta_box_provider',
			__( 'Email Marketing Provider Integration' ),
			array( &$this, 'meta_box_content_provider' ),
			'easy-opt-ins',
			'side',
			'high'
		);
		add_meta_box(
			'fca_eoi_meta_box_publish',
			__( 'Publication' ),
			array( &$this, 'meta_box_content_publish' ),
			'easy-opt-ins',
			'side',
			'high'
		);
		add_meta_box(
			'fca_eoi_meta_box_thanks',
			__( 'Thank You Page' ),
			array( &$this, 'meta_box_content_thanks' ),
			'easy-opt-ins',
			'side',
			'high'
		);
		if ( has_action( 'fca_eoi_powerups' ) ) {
			add_meta_box(
				'fca_eoi_meta_box_powerups',
				__( 'Power Ups' ),
				array( &$this, 'meta_box_content_powerups' ),
				'easy-opt-ins',
				'side',
				'high'
			);
		}
		if ( FCA_EOI_DEBUG ) {
			add_meta_box(
				'fca_eoi_meta_box_debug',
				'Debug', 
				array( &$this, 'meta_box_content_debug' ),
				'easy-opt-ins',
				'side',
				'high'
			);
		}
	}

	public function meta_box_content_nav() {
		?>
		<h2 class="nav-tab-wrapper" style="padding: 0 10px">
			<a href="#fca_eoi_meta_box_setup" class="nav-tab nav-tab-active">Choose</a>
			<a href="#fca_eoi_meta_box_build" class="nav-tab ">Build</a>
			<?php if( FCA_EOI_DEBUG ) : ?>
				<a href="#fca_eoi_meta_box_debug" class="nav-tab ">Debug</a>
			<?php endif; ?>
		</h2>
		<?php
	}

	public function meta_box_content_setup() {

		global $post;

		$layouts_types_labels = array(
			'widget' => 'Widgets',
			'postbox' => 'Post Boxes',
			'lightbox' => 'Lightbox Popups',
		);

		$fca_eoi = get_post_meta( $post->ID, 'fca_eoi', true );

		echo '<h2>' . __( 'Layouts' ) . '</h2>';
		echo '<script id="fca_eoi_texts" type="application/json">{ "headline_copy": "Headline Copy", "description_copy": "Description Copy", "name_placeholder": "Name Placeholder", "email_placeholder": "Email Placeholder", "button_copy": "Button Copy", "privacy_copy": "Privacy Copy" }</script>';
			
		// Build the layouts array
		$layouts = $layouts_types = $layouts_types_found = array();
		foreach ( glob( $this->settings[ 'plugin_dir' ] . 'layouts/*', GLOB_ONLYDIR ) as $v) {
			$layouts_types_found[] = basename( $v );
		}

		$layouts_types_accepted = array_keys( $layouts_types_labels );
		foreach ( $layouts_types_accepted as $layout_type ) {
			if ( in_array( $layout_type, $layouts_types_found ) ) {
				$layouts_types[] = $layout_type;
			}
		}

		// Layouts types mini-tabs
		echo '<ul class="category-tabs" id="layouts_types_tabs">';
		foreach ( $layouts_types as $layout_type ) {
			K::wrap(
				$layouts_types_labels[ $layout_type ]
				, array(
					'href' => '#'. 'layouts_type_' . $layout_type,
				)
				, array(
					'html_before' => '<li' . ( 'widget' === $layout_type ? ' class="tabs"' : '' ) . ' >',
					'html_after' => '</li> ',
					'in' => 'a',
				)
			);
		}
		echo '</ul>';

		// Layout types
		foreach ( $layouts_types as $layout_type ) {
			echo '<div id="layouts_type_' . $layout_type . '">';
			foreach ( glob( $this->settings[ 'plugin_dir' ] . "layouts/$layout_type/*", GLOB_ONLYDIR ) as $layout_path ) {
				// Grab layout details
				$layout_id = basename( $layout_path );
				include $layout_path . '/layout.php';
				$layout[ 'css' ] = file_exists( $layout_path . '/layout.css' )
					? file_get_contents( $layout_path . '/layout.css')
					: '';
				$layout[ 'template' ] = file_get_contents( $layout_path . '/layout.html');
				$layout[ 'template' ] = str_replace(
					array(
						'<form',
						'/form',
						'{{{description_copy}}}',
						'{{{headline_copy}}}',
						'{{{name_field}}}',
						'{{{email_field}}}',
						'{{{submit_button}}}',
						'{{{privacy_copy}}}',
						'{{{fatcatapps_link}}}'
					)
					, array(
						'<div id="fca_eoi_preview_form" class="fca_eoi_' . $layout_id . '"',
						'/div',
						'<div data-fca-eoi-fieldset-id ="description">{{{description_copy}}}</div>',
						'<span data-fca-eoi-fieldset-id ="headline" id="fca_eoi_preview_headline_copy">{{{headline_copy}}}</span>',
						'<input data-fca-eoi-fieldset-id ="name_field" type="text" placeholder="{{{name_placeholder}}}" />',
						'<input data-fca-eoi-fieldset-id ="email_field" type="email" placeholder="{{{email_placeholder}}}" />',
						'<input data-fca-eoi-fieldset-id ="button" type="submit" value="{{{button_copy}}}" />',
						'<span data-fca-eoi-fieldset-id ="privacy">{{{privacy_copy}}}</span>',
						'{{#show_fatcatapps_link}}<p class="fca_eoi_' . $layout_id . '_fatcatapps_link_wrapper"><span data-fca-eoi-fieldset-id ="fatcatapps"><a href="#" onclick="javascript:return false">Powered by fatcat apps</a></span></p>{{/show_fatcatapps_link}}',
					)
					, $layout[ 'template' ]
				);
				$layout[ 'template' ] .= sprintf( '<style>%s</style>', $layout[ 'css' ] );
				$layouts[ $layout_id ] = $layout;
				// Output the layout image and hidden template
				$layout_output_tpl = '
					<div
						class="fca_eoi_layout has-tip"
						data-layout-id=":id" data-layout-type=":type" title="Your theme’s built-in form styling will be used." data-tooltip				
					>
						<img src=":src" />
						<h3>:name</h3>
						<script id="fca_eoi_tpl_:id" type="x-tmpl-mustache">:template</script>
						<script id="fca_eoi_editables_:id" type="application/json">:editables</script>
						<script id="fca_eoi_texts_:id" type="application/json">:texts</script>
					</div>
				';
				$layout_output = str_replace(
					array(
						':id',
						':type',
						':name',
						':src',
						':template',
						':editables',
						':texts',
					),
					array(
						$layout_id,
						$layout_type,
						$layout[ 'name' ],
						file_exists( $layout_path . '/screenshot.png' )
							? $this->settings[ 'plugin_url' ] . '/layouts/' . $layout_type . '/' . $layout_id . '/screenshot.png'
							: $this->settings[ 'plugin_url' ] . '/layouts/no-image.jpg'
						,
						$layout[ 'template' ],
						! empty( $layout[ 'editables' ] )
							? json_encode( $layout[ 'editables' ] )
							: 'null'
						,
						! empty( $layout[ 'texts' ] )
							? json_encode( $layout[ 'texts' ] )
							: 'null'
						,
					),
					$layout_output_tpl
				);

				// Add autocolors
				if( ! empty( $layout[ 'autocolors' ] ) ) {
					foreach ($layout[ 'autocolors' ] as $autocolor ) {
						$layout_output .= '
							<script>
								jQuery( document ).ready( function( $ ) { 
									
									var source = "[name*=\'' . $autocolor[ 'source' ] . '\']";
									var destination = "[name*=\'' . $autocolor[ 'destination' ] . '\']";

									$( destination ).closest( "p" ).hide();

									$( document ).on( "change", source, function() {
										var c = tinycolor( $(this).val() );'
						;
						foreach ( $autocolor[ 'operations'] as $op => $val ) {
							$layout_output .= '
										c = c.' . $op. '( ' . 1*$val . ' );
										';
						}
						$layout_output .= '
										$( destination ).val( c.toString() )
										$( destination ).blur();
									} );
								} );
							</script>'
						;
					}
				}

				echo $layout_output;
			}
			echo '</div>';
		}
		echo '<br clear="all"/>';

		// Prepare layouts array (id->name) for K
		$layouts_array = array();
		foreach ( $layouts as $layout_id => $layout ) {
			$layouts_array[ $layout_id ] = $layout[ 'name' ];
		}

		// Print hidden select box, it will be controlled by images (UI)
		K::select(
			'fca_eoi[layout]',
			array(
				'class' => 'hidden',
				'id' => 'fca_eoi_layout_select',
			),
			array(
				'options' => $layouts_array,
				'selected' => K::get_var( 'layout', $fca_eoi ),
			)
		);

		// Prepare the properties fields templates
		$property_templates[ 'color' ] = K::input( '{{property_name}}',
			array(
				'class' => sprintf(
					"color { hash: true, caps: false, required: false, pickerPosition: '%s' }"
					, is_rtl() ? 'right' : 'left'
				),
				'value' => '{{property_value}}',
			),
			array(
				'format' => '<p class="clear"><label>{{property_label}}<br />:input <a href="#transparent">None</a></label></p>',
				'nocolorpicker' => true,
				'return' => true,
			)
		);
		$property_templates[ 'icon' ] = K::input( '{{property_name}}',
			array(
				'data-value-unchecked' => '{{property_value_unchecked}}',
				'data-is-checked' => '{{property_is_checked}}',
				'type' => 'checkbox',
				'value' => '{{property_value}}',
			),
			array(
				'format' => '<p style="display: inline;"><label class="fca_eoi_toggle">{{{icon}}} :input</label></p>',
				'return' => true,
			)
		);
		$property_templates[ 'font-size' ] = K::select( '{{property_name}}',
			array(
				'data-selected' => '{{selected}}',
			),
			array(
				'format' => '<p class="clear"><label>{{property_label}}<br />:select</label></p>',
				'options' => array(
					'none' => '',
					'7px' => '7px',
					'8px' => '8px',
					'9px' => '9px',
					'10px' => '10px',
					'11px' => '11px',
					'12px' => '12px',
					'13px' => '13px',
					'14px' => '14px',
					'15px' => '15px',
					'16px' => '16px',
					'17px' => '17px',
					'18px' => '18px',
					'19px' => '19px',
					'20px' => '20px',
					'21px' => '21px',
					'22px' => '22px',
					'23px' => '23px',
					'24px' => '24px',
					'25px' => '25px',
					'26px' => '26px',
					'27px' => '27px',
					'28px' => '28px',
					'29px' => '29px',
					'30px' => '30px',
					'31px' => '31px',
					'32px' => '32px',
					'33px' => '33px',
					'34px' => '34px',
					'35px' => '35px',
					'36px' => '36px',
				),
				'selected' => 'none',
				'return' => true,
			)
		);

		// Print the proprties fields templates
		foreach ( $property_templates as $prop => $property_template) {
			echo '<script id="fca_eoi_property_' . $prop . '" type="x-tmpl-mustache">' . $property_template . '</script>';
		}

		// Print a copy of the current settings
		echo
			'<script id="fca_eoi_post_meta" type="application/json">'
			. ( $fca_eoi ? json_encode( $fca_eoi ) : '{}' )
			. '</script>'
		;
	}

	public function meta_box_content_provider() {

		global $post;
		$fca_eoi = get_post_meta( $post->ID, 'fca_eoi', true );
		$providers_available = array_keys( $this->settings[ 'providers' ] );
		$providers_options = array();
		$screen = get_current_screen();
		$fca_eoi_last_3_forms = $this->settings[ 'fca_eoi_last_3_forms' ];

		// @todo: remove
		// Hack for mailchimp upgrade
		$fca_eoi[ 'mailchimp_list_id' ] = K::get_var(
			'mailchimp_list_id'
			, $fca_eoi
			, K::get_var( 'list_id' , $fca_eoi )
		);
		if( K::get_var( 'list_id' , $fca_eoi ) ) {
			$fca_eoi[ 'provider' ] = 'mailchimp';
		}
		// End of hack
		// Hack for campaignmonitor upgrade
		$fca_eoi[ 'campaignmonitor_list_id' ] = K::get_var(
			'campaignmonitor_list_id'
			, $fca_eoi
			, K::get_var( 'list_id' , $fca_eoi )
		);
		if( strlen( K::get_var( 'campaignmonitor_list_id' , $fca_eoi ) ) == 32){
			$fca_eoi[ 'provider' ] = 'campaignmonitor';
		}
		// End of hack

		// Prepare providers options
		foreach ($this->settings[ 'providers' ] as $provider_id => $provider ) {
			$providers_options[ $provider_id ] = $provider[ 'info' ][ 'name' ];
		}

		// Provider choice if there are many providers
		if ( 1 < count( $providers_available) ) {

			K::select( 'fca_eoi[provider]'
				, array( 
					'class' => 'select2',
					'style' => 'width: 27em;',
				)
				, array( 
					'format' => '<p><label>:select</label></p>',
					'options' => array( '' => 'Not set' ) + $providers_options,
					'selected' => K::get_var( 'provider', $fca_eoi ),
				)
			);
		}

		$providers_available = array_keys( $this->settings[ 'providers' ] );
		foreach ( $providers_available as $provider ) {
			call_user_func( $provider . '_integration', $this->settings );
		}
	}

	public function meta_box_content_publish() {

		global $post;

		$fca_eoi = get_post_meta( $post->ID, 'fca_eoi', true );

		// Widgets
		K::wrap(
			sprintf(
				__( 'You can publish this opt-in box by going to <a href="%s" target="_blank">Appearance › Widgets</a>')
				, admin_url( 'widgets.php')
			)
			, array( 'id' => 'fca_eoi_publish_widget' )
			, array( 'in' => 'p' )
		);

		// Post boxes
		echo '<div id ="fca_eoi_publish_postbox">';
		K::wrap( __( 'Shortcode')
			, array( 'style' => 'padding-left: 0px; padding-right: 0px; ' )
			, array( 'in' => 'h3' )
		);
		K::wrap( __( "Copy and paste beneath shortcode anywhere on your site where you'd like this opt-in form to appear." )
			, null
			, array( 'in' => 'p' )
		);
		K::input( ''
			, array(
				'class' => 'regular-text autoselect',
				'readonly' => 'readonly',
				'value' => sprintf( '[%s id=%d]', $this->settings[ 'shortcode' ], $post->ID ),
			)
			, array( 'format' => '<p>:input</p>', )
		);
		K::wrap( __( 'Append to post or page')
			, array( 'style' => 'padding-left: 0px; padding-right: 0px; ' )
			, array( 'in' => 'h3' )
		);
		K::wrap( __( 'Automatically append this opt-in to the following posts, categories and/or pages.' )
			, null
			, array( 'in' => 'p' )
		);
		k_selector( 'fca_eoi[publish_postbox]', K::get_var( 'publish_postbox', $fca_eoi, array( 'post' ) ) );
		echo '</div>';

		// Light boxes
		echo '<div id ="fca_eoi_publish_lightbox">';
		K::wrap( __( 'Shortcode')
			, array( 'style' => 'padding-left: 0px; padding-right: 0px; ' )
			, array( 'in' => 'h3' )
		);
		K::wrap( __( "Copy and paste beneath shortcode anywhere on your site where you'd like this opt-in form to appear." )
			, null
			, array( 'in' => 'p' )
		);
		K::input( ''
			, array(
				'class' => 'regular-text autoselect',
				'readonly' => 'readonly',
				'value' => "[easy-opt-ins id=$post->ID]",
			)
			, array( 'format' => '<p>:input</p>', )
		);
		K::wrap( __( 'Append to post or page')
			, array( 'style' => 'padding-left: 0px; padding-right: 0px; ' )
			, array( 'in' => 'h3' )
		);
		K::wrap( __( 'Automatically append this opt-in to the following posts, categories and/or pages.' )
			, null
			, array( 'in' => 'p' )
		);
		k_selector( 'fca_eoi[publish_lightbox]', K::get_var( 'publish_lightbox', $fca_eoi, array( 'post' ) ) );
		echo '</div>';
	}

	public function meta_box_content_build() {

		global $post;
		$fca_eoi = get_post_meta( $post->ID, 'fca_eoi', true );
		$providers_available = array_keys( $this->settings[ 'providers' ] );
		$providers_options = array();
		$screen = get_current_screen();
		$fca_eoi_last_3_forms = $this->settings[ 'fca_eoi_last_3_forms' ];

		// Prepare providers options
		foreach ($this->settings[ 'providers' ] as $provider_id => $provider ) {
			$providers_options[ $provider_id ] = $provider[ 'info' ][ 'name' ];
		}

		K::wrap( '', array( 'id' => 'fca_eoi_preview' ) );

		echo '<div id="fca_eoi_settings">';

		K::fieldset( 'Form',
			array(
			),
			array(
				'class' => 'k collapsible collapsed',
				'id' => 'fca_eoi_fieldset_form',
			)
		);
		K::fieldset( 'Headline',
			array(
				array( 'input', 'fca_eoi[headline_copy]',
					array( 'value' => K::get_var( 'headline_copy', $fca_eoi ), ),
					array( 'format' => '<p><label>Headline Copy<br />:input</label></p>' )
				),
			),
			array(
				'class' => 'k collapsible collapsed',
				'id' => 'fca_eoi_fieldset_headline'
			)
		);
		K::fieldset( 'Description',
			array(
				array( 'textarea', 'fca_eoi[description_copy]',
					array(),
					array(
						'format' => ':textarea<br />',
						'editor' => true,
						'value' => K::get_var( 'description_copy', $fca_eoi ),
					)
				),
			),
			array(
				'class' => 'k collapsible collapsed',
				'id' => 'fca_eoi_fieldset_description',
			)
		);
		K::fieldset( 'Name Field', 
			array(
				array(
					'input',
					'fca_eoi[show_name_field]',
					array(
						'type' => 'checkbox',
						'checked' => K::get_var( 'show_name_field', $fca_eoi ),
					),
					array( 'format' => '<p><label>:input Show Name Field</label></p>' ),
				),
				array( 'input', 'fca_eoi[name_placeholder]',
					array( 
						'value' => K::get_var( 'name_placeholder', $fca_eoi ) ? K::get_var( 'name_placeholder', $fca_eoi ) : 'Your Name',
					),
					array( 'format' => '<p><label>Placeholder Text<br />:input</label></p>' )
				),
			),
			array(
				'class' => 'k collapsible collapsed',
				'id' => 'fca_eoi_fieldset_name_field',
			)
		);
		K::fieldset( 'Email Field', 
			array(
				array( 'input', 'fca_eoi[email_placeholder]',
					array( 'value' => K::get_var( 'email_placeholder', $fca_eoi ) ? K::get_var( 'email_placeholder', $fca_eoi ) : 'Your Email' ),
					array( 'format' => '<p><label>Placeholder Text<br />:input</label></p>' )
				),
			),
			array(
				'class' => 'k collapsible collapsed',
				'id' => 'fca_eoi_fieldset_email_field',
			)
		);
		K::fieldset( 'Button',
			array(
				array( 'input', 'fca_eoi[button_copy]',
					array( 'value' => K::get_var( 'button_copy', $fca_eoi ) ? K::get_var( 'button_copy', $fca_eoi ) : 'Subscribe Now' ),
					array( 'format' => '<p><label>Button Copy<br />:input</label></p>' ) 
				),
			),
			array(
				'class' => 'k collapsible collapsed',
				'id' => 'fca_eoi_fieldset_button'
			)
		);
		K::fieldset( 'Privacy Policy',
			array(
				array( 'textarea', 'fca_eoi[privacy_copy]',
					array(
						'class' => 'large-text',
					),
					array(
						'format' => '<p><label>Privacy Policy Copy<br />:textarea</label></p>',
						'value' => K::get_var( 'privacy_copy', $fca_eoi ),
					)
				),
			),
			array(
				'class' => 'k collapsible collapsed',
				'id' => 'fca_eoi_fieldset_privacy',
			)
		);
		K::fieldset( 'Branding',
			array(
				// array( 'wrap', 'Marketing copy goes here'
				// 	, array( 'style' => 'font-weight:bold; font-size: 1.2em;' )
				// 	, array( 'in' => 'p' )
				// ),
				array(
					'input',
					'fca_eoi[show_fatcatapps_link]',
					array(
						'type' => 'checkbox',
						'checked' => K::get_var( 'show_fatcatapps_link', $fca_eoi ),
					),
					array( 'format' => '<p><label>:input Show <a href="http://fatcatapps.com/" target="_blank">Easy Opt-ins</a> Branding</label></p>' ),
				),
			),
			array(
				'class' => 'k collapsible collapsed',
				'id' => 'fca_eoi_fieldset_fatcatapps',
			)
		);
		echo '</div>';
	}

	public function meta_box_content_thanks() {
		global $post;
		$fca_eoi = get_post_meta( $post->ID, 'fca_eoi', true );
		$screen = get_current_screen();
		$fca_eoi_last_3_forms = $this->settings[ 'fca_eoi_last_3_forms' ];

		// Get the previous thank you page if this is a new post
		$thank_you_page_suggestion = false;
		if ( 'add' === $screen->action ) {
			foreach ( $fca_eoi_last_3_forms as $fca_eoi_previous_form ) {
				try {
					if(
						K::get_var( 'fca_eoi', $fca_eoi_previous_form )
						&& K::get_var( 'thank_you_page', $fca_eoi_previous_form[ 'fca_eoi' ] )
					) {
						$thank_you_page_suggestion = $fca_eoi_previous_form[ 'fca_eoi' ][ 'thank_you_page' ];
						break;
					}
				} catch ( Exception $e ) {}
			}
		}

		// Prepare options
		$pages_objects = get_pages();
		$pages = array();
		foreach ( $pages_objects as $page_obj ) {
			$pages[ $page_obj->ID ] = $page_obj->post_title;
		}

		K::wrap( 'Redirect user to the following page after submitting the form:'
			, null
			, array( 'in' => 'p' )
		);
		K::select( 'fca_eoi[thank_you_page]'
			, array( 
				'class' => 'select2',
				'style' => 'width: 27em;',
			)
			, array( 
				'format' => '<p><label>:select</label></p>',
				'options' => array( '' => 'Not set' ) + $pages,
				'selected' => 'add' === $screen->action
					? $thank_you_page_suggestion
					: K::get_var( 'thank_you_page', $fca_eoi )
				,
			)
		);
		K::wrap( __( 'Create a new "Thank You Page" &rsaquo;' )
			, array(
				'href' => admin_url( 'post-new.php?post_type=page' ),
				'target' => '_blank',
			)
			, array(
				'in' => 'a',
				'html_before' => '<p>',
				'html_after' => '</p>',
			)
		);
	}

	public function meta_box_content_powerups() {

		global $post;
		$fca_eoi = get_post_meta( $post->ID, 'fca_eoi', true );

		do_action('fca_eoi_powerups', $fca_eoi ); 
	}

	public function meta_box_content_debug() {

		global $post;

		$fca_eoi = get_post_meta( $post->ID, 'fca_eoi', true );

		!d( $fca_eoi );
	}

	/**
	 * Save the Metabox Data
	 */
	public function save_meta_box_content( $post_id, $post ) {

		// Save meta data
		delete_post_meta( $post->ID, 'fca_eoi' );
		if( $meta = K::get_var( 'fca_eoi', $_POST ) ) {
			// Add provider if missing (happens on free distros where there is only one provider)
			if( ! K::get_var( 'provider', $meta ) ) {
				$meta[ 'provider' ] = $this->settings[ 'provider' ];
			}

			// Keep only the current providers settings, Remove all [provider]_[setting] not belonging to the current provider
			$provider = K::get_var( 'provider', $meta );
			if( $provider ) {
				$providers = array_keys( $this->settings[ 'providers' ] );
				$other_providers = array_values( array_diff( $providers, array( $provider ) ) );
				foreach ( $meta as $k => $v ) {
					$p = explode( '_', $k );
					$k_1 = array_shift( $p );
					if( in_array( $k_1, $other_providers ) ) {
						unset( $meta[ $k ] );
					}
				}
			}

			// Sanitize custom CSS
			$meta[ 'custom_css' ] = sanitize_text_field( K::get_var( 'custom_css', $meta ) );

			// Make sure emtpy value for publish_postbox or publish_lightbox are saved as array(-1)
			if( ! K::get_var( 'publish_postbox' , $meta, array() ) ) {
				$meta[ 'publish_postbox' ] = array(-1);
			}
			if( ! K::get_var( 'publish_lightbox' , $meta, array() ) ) {
				$meta[ 'publish_lightbox' ] = array(-1);
			}

			add_post_meta( $post->ID, 'fca_eoi', $meta );
		}
	}

	public function live_preview( $content ) {
		global $post;
		if (get_post_type() == 'easy-opt-ins' && is_main_query()) {
			$shortcode = sprintf( '[%s id=%d]', $this->settings[ 'shortcode' ], $post->ID );
			return do_shortcode($shortcode);
		} else {
			return $content;
		}
	}

	public function admin_enqueue() {

		$protocol = is_ssl() ? 'https' : 'http';
		$provider = $this->settings[ 'provider' ];
		$providers_available = array_keys( $this->settings[ 'providers' ] );

		$screen = get_current_screen();
		if( 'easy-opt-ins' === $screen->id ){
			wp_enqueue_script( 'mustache', $protocol . '://cdnjs.cloudflare.com/ajax/libs/mustache.js/0.8.1/mustache.min.js' );
			wp_enqueue_script( 'select2', $protocol . '://cdnjs.cloudflare.com/ajax/libs/select2/3.5.0/select2.js' );
			wp_enqueue_script( 'tinycolor', $protocol . '://cdnjs.cloudflare.com/ajax/libs/tinycolor/1.0.0/tinycolor.min.js' );
			wp_enqueue_script( 'jscolor', $this->settings['plugin_url'] . '/assets/vendor/jscolor/jscolor.js' );
			wp_enqueue_script( 'admin-cpt-easy-opt-ins', $this->settings['plugin_url'] . '/assets/admin/cpt-easy-opt-ins.js' );
			wp_enqueue_style( 'fca_eoi', $this->settings[ 'plugin_url' ].'/assets/style.css' );
			foreach ( $providers_available as $provider ) {
				wp_enqueue_script( 'admin-cpt-easy-opt-ins-' . $provider, $this->settings['plugin_url'] . '/providers/' . $provider . '/cpt-easy-opt-ins.js' );
			}
			wp_enqueue_style( 'admin-cpt-easy-opt-ins', $this->settings['plugin_url'] . '/assets/admin/cpt-easy-opt-ins.css' );
			wp_enqueue_style( 'font-awesome', $protocol . '://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.1.0/css/font-awesome.min.css' );
			wp_enqueue_style( 'select2', $protocol . '://cdnjs.cloudflare.com/ajax/libs/select2/3.5.0/select2.min.css' );
			wp_enqueue_script('bootstrap-js', $protocol . '://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.2.0/js/bootstrap.min.js');
		}
		if( 'widgets' === $screen->id ){
			wp_enqueue_script( 'select2', $protocol . '://cdnjs.cloudflare.com/ajax/libs/select2/3.5.0/select2.js' );
			wp_enqueue_style( 'select2', $protocol . '://cdnjs.cloudflare.com/ajax/libs/select2/3.5.0/select2.min.css' );
		}
	}

	/**
	 * Hides minor publising form items (status, visibility and publication date)
	 * 
	 * This function shoud be used along with force_published to prevent
	 * saving posts as drafts
	 */
	public function hide_minor_publishing() {
		$screen = get_current_screen();
		if( in_array( $screen->id, array( 'easy-opt-ins' ) ) ) {
			echo '<style>#minor-publishing { display: none; }</style>';
		}
	}

	/**
	 * Disables meta box toggling (collapse/expand) for specified post types
	 */
	public function disable_metabox_toggle() {
		
		$current_screen = get_current_screen();

		// Array of post types where we want to remove metabox toggling
		$post_types = array(
			'easy-opt-ins',
		);

		if( in_array( $current_screen->id, $post_types ) ) {
			?>
			<script type="text/javascript">
				jQuery( document ).ready( function($) {
					$( '.postbox' ).removeClass( 'closed' );
					$( '.postbox .hndle' ).css( 'cursor', 'default' );
					$( document ).delegate( '.postbox h3, .postbox .handlediv', 'click', function() {
						$( this )
							.unbind( 'click.postboxes' )
							.parent().removeClass( 'closed' );
					} );
				} );
			</script>
			<?php		
		}
	}

	/**
	 * Forces one column
	 */
	public function force_one_column() {
		
		return 1;
	}

	/**
	 * Sort metaboxes
	 */
	public function order_columns( $order ) {
		return array(
			'normal' => join( ",", array(
				'submitdiv',
				'fca_eoi_meta_box_nav',
				'fca_eoi_meta_box_setup',
				'fca_eoi_meta_box_build',
				'fca_eoi_meta_box_provider',
				'fca_eoi_meta_box_thanks',
				'fca_eoi_meta_box_publish',
				'fca_eoi_meta_box_powerups',
				'fca_eoi_meta_box_debug',
			) ),
			'side' => '',
			'advanced' => '',
		);
	}

	/**
	 * replacing the default "Enter title here" placeholder text in the title input box to 
	 * 
	 */
	public function change_default_title($title) {

		$screen = get_current_screen();

		if ( 'easy-opt-ins' == $screen->post_type ) {
			$title = 'Enter name here';
		}

		return $title;
	}

	/**
	 * Override some strings to match our likings
	 */
	public function override_text( $text ) {

		if( $post = K::get_var( 'post', $GLOBALS ) ) {
			if ( 'easy-opt-ins' === $post->post_type ) {
				switch ( $text ) {
				case 'Post published. <a href="%s">View post</a>':
				case 'Post updated. <a href="%s">View post</a>':
					$text = __( 'Opt-In Form saved.' );
					break;
				case 'Publish':
					$text = __( 'Save' );
					break;
				case 'Update':
					$text = __( 'Save' );
					break;
				}
			}
		}
		return $text;
	}
	
	public function force_published( $post ) {

		if( ! in_array( $post[ 'post_status' ], array( 'auto-draft', 'trash') ) ) {
			if( in_array( $post[ 'post_type' ], array( 'easy-opt-ins' ) ) ) {
				$post['post_status'] = 'publish';
			}
		}
		return $post;
	}

	/**
	 * Disables bulk editing
	 */
	public function disable_bulk_edit( $actions ){
		unset( $actions[ 'edit' ] );
		return $actions;
	}

	/**
	 * Removes quick edit
	 */
	public function remove_quick_edit( $actions ) {
		unset($actions['inline hide-if-no-js']);
		return $actions;
	}

	/**
	 * Add the desired body classes (backend)
	 */
	public function add_body_class( $classes ) {
		
		return "$classes fca_eoi";
	}

	/**
	 * Handle Adding a subscriber with Ajax
	 */
	public function ajax_subscribe() {

		// Check a list_id is provided
		$list_id = K::get_var( 'list_id' , $_POST );
		if( ! $list_id ) {
			echo '✗';
			exit;
		}

		// Subscribe user
		$status = call_user_func( $this->settings[ 'provider' ] . '_add_user' , $this->settings , $_POST , $list_id );

		// Output ✓ or ✗
		if( $status ) {
			echo '✓';
		} else {
			echo '✗';
		}
		exit;
	}

	/**
	 * Handle form submissions
	 */
	public function handle_submission() {

		// Check that we have a form submission
		if ( ! K::get_var( 'fca_eoi', $_POST ) ) {
			return;
		}

		// Check that we have a valid form
		$id = K::get_var( 'id', $_POST );
		$post = get_post( $id );
		if ( $post && 'easy-opt-ins' === $post->post_type ) {
			// Nothing, we're good
		} else {
			return;
		}

		// Get meta
		$fca_eoi = get_post_meta( $id, 'fca_eoi', true );
		$provider = K::get_var( 'provider' , $fca_eoi );

		// Check a list_id is provided
		$list_id = K::get_var( $provider . '_list_id' , $fca_eoi );

		// @todo: remove
		// Hack for mailchimp upgrade
		if( empty( $fca_eoi[ 'provider' ] ) ) {
			$list_id = K::get_var(
				'mailchimp_list_id'
				, $fca_eoi
				, K::get_var( 'list_id' , $fca_eoi )
			);
			$provider = 'mailchimp';
		}
		// End of Hack

		// Hack for campaignmonitor upgrade
		if( strlen( K::get_var( 'list_id' , $fca_eoi ) ) == 32){
			$list_id = K::get_var(
				'campaignmonitor_list_id'
				, $fca_eoi
				, K::get_var( 'list_id' , $fca_eoi )
			);
			$provider = 'campaignmonitor';
		}
		// End of Hack
		if( ! $list_id ) {
			return;
		}

		// Subscribe user
		$status = call_user_func( $provider . '_add_user' , $this->settings , $_POST , $list_id );

		// Go to thank you page if any
		$thank_you_page = K::get_var( 'thank_you_page', $fca_eoi );
		if( $thank_you_page ) {
			wp_redirect( get_permalink( $thank_you_page ) );
			exit;
		}
	}

	public function admin_notices() {

		$current_screen = get_current_screen();

		// Exit function if we are not on the opt-in editing page
		if ( ! (
				'easy-opt-ins' === $current_screen->id 
				&& 'post' === $current_screen->base
				&& 'edit' === $current_screen->parent_base
				&& '' === $current_screen->action
			) ) {
			return;
		}

		global $post;
		$fca_eoi = get_post_meta( $post->ID, 'fca_eoi', true );
		$provider = K::get_var( 'provider', $fca_eoi);
		$errors = array();

		// Add error for missing thank you page
		$confirmation_page_set = ( bool ) K::get_var( 'thank_you_page', $fca_eoi);
		if( ! $confirmation_page_set ) {
			$errors[] = __( 'No "Thank you" page selected. You will not be able to use this form.' );
		}

		// Add error for missing list setting for the current provider
		$list_set = ( bool ) K::get_var( $provider . '_list_id', $fca_eoi);

		// @todo: remove
		// Hack for mailchimp upgrade
		if( empty( $fca_eoi[ 'provider' ] ) ) {
			$fca_eoi[ 'mailchimp_list_id' ] = K::get_var(
				'mailchimp_list_id'
				, $fca_eoi
				, K::get_var( 'list_id' , $fca_eoi )
			);
			$list_set = ( bool ) K::get_var( 'mailchimp_list_id', $fca_eoi);
		}
		// End of Hack


		if( ! $list_set ) {
			$errors[] = __( 'No List selected. You will not be able to use this form.' );
		}

		$errors = apply_filters( 'fca_eoi_alter_admin_notices', $errors );

		foreach ( $errors as $error ) {
			echo '<div class="error"><p>' . $error . '</p></div>';
		}
	}

	public function bind_content_filter() {

		// Do nothing in backend
		if ( is_admin() ) {
			return;
		}

		$url = 'http'
			. ( is_ssl() ? 's' : '' )
			. '://'
			. $_SERVER[ 'HTTP_HOST' ]
			. $_SERVER[ 'REQUEST_URI' ]
		;
		$post_ID = url_to_postid( $url );
		
		// Do nothing if not viewing a post
		if( ! $post_ID ) {
			return;
		}

		// Do nothing if viewing an opt-in
		if( 'easy-opt-ins' === get_post_type( get_post( $post_ID ) ) ) {
			return;
		}

		// Great, we attach the filter now
		add_filter( 'the_content', array( $this, 'content' ), 10, 1 );
	}

	public function content( $content ) {

		global $post;

		// Post details
		$post_ID = $post->ID;
		$post_type = get_post_type( $post_ID );

		// Build the array for testing
		$post_cond = array(
			'*',
			$post_type,
			'#' . $post_ID,
		);
		$taxonomies = get_taxonomies('','names');
		$post_taxonomies = wp_get_object_terms( $post->ID,$taxonomies);
		foreach ( $post_taxonomies as $t ) {
			$post_cond[] = $post_type . ':' . $t->term_id;
		}

		$fca_eoi_last_99_forms = array();
		foreach (query_posts( 'posts_per_page=99&post_type=easy-opt-ins' ) as $i => $f ) {
			$fca_eoi_last_99_forms[ $i ][ 'post' ] = $f;
			$fca_eoi_last_99_forms[ $i ][ 'fca_eoi' ] = get_post_meta( $f->ID, 'fca_eoi', true );
		}
		wp_reset_query();

		// Append postcode shortcode when the conditions match
		foreach( $fca_eoi_last_99_forms as $f) {
		
			// Exclude other layout types
			if ( empty ( $f[ 'fca_eoi' ][ 'layout' ] ) ) {
				continue;
			}
			if ( strpos( $f[ 'fca_eoi' ][ 'layout' ], 'postbox_' ) !== 0 ) {
				continue;
			}
		
			// Get conditions
			$eoi_form_cond = K::get_var( 'publish_postbox', $f[ 'fca_eoi' ], array() );
		
			// Append
			if ( array_intersect( $eoi_form_cond, $post_cond ) ) {
				$shortcode = sprintf( '[%s id=%d]', $this->settings[ 'shortcode' ], $f[ 'post' ]->ID );
				$content .= $shortcode;
			}
		}

		return $content;
	}
}

function k_selector( $name, $selected_options = array() ) {

	global $post;
	// Dirty fix to restore the global $post
	$post_bak = $post;

	// Get all post types except media
	$post_types = get_post_types( array( 'public' => true ) );
	unset( $post_types[ 'attachment' ] );

	// Start ouput
	echo '<select 
		data-placeholder="' . __( 'Type to search for posts, categories or pages.' ) . '"
		name = "' . $name . '[]"
		class="select2"
		multiple="multiple"
		style="width: 27em;"
	>';

	// All posts
	// K::wrap( __( 'All' )
	// 	, array(
	// 		'value' => '*',
	// 		'selected' => in_array( '*', $selected_options ),
	// 	)
	// 	, array( 'in' => 'option' )
	// );

	foreach ($post_types as $post_type => $post_type_args ) {

		$post_type_obj = get_post_type_object( $post_type );
		$post_type_name = $post_type_obj->labels->singular_name;

		$options = array();

		// Add taxonomy/terms options
		$taxonomies = get_object_taxonomies( $post_type );
		foreach ( $taxonomies as $taxonomy ) {
			$taxonomy_obj = get_taxonomy( $taxonomy );
			$taxonomy_name = $taxonomy_obj->labels->singular_name;
			$terms = get_categories("taxonomy=$taxonomy&type=$post_type"); 
			foreach ($terms as $term) {
				$options[ 'taxonomies' ][ "$post_type:$term->term_id" ] =
					$post_type_name
					. " › $taxonomy_name"
					. " › $term->name"
				;
			}
		}

		// Add posts options
		$the_query = new WP_Query( "post_type=$post_type&posts_per_page=-1" );
		if ( $the_query->have_posts() ) {

			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$options[ 'posts' ][ '#' . get_the_ID() ] = $post_type_name
					. ' ' . __( '›' ) . ' '
					. '#' . get_the_ID() . ' &ndash; '
					. ( get_the_title() ? get_the_title() : __('[Untitled]') )
				;
			}
		}

		// Dirty fix to restore the global $post
		$post = $post_bak;

		// Posts > All
		echo '<optgroup label="' . $post_type_name . '">';
		printf(
			'<option value="%s" %s >%s</option>'
			, $post_type
			, ( in_array( $post_type, $selected_options ) ? 'selected' : '' )
			, $post_type_name . ' ' . ( '›' ) . ' ' . __( 'All' )
		);
		echo '</optgroup>';

		// Posts > Taoxonomies
		if ( ! empty( $options[ 'taxonomies' ] ) ) {
			printf(
				'<optgroup label="%s">'
				, $post_type_name . ' ' . __( '›' ) . ' ' . __( 'Taxonomies' )
			);
			foreach ( $options[ 'taxonomies' ] as $k => $v ) {
				$selected = ( in_array( $k, $selected_options ) ) ? 'selected="selected"' : '';
				printf( '<option value="%s" %s >%s</option>', $k, $selected, $v );
			}
			echo '</optgroup>';
		}

		// Posts > content
		if ( ! empty( $options[ 'posts' ] ) ) {
			printf( '<optgroup label="%s">'
				, $post_type_name . ' ' . __( '›' ) . ' ' . __( 'Content' )
			);
			foreach ( $options[ 'posts' ] as $k => $v ) {
				$selected = ( in_array( $k, $selected_options ) ) ? 'selected="selected"' : '';
				printf( '<option value="%s" %s >%s</option>'
					, $k
					, $selected
					, $v
				);
			}
			echo '</optgroup>';
		}
	}
	echo '</select>';
}
