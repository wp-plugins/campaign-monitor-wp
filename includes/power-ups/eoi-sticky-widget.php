<?php

class EoiStickyWidget {

	static $fixed_widgets;
	static $fca_eoi_meta_post;

	var $settings;

	public function __construct( $settings ) {

		$this->settings = $settings;

		if ( is_admin() ) {
			add_action( 'fca_eoi_powerups', array( $this, 'add_fieldset_sticky_widget' ) );
		} else {
			add_filter( 'widget_display_callback', array( $this, 'is_widget_fixed' ), 30, 3 );
		}
	}

	/*
	 * add fieldset sticky widget
	 */
	public function add_fieldset_sticky_widget( $fca_eoi_meta_post ) {

		K::input( 'fca_eoi[sticky_widget_margin_top]'
			, array(
				'value' => 10,
				'onkeypress' => "return isNumberKey(this)",
			)
			, array( 'format' => '<p style="display: none;"><label>Margin Top: :input px</label></p>' )
		);
		K::input( 'fca_eoi[sticky_widget_margin_bottom]'
			, array(
				'value' => K::get_var( 'sticky_widget_margin_bottom', $fca_eoi_meta_post, '0' ),
				'onkeypress' => "return isNumberKey(this)",
			)
			, array( 'format' => '<p style="display:none"><label>Margin Bottom: :input px</label></p>' )
		);
		K::input( 'fca_eoi[is_sticky_widget]'
			, array(
				'type' => 'checkbox',
				'checked' => ( ! K::get_var( 'sticky_widget_margin_top', $fca_eoi_meta_post) )
					? true
					: K::get_var( 'is_sticky_widget', $fca_eoi_meta_post )
				,
			)
			, array( 'format' => '<p><label>:input Make this Opt-in Form Sticky</label></p>' )
		);
	}

	/*
	 * get all easy-opt-in-widget
	 */
	public function is_widget_fixed( $instance, $widget, $args ) {

		if ( K::get_var( 'eoi_form_id', $instance ) ) {
			self::$fca_eoi_meta_post = get_post_meta(
				$instance['eoi_form_id']
				, 'fca_eoi'
				, true )
			;
			if ( K::get_var('is_sticky_widget', self::$fca_eoi_meta_post) ) {
				self::$fixed_widgets[ $args[ 'id' ] ][ $widget->id ] = "'"
					. $widget->id
					. "'"
				;
				add_action( 'wp_footer', array( $this, 'action_script' ) );
			}
		}
		return $instance;
	}

	/*
	 * excute the sticky js
	 */
	public function action_script() {

		wp_enqueue_script(
			'fca-eoi-sticky-sidebar'
			, $this->settings[ 'plugin_url' ] . '/assets/power-ups/js/eoi-sticky-widget.js'
			, array( 'jquery' )
		);

		if ( is_array( self::$fixed_widgets ) && ! empty( self::$fixed_widgets ) ) {

			$i = 0;

			echo '<script>jQuery( document ).ready( function() {';

			foreach ( self::$fixed_widgets as $sidebar => $widgets ) {

				$i++;

				$width_inherit = K::get_var( 'width-inherit', $fca_eoi_meta_post )
					? 'true'
					: 'false'
				;

				$widgets_array = implode( ',', $widgets );

				echo '
					var fca_eoi_sticky_sidebar_' . $i . '_options = {
						"sidebar" : "' . $sidebar . '",
						"margin_top" : ' . self::$fca_eoi_meta_post[ 'sticky_widget_margin_top' ] . ',
						"margin_bottom" : ' . self::$fca_eoi_meta_post[ 'sticky_widget_margin_bottom' ] . ',
						"width_inherit" : ' . $width_inherit . ',
						"widgets" : [' . $widgets_array . ']
					};
					fca_eoi_sticky_sidebar( fca_eoi_sticky_sidebar_' . $i . '_options );
				';
			}
			echo '} );</script>';
		}
	}
}
