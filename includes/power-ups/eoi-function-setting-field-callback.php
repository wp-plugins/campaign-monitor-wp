<?php


/**
 * Textarea Callback
 *
 * Renders textarea fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $eoi_power_ups_settings Array of all the eoi Options
 * @return void
 */
function eoi_setting_field_textarea_callback( $args ) {
	global $eoi_power_ups_settings;

	if ( isset( $eoi_power_ups_settings[ $args['id'] ] ) )
		$value = $eoi_power_ups_settings[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<textarea class="large-text" cols="50" rows="5" id="eoi_power_ups_settings[' . $args['id'] . ']" name="eoi_power_ups_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	$html .= '<label for="eoi_power_ups_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Checkbox Callback
 *
 * Renders checkboxes.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $eoi_power_ups_settings Array of all the eoi Options
 * @return void
 */
function eoi_setting_field_checkbox_callback( $args ) {
	global $eoi_power_ups_settings;
        
        if ( isset( $eoi_power_ups_settings[ $args['id'] ] ) )
		$value = $eoi_power_ups_settings[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';
        
	$checked = isset( $value ) ? checked( 1, $value, false ) : '';
        K::input( 'eoi_power_ups_settings['.$args["id"] .']'
            , array(
              'type' => 'checkbox',
              'value' => 1,
              'checked' => $value == 1 ? 'checked' : null,
            )
            , array( 'format' => '<div>:input <label>'.$args['desc'].'</label></div>' )
          );

}



/**
 * Missing Callback
 *
 * If a function is missing for settings callbacks alert the user.
 *
 * @since 1.3.1
 * @param array $args Arguments passed by the setting
 * @return void
 */
function eoi_setting_field_missing_callback($args) {
	printf( __( 'The callback function used for the <strong>%s</strong> setting is missing.', 'eoi' ), $args['id'] );
}


/**
 * Select Callback
 *
 * Renders select fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $eoi_power_ups_settings Array of all the eoi Options
 * @return void
 */
function eoi_setting_field_select_callback($args) {
	global $eoi_power_ups_settings;

	if ( isset( $eoi_power_ups_settings[ $args['id'] ] ) )
		$value = $eoi_power_ups_settings[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';
       /*
	$html = '<select id="eoi_power_ups_settings[' . $args['id'] . ']" name="eoi_power_ups_settings[' . $args['id'] . ']"/>';

	foreach ( $args['options'] as $option => $name ) :
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
	endforeach;

	$html .= '</select>';
	$html .= '<label for="eoi_power_ups_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
        */
        
        K::select( 'eoi_power_ups_settings['.$args["id"] .']'
			, array(
				'id' => 'eoi_power_ups_settings['.$args["id"] .']',
			)
			, array(
				'options' => $args['options'],
				'default' => '',
				'selected' => $value,
				'format' => '<p><label for="' . 'eoi_power_ups_settings['.$args["id"] .']' . '">' . $args['desc'] . '</label><p>:select</p>',
			)
		);
}

?>
