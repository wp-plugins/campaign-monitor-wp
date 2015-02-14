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
        
        $this->fca_maketing_page_left_menu = 'https://fatcatapps.com/optincat?utm_campaign=wp%2Bsubmenu&utm_source=Optin%2BCat%2BFree&utm_medium=plugin';
        $this->fca_maketing_page_top_ad = 'https://fatcatapps.com/optincat?utm_campaign=plugin%2Btop%2Bad&utm_source=Optin%2BCat%2BFree&utm_medium=plugin';
        
        add_action( 'admin_menu', array( $this, 'fca_eoi_upgrade_to_premium_menu' ));
        add_action( 'admin_footer', array( $this,  'fca_eoi_upgrade_to_premium_menu_js' ));
        
        if ('easy-opt-ins' == $settings['post_type'] &&
                (( isset($_REQUEST['action']) && 'edit' == $_REQUEST['action']) || 'post-new.php' == $pagenow || 'edit.php' == $pagenow ) ) {
                 add_action( 'admin_notices', array( $this, 'fca_eoi_information_notice' ) );
        }
    }
    
    function fca_eoi_upgrade_to_premium_menu() {
        
        $page_hook = add_submenu_page( 'edit.php?post_type=easy-opt-ins', __( 'Upgrade to Premium'), __( 'Upgrade to Premium' ), 'manage_options', 'eoi_premium_upgrade', array( $this, 'fca_eoi_upgrade_to_premium' ));
        add_action( 'load-' . $page_hook , array( $this, 'fca_eoi_upgrade_to_premium_redirect' ));
     }
   
    function fca_eoi_upgrade_to_premium_redirect() {
        
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
    
    function fca_eoi_information_notice () {
        echo '<div class="updated" style="padding-top:5px;padding-bottom:5px;line-height:22px;text-align:center;border-color:#FDFDFD;background-color:#FDFDFD;">' . sprintf( __( '<p><strong style="font-size:18px;">Wanna Get More Optins?</strong></p><p>Optin Cat Premium Increases Conversions With More Layouts, Advanced Popup Targeting, 2-Step-Optins, Email Support And More.</p><p><a class="button button-primary button-large" style="padding-left:30px;padding-right:30px;font-weight:bold;" href="%s" target="_blank">LEARN MORE</a></p>' ), $this->fca_maketing_page_top_ad ) . '</div>';
    }    
}