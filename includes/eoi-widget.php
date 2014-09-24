<?php

class EasyOptInsWidgetHelper
{
  var $settings;

  function __construct($settings = array())
  {
    $this->settings = $settings;

    // Register widget
    add_action('widgets_init', array($this, 'register_widget'));
  }

  function register_widget()
  {
    register_widget('EasyOptInsWidget');
  }
}

class EasyOptInsWidget extends WP_Widget
{
  function __construct()
  {
    $widget_ops = array('classname' => 'easy-opt-in-widget', 'description' => 'Displays your opt-in sign-up form.');
    $control_ops = array('width' => 300, 'height' => 350, 'id_base' => 'easy-opt-in-widget');
    $this->WP_Widget('easy-opt-in-widget', 'Opt-in Widget', $widget_ops, $control_ops);
  }

  function widget($args, $instance)
  {
    global $fca_eoi_shortcodes;

    extract($args);
    $form_id = (preg_match('/^([0-9])+$/sim', $instance['eoi_form_id']))?$instance['eoi_form_id']:0;

    echo $before_widget;

    include_once plugin_dir_path( __FILE__ ) . 'eoi-shortcode.php';
    $widget = $fca_eoi_shortcodes->shortcode_content(array('id' => $form_id));
    echo $widget;

    echo $after_widget;
  }

  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['eoi_form_id'] = strip_tags($new_instance['eoi_form_id']);

    return $instance;
  }

  function form($instance)
  {

    global $post;

    $defaults = array('eoi_form_id' => '');
    $instance = wp_parse_args((array)$instance, $defaults);
    $options = array();
    $forms = get_posts( array(
      'post_type' => 'easy-opt-ins',
      'post_status' => 'publish',
      'posts_per_page' => -1
    ) );

    // Show error if there are no forms, then exit function
    if( empty ( $forms ) ) {
      K::wrap(
        sprintf( 
          __( 'You have not created a form yet. <a href="%s" target="_blank">Create a form</a>.' )
          , admin_url( 'post-new.php?post_type=easy-opt-ins' )
        )
        , null
        , array( 'in' => 'p' )
      );
      return;
    }

    // Prepare options, for with a thank you page and a list chosen
    foreach ( $forms as $form ) {
      $fca_eoi = get_post_meta( $form->ID, 'fca_eoi', true );
      if ( ! K::get_var( 'list_id', $fca_eoi ) || ! K::get_var( 'thank_you_page', $fca_eoi ) ) {
        continue;
      }
      $title = $form->post_title
        ? $form->post_title
        : __( 'Untitled form' ) . ' (#' . $form->ID . ')'
      ;
      $options[ $form->ID ] = $title;
    }
    
    // If no suitable form exists, show an indication and exit function
    if( ! $options ) {
      K::wrap(
        sprintf( 
          __( 'No suitable form was found. Please check <a href="%s" target="_blank">your forms</a> for errors.' )
          , admin_url( 'edit.php?post_type=easy-opt-ins' )
        )
        , null
        , array( 'in' => 'p' )
      );
      return;
    }

    // Add empty choice
    $options = array_reverse( $options, true );
    $options[ '' ] = 'Not set';
    $options = array_reverse( $options, true );

    // Show choices
    K::select( $this->get_field_name('eoi_form_id')
      , array(
        'id' => $this->get_field_id( 'eoi_form_id' ),
        'class' => 'select2',
        'style' => 'width:100%',
      )
      , array(
        'options' => $options,
        'default' => '',
        'selected' => $instance['eoi_form_id'],
        'format' => '<p><label for="' . $this->get_field_id( 'eoi_form_id' ) . '">' . __( 'Select form:' ) . '</label><p>:select</p>',
      )
    );
    
  }
}
?>
