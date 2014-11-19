<?php

class EoiCustomCssBox {

	var $settings;

	public function __construct( $settings ) {

		global $pagenow;
		$this->settings = $settings;

		if ( is_admin() ) {
			if ( 'easy-opt-ins' !== $settings[ 'post_type' ] || ( $pagenow == 'edit.php' && 'easy-opt-ins' == $settings[ 'post_type' ] ) ) { 
				return;
			}			
			add_action(
				'fca_eoi_powerups'
				, array( $this, 'add_fieldset_custom_css_box' )
			);  
			add_action( 'admin_enqueue_scripts', array( $this, 'eoi_admin_js_enqueue' ) );
		} else {			
			add_filter( 'fca_eoi_alter_form'
				, array($this, 'append_custom_css_text')
				, 10
				, 2
			);
		}
	}

	public function append_custom_css_text( $content , $fca_eoi_meta ) {
		
		if( $css = K::get_var( 'custom_css', $fca_eoi_meta ) ) {
			$content .= "<style>$css</style>";
		}

		return $content;
	}

	/*
	 * Add fieldset custom css box
	 */
	public function add_fieldset_custom_css_box( $fca_eoi_meta ) {

		echo '<div style="width:40.5em;">';
		K::textarea( 'fca_eoi[custom_css]'
			, array(
				'class' => 'fca_eoi_custom_css_textbox',
				'placeholder' => __( "/* Enter your custom CSS here. */" ),
			)
			, array(
				'value' => K::get_var( 'custom_css', $fca_eoi_meta, '' ),
				'format' => 'Custom CSS<br />:textarea',
			)
		);
		echo '</div>';
	}

	public function eoi_admin_js_enqueue() {

		$protocol = is_ssl() ? 'https' : 'http';

		// Enqueue CodeMirror JS and CSS
		$codemirror_prefix = "$protocol://cdnjs.cloudflare.com/ajax/libs/codemirror/4.7.0/";
		$codemirror_js = array(
			'codemirror' => 'codemirror.min.js',
			'codemirror-css' => 'mode/css/css.min.js',
			'codemirror-placeholder' => 'addon/display/placeholder.min.js',
		);
		foreach ( $codemirror_js as $handle => $path) {
			wp_enqueue_script( $handle, $codemirror_prefix . $path );
		}
		wp_enqueue_style('codemirror', $codemirror_prefix . 'codemirror.min.css' );

		// Bind to footer
		add_action( 'admin_print_footer_scripts', array( $this, 'print_js' ) );
	}

	public function print_js() {
		?><script type="text/javascript">

			var cusid_ele = document.getElementsByClassName('fca_eoi_custom_css_textbox');
			for (var i = 0; i < cusid_ele.length; ++i) {
				var item = cusid_ele[i];  

				var editor = CodeMirror.fromTextArea( item, {
					mode: "text/css",  
					matchBrackets: true,
					lineNumbers: true,
					lineWrapping: true,
					foldGutter: true,
					gutters: [ "CodeMirror-linenumbers", "CodeMirror-foldgutter" ]
				} );
			}

			jQuery( document ).ready( function( $ ) {
				$('#fca_eoi_fieldset_custom_css_box:not(.expanded) legend').click(function(){
					// Refresh CodeMirror
					$( '.CodeMirror' ).each( function( i, el ){
						el.CodeMirror.refresh();
					} );
				} )
			} );
		</script><?php
	}
}
