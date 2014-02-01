<?php
/*
Plugin Name: Instagram Slider Widget
Plugin URI: http://jrwebstudio.com/instagram-slider/
Description: Instagram Slider Widget is a responsive slider widget that shows 20 latest images from a public instagram user.
Author: jetonr
Author URI: http://jrwebstudio.com/
License: GPLv2 or later
*/

/* Define Constants for this widget */
define('JR_INSTAGWP_PATH_BASE'      , dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('JR_INSTAGWP_PATH_TEMPLATE'  , JR_INSTAGWP_PATH_BASE . 'templates/');
define('JR_INSTAGWP_PATH_INC'       , JR_INSTAGWP_PATH_BASE . 'inc/');
define('JR_INSTAGWP_URL'      		, plugins_url( '/' , __FILE__ ));
define('JR_INSTAGWP_WP_VERSION'     , get_bloginfo('version'));
define('JR_INSTAGWP_WP_MIN_VERSION' , 3.5);

$upload_dir = wp_upload_dir();
define('JR_INSTAGWP_UPLOAD_PATH'    , $upload_dir['path'] . '/');
define('JR_INSTAGWP_UPLODAD_URL'    , $upload_dir['baseurl'] . $upload_dir['subdir'] . '/');

// Require functions need for this widget
require_once ( JR_INSTAGWP_PATH_INC . 'functions.php' );

/* Enqueue Frontend Plugin Styles & Scripts */
function jr_insta_slider_enqueue() {
	
	// Register and enqueue Styles
	wp_enqueue_style( 'instag-slider', JR_INSTAGWP_URL . 'css/instag-slider.css' );
	
    // Register and enqueue Scripts
	wp_enqueue_script(
		'jquery-flexi-slider',
		JR_INSTAGWP_URL . 'js/jquery.flexslider-min.js',
		array( 'jquery' ),
		false,
		true
	);

}
add_action( 'wp_enqueue_scripts', 'jr_insta_slider_enqueue' );

/* Register widget on windgets init */
add_action( 'widgets_init', 'jr_insta_slider_register' );
function jr_insta_slider_register() {
	register_widget( 'JR_InstagramSlider' );
}

class JR_InstagramSlider extends WP_Widget {

	public function __construct() {
		parent::__construct( 'jr_insta_slider', __( 'Instagram Slider', 'jrinstaslider' ), array(
			'classname'   => 'jr-insta-slider',
			'description' => __( 'A widget that displays a slider with instagram images ', 'jrinstaslider', 'jrinstaslider' ),
		) );
	}
	
	function widget( $args, $instance ) {
		extract( $args );

		//Our variables from the widget settings.
		$title        = apply_filters('widget_title', $instance['title'] );
		$username     = $instance['username'];
		$images_nr    = $instance['images_number'];
		$refresh_hour = $instance['refresh_hour'];
		$template	  = $instance['template'];

		echo $before_widget;

		// Display the widget title 
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		// Get instagram data 
		$insta_data = instag_images_data($username, $refresh_hour, $images_nr );

		//include the template based on user choice
		instag_templates( $template, $insta_data );
		
		echo $after_widget;
	}

	//Update the widget 
	 
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		//Strip tags from title and name to remove HTML 
		$instance['title']         = strip_tags( $new_instance['title'] );
		$instance['username']      = $new_instance['username'];
		$instance['images_number'] = $new_instance['images_number'];
		$instance['refresh_hour']  = $new_instance['refresh_hour'];
		$instance['template']      = $new_instance['template'];

		return $instance;
	}

	
	function form( $instance ) {

		//Set up some default widget settings.
		$defaults = array( 'title' => __('Instagram Slider', 'jrinstaslider'), 'username' => __('', 'jrinstaslider'), 'images_number' => 5, 'refresh_hour' => 5, 'template' => 'slider' );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'jrinstaslider'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'username' ); ?>"><?php _e('Instagram Username:', 'jrinstaslider'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" value="<?php echo $instance['username']; ?>" />
		</p>
 
        <p>
          <label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Images Layout', 'jrinstaslider' ); ?>
          <select class="widefat" name="<?php echo $this->get_field_name( 'template' ); ?>">
          <option value="slider" <?php ($instance['template'] == 'slider') ? ' selected="selected"' : ''; ?>><?php _e('Slider', 'jrinstaslider'); ?></option>
          <option value="thumbs" <?php ($instance['template'] == 'thumbs') ? ' selected="selected"' : ''; ?>><?php _e('Thumbnails', 'jrinstaslider'); ?></option>
          </select>  
          </label>
        </p>
        
		<p>
			<label  for="<?php echo $this->get_field_id( 'images_number' ); ?>"><?php _e('Number of Images to Show:', 'jrinstaslider'); ?>
			<input  class="small-text" id="<?php echo $this->get_field_id( 'images_number' ); ?>" name="<?php echo $this->get_field_name( 'images_number' ); ?>" value="<?php echo $instance['images_number']; ?>" />
			<small><?php _e('( max 20 )', 'jrinstaslider'); ?></small>
            </label>
		</p>
               
		<p>
			<label  for="<?php echo $this->get_field_id( 'refresh_hour' ); ?>"><?php _e('Check for new images every:', 'jrinstaslider'); ?>
			<input  class="small-text" id="<?php echo $this->get_field_id( 'refresh_hour' ); ?>" name="<?php echo $this->get_field_name( 'refresh_hour' ); ?>" value="<?php echo $instance['refresh_hour']; ?>" />
			<small><?php _e('hours', 'jrinstaslider'); ?></small>
            </label>
		</p>
        
		<?php
	}
}
