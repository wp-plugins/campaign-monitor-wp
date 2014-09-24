<?php

class EasyOptInsCustomize
{
    var $settings;

    function __construct($settings = array())
    {
        $this->settings = $settings;

        // Enqueue assets
        add_action('customize_controls_print_scripts', array($this, 'enqueue_assets'));
		add_action('customize_preview_init', array($this, 'customizer_live_preview'));
		
        // Actions
		add_action('wp_ajax_dh_easy_opt_in_customize', array($this, 'load_customize_page'));
        add_action('customize_register', array($this, 'customize_register'));
		add_action('wp_head', array($this, 'custom_css'));
	}
	
	public static function customizer_live_preview()
	{
		wp_enqueue_script( 
			'dh-easy-opt-ins-live',
			plugins_url('', dirname(__FILE__)).'/assets/ui/live.js',
			array('jquery', 'customize-preview'),
			time(),
			true
		);
	}
	
	function update_live_js()
	{
		$ids = $this->get_form_ids();
		$options = array(
			'easy_opt_in_wrapper_bg_color' => array('selector' => '#easy_opt_in_wrapper_{id}', 'property'=>'background'),
			'easy_opt_in_h3_font_color' => array('selector' => '#easy_opt_in_wrapper_{id} h3', 'property'=>'color'),
			'easy_opt_in_h3_font_size' => array('selector' => '#easy_opt_in_wrapper_{id} h3', 'property' => 'font-size'),
			'easy_opt_in_copy_text' => array('selector' => '#easy_opt_in_wrapper_{id} p.easy_opt_in_copy', 'property' => 'html'),
			'easy_opt_in_copy_font_color' => array('selector' => '#easy_opt_in_wrapper_{id} p.easy_opt_in_copy', 'property'=>'color'),
			'easy_opt_in_copy_font_size' => array('selector' =>  '#easy_opt_in_wrapper_{id} p.easy_opt_in_copy', 'property'=>'font-size'),
			'easy_opt_in_name_border_color' => array('selector' => '#easy_opt_in_wrapper_{id} #easy_opt_in_name', 'property' => 'border-color'),
			'easy_opt_in_name_padding' => array('selector' => '#easy_opt_in_wrapper_{id} #easy_opt_in_name', 'property' => 'padding'),
			'easy_opt_in_name_visible' => array('selector' => '#easy_opt_in_wrapper_{id} #easy_opt_in_name', 'property' => 'display'),
			'easy_opt_in_email_border_color' => array('selector' => '#easy_opt_in_wrapper_{id} #easy_opt_in_email', 'property' => 'border-color'),
			'easy_opt_in_email_padding' => array('selector' => '#easy_opt_in_wrapper_{id} #easy_opt_in_email', 'property' => 'padding'),
			'easy_opt_in_privacy_policy_text' => array('selector' => '#easy_opt_in_wrapper_{id} p.easy_opt_in_privacy', 'property' => 'html'),
			'easy_opt_in_privacy_policy_font_color' => array('selector' => '#easy_opt_in_wrapper_{id} p.easy_opt_in_privacy', 'property' => 'color'),
			'easy_opt_in_privacy_policy_font_size' => array('selector' => '#easy_opt_in_wrapper_{id} p.easy_opt_in_privacy', 'property' => 'font-size'),
			'easy_opt_in_button_font_size' => array('selector' => '#easy_opt_in_wrapper_{id} #easy_opt_in_submit', 'property' => 'font-size'),
			'easy_opt_in_button_font_color' => array('selector' => '#easy_opt_in_wrapper_{id} #easy_opt_in_submit', 'property' => 'color'),
			'easy_opt_in_button_bg_color' => array('selector' => '#easy_opt_in_wrapper_{id} #easy_opt_in_submit', 'property'=>'background'),
			'easy_opt_in_button_bg_hover_color' => array('selector' => '#easy_opt_in_wrapper_{id} #easy_opt_in_submit', 'property'=>'hover-background'),
			'easy_opt_in_button_border_bottom_color' => array('selector' => '#easy_opt_in_wrapper_{id} #easy_opt_in_submit', 'property' => 'border-color'),
			'easy_opt_in_button_bottom_width' => array('selector' => '#easy_opt_in_wrapper_{id} #easy_opt_in_submit', 'property' => 'border-bottom-width'),
			'easy_opt_in_button_label' => array('selector' => '#easy_opt_in_wrapper_{id} #easy_opt_in_submit', 'property' => 'value')
		);
		
		$live_js = "( function( $ ) {" . "\n\n";

		foreach ($options as $key => $value) {			
			foreach ($ids as $id) {
				
				$live_js .= "\t" . 'wp.customize("' . $key . '_' . $id . '", function( value ) { '. "\n";
				$live_js .= "\t\t" . 'value.bind( function( newval ) {' . "\n";
				
				if ($value['property'] == 'background') {
					$live_js .= "\t\t\t" . '$("'.str_replace('{id}', $id, $value['selector']).'").css("background", newval );' . "\n";
				} elseif ($value['property'] == 'hover-background') {
					$live_js .= "\t\t\t" . 'var bg_color = $("'.str_replace('{id}', $id, $value['selector']).'").css("background");' . "\n";
					$live_js .= "\t\t\t" . '$("'.str_replace('{id}', $id, $value['selector']).'").mouseenter(function(){ $(this).css("background", newval ); }).mouseleave(function() {$(this).css("background", bg_color)});' . "\n";
				} elseif ($value['property'] == 'color') {
					$live_js .= "\t\t\t" . '$("'.str_replace('{id}', $id, $value['selector']).'").css("color", newval );' . "\n";
				} elseif ($value['property'] == 'border-color') {
					$live_js .= "\t\t\t" . '$("'.str_replace('{id}', $id, $value['selector']).'").css("border-color", newval );' . "\n";
				} elseif ($value['property'] == 'font-size') {
					$live_js .= "\t\t\t" . '$("'.str_replace('{id}', $id, $value['selector']).'").css("font-size", newval + "px" );' . "\n";
				} elseif ($value['property'] == 'border-bottom-width') {
					$live_js .= "\t\t\t" . '$("'.str_replace('{id}', $id, $value['selector']).'").css("border-bottom-width", newval + "px" );' . "\n";
				} elseif ($value['property'] == 'display') {
					$live_js .= "\t\t\t var display_value = (newval == true)?'block':'none';" . "\n";
					$live_js .= "\t\t\t" . '$("'.str_replace('{id}', $id, $value['selector']).'").css("display", display_value);' . "\n";
				} elseif ($value['property'] == 'padding') {
					$live_js .= "\t\t\t" . '$("'.str_replace('{id}', $id, $value['selector']).'").css("margin-top", newval + "px").css("margin-bottom", newval + "px");' . "\n";
				} elseif ($value['property'] == 'html') {
					$live_js .= "\t\t\t" . '$("'.str_replace('{id}', $id, $value['selector']).'").html(newval);' . "\n";
				} elseif ($value['property'] == 'value') {
					$live_js .= "\t\t\t" . '$("'.str_replace('{id}', $id, $value['selector']).'").val(newval);' . "\n";
				}
				
				$live_js .= "\t\t". '} );' . "\n";
				$live_js .= "\t" . '} );' . "\n\n";
			}
		}

		$live_js .= "} )( jQuery );";

		// Write to file
		$f = fopen($this->settings['plugin_dir'].'/assets/ui/live.js', 'w+');
		fwrite($f, $live_js);
		fclose($f);
	}
	
	function load_customize_page()
	{
		// Set cookie file
		setcookie("easy_opt_in_post", $_REQUEST['post_id']);
		
		// Update live.js
		$this->update_live_js();
		
		// Redirect to customize page
		wp_redirect(admin_url('customize.php?plugin=easy-opt-in&post_id='.$_REQUEST['post_id'].'&url='.$_REQUEST['url']), 301);
	}
	
	function enqueue_assets()
	{	
        /* Hack */
        ?>
            <script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
			<script type="text/javascript">
				var back_url = '<?php echo admin_url(sprintf('post.php?post=%s&action=edit', $_REQUEST['post_id'])); ?>';
			</script>
            <script type="text/javascript" src="<?php echo $this->settings['plugin_url'];?>/assets/ui/customize.js"></script>
        <?php
	}
    
    function customize_register($wp_customize)
    {		
		$post_id = (isset($_COOKIE['easy_opt_in_post']) && $_COOKIE['easy_opt_in_post'])?$_COOKIE['easy_opt_in_post']:0;
		
		// Load custom customize control (textarea)
		if (file_exists(plugin_dir_path( __FILE__ ).'eoi-customize_textarea_control.php')) {
			include_once plugin_dir_path( __FILE__ ).'eoi-customize_textarea_control.php';
		}
	
        // Add sections
        $wp_customize->add_section('easy_opt_in_general_settings', array('title' => 'General Settings', 'priority' => 10));
		$wp_customize->add_section('easy_opt_in_headline_settings', array('title' => 'Headline', 'priority' => 20));
		$wp_customize->add_section('easy_opt_in_copy_area_settings', array('title' => 'Copy', 'priority' => 30));
		$wp_customize->add_section('easy_opt_in_name_field_settings', array('title' => 'Name Field', 'priority' => 40));
		$wp_customize->add_section('easy_opt_in_email_field_settings', array('title' => 'Email Field', 'priority' => 50));
		$wp_customize->add_section('easy_opt_in_button_settings', array('title' => 'Button', 'priority' => 60));
		$wp_customize->add_section('easy_opt_in_privacy_policy_settings', array('title' => 'Privacy Policy', 'priority' => 70));
		
        $settings = array();
		
		// Default
        $settings[] = array(
			'slug' => 'easy_opt_in_wrapper_bg_color_'.$post_id,
			'default' => '#f39c12',
			'label' => 'Wrapper Background Color',
			'section' => 'easy_opt_in_general_settings',
			'type' => 'color'
		);
		
		// Headline
		$settings[] = array(
			'slug' => 'easy_opt_in_h3_font_color_'.$post_id,
			'default' => '#000000',
			'label' => 'Font Color',
			'section' => 'easy_opt_in_headline_settings',
			'type' => 'color'
		);
		$settings[] = array(
			'slug' => 'easy_opt_in_h3_font_size_'.$post_id,
			'default' => '20',
			'label' => 'Font Size',
			'section' => 'easy_opt_in_headline_settings',
			'type' => 'text'
		);
        
		// Copy
		$settings[] = array(
			'slug' => 'easy_opt_in_copy_text_'.$post_id,
			'default' => '',
			'label' => 'Copy',
			'section' => 'easy_opt_in_copy_area_settings',
			'type' => 'textarea'
		);
		
		$settings[] = array(
			'slug' => 'easy_opt_in_copy_font_color_'.$post_id,
			'default' => '#000000',
			'label' => 'Font Color',
			'section' => 'easy_opt_in_copy_area_settings',
			'type' => 'color'
		);
		$settings[] = array(
			'slug' => 'easy_opt_in_copy_font_size_'.$post_id,
			'default' => '16',
			'label' => 'Font Size',
			'section' => 'easy_opt_in_copy_area_settings',
			'type' => 'text'
		);
		
		// Name Field
		$settings[] = array(
			'slug' => 'easy_opt_in_name_border_color_'.$post_id,
			'default' => '#bbbbbb',
			'label' => 'Border Color',
			'section' => 'easy_opt_in_name_field_settings',
			'type' => 'color'
		);
		$settings[] = array(
			'slug' => 'easy_opt_in_name_padding_'.$post_id,
			'default' => '5',
			'label' => 'Padding',
			'section' => 'easy_opt_in_name_field_settings',
			'type' => 'text'
		);
		$settings[] = array(
			'slug' => 'easy_opt_in_name_visible_'.$post_id,
			'default' => '1',
			'label' => 'Show Name Field',
			'section' => 'easy_opt_in_name_field_settings',
			'type' => 'checkbox'
		);
		
		// Email
		$settings[] = array(
			'slug' => 'easy_opt_in_email_border_color_'.$post_id,
			'default' => '#bbbbbb',
			'label' => 'Border Color',
			'section' => 'easy_opt_in_email_field_settings',
			'type' => 'color'
		);
		$settings[] = array(
			'slug' => 'easy_opt_in_email_padding_'.$post_id,
			'default' => '5',
			'label' => 'Padding',
			'section' => 'easy_opt_in_email_field_settings',
			'type' => 'text'
		);
		
		// Button
		$settings[] = array(
			'slug' => 'easy_opt_in_button_label_'.$post_id,
			'default' => 'Subscribe!',
			'label' => 'Button Label',
			'section' => 'easy_opt_in_button_settings',
			'type' => 'text'
		);
		$settings[] = array(
			'slug' => 'easy_opt_in_button_font_size_'.$post_id,
			'default' => '16',
			'label' => 'Font Size',
			'section' => 'easy_opt_in_button_settings',
			'type' => 'text'
		);
		$settings[] = array(
			'slug' => 'easy_opt_in_button_font_color_'.$post_id,
			'default' => '#ffffff',
			'label' => 'Font Color',
			'section' => 'easy_opt_in_button_settings',
			'type' => 'color'
		);
		$settings[] = array(
			'slug' => 'easy_opt_in_button_bg_color_'.$post_id,
			'default' => '#95a5a6',
			'label' => 'Background Color',
			'section' => 'easy_opt_in_button_settings',
			'type' => 'color'
		);
		$settings[] = array(
			'slug' => 'easy_opt_in_button_bg_hover_color_'.$post_id,
			'default' => '#7f8c8d',
			'label' => 'Hover Color',
			'section' => 'easy_opt_in_button_settings',
			'type' => 'color'
		);
		$settings[] = array(
			'slug' => 'easy_opt_in_button_border_bottom_color_'.$post_id,
			'default' => '#7f8c8d',
			'label' => 'Border Bottom Color',
			'section' => 'easy_opt_in_button_settings',
			'type' => 'color'
		);
		$settings[] = array(
			'slug' => 'easy_opt_in_button_bottom_width_'.$post_id,
			'default' => '4',
			'label' => 'Border Bottom Width',
			'section' => 'easy_opt_in_button_settings',
			'type' => 'text'
		);
	
		// Privacy Policy
		$settings[] = array(
			'slug' => 'easy_opt_in_privacy_policy_text_'.$post_id,
			'default' => '',
			'label' => 'Privacy Policy',
			'section' => 'easy_opt_in_privacy_policy_settings',
			'type' => 'textarea'
		);
		$settings[] = array(
			'slug' => 'easy_opt_in_privacy_policy_font_color_'.$post_id,
			'default' => '#000000',
			'label' => 'Font Color',
			'section' => 'easy_opt_in_privacy_policy_settings',
			'type' => 'color'
		);
		$settings[] = array(
			'slug' => 'easy_opt_in_privacy_policy_font_size_'.$post_id,
			'default' => '12',
			'label' => 'Font Size',
			'section' => 'easy_opt_in_privacy_policy_settings',
			'type' => 'text'
		);
		
		// Load settings
        foreach( $settings as $item ) {
            $wp_customize->add_setting(
				$item['slug'],
				array('default' => $item['default'], 'type' => 'option', 'capability' => 'manage_options', 'transport' => 'postMessage')
			);
            
			if ($item['type'] == 'color') {
				$wp_customize->add_control(
					new WP_Customize_Color_Control(
						$wp_customize,
						$item['slug'],
						array('label' => $item['label'], 'section' => $item['section'], 'settings' => $item['slug'])
					)
				);
			} elseif ($item['type'] == 'text') {
				$wp_customize->add_control(
					new WP_Customize_Control(
						$wp_customize,
						$item['slug'],
						array('label' => $item['label'], 'section' => $item['section'], 'settings' => $item['slug'])
					)
				);
			} elseif ($item['type'] == 'textarea') {
				$wp_customize->add_control(
					new EOI_Customize_Textarea_Control(
						$wp_customize,
						$item['slug'],
						array('label' => $item['label'], 'section' => $item['section'], 'settings' => $item['slug'])
					)
				);
			} elseif ($item['type'] == 'checkbox') {
				$wp_customize->add_control(
					new WP_Customize_Control(
						$wp_customize,
						$item['slug'],
						array('label' => $item['label'], 'section' => $item['section'], 'settings' => $item['slug'], 'type' => 'checkbox')
					)
				);	
			}
        }
        
    }
	
	function custom_css()
	{
		$easy_opt_in_ids = $this->get_form_ids();

		// Print CSS
		echo '<style type="text/css">'."\n";
		
		foreach ($easy_opt_in_ids as $id) :
			
			// Wrapper
			echo
				"#easy_opt_in_wrapper_".$id." { " . 
					"background: ".get_option('easy_opt_in_wrapper_bg_color_'.$id, '#f39c12')."; ".
				"}"."\n";
			
			// H3
			echo "#easy_opt_in_wrapper_".$id." h3 { " .
					"color: " . get_option('easy_opt_in_h3_font_color_' . $id, '#000000') . "; ".
					"font-size: " . get_option('easy_opt_in_h3_font_size_' . $id, 20) . "; ".
				"}"."\n";
			
			// Copy
			echo "#easy_opt_in_wrapper_".$id." p.easy_opt_in_copy { " .
					"color: " . get_option('easy_opt_in_copy_font_color_'.$id, '#000000')."; " .
					"font-size: " . get_option('easy_opt_in_copy_font_size_'.$id, '16')."; " . 
				"}"."\n";
			
			// Name
			$display = (get_option('easy_opt_in_name_visible_'.$id, true) == true)?'block':'none';
			echo "#easy_opt_in_wrapper_".$id." #easy_opt_in_name { " .
					"border-color: " . get_option('easy_opt_in_name_border_color_'.$id, '#bbbbbb')."; ".
					"border-style: solid; " .
					"border-width: 1px;" .
					"display: " . $display . "; ".
					"margin-top: " . get_option('easy_opt_in_name_padding_'.$id, '5') . "px; ".
					"margin-bottom: " . get_option('easy_opt_in_name_padding_'.$id, '5')."px; ".
				"}"."\n";
			
			// Email
			echo "#easy_opt_in_wrapper_".$id." #easy_opt_in_email { " .
					"border-color: " . get_option('easy_opt_in_email_border_color_'.$id, '#bbbbbb')."; ".
					"border-style: solid; " .
					"border-width: 1px; " .
					"margin-top: " . get_option('easy_opt_in_email_padding_'.$id, 5) . "px; ".
					"margin-bottom: " . get_option('easy_opt_in_email_padding_'.$id, 5)."px; ".
				"}"."\n";
			
			// Button
			echo "#easy_opt_in_wrapper_".$id." #easy_opt_in_submit { " .
					"font-size: " . get_option('easy_opt_in_button_font_size_'.$id, 16)."px; ".
					"color: " . get_option('easy_opt_in_button_font_color_'.$id, '#ffffff') . "; ".
					"background: " . get_option('easy_opt_in_button_bg_color_'.$id, '#95a5a6') . "; ".
					"border-color: " . get_option('easy_opt_in_button_border_bottom_color_'.$id, '#7f8c8d') . ";" .
					"border-bottom-width: ". get_option('easy_opt_in_button_bottom_width_'.$id, '4')."px; ".
				"}"."\n";
				
			echo "#easy_opt_in_wrapper_".$id." #easy_opt_in_submit:hover { " .
					"background: " . get_option('easy_opt_in_button_bg_hover_color_'.$id, '#7f8c8d') . "; ".
				"}"."\n";
			
			// Privacy Policy
			echo "#easy_opt_in_wrapper_".$id." p.easy_opt_in_privacy { " .
					"color: " . get_option('easy_opt_in_privacy_policy_font_color_'.$id, '#000000')."; ".
					"font-size: " . get_option('easy_opt_in_privacy_policy_font_size_'.$id, '12')."px; ".
				"}"."\n";
				
		endforeach;
	
		echo '</style>'."\n";
	}
	
	function get_form_ids()
	{
		$data = array();
		
		$query = new WP_Query(array('post_type' => 'easy-opt-ins', 'post_status' => 'publish', 'posts_per_page' => -1));
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) : $query->the_post();
				$data[] = get_the_ID();
			endwhile;
		}
		wp_reset_postdata();
		
		return $data;
	}
}
?>