<?php

class EasyOptInsShortcodes {

	var $settings;

	public function __construct( $settings = array() ) {

		$this->settings = $settings;

		// Add shortcode
		add_shortcode( $this->settings[ 'shortcode' ], array( $this, 'shortcode_content' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets() {

		$protocol = is_ssl() ? 'https' : 'http';

		wp_enqueue_style( 'fca_eoi', $this->settings[ 'plugin_url' ].'/assets/style.css' );
		wp_enqueue_script( 'fca_eoi', $this->settings[ 'plugin_url' ].'/assets/script.js', array( 'jquery' ) );
		wp_localize_script(
			'fca_eoi'
			, 'fca_eoi'
			, array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'field_required' => 'Error: This field is required.',
				'invalid_email' => "Error: Please enter a valid email address. For example \"max@domain.com\".",
			)
		);
		wp_enqueue_style( 'fontawesome', $protocol . '://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.1.0/css/font-awesome.min.css' );
		wp_enqueue_script( 'tooltipster', $protocol . '://cdnjs.cloudflare.com/ajax/libs/tooltipster/3.0.5/js/jquery.tooltipster.min.js' );
		wp_enqueue_style( 'tooltipster', $protocol . '://cdnjs.cloudflare.com/ajax/libs/tooltipster/3.0.5/css/tooltipster.min.css' );
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
		if( !$fca_eoi_meta ) {
			return 'Form doesn\'t exist';
		}

		// Get template
		$layout_id = $fca_eoi_meta[ 'layout' ];
		$layout_path = $this->settings[ 'plugin_dir' ] . "layouts/$layout_id";
		$template = file_get_contents( $layout_path . '/layout.html' );
		if( file_exists( $layout_path . '/layout.css' ) ) {
			$template .= '<style>' . file_get_contents( $layout_path . '/layout.css' ) . '</style>';
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
				sprintf( '<form method="post" action="" class="fca_eoi_form fca_eoi_%s" data-fca_eoi_list_id="%s" data-fca_eoi_thank_you_page="%s" novalidate><input type="hidden" name="fca_eoi_form_id" value="%s" />'
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
				'{{#show_fatcatapps_link}}<p class="fca_eoi_' . $layout_id . '_fatcatapps_link_wrapper"><a href="http://fatcatapps.com/eoi" target="_blank">Powered by Easy Opt-ins</a></p>{{/show_fatcatapps_link}}',
				'<input type="hidden" name="id" value="' . $atts[ 'id' ] . '"><input type="hidden" name="fca_eoi" value="1"></form>',
			),
			$template
		);

		// Add per form CSS
		$css = '';
		if( ! empty( $fca_eoi_meta[ $layout_id ] ) ) {
			$css .= '<style>';
			foreach ($fca_eoi_meta[ $layout_id ] as $selector => $declarations) {
				$css .= "$selector{";
				foreach ($declarations as $property => $value) {
					if( strlen( $value ) ) {
						$css .= "$property:$value !important;";
					}
				}
				$css .= '}';
			}
			$css .= '</style>';
		}

		// Add styles global to layouts
		printf( "<style>%s { %s: %s !important; } </style>", ".fca_eoi_$layout_id", 'width', $fca_eoi_meta[ 'form_width' ] );
		printf( "<style>%s { %s: %s !important; } </style>", ".fca_eoi_$layout_id", 'max-width', $fca_eoi_meta[ 'form_max_width' ] );

		
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

		// Return form with debugging information if applicable
		return $output . ( FCA_EOI_DEBUG ? @d( $fca_eoi_meta, $template ) : '' );
	}
}
