<?php
/*
    Plugin Name: Easy Opt-ins For Campaign Monitor
    Plugin URI: http://fatcatapps.com/eoi
    Description: The Easy Opt-ins For Campaign Monitor WordPress Plugin Helps You Get More Email Subscribers. Create Beautiful & Highly Converting Opt-In Widgets In Minutes.
    Author: Fatcat Apps
    Version: 1.1.1
    Author URI: http://http://fatcatapps.com/
*/

// define( 'FCA_EOI_DEBUG', true );

if ( ! function_exists( 'is_admin' ) ) {
    exit();
}

if ( ! class_exists ( 'K' ) ) {
    require plugin_dir_path( __FILE__ ) . 'includes/classes/K/K.php';
}
if( ! defined ( 'FCA_EOI_DEBUG' ) ) {
    define( 'FCA_EOI_DEBUG', false );
}
if ( FCA_EOI_DEBUG && ! class_exists ( 'Kint' ) ) {
    require plugin_dir_path( __FILE__ ) . 'includes/classes/kint/Kint.class.php';
}
if ( ! class_exists ( 'Mustache_Engine' ) ) {
    require plugin_dir_path( __FILE__ ) . 'includes/classes/Mustache/Autoloader.php';
    Mustache_Autoloader::register();
}
if ( ! class_exists ( 'scssc' ) ) {
    require plugin_dir_path( __FILE__ ) . 'includes/classes/scssphp/scss.inc.php';
}

include_once plugin_dir_path( __FILE__ ) . 'includes/eoi-post-types.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/eoi-settings.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/eoi-shortcode.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/eoi-widget.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/eoi-pointer.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/eoi-tour-pointer.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/power-ups/eoi-powerups-setting.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/compatibility-mode/eoi-compatibility-mode.php';

define(
    'EOI_PLUGIN_PATH_FOR_SUBDIRS'
    , plugin_dir_path( str_replace( dirname( dirname( __FILE__ ) ), '', dirname( __FILE__ ) ) )
);

class DhEasyOptIns {

    var $ver = '1.0.0';
    var $shortcode = 'easy-opt-in';
    var $settings;
    var $provider = '';
    var $providers = array();

    function __construct() {
        global $fca_eoi_shortcodes;

        $post_type = $this->get_current_post_type();
        $eoi_settings = get_option('easy_opt_in_settings');

        // Settings
        $this->settings();

        // Check plugin sanity (we must have at least one provider and one layout)
        if( ! $this->check_sanity() ) {
            add_action( 'admin_init', array( $this, 'shutdown' ) );
            return;
        }

        // Add provider to settings
        $providers_available = array_keys( $this->settings[ 'providers' ] );
        
        //set current post type to setting array
        $this->settings[ 'post_type' ] = $post_type;

        // If there is only one provider, use it
        if( 1 == count( $providers_available ) ) {
            $this->provider = $this->settings[ 'provider' ] = $providers_available[ 0 ];
        }

        // Add options that are stored in DB if any
        $this->settings[ 'eoi_settings' ] = $eoi_settings;

        // Include provider helper class(es)
        foreach ( $providers_available as $provider ) {
            include_once $this->settings[ 'plugin_dir' ] . "providers/$provider/functions.php";
        }

        // Load extensions
        $post_types = new EasyOptInsPostTypes($this->settings);
        $fca_eoi_shortcodes = new EasyOptInsShortcodes($this->settings);
        $widget     = new EasyOptInsWidgetHelper($this->settings);
        
        //Load Power uups admin page
        new EasyOptInsPowerUpsSettings( $this->settings );

        // Load subscribing banner
        $pointer = new EasyOptInsPointer( $this->settings );
        
        //Load tour pointer
        //$tour_pointer = new EOITourPointer($this->settings );
        
        //load compatibility-mode
        new  EasyOptInsCompatibilityMode( $this->settings );
    }

    function get_current_post_type() {

        global $post, $typenow, $current_screen;

        if ( $post && $post->post_type ) {
            return $post->post_type;
        } elseif( $typenow ) {
            return $typenow;
        } elseif( $current_screen && $current_screen->post_type ) {
            return $current_screen->post_type;
        } elseif( isset( $_REQUEST['post_type'] ) ) {
            return sanitize_key( $_REQUEST['post_type'] );
        } elseif ( isset( $_REQUEST['post'] ) && $_REQUEST['post'] ) {
            $id =  $_REQUEST['post'];
            $post_obj = get_post( $id );
            if( $post_obj ) {
                return $post_obj->post_type;
            }
        }
        return null;
    }

    function settings() {
        $this->settings['plugin_dir'] = plugin_dir_path( __FILE__ );
        $this->settings['plugin_url'] = plugins_url('', __FILE__);
        $this->settings['shortcode']  = $this->shortcode;
        $this->settings['version']    = $this->ver;
        $this->settings['provider']   = $this->provider;
        // Load all providers
        foreach ( glob( $this->settings[ 'plugin_dir' ] . 'providers/*', GLOB_ONLYDIR ) as $provider_path ) {  
            $provider_id = basename( $provider_path );
            require_once "$provider_path/provider.php";
            $this->settings[ 'providers' ][ $provider_id ] = call_user_func( "provider_$provider_id" );
        }
    }

    function check_sanity() {

        $providers = glob( $this->settings[ 'plugin_dir' ] . 'providers/*', GLOB_ONLYDIR );
        $layouts = glob( $this->settings[ 'plugin_dir' ] . 'layouts/*', GLOB_ONLYDIR );

        return ! empty( $providers ) && ! empty( $layouts );
    }

    function shutdown() {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( sprintf( 
            '<h2>%s</h2><p>%s</p><p><a class="button button-large" href="%s">%s</a></p>'
            , __( 'Easy Opt-ins is broken!' )
            , __( 'The plugin is broken, it has been deactivated, please <strong>delete and install again</strong>.' )
            , admin_url( 'plugins.php' )
            , __( 'Go to plugins page' )
        ) );
    }
}

$dh_easy_opt_ins_plugin = new DhEasyOptIns();
