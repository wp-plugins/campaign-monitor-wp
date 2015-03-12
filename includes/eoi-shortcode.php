<?php

class EasyOptInsShortcodes {

	var $settings;

	public function __construct( $settings = array() ) {

		$this->settings = $settings;

		// Add shortcode
		add_shortcode( $this->settings[ 'shortcode' ], array( $this, 'shortcode_content' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Add shortcode aliases
		foreach ( $settings[ 'shortcode_aliases' ] as $shortcode) {
			add_shortcode( $shortcode, array( $this, 'shortcode_content' ) );
		}
	}

	public function enqueue_assets() {

		$protocol = is_ssl() ? 'https' : 'http';

		// Get lightboxes
		$lightboxes = get_posts( array(
			'post_type' => 'easy-opt-ins',
			'posts_per_page' => -1,
			'orderby' => 'ID',
			'meta_key' => 'fca_eoi_layout',
			'meta_value' => 'lightbox_',
			'meta_compare' => 'like',
		) );

		// Get postboxes
		$postboxes = get_posts( array(
			'post_type' => 'easy-opt-ins',
			'posts_per_page' => -1,
			'orderby' => 'ID',
			'meta_key' => 'fca_eoi_layout',
			'meta_value' => 'postbox_',
			'meta_compare' => 'like',
		) );

		// Get postboxes
		$widgets = get_posts( array(
			'post_type' => 'easy-opt-ins',
			'posts_per_page' => -1,
			'orderby' => 'ID',
			'meta_key' => 'fca_eoi_layout',
			'meta_value' => 'layout_',
			'meta_compare' => 'like',
		) );

		// Exit function if not optin form exist
		if ( ! $lightboxes && ! $postboxes && ! $widgets ) {
			return;
		}

		wp_enqueue_script( 'jquery' );

		wp_enqueue_style( 'fca_eoi', $this->settings[ 'plugin_url' ].'/assets/style.css' );
		wp_enqueue_script( 'fca_eoi', $this->settings[ 'plugin_url' ].'/assets/script.js' );

		wp_enqueue_style( 'fontawesome', $protocol . '://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.1.0/css/font-awesome.min.css' );

		// For lightboxes only
		if ( $lightboxes ) {
			wp_enqueue_script( 'featherlight', $this->settings['plugin_url'] . '/assets/vendor/featherlight/release/featherlight.min.js' );
			wp_enqueue_style( 'featherlight', $this->settings['plugin_url'] . '/assets/vendor/featherlight/release/featherlight.min.css' );
		}

		wp_enqueue_script( 'tooltipster', $this->settings[ 'plugin_url' ] . '/assets/vendor/tooltipster/jquery.tooltipster.min.js' );
		wp_enqueue_style( 'tooltipster', $this->settings[ 'plugin_url' ] . '/assets/vendor/tooltipster/tooltipster.css' );
		wp_localize_script(
			'fca_eoi'
			, 'fca_eoi'
			, array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'field_required' => 'Error: This field is required.',
				'invalid_email' => "Error: Please enter a valid email address. For example \"max@domain.com\".",
			)
		);
	}

	public function shortcode_content( $atts ) {

		/**
		 * Check that we have a valid post ID
		 */
		if( empty ( $atts[ 'id' ] ) ) {
			return 'Missing form ID';
		}
		if( ! $post = get_post( $atts[ 'id' ] ) ) {
			return 'Wrong form ID';
		}

		$fca_eoi_meta = get_post_meta( $atts[ 'id' ], 'fca_eoi', true );
		if( ! $fca_eoi_meta ) {
			return 'Form doesn\'t exist';
		}

		// Get template
		$layout_id = $fca_eoi_meta[ 'layout' ];
		$layout_type = preg_replace( '/_\d+$/', '', $layout_id);
		$layout_path_arr = glob( $this->settings[ 'plugin_dir' ] . "layouts/*/$layout_id", GLOB_ONLYDIR );
		$layout_path = array_pop( $layout_path_arr );
		if( ! file_exists( $layout_path . '/layout.html' )) return;
		$template = file_get_contents( $layout_path . '/layout.html' );
		if( file_exists( $layout_path . '/layout.css' ) ) {
			$scss = new scssc();
			$scss->setFormatter("scss_formatter_compressed");
			$template = '<style>'
				. '.fca_eoi_form p { width: auto; }'
				. $scss->compile(
					sprintf( '$ltr: %s;', is_rtl() ? 'false' : 'true' )
					. '#fca_eoi_form_' . $atts[ 'id' ] . '{'
					. 'input{ max-width: 9999px; }'
					. file_get_contents( $layout_path . '/layout.css' )
					. '}'
				)
				. '</style>'
				. $template
			;
		}

		// Fill template with our formatting stuff
		$template = str_replace(
			array(
				'<form>',
				'{{{description_copy}}}',
				'{{{headline_copy}}}',
				'{{{name_field}}}',
				'{{{email_field}}}',
				'{{{submit_button}}}',
				'{{{privacy_copy}}}',
				'{{{fatcatapps_link}}}',
				'</form>',
			),
			array(
				sprintf( '<div id="fca_eoi_form_%s" style="margin:0 !important; padding: 0 !important;"><form method="post" action="" class="fca_eoi_form fca_eoi_%s fca_eoi_%s" data-fca_eoi_list_id="%s" data-fca_eoi_thank_you_page="%s" novalidate><input type="hidden" name="fca_eoi_form_id" value="%s" />'
					, $atts[ 'id' ]
					, $layout_type
					, $layout_id
					, K::get_var( 'list_id', $fca_eoi_meta )
					, get_permalink( K::get_var( 'thank_you_page', $fca_eoi_meta ) ) 
						? get_permalink( K::get_var( 'thank_you_page', $fca_eoi_meta ) ) 
						: ''
					, $post->ID
				),
				'<div>{{{description_copy}}}</div>',
				'<span>{{{headline_copy}}}</span>',
				'<input type="text" name="name" placeholder="{{{name_placeholder}}}" />',
				'<input type="email" name="email" placeholder="{{{email_placeholder}}}" 	/>',
				'<input type="submit" value="{{{button_copy}}}" />',
				'<span >{{{privacy_copy}}}</span>',
				'{{#show_fatcatapps_link}}<p class="fca_eoi_' . $layout_id . '_fatcatapps_link_wrapper"><a href="http://fatcatapps.com/eoi" target="_blank">Powered by Optin Cat</a></p>{{/show_fatcatapps_link}}',
				'<input type="hidden" name="id" value="' . $atts[ 'id' ] . '"><input type="hidden" name="fca_eoi" value="1"></form></div>',
			),
			$template
		);

		// Add per form CSS
		$css = '<style>.fca_eoi_form{ margin: auto; }</style>';
		$css_for_scss ='';
		if( ! empty( $fca_eoi_meta[ $layout_id ] ) ) {
			$css .= '<style>';
			$css_for_scss .= "#fca_eoi_form_${atts[ 'id' ]} {";
			foreach ($fca_eoi_meta[ $layout_id ] as $selector => $declarations) {
				$css_for_scss .= "$selector{";
				foreach ($declarations as $property => $value) {
					if( strlen( $value ) ) {
						$css_for_scss .= "$property:$value !important;";
					}
				}
				$css_for_scss .= '}';
			}                       
			$css_for_scss .= '}';
			$css .= $scss->compile( $css_for_scss ) . '</style>';
		}               

		$mustache = new Mustache_Engine;
		$output = $css . $mustache->render(
			$template,
			array(
				'headline_copy' => $fca_eoi_meta[ 'headline_copy' ],
				'description_copy' => $fca_eoi_meta[ 'description_copy' ],
				'privacy_copy' => $fca_eoi_meta[ 'privacy_copy' ],
				'name_placeholder' => $fca_eoi_meta[ 'name_placeholder' ],
				'email_placeholder' => $fca_eoi_meta[ 'email_placeholder' ],
				'button_copy' => $fca_eoi_meta[ 'button_copy' ],
				'show_name_field' => K::get_var( 'show_name_field', $fca_eoi_meta ),
				'show_fatcatapps_link' => K::get_var( 'show_fatcatapps_link', $fca_eoi_meta ),
			)
		);

		// add the fca_eoi_alter_form             
		$output = apply_filters( 'fca_eoi_alter_form'
			, $output
			, $fca_eoi_meta
		);

		// Return form with debugging information if applicable
		return $output . ( FCA_EOI_DEBUG ? @d( $fca_eoi_meta, $template ) : '' );
	}
}
