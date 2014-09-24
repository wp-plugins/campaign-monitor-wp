( function( $ ) {

	wp.customize("easy_opt_in_wrapper_bg_color_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610").css("background", newval );
		} );
	} );

	wp.customize("easy_opt_in_h3_font_color_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 h3").css("color", newval );
		} );
	} );

	wp.customize("easy_opt_in_h3_font_size_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 h3").css("font-size", newval + "px" );
		} );
	} );

	wp.customize("easy_opt_in_copy_text_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 p.easy_opt_in_copy").html(newval);
		} );
	} );

	wp.customize("easy_opt_in_copy_font_color_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 p.easy_opt_in_copy").css("color", newval );
		} );
	} );

	wp.customize("easy_opt_in_copy_font_size_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 p.easy_opt_in_copy").css("font-size", newval + "px" );
		} );
	} );

	wp.customize("easy_opt_in_name_border_color_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 #easy_opt_in_name").css("border-color", newval );
		} );
	} );

	wp.customize("easy_opt_in_name_padding_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 #easy_opt_in_name").css("margin-top", newval + "px").css("margin-bottom", newval + "px");
		} );
	} );

	wp.customize("easy_opt_in_name_visible_610", function( value ) { 
		value.bind( function( newval ) {
			 var display_value = (newval == true)?'block':'none';
			$("#easy_opt_in_wrapper_610 #easy_opt_in_name").css("display", display_value);
		} );
	} );

	wp.customize("easy_opt_in_email_border_color_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 #easy_opt_in_email").css("border-color", newval );
		} );
	} );

	wp.customize("easy_opt_in_email_padding_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 #easy_opt_in_email").css("margin-top", newval + "px").css("margin-bottom", newval + "px");
		} );
	} );

	wp.customize("easy_opt_in_privacy_policy_text_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 p.easy_opt_in_privacy").html(newval);
		} );
	} );

	wp.customize("easy_opt_in_privacy_policy_font_color_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 p.easy_opt_in_privacy").css("color", newval );
		} );
	} );

	wp.customize("easy_opt_in_privacy_policy_font_size_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 p.easy_opt_in_privacy").css("font-size", newval + "px" );
		} );
	} );

	wp.customize("easy_opt_in_button_font_size_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 #easy_opt_in_submit").css("font-size", newval + "px" );
		} );
	} );

	wp.customize("easy_opt_in_button_font_color_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 #easy_opt_in_submit").css("color", newval );
		} );
	} );

	wp.customize("easy_opt_in_button_bg_color_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 #easy_opt_in_submit").css("background", newval );
		} );
	} );

	wp.customize("easy_opt_in_button_bg_hover_color_610", function( value ) { 
		value.bind( function( newval ) {
			var bg_color = $("#easy_opt_in_wrapper_610 #easy_opt_in_submit").css("background");
			$("#easy_opt_in_wrapper_610 #easy_opt_in_submit").mouseenter(function(){ $(this).css("background", newval ); }).mouseleave(function() {$(this).css("background", bg_color)});
		} );
	} );

	wp.customize("easy_opt_in_button_border_bottom_color_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 #easy_opt_in_submit").css("border-color", newval );
		} );
	} );

	wp.customize("easy_opt_in_button_bottom_width_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 #easy_opt_in_submit").css("border-bottom-width", newval + "px" );
		} );
	} );

	wp.customize("easy_opt_in_button_label_610", function( value ) { 
		value.bind( function( newval ) {
			$("#easy_opt_in_wrapper_610 #easy_opt_in_submit").val(newval);
		} );
	} );

} )( jQuery );