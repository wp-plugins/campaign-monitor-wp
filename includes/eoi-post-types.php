<?php

class EasyOptInsPostTypes {

	public $settings;

	public function __construct( $settings ) {

		$this->settings = $settings;

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

		add_action( 'admin_footer', array( $this, 'disable_metabox_toggle' ) );

		add_action( 'wp_ajax_fca_eoi_subscribe', array( $this, 'ajax_subscribe' ) );

		add_filter( 'get_user_option_screen_layout_easy-opt-ins', array( $this, 'force_one_column' ) );

		add_filter( 'get_user_option_meta-box-order_easy-opt-ins', array( $this, 'order_columns' ) );

		add_filter( 'gettext', array( $this, 'override_text' ) );

		add_filter( 'bulk_actions-edit-easy-opt-ins', array( $this, 'disable_bulk_edit' ) );

		add_filter( 'post_row_actions', array( $this, 'remove_quick_edit' ) );

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		add_filter('enter_title_here', array($this, 'change_default_title'));

		add_action( 'wp_ajax_fca_eoi_get_lists', $settings['provider'] . '_ajax_get_lists' );

                add_action('post_submitbox_start', array($this, 'trigger_post_submitbox_start'),50);
                
		// Hook provder callback functions
		add_filter( 'fca_eoi_alter_admin_notices', $settings['provider'] . '_admin_notices', 10, 1 );
	}

	public function more_settings() {

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

		// Initialize a provider instance
		$this->settings[ 'helper' ] = empty ( $form_id )
			? false
			: call_user_func( $this->settings[ 'provider' ] . '_object', $this->settings );
		;
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
			'menu_icon' =>
				$this->settings[ 'plugin_url' ]
				. '/providers/'
				. $this->settings[ 'provider' ] 
				. '/icon.png'
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
			'Navigation', 
			array( &$this, 'meta_box_content_nav' ),
			'easy-opt-ins',
			'side',
			'high'
		);
		add_meta_box(
			'fca_eoi_meta_box_setup',
			'Setup', 
			array( &$this, 'meta_box_content_setup' ),
			'easy-opt-ins',
			'side',
			'high'
		);
		add_meta_box(
			'fca_eoi_meta_box_build',
			'Build', 
			array( &$this, 'meta_box_content_build' ),
			'easy-opt-ins',
			'side',
			'high'
		);
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
			<?php if( FCA_EOI_DEBUG) : ?>
				<a href="#fca_eoi_meta_box_debug" class="nav-tab ">Debug</a>
			<?php endif; ?>
		</h2>
		<?php
	}

	public function meta_box_content_setup() {

		global $post;
		$fca_eoi = get_post_meta( $post->ID, 'fca_eoi', true );

		echo '<h2>' . __( 'Layouts' ) . '</h2>';
		echo '<script id="fca_eoi_texts" type="application/json">{ "headline_copy": "Headline Copy", "description_copy": "Description Copy", "name_placeholder": "Name Placeholder", "email_placeholder": "Email Placeholder", "button_copy": "Button Copy", "privacy_copy": "Privacy Copy" }</script>';
			
		// Build the layouts array
		$layouts = array();
		foreach ( glob( $this->settings[ 'plugin_dir' ] . 'layouts/*', GLOB_ONLYDIR ) as $layout_path ) {
			// Grab layout details
			$layout_id = basename( $layout_path );
			include $layout_path . '/layout.php';
			if( file_exists( $layout_path . '/screenshot.jpg' ) ) {
				$layout[ 'has_screenshot' ] = true;
			} else {
				$layout[ 'has_screenshot' ] = false;
			}
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
					'{{#show_fatcatapps_link}}<p class="fca_eoi_' . $layout_id . '_fatcatapps_link_wrapper"><span data-fca-eoi-fieldset-id ="fatcatapps"><a href="#" onclick="javascript:return false">Powered by Easy Opt-ins</a></span></p>{{/show_fatcatapps_link}}',
				)
				, $layout[ 'template' ]
			);
			$layout[ 'template' ] .= sprintf( '<style>%s</style>', $layout[ 'css' ] );
			$layouts[ $layout_id ] = $layout;
			// Output the layout image and hidden template
			$layout_output_tpl = '
				<div
					class="fca_eoi_layout has-tip"
					data-layout-id=":id" title="Your theme’s built-in form styling will be used." data-tooltip				
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
					':name',
					':src',
					':template',
					':editables',
					':texts',
				),
				array(
					$layout_id,
					$layout[ 'name' ],
					$layout[ 'has_screenshot' ] 
						? $this->settings[ 'plugin_url' ] . '/layouts/' . $layout_id . '/screenshot.jpg'
						: $this->settings[ 'plugin_url' ] . '/layouts/no-image.jpg'
					,
					$layout[ 'template' ],
					( ! empty( $layout[ 'editables' ] ) )
						? json_encode( $layout[ 'editables' ] )
						: 'null'
					,
					( ! empty( $layout[ 'texts' ] ) )
						? json_encode( $layout[ 'texts' ] )
						: 'null'
					,
				),
				$layout_output_tpl
			);
			echo $layout_output;
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
				'class' => 'color { hash: true, caps: false, required: false }',
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

	public function meta_box_content_build() {

		global $post;
		$fca_eoi = get_post_meta( $post->ID, 'fca_eoi', true );

		K::wrap( '', array( 'id' => 'fca_eoi_preview' ) );

		echo '<div id="fca_eoi_settings">';

		// General Settings
		do_action( 'fca_eoi_before_fieldset_group_general_settings', $fca_eoi );
		K::wrap( 'Setup',
			array( 'style' => 'color: white; background: gray; padding: .5em; border-radius: 2px' ),
			array( 'in' => 'h4' )
		);
		do_action( 'fca_eoi_before_fieldset_form_inegration', $fca_eoi );
		call_user_func( $this->settings[ 'provider' ] . '_integration', $this->settings );

		$pages_objects = get_pages();
		$pages = array();
		foreach ( $pages_objects as $page_obj ) {
			// !d( $page_obj->ID );
			$pages[ $page_obj->ID ] = $page_obj->post_title;
		}

		do_action( 'fca_eoi_before_fieldset_thank_you_page', $fca_eoi );
		K::fieldset( 'Thank you page',
			array(
				array(
					'wrap',
					'Redirect user to the following page after submitting the form:',
					null,
					array( 'in' => 'p' ),
				),
				array(
					'select',
					'fca_eoi[thank_you_page]',
					array( 
						'class' => 'select2',
						'style' => 'width: 100%',
					),
					array( 
						'format' => '<p><label>:select</label></p>',
						'options' => array( '' => 'Not set' ) + $pages,
						'selected' => K::get_var( 'thank_you_page', $fca_eoi ),
					),
				),
			),
			array(
				'class' => 'k collapsible collapsed',
				'id' => 'fca_eoi_fieldset_form_integration',
			)
		);

		// Design Settings
		do_action( 'fca_eoi_before_fieldset_group_design_settings', $fca_eoi );
		K::wrap( 'Design & Content',
			array( 'style' => 'color: white; background: gray; padding: .5em; border-radius: 2px' ),
			array( 'in' => 'h4' ) 
		);
		K::fieldset( 'Form',
			array(
				array(
					'input',
					'fca_eoi[form_width]',
					array(
						'value' => ( K::get_var( 'form_width', $fca_eoi) )
							? K::get_var( 'form_width', $fca_eoi )
							: '100%'
						,
					),
					array( 'format' => '<p style="display:none" ><label>Width :input</label></p>' )
				),
				array(
					'input',
					'fca_eoi[form_max_width]',
					array(
						'value' => ( K::get_var( 'form_max_width', $fca_eoi) )
							? K::get_var( 'form_max_width', $fca_eoi )
							: '280px'
						,
					),
					array( 'format' => '<p style="display:none" ><label>Max Width :input</label></p>' )
				),
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
					array( 'format' => '<p><label>Headline Copy :input</label></p>' )
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
					array( 'format' => '<p><label>Placeholder Text :input</label></p>' )
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
					array( 'format' => '<p><label>Placeholder Text :input</label></p>' )
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
					array( 'format' => '<p><label>Button Copy :input</label></p>' ) 
				),
			),
			array(
				'class' => 'k collapsible collapsed',
				'id' => 'fca_eoi_fieldset_button'
			)
		);
		K::fieldset( 'Privacy Policy',
			array(
				array( 'input', 'fca_eoi[privacy_copy]',
					array( 'value' => K::get_var( 'privacy_copy', $fca_eoi ) ),
					array( 'format' => '<p><label>Privacy Policy Copy :input</label></p>' )
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
                
                $is_showing_power_up = 'none';
                if (has_action('fca_eoi_after_fieldset_group_power_ups')) {
                    $is_showing_power_up = 'block';
                }
                $power_up_style = 'color: white; background: gray; padding: .5em; border-radius: 2px;display:' . $is_showing_power_up;
                
                K::wrap('Power Ups', array('style' => $power_up_style), array('in' => 'h4')
                );
                
                do_action('fca_eoi_after_fieldset_group_power_ups', $fca_eoi);
                
		echo '</div>';
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
			add_post_meta( $post->ID, 'fca_eoi', $meta );
		}
	}

	public function custom_post_type_columns( $columns ) {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => 'Name',
			'shortcode' => 'Shortcode',
			'date' => 'Date'
		);
		return $columns;
	}

	public function custom_post_type_columns_content( $column_name, $post_id ) {
		if ($column_name === 'shortcode') {
			echo sprintf('[%s id="%d"]', $this->settings['shortcode'], $post_id);
		}
	}

	public function live_preview( $content ) {
		global $post;
		if (get_post_type() == 'easy-opt-ins' && is_main_query()) {
			$shortcode = sprintf('[%s id="%d"]', $this->settings['shortcode'], $post->ID);
			return do_shortcode($shortcode);
		} else {
			return $content;
		}
	}

	public function admin_enqueue() {

		$protocol = is_ssl() ? 'https' : 'http';
		$provider = $this->settings[ 'provider' ];

		$screen = get_current_screen();
		if( 'easy-opt-ins' === $screen->id ){
			wp_enqueue_script( 'mustache', $protocol . '://cdnjs.cloudflare.com/ajax/libs/mustache.js/0.8.1/mustache.min.js' );
			wp_enqueue_script( 'select2', $protocol . '://cdnjs.cloudflare.com/ajax/libs/select2/3.5.0/select2.js' );
			wp_enqueue_script( 'jscolor', $this->settings['plugin_url'] . '/assets/vendor/jscolor/jscolor.js' );
			wp_enqueue_script( 'admin-cpt-easy-opt-ins', $this->settings['plugin_url'] . '/assets/admin/cpt-easy-opt-ins.js' );
			wp_enqueue_script( 'admin-cpt-easy-opt-ins-' . $provider, $this->settings['plugin_url'] . '/providers/' . $provider . '/cpt-easy-opt-ins.js' );



			wp_enqueue_style( 'admin-cpt-easy-opt-ins', $this->settings['plugin_url'] . '/assets/admin/cpt-easy-opt-ins.css' );
			wp_enqueue_style( 'font-awesome', $protocol . '://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.1.0/css/font-awesome.min.css' );
			wp_enqueue_style( 'select2', $protocol . '://cdnjs.cloudflare.com/ajax/libs/select2/3.5.0/select2.min.css' );
                        wp_enqueue_script('tooltipster', $protocol . '://cdnjs.cloudflare.com/ajax/libs/tooltipster/3.0.5/js/jquery.tooltipster.min.js');
                        wp_enqueue_style('tooltipster', $protocol . '://cdnjs.cloudflare.com/ajax/libs/tooltipster/3.0.5/css/tooltipster.min.css');
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
				'fca_eoi_meta_box_nav',
				'fca_eoi_meta_box_setup',
				'fca_eoi_meta_box_build',
				'fca_eoi_meta_box_debug',
				'submitdiv',
			) ),
			'side' => '',
			'advanced' => '',
		);
	}
        
        /*
           replacing the default "Enter title here" placeholder text in the title input box
           to 
          */

        public function change_default_title($title) {

            $screen = get_current_screen();

            if ('easy-opt-ins' == $screen->post_type) {
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
					$text = __( 'Opt-In Form saved. <a href="%s" target="fca-eoi-preview">Preview</a>.' );
					break;
				case 'Publish':
					$text = __( 'Save' );
					break;
				case 'Update':
					$text = __( 'Save Form' );
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
			die();
		}

		// Subscribe user
		$status = call_user_func( $this->settings[ 'provider' ] . '_add_user' , $this->settings , $_POST , $list_id );

		// Output ✓ or ✗
		if( $status ) {
			echo '✓';
		} else {
			echo '✗';
		}
		die();
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
		$post_meta = get_post_meta( $id, 'fca_eoi', true );

		// Check a list_id is provided
		$list_id = K::get_var( 'list_id' , $post_meta );
		if( ! $list_id ) {
			return;
		}

		// Subscribe user
		$status = call_user_func( $this->settings[ 'provider' ] . '_add_user' , $this->settings , $_POST , $list_id );

		// Go to thank you page if any
		$thank_you_page = K::get_var( 'thank_you_page', $post_meta );
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
		$errors = array();

		// Add error for missing thank you page
		$confirmation_page_set = ( bool ) K::get_var( 'thank_you_page', $fca_eoi);
		if( ! $confirmation_page_set ) {
			$errors[] = __( 'No "Thank you" page selected. You will not be able to use this form.' );
		}

		// Add error for missing list setting
		$list_set = ( bool ) K::get_var( 'list_id', $fca_eoi);
		if( ! $list_set ) {
			$errors[] = __( 'No List selected. You will not be able to use this form.' );
		}

		$errors = apply_filters( 'fca_eoi_alter_admin_notices', $errors );

		foreach ( $errors as $error ) {
			echo '<div class="error"><p>' . $error . '</p></div>';
		}
	}
        
        //change text of "Save" button to 'Saving' while processing
        function trigger_post_submitbox_start(){
            
            if( $post = K::get_var( 'post', $GLOBALS ) ) {
		if ( 'easy-opt-ins' === $post->post_type ) {
                    ?>
                            <script type="text/javascript">
                                        jQuery( document ).ready( function($) {
                                            $( '#publish' ).click(function(){
                                                postL10n.publish = 'Saving';
                                                postL10n.update= 'Saving';
                                            });

                                        } );
                                </script>   
                        <?php
                        }}
        }
}
