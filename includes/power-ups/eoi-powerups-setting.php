<?php


include_once plugin_dir_path( __FILE__ ) . 'eoi-sticky-widget.php';
include_once plugin_dir_path( __FILE__ ) . 'eoi-cusstom-css-textbox.php';

/**
 * This class setup the sub admin page "Power ups"
 *
 * @author thuantp
 */
class EasyOptInsPowerUpsSettings
{
    
  var $settings;
  var $sanitize_done = false;
  var $power_ups_settings;

  function __construct( $settings )
  {
    $this->settings = $settings;
    
    // Enqueue assets
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    add_action( 'admin_menu', array($this, 'settings_menu') );
    add_action( 'admin_init', array($this, 'register_settings') );  
    include_once $this->settings[ 'plugin_dir' ] . "includes/power-ups/eoi-function-setting-field-callback.php";
    $this->power_ups_settings = get_option( 'eoi_power_ups_settings' );
    
    if( K::get_var( 'eoi_sticky_widget', $this->power_ups_settings ) ) {
        new EoiStickyWidget( $settings );
    }
    
    if( K::get_var( 'eoi_custom_css', $this->power_ups_settings ) ) {
        new EoiCustomCssBox( $settings );
    }
    
  }
  
  function enqueue_assets() {

      // CSS Style
        wp_enqueue_style(
            'easy-opt-in-power-ups-style'
            , $this->settings['plugin_url'] . '/assets/power-ups/fca_eoi_power_up_style.css'
        );

        // JS
        wp_enqueue_script(
            'easy-opt-in-power-ups-script'
            , $this->settings['plugin_url'] . '/assets/power-ups/fca_eoi_powerups_script.js'
            , array( 'jquery' )
        );
  }


  function settings_content()
  {
    
    ?>

      <div class="wrap">
        <h2><?php echo __('Power Ups') ?></h2>
        <label for="eoi_power_ups_settings['header']"> Power ups are advanded features that have to be manually activated.</label>

        <?php settings_errors('eoi_power_ups_settings_group'); ?>

        <form method="post" action="options.php">
            <table class="form-table">
            <?php
              wp_nonce_field( 'update-options' );
              settings_fields('eoi_power_ups_settings_group');
              do_settings_fields(  'eoi_power_ups_settings', 'eoi_power_ups_settings' );
            ?>
            </table>
             <?php
               submit_button();
            ?>
        </form>
      </div>
    <?php
  }

  function settings_menu()
    {
        add_submenu_page('edit.php?post_type=easy-opt-ins', __('Power Ups'), __('Power Ups'), 'manage_options', 'eoi_power_ups_settings', array($this, 'settings_content'));
    }

  /**
   * Add all settings sections and fields
   *
   * @since 1.0
   * @return void
  */
  function register_settings() {

          add_settings_section(
                          'eoi_power_ups_settings',
                          __('Power Ups'),
                          '__return_false',
                          'eoi_power_ups_settings'
                  );
          
          foreach ( $this->get_registered_settings() as $option ) {

                  

                          $name = isset( $option['name'] ) ? $option['name'] : '';

                          add_settings_field(
                                  'eoi_power_ups[' . $option['id'] . ']',
                                  $name,
                                  function_exists( 'eoi_setting_field_' . $option['type'] . '_callback' ) ? 'eoi_setting_field_' . $option['type'] . '_callback' : 'eoi_setting_field__missing_callback',
				
                                  'eoi_power_ups_settings' ,
                                  'eoi_power_ups_settings' ,
                                  array(
                                          'id'      => isset( $option['id'] ) ? $option['id'] : null,
                                          'desc'    => ! empty( $option['desc'] ) ? $option['desc'] : '',
                                          'name'    => isset( $option['name'] ) ? $option['name'] : null,
                                          'section' => '',
                                          'size'    => isset( $option['size'] ) ? $option['size'] : null,
                                          'options' => isset( $option['options'] ) ? $option['options'] : '',
                                          'std'     => isset( $option['std'] ) ? $option['std'] : ''
                                  )
                          );
          }

          // Creates our settings in the options table
          register_setting( 'eoi_power_ups_settings_group', 'eoi_power_ups_settings', array( $this, 'settings_sanitize' ) );

  }
  

    
    /**
    * Retrieve the array of power ups settings
    *
    * @since 1.8
    * @return array
   */
   function get_registered_settings() {
                 
                   $eoi_power_ups_settings = $this->power_ups_settings;

                   /** Power Ups Settings */
                   $power_ups_setting = array(
                                   /*'header' => array(
                                           'id' => 'power_up_header',
                                           'name' => '',
                                           'desc' => __( 'Power ups are advanded features that have to be manually activated.' ),
                                           'type' => 'header'
                                   ),*/
                                  'eoi_custom_css' => array(
					'id' => 'eoi_custom_css',
					'name' => '<strong  >' . __( 'Custom CSS' )  . '</strong>',
					'desc' => '<span id="eoi_custom_css" class="header_power_up_eoi_items" >'. __( 'Adds a settting that lets you add custom CSS to your opt-in box to the Easy Opt-ins editor.' ) . '</span>',
					'type' => 'checkbox',
                                        'std'  => isset($eoi_power_ups_settings['eoi_custom_css'])?$eoi_power_ups_settings['eoi_custom_css']:0        
				 )
                          );
                   
                   $eoi_free = ( count( $this->settings[ 'providers' ] ) ==1 )?TRUE:FALSE ;
                   if (!$eoi_free) {
                       
                       $eoi_sticky_widget = array(
					'id' => 'eoi_sticky_widget',
					'name' => '<strong  >'. __( 'Sticky Widget' ) .  '</strong>',
					'desc' => '<span id="eoi_sticky_widget" class="header_power_up_eoi_items" >' . __( 'Adds a settting that lets you make your sidebar widget "sticky" to the Easy Opt-ins editor.' ) . '</span>',
					'type' => 'checkbox',
                                        'std'  => isset($eoi_power_ups_settings['eoi_sticky_widget'])?$eoi_power_ups_settings['eoi_sticky_widget']:0
				 );
                       array_push( $power_ups_setting , $eoi_sticky_widget );
                   }
                   
           return $power_ups_setting;
   }   
    

   public function settings_sanitize($input)
    {
        // Update notice
      if( ! $this->sanitize_done ) {
        add_settings_error('eoi_power_ups_settings_group', '200', 'Settings saved.', 'updated');
        $this->sanitize_done = true;
      } 
      return $input;
    }
}

