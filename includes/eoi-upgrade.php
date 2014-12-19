<?php

/**
 * Show upgrading notifications for EOI  
 *
 */
class EasyOptInsUpgrade {
    
    private $settings;
    private $fca_maketing_page_left_menu;
    private $fca_maketing_page_top_ad;

    public function __construct( $settings=null ) {
        
        global $pagenow;
        $this->settings = $settings;
        
        $this->fca_maketing_page_left_menu = 'http://fatcatapps.com/easyoptins?utm_campaign=eoi-left-menu&utm_source=eoi-free-mailchimp&utm_medium=referral';
        $this->fca_maketing_page_top_ad = 'http://fatcatapps.com/easyoptins?utm_campaign=eoi-top-ad&utm_source=eoi-free-mailchimp&utm_medium=referral';
        
        add_action( 'admin_menu', array( $this, 'fca_eoi_upgrade_to_premium_menu' ));
        add_action( 'admin_footer', array( $this,  'fca_eoi_upgrade_to_premium_menu_js' ));
        
        if ('easy-opt-ins' == $settings['post_type'] &&
                (( isset($_REQUEST['action']) && 'edit' == $_REQUEST['action']) || 'post-new.php' == $pagenow || 'edit.php' == $pagenow ) ) {
                 add_action( 'admin_notices', array( $this, 'information_notice' ) );
        }
    }
    
    function fca_eoi_upgrade_to_premium_menu() {
        
        $page_hook = add_submenu_page( 'edit.php?post_type=easy-opt-ins', __( 'Upgrade to Premium'), __( 'Upgrade to Premium' ), 'manage_options', 'eoi_premium_upgrade', array( $this, 'fca_eoi_upgrade_to_premium' ));
        add_action( 'load-' . $page_hook , array( $this, 'fca_eoi_upgrade_to_premium' ));
     }
   
    function fca_eoi_upgrade_to_premium() {
        
        wp_redirect( $this->fca_maketing_page_left_menu, 301 );
        exit();
      }
      
    function fca_eoi_upgrade_to_premium_menu_js()
     {
    ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $('a[href="edit.php?post_type=easy-opt-ins&page=eoi_premium_upgrade"]').on('click', function () {
                            $(this).attr('target', '_blank');
                });
            });
        </script>
        <style>
            a[href="edit.php?post_type=easy-opt-ins&page=eoi_premium_upgrade"] {
                color: #6bbc5b !important;
            }
            a[href="edit.php?post_type=easy-opt-ins&page=eoi_premium_upgrade"]:hover {
                color: #7ad368 !important;
            }
            .eoi-changelogs {
                background: #f1f1f1;
            }
            .eoi-changelogs-content {
                margin: 20px 10px;
                background: #fff;
            }
            
        </style>
    <?php 
    }
    
    function information_notice () {
        echo '<div class="update-nag">' . sprintf( __( 'Easy Opt-ins Premium comes with lots of additional layouts, and various conversion increasing features. Special launch discount. <a href="%s" target="_blank">Learn more</a>' ), $this->fca_maketing_page_top_ad ) . '</div>';
    }    
}