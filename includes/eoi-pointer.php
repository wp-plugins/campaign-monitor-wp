<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of eoi-pointer
 *
 * @author thuantp
 */
class EasyOptInsPointer {

    private $settings;

    public function __construct($post_type = null, $settings=null ) {

        add_action('wp_ajax_tt_eoi_mailing_list', array($this, 'tt_eoi_mailing_list_pointer_ajax'));

        // Load mailing list pointer popup
        global $current_screen;
        global $pagenow;
        // Show mailing list pointer popup once
        $mailing_list = get_option('tt_eoi_mailing_list');
        if ('easy-opt-ins' == $post_type &&
                (( isset($_REQUEST['action']) && 'edit' == $_REQUEST['action']) || 'post-new.php' == $pagenow) &&
                !in_array($mailing_list, array('yes', 'no'))) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        }
        
        $this->settings = $settings;
    }

    function enqueue_assets() {
        wp_enqueue_style('wp-pointer');
        wp_enqueue_script('wp-pointer');
        add_action('admin_print_footer_scripts', array($this, 'tt_eoi_mailing_list_pointer'));
    }

    // Add mailing list subscription
    function tt_eoi_mailing_list_pointer() {
        global $current_user;

        // Get current user info
        get_currentuserinfo();

        // Ajax request template    
        $ajax = '
        jQuery.ajax({
            type: "POST",
            url:  "' . admin_url('admin-ajax.php') . '",
            data: {action: "tt_eoi_mailing_list", email: jQuery("#ept_email").val(), nonce: "' . wp_create_nonce('tt_eoi_mailing_list') . '", subscribe: "%s" }
        });
    ';

        // Target
        $id = '#wpadminbar';

        // Buttons
        $button_1_title = __('No, thanks');
        $button_1_fn = sprintf($ajax, 'no');
        $button_2_title = __("Let&#39;s do it!");
        $button_2_fn = sprintf($ajax, 'yes');

        // Content
        $content = '<h3>' . __('Free Report') . '</h3>';
        $content .= '<p>' . __("Get a free 1-page PDF on \"How To Double Your Opt-in Conversion Rate\".") . '</p>';
        $content .= '<p>' . '<input type="text" name="ept_email" id="ept_email" value="' . $current_user->user_email . '" style="width: 100%"/>' . '</p>';

        // Options
        $options = array(
            'content' => $content,
            'position' => array('edge' => 'top', 'align' => 'center')
        );

        $this->tt_eoi_print_script($id, $options, $button_1_title, $button_2_title, $button_1_fn, $button_2_fn);
    }

    function tt_eoi_mailing_list_pointer_ajax() {
        global $current_user;

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tt_eoi_mailing_list') && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            die('No tricky business!');
        }

        // Check status
        $result = ($_POST['subscribe'] == 'yes') ? 'yes' : 'no';
        if ($result == 'no') {
            update_option('tt_eoi_mailing_list', 'no');
            exit();
        }


        // Get current user info
        get_currentuserinfo();

        // Subscribe
        if (!class_exists('EOIDripApi')) {
            include_once plugin_dir_path(__FILE__) . '/classes/drip/drip.php';
        }

        $drip_api = new EOIDripApi();
        $drip_api->add_subscriber_campain(
                $_POST['email'], //$current_user->user_email,
                array(
            'name' => $current_user->display_name,
            'url' => get_bloginfo('url')
                )
        );
        $plugin_data = get_plugin_data( $this->settings['plugin_dir'].'easy-opt-ins.php' );
        $drip_api->fire_event(
                            $_POST['email'],
                            'Installed '.  $plugin_data['Name'],
                            array()
                    );
        
        
        update_option('tt_eoi_mailing_list', $result);



        exit();
    }

// Print JS Content
    function tt_eoi_print_script($selector, $options, $button1, $button2 = false, $button1_fn = '', $button2_fn = '') {
        ?>
        <script type="text/javascript">
            //<![CDATA[
            (function ($) {
                var tt_eoi_pointer_options = <?php echo json_encode($options); ?>, setup;
                                     
                tt_eoi_pointer_options = $.extend(tt_eoi_pointer_options, {
                    buttons:function (event, t) {
                        button = jQuery('<a id="pointer-close" style="margin-left:5px" class="button-secondary">' + '<?php echo $button1; ?>' + '</a>');
                        button.bind('click.pointer', function () {
                            t.element.pointer('close');
                        });
                        return button;
                    },
                    close:function () {
                    }
                });
                                     
                setup = function () {
                    $('<?php echo $selector; ?>').pointer(tt_eoi_pointer_options).pointer('open');
        <?php if ($button2) : ?>
                        jQuery('#pointer-close').after('<a id="pointer-primary" class="button-primary">' + '<?php echo $button2; ?>' + '</a>');
                        jQuery('#pointer-primary').click(function () {
            <?php echo $button2_fn; ?>
                                $('<?php echo $selector; ?>').pointer('close');
                            });
                            jQuery('#pointer-close').click(function () {
            <?php echo $button1_fn; ?>
                                $('<?php echo $selector; ?>').pointer('close');
                            });
        <?php endif; ?>
                };
                                 
                $(document).ready(setup);
            })(jQuery);
            //]]>
        </script>
        <?php
    }

}
?>
