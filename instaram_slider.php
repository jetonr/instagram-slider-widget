<?php
/*
Plugin Name: Instagram Slider Widget
Plugin URI: http://instagram.jrwebstudio.com/
Version: 1.4.3
Description: Instagram Slider Widget is a responsive slider widget that shows 12 latest images from a public Instagram user and up to 18 images from a hashtag.
Author: jetonr
Author URI: http://jrwebstudio.com/
License: GPLv2 or later
*/

/**
 * On widgets Init register Widget
 */
add_action( 'widgets_init', array( 'JR_InstagramSlider', 'register_widget' ) );

/**
 * JR_InstagramSlider Class
 */
class JR_InstagramSlider extends WP_Widget {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @var     string
	 */
	const VERSION = '1.4.3';	
	
	const USERNAME_URL = 'https://www.instagram.com/{username}/';
	
	const TAG_URL = 'https://www.instagram.com/explore/tags/{tag}/?__a=1';
	
	/**
	 * Initialize the plugin by registering widget and loading public scripts
	 *
	 */
	public function __construct() {
		
		// Widget ID and Class Setup
		parent::__construct( 'jr_insta_slider', __( 'Instagram Slider', 'jrinstaslider' ), array(
			'classname' => 'jr-insta-slider',
			'description' => __( 'A widget that displays a slider with instagram images ', 'jrinstaslider' ) 
			) 
		);

		// Shortcode				
		add_shortcode( 'jr_instagram', array( $this, 'shortcode' ) );
		
		// Instgram Action to display images
		add_action( 'jr_instagram', array( $this, 'instagram_images' ) );

		// Enqueue Plugin Styles and scripts
		add_action( 'wp_enqueue_scripts', array( $this,	'public_enqueue' ) );
		
		// Enqueue Plugin Styles and scripts for admin pages
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );

		// Ajax action to unblock images from widget 
		add_action( 'wp_ajax_jr_delete_insta_dupes', array( $this, 'delete_dupes' ) );

		// Add new attachment field desctiptions
		add_filter( 'attachment_fields_to_edit', array( $this, 'insta_attachment_fields' ) , 10, 2 );

		// Add action for single cron events
		add_action( 'jr_insta_cron', array( $this, 'jr_cron_trigger' ), 10 , 3 );		
	}

	/**
	 * Register widget on windgets init
	 */
	public static function register_widget() {
		register_widget( __CLASS__ );
		register_sidebar( array(
			'name' => __( 'Instagram Slider - Shortcode Generator', 'jrinstaslider' ),
			'id' => 'jr-insta-shortcodes',
			'description' => __( "1. Drag Instagram Slider widget here. 2. Fill in the fields and hit save. 3. Copy the shortocde generated at the bottom of the widget form and use it on posts or pages.", 'jrinstaslider' )
		) 
	);
	}
	
	/**
	 * Enqueue public-facing Scripts and style sheet.
	 */
	public function public_enqueue() {
		
		wp_enqueue_style( 'instag-slider', plugins_url( 'assets/css/instag-slider.css', __FILE__ ), array(), self::VERSION );
		
		wp_enqueue_script( 'jquery-pllexi-slider', plugins_url( 'assets/js/jquery.flexslider-min.js', __FILE__ ), array( 'jquery' ), '2.2', false );
	}
	
	/**
	 * Enqueue admin side scripts and styles
	 * 
	 * @param  string $hook
	 */
	public function admin_enqueue( $hook ) {
		
		if ( 'widgets.php' != $hook ) {
			return;
		}
		
		wp_enqueue_style( 'jr-insta-admin-styles', plugins_url( 'assets/css/jr-insta-admin.css', __FILE__ ), array(), self::VERSION );
		wp_enqueue_script( 'jr-insta-admin-script', plugins_url( 'assets/js/jr-insta-admin.js', __FILE__ ), array( 'jquery' ), self::VERSION, true );	
	}
	
	/**
	 * The Public view of the Widget  
	 *
	 * @return mixed
	 */
	public function widget( $args, $instance ) {

		extract( $args );

		//Our variables from the widget settings.
		$title = apply_filters( 'widget_title', $instance['title'] );
		
		echo $before_widget;
		
		// Display the widget title 
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		do_action( 'jr_instagram', $instance );

		echo $after_widget;		
	}

	/**
	 * Update the widget settings 
	 *
	 * @param    array    $new_instance    New instance values
	 * @param    array    $old_instance    Old instance values	 
	 *
	 * @return array
	 */
	public function update( $new_instance, $instance ) {

		$instance['title']            = strip_tags( $new_instance['title'] );
		$instance['search_for']       = $new_instance['search_for'];
		$instance['username']         = $new_instance['username'];
		$instance['hashtag']          = $new_instance['hashtag'];
		$instance['blocked_users']    = $new_instance['blocked_users'];
		$instance['attachment']       = $new_instance['attachment'];
		$instance['template']         = $new_instance['template'];
		$instance['images_link']      = $new_instance['images_link'];
		$instance['custom_url']       = $new_instance['custom_url'];
		$instance['orderby']          = $new_instance['orderby'];
		$instance['images_number']    = $new_instance['images_number'];
		$instance['columns']          = $new_instance['columns'];
		$instance['refresh_hour']     = $new_instance['refresh_hour'];
		$instance['image_size']       = $new_instance['image_size'];
		$instance['image_link_rel']   = $new_instance['image_link_rel'];
		$instance['image_link_class'] = $new_instance['image_link_class'];
		$instance['no_pin']           = $new_instance['no_pin'];
		$instance['controls']         = $new_instance['controls'];
		$instance['animation']        = $new_instance['animation'];
		$instance['caption_words']    = $new_instance['caption_words'];
		$instance['slidespeed']       = $new_instance['slidespeed'];
		$instance['description']      = $new_instance['description'];
		$instance['support_author']   = $new_instance['support_author'];
		
		return $instance;
	}
	
	
	/**
	 * Widget Settings Form
	 *
	 * @return mixed
	 */
	public function form( $instance ) {

		$defaults = array(
			'title'            => __('Instagram Slider', 'jrinstaslider'),
			'search_for'       => 'username',
			'username'         => '',
			'hashtag'          => '',
			'blocked_users'    => '',
			'attachment' 	   => 0,
			'template'         => 'slider',
			'images_link'      => 'image_url',
			'custom_url'       => '',
			'orderby'          => 'rand',
			'images_number'    => 5,
			'columns'          => 4,
			'refresh_hour'     => 5,
			'image_size'       => 'standard',
			'image_link_rel'   => '',
			'image_link_class' => '',
			'no_pin' 	       => 0,
			'controls'		   => 'prev_next',
			'animation'        => 'slide',
			'caption_words'    => 100,
			'slidespeed'       => 7000,
			'description'      => array( 'username', 'time','caption' ),
			'support_author'   => 0
		);
		
		$instance = wp_parse_args( (array) $instance, $defaults );

		?>
		<div class="jr-container">
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><strong><?php _e('Title:', 'jrinstaslider'); ?></strong></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
			</p>
			<p>
				<strong><?php _e( 'Search Instagram for:', 'jrinstaslider' ); ?></strong>
				<span class="jr-search-for-container"><label class="jr-seach-for"><input type="radio" id="<?php echo $this->get_field_id( 'search_for' ); ?>" name="<?php echo $this->get_field_name( 'search_for' ); ?>" value="username" <?php checked( 'username', $instance['search_for'] ); ?> /> <?php _e( 'Username:', 'jrinstaslider' ); ?></label> <input id="<?php echo $this->get_field_id( 'username' ); ?>" class="inline-field-text" name="<?php echo $this->get_field_name( 'username' ); ?>" value="<?php echo $instance['username']; ?>" /></span>
				<span class="jr-search-for-container"><label class="jr-seach-for"><input type="radio" id="<?php echo $this->get_field_id( 'search_for' ); ?>" name="<?php echo $this->get_field_name( 'search_for' ); ?>" value="hashtag" <?php checked( 'hashtag', $instance['search_for'] ); ?> /> <?php _e( 'Hashtag:', 'jrinstaslider' ); ?></label> <input id="<?php echo $this->get_field_id( 'hashtag' ); ?>" class="inline-field-text" name="<?php echo $this->get_field_name( 'hashtag' ); ?>" value="<?php echo $instance['hashtag']; ?>" /> <small><?php _e('without # sign', 'jrinstaslider'); ?></small></span>
			</p>
			<p class="<?php if ( 'hashtag' != $instance['search_for'] ) echo 'hidden'; ?>">
				<label for="<?php echo $this->get_field_id( 'blocked_users' ); ?>"><?php _e( 'Block Users', 'jrinstaslider' ); ?>:</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'blocked_users' ); ?>" name="<?php echo $this->get_field_name( 'blocked_users' ); ?>" value="<?php echo $instance['blocked_users']; ?>" />
				<span class="jr-description"><?php _e( 'Enter usernames separated by commas whose images you don\'t want to show', 'jrinstaslider' ); ?></span>
			</p>
			<p class="<?php if ( 'username' != $instance['search_for'] ) echo 'hidden'; ?>"><strong><?php _e( 'Save in Media Library: ', 'jrinstaslider' ); ?></strong>
				<label class="switch" for="<?php echo $this->get_field_id( 'attachment' ); ?>">
					<input class="widefat" id="<?php echo $this->get_field_id( 'attachment' ); ?>" name="<?php echo $this->get_field_name( 'attachment' ); ?>" type="checkbox" value="1" <?php checked( '1', $instance['attachment'] ); ?> /><span class="slider round"></span></label>
					<br><span class="jr-description"><?php _e( ' Turn on to save Instagram Images into WordPress media library.', 'jrinstaslider') ?></span>
					<?php 		
					if ( isset ( $instance['username'] ) && !empty($instance['username'] ) ) {
						echo '<br><button class="button action jr-delete-instagram-dupes" type="button" data-username="'.$instance['username'].'"><strong>Remove</strong> duplicate images for <strong>'.$instance['username'].'</strong></button><span class="jr-spinner"></span>';
						echo '<br><br><strong><span class="deleted-dupes-info"></span></strong>';
						wp_nonce_field( 'jr_delete_instagram_dupes', 'delete_insta_dupes_nonce' );
					}				
					?>	        
				</p>	        
				<p>
					<label  for="<?php echo $this->get_field_id( 'images_number' ); ?>"><strong><?php _e( 'Number of images to show:', 'jrinstaslider' ); ?></strong>
						<input  class="small-text" id="<?php echo $this->get_field_id( 'images_number' ); ?>" name="<?php echo $this->get_field_name( 'images_number' ); ?>" value="<?php echo $instance['images_number']; ?>" />
					</label>
				</p>
				<p>
					<label  for="<?php echo $this->get_field_id( 'refresh_hour' ); ?>"><strong><?php _e( 'Check for new images every:', 'jrinstaslider' ); ?></strong>
						<input  class="small-text" id="<?php echo $this->get_field_id( 'refresh_hour' ); ?>" name="<?php echo $this->get_field_name( 'refresh_hour' ); ?>" value="<?php echo $instance['refresh_hour']; ?>" />
						<span><?php _e('hours', 'jrinstaslider'); ?></span>
					</label>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id( 'template' ); ?>"><strong><?php _e( 'Template', 'jrinstaslider' ); ?></strong>
						<select class="widefat" name="<?php echo $this->get_field_name( 'template' ); ?>" id="<?php echo $this->get_field_id( 'template' ); ?>">
							<option value="slider" <?php echo ($instance['template'] == 'slider') ? ' selected="selected"' : ''; ?>><?php _e( 'Slider - Normal', 'jrinstaslider' ); ?></option>
							<option value="slider-overlay" <?php echo ($instance['template'] == 'slider-overlay') ? ' selected="selected"' : ''; ?>><?php _e( 'Slider - Overlay Text', 'jrinstaslider' ); ?></option>
							<option value="thumbs" <?php echo ($instance['template'] == 'thumbs') ? ' selected="selected"' : ''; ?>><?php _e( 'Thumbnails', 'jrinstaslider' ); ?></option>
							<option value="thumbs-no-border" <?php echo ($instance['template'] == 'thumbs-no-border') ? ' selected="selected"' : ''; ?>><?php _e( 'Thumbnails - Without Border', 'jrinstaslider' ); ?></option>
						</select>  
					</label>
				</p>
				<p class="<?php if ( 'thumbs' != $instance['template'] && 'thumbs-no-border' != $instance['template'] ) echo 'hidden'; ?>">
					<label  for="<?php echo $this->get_field_id( 'columns' ); ?>"><strong><?php _e( 'Number of Columns:', 'jrinstaslider' ); ?></strong>
						<input class="small-text" id="<?php echo $this->get_field_id( 'columns' ); ?>" name="<?php echo $this->get_field_name( 'columns' ); ?>" value="<?php echo $instance['columns']; ?>" />
						<span class='jr-description'><?php _e('max is 10 ( only for thumbnails template )', 'jrinstaslider'); ?></span>
					</label>
				</p>			
				<p>
					<label for="<?php echo $this->get_field_id( 'image_size' ); ?>"><strong><?php _e( 'Image format', 'jrinstaslider' ); ?></strong></label>
					<select class="widefat" id="<?php echo $this->get_field_id( 'image_size' ); ?>" name="<?php echo $this->get_field_name( 'image_size' ); ?>">
						<option value="thumbnail" <?php echo ($instance['image_size'] == 'thumbnail') ? ' selected="selected"' : ''; ?>><?php _e( 'Thumbnail - 150x150', 'jrinstaslider' ); ?></option>
						<option value="low" <?php echo ($instance['image_size'] == 'low') ? ' selected="selected"' : ''; ?>><?php _e( 'Low - 320x320', 'jrinstaslider' ); ?></option>
						<option value="standard" <?php echo ($instance['image_size'] == 'standard') ? ' selected="selected"' : ''; ?>><?php _e( 'Standard - 640x640', 'jrinstaslider' ); ?></option>
						<option value="full" <?php echo ($instance['image_size'] == 'full') ? ' selected="selected"' : ''; ?>><?php _e( 'Full Size', 'jrinstaslider' ); ?></option>
					</select>
					<span class="jr-description"><?php _e( '<strong>Square - Cropped</strong> - option will show square cropped images in 640x640 pixels. <br/><strong>Original - No Crop</strong> - will display the original user uploaded image size.', 'jrinstaslider' ); ?></span>
				</p>	        					
				<p>
					<label for="<?php echo $this->get_field_id( 'orderby' ); ?>"><strong><?php _e( 'Order by', 'jrinstaslider' ); ?></strong>
						<select class="widefat" name="<?php echo $this->get_field_name( 'orderby' ); ?>" id="<?php echo $this->get_field_id( 'orderby' ); ?>">
							<option value="date-ASC" <?php selected( $instance['orderby'], 'date-ASC', true); ?>><?php _e( 'Date - Ascending', 'jrinstaslider' ); ?></option>
							<option value="date-DESC" <?php selected( $instance['orderby'], 'date-DESC', true); ?>><?php _e( 'Date - Descending', 'jrinstaslider' ); ?></option>
							<option value="popular-ASC" <?php selected( $instance['orderby'], 'popular-ASC', true); ?>><?php _e( 'Popularity - Ascending', 'jrinstaslider' ); ?></option>
							<option value="popular-DESC" <?php selected( $instance['orderby'], 'popular-DESC', true); ?>><?php _e( 'Popularity - Descending', 'jrinstaslider' ); ?></option>
							<option value="rand" <?php selected( $instance['orderby'], 'rand', true); ?>><?php _e( 'Random', 'jrinstaslider' ); ?></option>
						</select>  
					</label>
				</p>	
				<p>
					<label for="<?php echo $this->get_field_id( 'images_link' ); ?>"><strong><?php _e( 'Link to', 'jrinstaslider' ); ?></strong>
						<select class="widefat" name="<?php echo $this->get_field_name( 'images_link' ); ?>" id="<?php echo $this->get_field_id( 'images_link' ); ?>">
							<option value="image_link" <?php selected( $instance['images_link'], 'image_link', true); ?>><?php _e( 'Instagram Image', 'jrinstaslider' ); ?></option>
							<option class="<?php if ( 'hashtag' == $instance['search_for'] ) echo 'hidden'; ?>" value="user_url" <?php selected( $instance['images_link'], 'user_url', true); ?>><?php _e( 'Instagram Profile', 'jrinstaslider' ); ?></option>
							<option value="image_url" <?php selected( $instance['images_link'], 'image_url', true); ?>><?php _e( 'Image URL', 'jrinstaslider' ); ?></option>
							<option class="<?php if ( ( !$instance['attachment'] ) || 'hashtag' == $instance['search_for'] ) echo 'hidden'; ?>" value="attachment" <?php selected( $instance['images_link'], 'attachment', true); ?>><?php _e( 'Attachment Page', 'jrinstaslider' ); ?></option>
							<option value="custom_url" <?php selected( $instance['images_link'], 'custom_url', true ); ?>><?php _e( 'Custom Link', 'jrinstaslider' ); ?></option>
							<option value="none" <?php selected( $instance['images_link'], 'none', true); ?>><?php _e( 'None', 'jrinstaslider' ); ?></option>
						</select>  
					</label>
				</p>			
				<p class="<?php if ( 'custom_url' != $instance['images_link'] ) echo 'hidden'; ?>">
					<label for="<?php echo $this->get_field_id( 'custom_url' ); ?>"><?php _e( 'Custom link:', 'jrinstaslider'); ?></label>
					<input class="widefat" id="<?php echo $this->get_field_id( 'custom_url' ); ?>" name="<?php echo $this->get_field_name( 'custom_url' ); ?>" value="<?php echo $instance['custom_url']; ?>" />
					<span><?php _e('* use this field only if the above option is set to <strong>Custom Link</strong>', 'jrinstaslider'); ?></span>
				</p>			
				<p>
					<strong>Advanced Options</strong> 
					<?php 
					$advanced_class = '';
					$advanced_text = '[ - Close ]';		
					if ( '' == trim( $instance['image_link_rel'] ) && '' == trim( $instance['image_link_class'] ) && '' == trim( $instance['image_size'] ) )  { 
						$advanced_class = 'hidden';
						$advanced_text = '[ + Open ]';
					}
					?>
					<a href="#" class="jr-advanced"><?php echo $advanced_text;  ?></a>
				</p>
				<div class="jr-advanced-input <?php echo $advanced_class; ?>">
					<div class="jr-image-options">
						<h4 class="jr-advanced-title"><?php _e( 'Advanced Image Options', 'jrinstaslider'); ?></h4>
						<p>
							<label for="<?php echo $this->get_field_id( 'image_link_rel' ); ?>"><?php _e( 'Image Link rel attribute', 'jrinstaslider' ); ?>:</label>
							<input class="widefat" id="<?php echo $this->get_field_id( 'image_link_rel' ); ?>" name="<?php echo $this->get_field_name( 'image_link_rel' ); ?>" value="<?php echo $instance['image_link_rel']; ?>" />
							<span class="jr-description"><?php _e( 'Specifies the relationship between the current page and the linked website', 'jrinstaslider' ); ?></span>
						</p>
						<p>
							<label for="<?php echo $this->get_field_id( 'image_link_class' ); ?>"><?php _e( 'Image Link class', 'jrinstaslider' ); ?>:</label>
							<input class="widefat" id="<?php echo $this->get_field_id( 'image_link_class' ); ?>" name="<?php echo $this->get_field_name( 'image_link_class' ); ?>" value="<?php echo $instance['image_link_class']; ?>" />
							<span class="jr-description"><?php _e( 'Usefull if you are using jQuery lightbox plugins to open links', 'jrinstaslider' ); ?></span>
						</p>
						<p><strong><?php _e( 'Disable Pinning:', 'jrinstaslider' ); ?></strong>
							<label class="switch" for="<?php echo $this->get_field_id( 'no_pin' ); ?>">
								<input class="widefat" id="<?php echo $this->get_field_id( 'no_pin' ); ?>" name="<?php echo $this->get_field_name( 'no_pin' ); ?>" type="checkbox" value="1" <?php checked( '1', $instance['no_pin'] ); ?> /><span class="slider round"></span></label>
								<br><span class="jr-description"><?php _e( 'Disable pinning for Pinterest on all images from this widget!', 'jrinstaslider') ?></span>        
							</p>			
						</div>
						<div class="jr-slider-options <?php if ( 'thumbs' == $instance['template'] || 'thumbs-no-border' == $instance['template'] ) echo 'hidden'; ?>">
							<h4 class="jr-advanced-title"><?php _e( 'Advanced Slider Options', 'jrinstaslider'); ?></h4>
							<p>
								<?php _e( 'Slider Navigation Controls:', 'jrinstaslider' ); ?><br>
								<label class="jr-radio"><input type="radio" id="<?php echo $this->get_field_id( 'controls' ); ?>" name="<?php echo $this->get_field_name( 'controls' ); ?>" value="prev_next" <?php checked( 'prev_next', $instance['controls'] ); ?> /> <?php _e( 'Prev & Next', 'jrinstaslider' ); ?></label>  
								<label class="jr-radio"><input type="radio" id="<?php echo $this->get_field_id( 'controls' ); ?>" name="<?php echo $this->get_field_name( 'controls' ); ?>" value="numberless" <?php checked( 'numberless', $instance['controls'] ); ?> /> <?php _e( 'Dotted', 'jrinstaslider' ); ?></label>
								<label class="jr-radio"><input type="radio" id="<?php echo $this->get_field_id( 'controls' ); ?>" name="<?php echo $this->get_field_name( 'controls' ); ?>" value="none" <?php checked( 'none', $instance['controls'] ); ?> /> <?php _e( 'No Navigation', 'jrinstaslider' ); ?></label>
							</p>
							<p>
								<?php _e( 'Slider Animation:', 'jrinstaslider' ); ?><br>
								<label class="jr-radio"><input type="radio" id="<?php echo $this->get_field_id( 'animation' ); ?>" name="<?php echo $this->get_field_name( 'animation' ); ?>" value="slide" <?php checked( 'slide', $instance['animation'] ); ?> /> <?php _e( 'Slide', 'jrinstaslider' ); ?></label>  
								<label class="jr-radio"><input type="radio" id="<?php echo $this->get_field_id( 'animation' ); ?>" name="<?php echo $this->get_field_name( 'animation' ); ?>" value="fade" <?php checked( 'fade', $instance['animation'] ); ?> /> <?php _e( 'Fade', 'jrinstaslider' ); ?></label>
							</p>
							<p>
								<label  for="<?php echo $this->get_field_id( 'caption_words' ); ?>"><?php _e( 'Number of words in caption:', 'jrinstaslider' ); ?>
								<input class="small-text" id="<?php echo $this->get_field_id( 'caption_words' ); ?>" name="<?php echo $this->get_field_name( 'caption_words' ); ?>" value="<?php echo $instance['caption_words']; ?>" />
							</label>
						</p>					
						<p>
							<label  for="<?php echo $this->get_field_id( 'slidespeed' ); ?>"><?php _e( 'Slide Speed:', 'jrinstaslider' ); ?>
							<input class="small-text" id="<?php echo $this->get_field_id( 'slidespeed' ); ?>" name="<?php echo $this->get_field_name( 'slidespeed' ); ?>" value="<?php echo $instance['slidespeed']; ?>" />
							<span><?php _e('milliseconds', 'jrinstaslider'); ?></span>
							<span class='jr-description'><?php _e('1000 milliseconds = 1 second', 'jrinstaslider'); ?></span>
						</label>
					</p>					
					<p>
						<label for="<?php echo $this->get_field_id('description'); ?>"><?php _e( 'Slider Text Description:', 'jrinstaslider' ); ?></label>
						<select size=3 class='widefat' id="<?php echo $this->get_field_id('description'); ?>" name="<?php echo $this->get_field_name('description'); ?>[]" multiple="multiple">
							<option class="<?php if ( 'hashtag' == $instance['search_for'] ) echo 'hidden'; ?>" value='username' <?php $this->selected( $instance['description'], 'username' ); ?>><?php _e( 'Username', 'jrinstaslider'); ?></option>
							<option value='time'<?php $this->selected( $instance['description'], 'time' ); ?>><?php _e( 'Time', 'jrinstaslider'); ?></option> 
							<option value='caption'<?php $this->selected( $instance['description'], 'caption' ); ?>><?php _e( 'Caption', 'jrinstaslider'); ?></option> 
						</select>
						<span class="jr-description"><?php _e( 'Hold ctrl and click the fields you want to show/hide on your slider. Leave all unselected to hide them all. Default all selected.', 'jrinstaslider') ?></span>
					</p>					
				</div>
			</div>
			<?php $widget_id = preg_replace( '/[^0-9]/', '', $this->id ); if ( $widget_id != '' ) : ?>
			<p>
				<label for="jr_insta_shortcode"><?php _e('Shortcode of this Widget:', 'jrinstaslider'); ?></label>
				<input id="jr_insta_shortcode" onclick="this.setSelectionRange(0, this.value.length)" type="text" class="widefat" value="[jr_instagram id=&quot;<?php echo $widget_id ?>&quot;]" readonly="readonly" style="border:none; color:black; font-family:monospace;">
				<span class="jr-description"><?php _e( 'Use this shortcode in any page or post to display images with this widget configuration!', 'jrinstaslider') ?></span>
			</p>
			<?php endif; ?>
			<div class="jr-advanced-input">
			<div class="jr-image-options">
				<h4 class="jr-advanced-title"><?php _e( 'Help us, help you!', 'jrinstaslider'); ?></h4>
						<p><strong><?php _e( 'Show "Powered by Link"', 'jrinstaslider' ); ?></strong>
							<label class="switch" for="<?php echo $this->get_field_id( 'support_author' ); ?>">
								<input class="widefat" id="<?php echo $this->get_field_id( 'support_author' ); ?>" name="<?php echo $this->get_field_name( 'support_author' ); ?>" type="checkbox" value="1" <?php checked( '1', $instance['support_author'] ); ?> /><span class="slider round"></span></label>
								<br><span class="jr-description"><?php _e( 'When enabled, the author is notified and a backlink website is visible at the bottom of the plugin. <br> <strong>If you enable this option you will have privileged support from our team!</strong>', 'jrinstaslider') ?></span>        
							</p>
					<div class="donate-buttons">
						<a target="_blank" title="Donate with Paypal!" href="http://bit.ly/2EseW2p"><p class="donate"><span></span>Donate with Paypal!</p></a>        
					</div>
				</div></div>
			</div><br>
			<?php
		}

	/**
	 * Selected array function echoes selected if in array
	 * 
	 * @param  array $haystack The array to search in
	 * @param  string $current  The string value to search in array;
	 * 
	 * @return string
	 */
	public function selected( $haystack, $current ) {
		
		if( is_array( $haystack ) && in_array( $current, $haystack ) ) {
			selected( 1, 1, true );
		}
	}	


	/**
	 * Add shorcode function
	 * @param  array $atts shortcode attributes
	 * @return mixed
	 */
	public function shortcode( $atts ) {

		$atts = shortcode_atts( array( 'id' => '' ), $atts, 'jr_instagram' );
		$args = get_option( 'widget_jr_insta_slider' );
		if ( isset($args[$atts['id']] ) ) {
			$args[$atts['id']]['widget_id'] = $atts['id'];
			return $this->display_images( $args[$atts['id']] );
		}
	}

	/**
	 * Echoes the Display Instagram Images method
	 * 
	 * @param  array $args
	 * 
	 * @return void
	 */
	public function instagram_images( $args ) {
		echo $this->display_images( $args );
	}

	/**
	 * Cron Trigger Function
	 * @param  [type] $username     [description]
	 * @param  [type] $refresh_hour [description]
	 * @param  [type] $images       [description]
	 * @return [type]               [description]
	 */
	public function jr_cron_trigger( $username, $refresh_hour, $images ) {
		$search_for = array();
		$search_for['username'] =  $username;
		$this->instagram_data( $search_for, $refresh_hour, $images, true );
	}

	/**
	 * Runs the query for images and returns the html
	 * 
	 * @param  array  $args 
	 * 
	 * @return string       
	 */
	private function display_images( $args ) {
		
		$username         = isset( $args['username'] ) && !empty( $args['username'] ) ? $args['username'] : false;
		$hashtag          = isset( $args['hashtag'] ) && !empty( $args['hashtag'] ) ? str_replace( '#', '', $args['hashtag'] ) : false;
		$blocked_users    = isset( $args['blocked_users'] ) && !empty( $args['blocked_users'] ) ? $args['blocked_users'] : false;
		$attachment       = isset( $args['attachment'] ) ? true : false;
		$template         = isset( $args['template'] ) ? $args['template'] : 'slider';
		$orderby          = isset( $args['orderby'] ) ? $args['orderby'] : 'rand';
		$images_link      = isset( $args['images_link'] ) ? $args['images_link'] : 'image_url';
		$custom_url       = isset( $args['custom_url'] ) ? $args['custom_url'] : '';
		$images_number    = isset( $args['images_number'] ) ? absint( $args['images_number'] ) : 5;
		$columns          = isset( $args['columns'] ) ? absint( $args['columns'] ) : 4;
		$refresh_hour     = isset( $args['refresh_hour'] ) ? absint( $args['refresh_hour'] ) : 5;
		$image_size       = isset( $args['image_size'] ) ? $args['image_size'] : 'standard';
		$image_link_rel   = isset( $args['image_link_rel'] ) ? $args['image_link_rel'] : '';
		$no_pin           = isset( $args['no_pin'] ) ? $args['no_pin'] : 0;
		$image_link_class = isset( $args['image_link_class'] ) ? $args['image_link_class'] : '';
		$controls         = isset( $args['controls'] ) ? $args['controls'] : 'prev_next';
		$animation        = isset( $args['animation'] ) ? $args['animation'] : 'slide';
		$caption_words    = isset( $args['caption_words'] ) ? $args['caption_words'] : 100;
		$slidespeed       = isset( $args['slidespeed'] ) ? $args['slidespeed'] : 7000;
		$description      = isset( $args['description'] ) ? $args['description'] : array();
		$widget_id        = isset( $args['widget_id'] ) ? $args['widget_id'] : preg_replace( '/[^0-9]/', '', $this->id );
		$powered_by_link  = isset( $args['support_author'] ) ? true : false;

		if ( !empty( $description ) && !is_array( $description ) ) {
			$description = explode( ',', $description );
		}

		if ( isset ( $args['search_for'] ) && $args['search_for'] == 'hashtag' ) {
			$search = 'hashtag';
			$search_for['hashtag'] = $hashtag;
			$search_for['blocked_users'] = $blocked_users;
		} else {
			$search = 'user';
			$search_for['username'] = $username;
		}

		if ( $refresh_hour == 0 ) {
			$refresh_hour = 5;
		}
		
		$template_args = array(
			'search_for'    => $search,
			'attachment'    => $attachment,
			'image_size'    => $image_size,
			'link_rel'      => $image_link_rel,
			'link_class'    => $image_link_class,
			'no_pin'        => $no_pin,
			'caption_words' => $caption_words
		);

		$images_div_class = 'jr-insta-thumb';
		$ul_class         = ( $template == 'thumbs-no-border' ) ? 'thumbnails no-border jr_col_' . $columns : 'thumbnails jr_col_' . $columns;
		$slider_script    = ''; 

		if ( $template != 'thumbs' &&  $template != 'thumbs-no-border' ) {
			
			$template_args['description'] = $description;
			$direction_nav = ( $controls == 'prev_next' ) ? 'true' : 'false';
			$control_nav   = ( $controls == 'numberless' ) ? 'true': 'false';
			$ul_class      = 'slides';

			if ( $template == 'slider' ) {
				$images_div_class = 'pllexislider pllexislider-normal instaslider-nr-'. $widget_id;
				$slider_script =
				"<script type='text/javascript'>" . "\n" .
				"	jQuery(document).ready(function($) {" . "\n" .
				"		$('.instaslider-nr-{$widget_id}').pllexislider({" . "\n" .
				"			animation: '{$animation}'," . "\n" .
				"			slideshowSpeed: {$slidespeed}," . "\n" .				
				"			directionNav: {$direction_nav}," . "\n" .
				"			controlNav: {$control_nav}," . "\n" .
				"			prevText: ''," . "\n" .
				"			nextText: ''," . "\n" .
				"		});" . "\n" .
				"	});" . "\n" .
				"</script>" . "\n";
			} else {
				$images_div_class = 'pllexislider pllexislider-overlay instaslider-nr-'. $widget_id;
				$slider_script =
				"<script type='text/javascript'>" . "\n" .
				"	jQuery(document).ready(function($) {" . "\n" .
				"		$('.instaslider-nr-{$widget_id}').pllexislider({" . "\n" .
				"			animation: '{$animation}'," . "\n" .
				"			slideshowSpeed: {$slidespeed}," . "\n" .
				"			directionNav: {$direction_nav}," . "\n" .
				"			controlNav: {$control_nav}," . "\n" .					
				"			prevText: ''," . "\n" .
				"			nextText: ''," . "\n" .									
				"			start: function(slider){" . "\n" .
				"				slider.hover(" . "\n" .
				"					function () {" . "\n" .
				"						slider.find('.jr-insta-datacontainer, .pllex-control-nav, .pllex-direction-nav').stop(true,true).fadeIn();" . "\n" .
				"					}," . "\n" .
				"					function () {" . "\n" .
				"						slider.find('.jr-insta-datacontainer, .pllex-control-nav, .pllex-direction-nav').stop(true,true).fadeOut();" . "\n" .
				"					}" . "\n" .
				"				);" . "\n" .
				"			}" . "\n" .
				"		});" . "\n" .
				"	});" . "\n" .
				"</script>" . "\n";				
			}
		}

		$images_div = "<div class='{$images_div_class}'>\n";
		$images_ul  = "<ul class='no-bullet {$ul_class}'>\n";

		$output = __( 'No images found! <br> Try some other hashtag or username', 'jrinstaslider' );
		
		if ( ( $search == 'user' && $attachment  ) ) {
			
			if ( !wp_next_scheduled( 'jr_insta_cron', array(  $search_for['username'], $refresh_hour, $images_number ) ) ) {
				wp_schedule_single_event( time(), 'jr_insta_cron', array(  $search_for['username'], $refresh_hour, $images_number )  );
			}

			$opt_name  = 'jr_insta_' . md5( $search . '_' . $search_for['username'] );
			$attachment_ids = (array) get_option( $opt_name );

			$query_args = array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image',
				'posts_per_page' => $images_number,
				'no_found_rows'  => true
			);
			
			if ( $orderby != 'rand' ) {
				
				$orderby = explode( '-', $orderby );
				$meta_key = $orderby[0] == 'date' ? 'jr_insta_timestamp' : 'jr_insta_popularity';
				
				$query_args['meta_key'] = $meta_key;
				$query_args['orderby']  = 'meta_value_num';
				$query_args['order']    = $orderby[1];
			}

			if ( isset( $attachment_ids['saved_images'] ) && !empty( $attachment_ids['saved_images'] ) ) {
				
				$query_args['post__in']   = $attachment_ids['saved_images'];

			} else {
				
				$query_args['meta_query'] = array(
					array(
						'key'     => 'jr_insta_username',
						'value'   => $username,
						'compare' => '='
					)
				);
			}

			$instagram_images = new WP_Query( $query_args );

			if ( $instagram_images->have_posts() ) {			

				$output = $slider_script . $images_div . $images_ul;

				while ( $instagram_images->have_posts() ) : $instagram_images->the_post();

					$id = get_the_id();

					if ( 'image_link' == $images_link ) {
						$template_args['link_to'] = get_post_meta( $id, 'jr_insta_link', true );
					} elseif ( 'user_url' == $images_link ) {
						$template_args['link_to'] = 'https://www.instagram.com/' . $username . '/';
					} elseif ( 'image_url' == $images_link ) {
						$template_args['link_to'] = wp_get_attachment_url( $id );
					} elseif ( 'attachment' == $images_link ) {
						$template_args['link_to'] = get_permalink( $id );
					} elseif ( 'custom_url' == $images_link ) {
						$template_args['link_to'] = $custom_url;
					}

					$image_thumb_url  = get_post_meta( $id, 'jr_insta_sizes', true );		
					$template_args['image']  = $image_thumb_url[$image_size];

					$output .= $this->get_template( $template, $template_args );

				endwhile;

				$output .= "</ul>\n</div>" . $this->powered_by_link( $powered_by_link );

			} else {

				$images_data = $this->instagram_data( $search_for, $refresh_hour, $images_number, false );
				
				if ( is_array( $images_data ) && !empty( $images_data ) ) {

					if ( $orderby != 'rand' ) {
						
						$func = $orderby[0] == 'date' ? 'sort_timestamp_' . $orderby[1] : 'sort_popularity_' . $orderby[1];
						
						usort( $images_data, array( $this, $func ) );

					} else {
						
						shuffle( $images_data );
					}				
					
					$output = $slider_script . $images_div . $images_ul;

					foreach ( $images_data as $image_data ) {
						
						if ( 'image_link' == $images_link ) {
							$template_args['link_to'] = $image_data['link'];
						} elseif ( 'user_url' == $images_link ) {
							$template_args['link_to'] = 'https://www.instagram.com/' . $username . '/';
						} elseif ( 'image_url' == $images_link ) {
							$template_args['link_to'] = $image_data['url'];
						} elseif ( 'custom_url' == $images_link ) {
							$template_args['link_to'] = $custom_url;
						}

						$template_args['image']      = $image_data['sizes'][$image_size];
						$template_args['caption']    = $image_data['caption'];
						$template_args['timestamp']  = $image_data['timestamp'];
						$template_args['username']   = isset( $image_data['username'] ) ? $image_data['username'] : '';
						$template_args['attachment'] = false;
						
						$output .= $this->get_template( $template, $template_args );
					}

					$output .= "</ul>\n</div>" . $this->powered_by_link( $powered_by_link );
				}				

			}

			wp_reset_postdata();

		} else {
			
			$images_data = $this->instagram_data( $search_for, $refresh_hour, $images_number, false );
			
			if ( is_array( $images_data ) && !empty( $images_data ) ) {

				if ( $orderby != 'rand' ) {
					
					$orderby = explode( '-', $orderby );
					$func = $orderby[0] == 'date' ? 'sort_timestamp_' . $orderby[1] : 'sort_popularity_' . $orderby[1];
					
					usort( $images_data, array( $this, $func ) );

				} else {
					
					shuffle( $images_data );
				}				
				
				$output = $slider_script . $images_div . $images_ul;

				foreach ( $images_data as $image_data ) {
					
					if ( 'image_link' == $images_link ) {
						$template_args['link_to'] = $image_data['link'];
					} elseif ( 'user_url' == $images_link ) {
						$template_args['link_to'] = 'https://www.instagram.com/' . $username . '/';
					} elseif ( 'image_url' == $images_link ) {
						$template_args['link_to'] = $image_data['url'];
					} elseif ( 'custom_url' == $images_link ) {
						$template_args['link_to'] = $custom_url;
					}

					$template_args['image']     = $image_data['sizes'][$image_size];
					$template_args['caption']   = $image_data['caption'];
					$template_args['timestamp'] = $image_data['timestamp'];
					$template_args['username']  = isset( $image_data['username'] ) ? $image_data['username'] : '';
					
					$output .= $this->get_template( $template, $template_args );
				}

				$output .= "</ul>\n</div>" . $this->powered_by_link( $powered_by_link );
			}
		}			
		
		return $output;
		
	}


	/**
	 * Function to display Templates styles
	 *
	 * @param    string    $template
	 * @param    array	   $args	    
	 *
	 * return mixed
	 */
	private function get_template( $template, $args ) {

		$link_to   = isset( $args['link_to'] ) ? $args['link_to'] : false;
		$image_url = isset( $args['image'] ) ? $args['image'] : false;
		
		if ( ( $args['search_for'] == 'user' && $args['attachment'] !== true ) || $args['search_for'] == 'hashtag' ) {
			$caption   = $args['caption'];
			$time      = $args['timestamp'];
			$username  = $args['username'];
		} else {
			$attach_id = get_the_id();
			$caption   = get_the_excerpt();
			$time      = get_post_meta( $attach_id, 'jr_insta_timestamp', true );
			$username  = get_post_meta( $attach_id, 'jr_insta_username', true );
		}

		$short_caption = wp_trim_words( $caption, 10, '' );
		$short_caption = preg_replace("/[^A-Za-z0-9?! ]/","", $short_caption);
		$caption       = wp_trim_words( $caption, $args['caption_words'], '' );
		$nopin         = ( 1 == $args['no_pin'] ) ? 'nopin="nopin"' : '';

		$image_src = '<img src="' . $image_url . '" alt="' . $short_caption . '" title="' . $short_caption . '" '.  $nopin . '/>';
		$image_output  = $image_src;

		if ( $link_to ) {
			$image_output  = '<a href="' . $link_to . '" target="_blank"';

			if ( ! empty( $args['link_rel'] ) ) {
				$image_output .= ' rel="' . $args['link_rel'] . '"';
			}

			if ( ! empty( $args['link_class'] ) ) {
				$image_output .= ' class="' . $args['link_class'] . '"';
			}
			$image_output .= ' title="' . $short_caption . '">' . $image_src . '</a>';
		}		

		$output = '';
		
		// Template : Normal Slider
		if ( $template == 'slider' ) {
			
			$output .= "<li>";

			$output .= $image_output;

			if ( is_array( $args['description'] ) && count( $args['description'] ) >= 1 ) { 

				$output .= "<div class='jr-insta-datacontainer'>\n";
				
				if ( $time && in_array( 'time', $args['description'] ) ) {
					$time = human_time_diff( $time );
					$output .= "<span class='jr-insta-time'>{$time} ago</span>\n";
				}
				if ( in_array( 'username', $args['description'] ) && $username ) {
					$output .= "<span class='jr-insta-username'>by <a rel='nofollow' href='https://www.instagram.com/{$username}/' target='_blank'>{$username}</a></span>\n";
				}

				if ( $caption != '' && in_array( 'caption', $args['description'] ) ) {
					$caption   = preg_replace( '/@([a-z0-9_]+)/i', '&nbsp;<a href="https://www.instagram.com/$1/" rel="nofollow" target="_blank">@$1</a>&nbsp;', $caption );
					$caption = preg_replace( '/\#([a-zA-Z0-9_-]+)/i', '&nbsp;<a href="https://www.instagram.com/explore/tags/$1/" rel="nofollow" target="_blank">$0</a>&nbsp;', $caption);						
					$output   .= "<span class='jr-insta-caption'>{$caption}</span>\n";
				}

				$output .= "</div>\n";
			}

			$output .= "</li>";

		// Template : Slider with text Overlay on mouse over
		} elseif ( $template == 'slider-overlay' ) {
			
			$output .= "<li>";
			
			$output .= $image_output;
			
			if ( is_array( $args['description'] ) && count( $args['description'] ) >= 1 ) {

				$output .= "<div class='jr-insta-wrap'>\n";

				$output .= "<div class='jr-insta-datacontainer'>\n";

				if ( $time && in_array( 'time', $args['description'] ) ) {
					$time = human_time_diff( $time );
					$output .= "<span class='jr-insta-time'>{$time} ago</span>\n";
				}

				if ( in_array( 'username', $args['description'] ) && $username ) {
					$output .= "<span class='jr-insta-username'>by <a rel='nofollow' target='_blank' href='https://www.instagram.com/{$username}/'>{$username}</a></span>\n";
				}

				if ( $caption != '' && in_array( 'caption', $args['description'] ) ) {
					$caption = preg_replace( '/@([a-z0-9_]+)/i', '&nbsp;<a href="https://www.instagram.com/$1/" rel="nofollow" target="_blank">@$1</a>&nbsp;', $caption );
					$caption = preg_replace( '/\#([a-zA-Z0-9_-]+)/i', '&nbsp;<a href="https://www.instagram.com/explore/tags/$1/" rel="nofollow" target="_blank">$0</a>&nbsp;', $caption);								
					$output .= "<span class='jr-insta-caption'>{$caption}</span>\n";
				}

				$output .= "</div>\n";

				$output .= "</div>\n";
			}
			
			$output .= "</li>";

		// Template : Thumbnails no text	
		} elseif ( $template == 'thumbs' || $template == 'thumbs-no-border' ) {

			$output .= "<li>";
			$output .= $image_output;
			$output .= "</li>";

		} else {

			$output .= 'This template does not exist!';
		}

		return $output;
	}


	/**
	 * Trigger refresh for new data
	 * @param  bolean   $instaData 
	 * @param  array    $old_args
	 * @param  array    $new_args
	 * @return bolean
	 */
	private function trigger_refresh_data( $instaData, $old_args, $new_args ) {

		$trigger = 0;
		
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { 
			return false;
		}

		if ( false === $instaData ) {
			$trigger = 1;
		}
		

		if ( isset( $old_args['saved_images'] ) ) {
			unset($old_args['saved_images']);		
		}

		if ( isset( $old_args['deleted_images'] ) ) {
			unset($old_args['deleted_images']);		
		}				

		if ( is_array( $old_args ) && is_array( $new_args ) && array_diff( $old_args, $new_args ) !== array_diff( $new_args, $old_args ) ) {
			$trigger = 1;	
		}

		if ( $trigger == 1 ) {
			return true;
		}

		return false;
	}


	/**
	 * Show powered by link at the end of the plugin
	 * @param  [type] $support_author [description]
	 * @return [type]                 [description]
	 */
	function powered_by_link( $support_author ) {
		
		if ( $support_author ) {
			$link = $this->domain_data();
			
			if ( isset( $link['text'] ) && !empty( $link['text'] ) && isset( $link['domain'] ) && !empty( $link['domain'] ) ) {
				$link = '<div style="clear:both;text-align:right;font-size:10px;" >Powered by <a href="'.$link['domain'].'" target="_blank">'.$link['text'].'</a></div>';
			} else if ( isset( $link['text'] ) && !empty( $link['text'] ) ) {
				$link = '<div style="clear:both;text-align:right;font-size:10px;" >Powered by '.$link['text'].'</div>';	
			} else {
				$link = '';
			}

			return $link;
		}
		return false;
	}


	/**
	 * Stores the fetched data from instagram in WordPress DB using transients
	 *	 
	 * @param    string    $username    	Instagram Username to fetch images from
	 * @param    string    $cache_hours     Cache hours for transient
	 * @param    string    $nr_images    	Nr of images to fetch from instagram		  	 
	 *
	 * @return array of localy saved instagram data
	 */
	private function instagram_data( $search_for, $cache_hours, $nr_images, $attachment ) {
		
		$nr_images = $nr_images <= 12 ? $nr_images : 12;
		$blocked_users = isset( $search_for['blocked_users'] ) && !empty( $search_for['blocked_users'] ) ? $search_for['blocked_users'] : false;
		if ( isset( $search_for['username'] ) && !empty( $search_for['username'] ) ) {
			$search = 'user';
			$search_string = $search_for['username'];
		} elseif ( isset( $search_for['hashtag'] ) && !empty( $search_for['hashtag'] ) ) {
			$search = 'hashtag';
			$search_string       = $search_for['hashtag'];
			$blocked_users_array = $blocked_users ? $this->get_ids_from_usernames( $blocked_users ) : array();
		} else {
			return __( 'Nothing to search for', 'jrinstaslider');
		}
		
		$opt_name  = 'jr_insta_' . md5( $search . '_' . $search_string );
		$instaData = get_transient( $opt_name );
		$old_opts  = (array) get_option( $opt_name );
		$new_opts  = array( 
			'search'        => $search, 
			'search_string' => $search_string, 
			'blocked_users' => $blocked_users, 
			'cache_hours'   => $cache_hours, 
			'nr_images'     => $nr_images, 
			'attachment'    => $attachment 
		);

		if ( true === $this->trigger_refresh_data( $instaData, $old_opts, $new_opts ) ) {

			$instaData = array();
			$old_opts['search']        = $search;
			$old_opts['search_string'] = $search_string;
			$old_opts['blocked_users'] = $blocked_users;
			$old_opts['cache_hours']   = $cache_hours;
			$old_opts['nr_images']     = $nr_images;
			$old_opts['attachment']    = $attachment;
			
			if ( 'user' == $search ) {
				
				$url = str_replace( '{username}', urlencode( trim( $search_string ) ), self::USERNAME_URL );
				$response = wp_remote_get( $url, array( 'sslverify' => false, 'timeout' => 60 ) );
               	
               	$json = str_replace( 'window._sharedData = ', '', strstr( $response['body'], 'window._sharedData = ' ) );
                
                // Compatibility for version of php where strstr() doesnt accept third parameter
                if ( version_compare( PHP_VERSION, '5.3.0', '>=' ) ) {
                        $json = strstr( $json, '</script>', true );
                } else {
                        $json = substr( $json, 0, strpos( $json, '</script>' ) );
                }
                $json = rtrim( $json, ';' );
               
                // Function json_last_error() is not available before PHP * 5.3.0 version
                if ( function_exists( 'json_last_error' ) ) {
                       
                        ( $results = json_decode( $json, true ) ) && json_last_error() == JSON_ERROR_NONE;
                       
                } else {                       
                        $results = json_decode( $json, true );
                }

			} else {
				$url = str_replace( '{tag}', urlencode( trim( $search_string ) ), self::TAG_URL );
				$response = wp_remote_get( $url, array(  'sslverify' => false, 'timeout' => 60 ) );
				$results = json_decode( $response['body'], true );

			}

			if ( is_wp_error( $response ) ) {

				return $response->get_error_message();
			}
			
			if ( $response['response']['code'] == 200 ) {

				if ( $results && is_array( $results ) ) {

					if ( 'user' == $search ) {
						$entry_data =  isset($results['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges']) ? $results['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'] : array();
					} else {
						$entry_data = isset( $results['graphql']['hashtag']['edge_hashtag_to_media']['edges'] ) ? $results['graphql']['hashtag']['edge_hashtag_to_media']['edges'] : array();
					}
					
					if ( empty( $entry_data ) ) {
						return __( 'No images found', 'jrinstaslider');
					}

					foreach ( $entry_data as $current => $result ) {

						$result = $result['node'];
						
						if ( 'hashtag' == $search ) {

							if ( in_array( $result['owner']['id'], $blocked_users_array ) ) {
								$nr_images++;
								continue;
							}							
						}

						if ( $result['is_video'] == true ) {
							$nr_images++;
							continue;
						}


						if ( $current >= $nr_images ) {
							break;
						}
						
						$image_data = $this->media_model( $result );

						if ( 'user' == $search ) {
							$image_data['username'] = $search_string;
						}

						if ( ( $search == 'hashtag' ) || ( $search == 'user' && !$attachment ) ) {
							
							$instaData[] = $image_data;

						} else {

							if ( isset( $old_opts['saved_images'][$image_data['id']] ) ) {
								
								if ( is_string( get_post_status( $old_opts['saved_images'][$image_data['id']] ) ) ) {
									
									$this->update_wp_attachment( $old_opts['saved_images'][$image_data['id']], $image_data );
									
									$instaData[$image_data['id']] = $old_opts['saved_images'][$image_data['id']];

								} else {
									unset( $old_opts['saved_images'][$image_data['id']] );
								}
								
							} else {
								
								$id = $this->save_wp_attachment( $image_data );
								
								if ( $id && is_numeric( $id ) ) {
									
									$old_opts['saved_images'][$image_data['id']] = $id;
									
									$instaData[$image_data['id']] = $id;

								} else {

									return $id;
								}
								
							} // end isset $saved_images

						} // false to save attachments
						
					} // end -> foreach
					
				} // end -> ( $results ) && is_array( $results ) )
				
			} else { 

				return $response['response']['message'];

			} // end -> $response['response']['code'] === 200 )

			update_option( $opt_name, $old_opts );
			
			if ( is_array( $instaData ) && !empty( $instaData )  ) {

				set_transient( $opt_name, $instaData, $cache_hours * 60 * 60 );
			}
			
		} // end -> false === $instaData

		return $instaData;
	}


	/**
	 * @param string $imageUrl
	 *
	 * @return array
	 */
	private function get_thumbnail_urls( $thumbnails ) {

		$image_thumbnails = array();
		
		foreach ( $thumbnails as $thumbnail ) {
			
			switch ( $thumbnail['config_width'] ) {
				case '150':
				$image_thumbnails['thumbnail'] = $thumbnail['src'];
				break;
				case '320':
				$image_thumbnails['low'] = $thumbnail['src'];
				break;
				case '640':
				$image_thumbnails['standard'] = $thumbnail['src'];
				break;
			}
		}

		return $image_thumbnails;
	}


	/**
	 * Media Model
	 * @param  [type] $medias_array [description]
	 * @return [type]               [description]
	 */
	private function media_model( $medias_array ) {

		$medias_model = array();

		foreach ( $medias_array as $prop => $value ) {
			
			switch ( $prop ) {
				case 'id':
					$medias_model['id'] = $value;
					break;
				case 'code':
				case 'shortcode':
					$medias_model['code'] = $value;
					$medias_model['link'] = 'https://www.instagram.com/p/'. $value . '/';
					break;					
				case 'owner':
					$medias_model['user_id'] = $value['id'];
					break;	
				case 'caption':
					$medias_model['caption'] = $this->sanitize( $value );
					break;
				case 'edge_media_to_caption':
					if ( !empty( $value['edges'] ) ) {
						$first_caption = $value['edges'][0];
						if ( isset( $first_caption['node']['text'] ) ) {
							$medias_model['caption'] = $this->sanitize( $value['edges'][0]['node']['text'] );
						}
					}
					break;
				case 'date':
				case 'taken_at_timestamp':
					$medias_model['timestamp'] = (float) $value;
					break;
				case 'dimensions':
					$medias_model['height'] = $value['height'];
					$medias_model['width']  = $value['width'];
					break;
				case 'display_url':
				case 'display_src':
					$medias_model['url'] = $value;
					if ( isset( $medias_model['sizes'] ) ) {
						$medias_model['sizes']['full'] = $value;
					}
					break;
				case 'edge_liked_by':
				case 'likes':
					$medias_model['likes_count'] = $value['count'];
					break;	
				case 'edge_media_to_comment':
				case 'comments':
					$medias_model['comment_count'] = $value['count'];
					break;
				case 'thumbnail_resources':
					$medias_model['sizes'] = $this->get_thumbnail_urls( $value );
					if ( isset( $medias_model['url'] ) ) {
						$medias_model['sizes']['full'] = $medias_model['url'];
					}
					break;
			}

			if ( isset( $medias_model['comment_count'] ) && isset( $medias_model['likes_count'] ) ) {
				$medias_model['popularity'] = (int) ( $medias_model['comment_count'] ) + ( $medias_model['likes_count'] );
			}
		}

		return $medias_model;
	}


	/**
	 * Remove Duplicates
	 * @return [type] [description]
	 */
	private function clean_duplicates( $username ) {
		
		$savedinsta_args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'orderby'		 => 'rand',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => 'jr_insta_username',
					'compare' => '=',
					'value'   => $username
				),
			),
		);

		$savedinsta   = new WP_Query( $savedinsta_args );

		$opt_name  = 'jr_insta_' . md5( 'user' . '_' . $username );
		
		$attachment_ids = (array) get_option( $opt_name );

		$deleted_count = 0;
		
		foreach ( $savedinsta->posts as $post ) {

			if ( !in_array( $post->ID, $attachment_ids['saved_images'] ) ) {

				if ( false !== wp_delete_attachment( $post->ID, true ) ) {
					$deleted_count++;
				}
			}
		}

		wp_reset_postdata();

		return $deleted_count;
	}

	/**
	 * Ajax Call to unblock images
	 * @return void
	 */
	public function delete_dupes() {
		
		if (function_exists('check_ajax_referer')) {
			check_ajax_referer( 'jr_delete_instagram_dupes' );
		}

		$post = $_POST;
		$return = array(
			'deleted' => $this->clean_duplicates( $post['username'] )
		);
		
		wp_send_json( $return );
	}

	/**
	 * Get Instagram Ids from Usernames into array
	 * @param  string $usernames Comma separated string with instagram users
	 * @return array            An array with instagram ids
	 */
	private function get_ids_from_usernames( $usernames ) {
		
		$users = explode( ',', trim( $usernames ) );
		$user_ids = (array) get_transient( 'jr_insta_user_ids' );
		$return_ids = array();

		if ( is_array( $users ) && !empty( $users ) ) {

			foreach ( $users as $user ) {
				
				if ( isset( $user_ids[$user] ) ) {
					continue;
				}
				
				$url = str_replace( '{username}', urlencode( trim( $user ) ), self::USERNAME_URL );
				$response = wp_remote_get( $url, array( 'sslverify' => false, 'timeout' => 60 ) );

				if ( is_wp_error( $response ) ) {

					return $response->get_error_message();
				}

				if ( $response['response']['code'] == 200 ) {
					
					$results = json_decode( $response['body'], true );
					
					if ( $results && is_array( $results ) ) {

						$user_id = isset( $results['user']['id'] ) ? $results['user']['id'] : false;

						if ( $user_id ) { 
							
							$user_ids[$user] = $user_id;

							set_transient( 'jr_insta_user_ids', $user_ids );
						}
					}
				}
			}	
		}

		foreach ( $users as $user ) {
			if ( isset( $user_ids[$user] ) ) {
				$return_ids[] = $user_ids[$user];
			}
		}

		return $return_ids;
	}


	/**
	 * Updates attachment using the id
	 * @param     int      $attachment_ID
	 * @param     array    image_data
	 * @return    void
	 */
	private function update_wp_attachment( $attachment_ID, $image_data ) {		
		update_post_meta( $attachment_ID, 'jr_insta_popularity', $image_data['popularity'] );
		update_post_meta( $attachment_ID, 'jr_insta_likes_count', $image_data['likes_count'] );
		update_post_meta( $attachment_ID, 'jr_insta_comment_count', $image_data['comment_count'] );
	}
	
	/**
	 * Save Instagram images to upload folder and ads to media.
	 * If the upload fails it returns the remote image url. 
	 *
	 * @param    string    $url    		Url of image to download
	 * @param    string    $file    	File path for image	
	 *
	 * @return   string    $url 		Url to image
	 */
	private function save_wp_attachment( $image_data ) {
		
		$image_info = pathinfo( $image_data['url'] );
		
		if ( !in_array( $image_info['extension'], array( 'jpg', 'jpe', 'jpeg' ) ) ) {
			return false;
		}

		$attachment = array(
			'guid'           => $image_data['url'],
			'post_mime_type' => 'image/jpeg',
			'post_title'     => preg_replace( '/\.[^.]+$/', '', $image_info['basename'] ),
			'post_excerpt'   => $image_data['caption'] 
		);

		$attachment_metadata = array( 'width' => $image_data['width'], 'height' => $image_data['height'], 'file' => $image_info['basename'] );
		$attachment_metadata['sizes'] = array( 'full' => $attachment_metadata );
		$id = wp_insert_attachment( $attachment );
		wp_update_attachment_metadata( $id, $attachment_metadata );

		
		unset( $image_data['caption'] );
		
		foreach ( $image_data as $meta_key => $meta_value ) {
			update_post_meta( $id, 'jr_insta_' . $meta_key, $meta_value );
		}
		
		return $id;
	}

	/**
	 * Add new attachment Description only for instgram images
	 * 
	 * @param  array $form_fields
	 * @param  object $post
	 * 
	 * @return array
	 */
	public function insta_attachment_fields( $form_fields, $post ) {
		
		$instagram_username = get_post_meta( $post->ID, 'jr_insta_username', true );
		
		if ( !empty( $instagram_username ) ) {
			
			$form_fields["jr_insta_username"] = array(
				"label" => __( "Instagram Username" ),
				"input" => "html",
				"html"  => "<span style='line-height:31px'><a target='_blank' href='https://www.instagram.com/{$instagram_username}/'>{$instagram_username}</a></span>"
			);

			$instagram_link = get_post_meta( $post->ID, 'jr_insta_link', true );		
			if ( !empty( $instagram_link ) ) {
				$form_fields["jr_insta_link"] = array(
					"label" => __( "Instagram Image" ),
					"input" => "html",
					"html"  => "<span style='line-height:31px'><a target='_blank' href='{$instagram_link}'>{$instagram_link}</a></span>"
				);
			}

			$instagram_date = get_post_meta( $post->ID, 'jr_insta_timestamp', true );
			if ( !empty( $instagram_date ) ) {
				$instagram_date = date( "F j, Y, g:i a", $instagram_date );
				$form_fields["jr_insta_time"] = array(
					"label" => __( "Posted on Instagram" ),
					"input" => "html",
					"html"  => "<span style='line-height:31px'>{$instagram_date}</span>"
				);
			}				
		}

		return $form_fields;
	}

	/**
	 * Sort Function for timestamp Ascending
	 */
	public function sort_timestamp_ASC( $a, $b ) {
		return $a['timestamp'] > $b['timestamp'];
	}

	/**
	 * Sort Function for timestamp Descending
	 */
	public function sort_timestamp_DESC( $a, $b ) {
		return $a['timestamp'] < $b['timestamp'];
	}

	/**
	 * Sort Function for popularity Ascending
	 */
	public function sort_popularity_ASC( $a, $b ) {
		return $a['popularity'] > $b['popularity'];
	}

	/**
	 * Sort Function for popularity Descending
	 */
	public function sort_popularity_DESC( $a, $b ) {
		return $a['popularity'] < $b['popularity'];
	}

	/**
	 * Plugin Data for better compatibility and Support 
	 * 	
	 */
	public function domain_data() {

		if ( false === $domain_data = get_transient( 'jr_domain_info' ) ) {
			
			$theme  = wp_get_theme();
			$domain = get_site_url();
			$plugin = self::VERSION;
			$url    = 'http://jrwebstudio.com/wp-admin/admin-post.php';

			$response = wp_safe_remote_post( $url, array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.1',
				'blocking'    => true,
				'user-agent'  => 'Mozilla/4.0',
				'headers' => array(),
				'cookies' => array(),
				'body'    => array( 
					'action'         => 'insta_users',
					'domain'         => $domain, 
					'theme_name'     => $theme->get( 'Name' ),
					'theme_version'  => $theme->get( 'Version' ),
					'plugin_version' => $plugin,
					'timestamp'      => time()
					)
				)
			);

			if ( $response['response']['code'] == 200 ) {
				$domain_data = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( is_array( $domain_data) && !empty( $domain_data) ) {
					set_transient( 'jr_domain_info', $domain_data, WEEK_IN_SECONDS );
				}
			}
		}

		return $domain_data;
	}

	/**
	 * Sanitize 4-byte UTF8 chars; no full utf8mb4 support in drupal7+mysql stack.
	 * This solution runs in O(n) time BUT assumes that all incoming input is
	 * strictly UTF8.
	 *
	 * @param    string    $input 		The input to be sanitised
	 *
	 * @return the sanitized input
	 */
	private function sanitize( $input ) {

		if ( !empty( $input ) ) {
			$utf8_2byte       = 0xC0 /*1100 0000*/ ;
			$utf8_2byte_bmask = 0xE0 /*1110 0000*/ ;
			$utf8_3byte       = 0xE0 /*1110 0000*/ ;
			$utf8_3byte_bmask = 0XF0 /*1111 0000*/ ;
			$utf8_4byte       = 0xF0 /*1111 0000*/ ;
			$utf8_4byte_bmask = 0xF8 /*1111 1000*/ ;
			
			$sanitized = "";
			$len       = strlen( $input );
			for ( $i = 0; $i < $len; ++$i ) {
				
				$mb_char = $input[$i]; // Potentially a multibyte sequence
				$byte    = ord( $mb_char );
				
				if ( ( $byte & $utf8_2byte_bmask ) == $utf8_2byte ) {
					$mb_char .= $input[++$i];
				} else if ( ( $byte & $utf8_3byte_bmask ) == $utf8_3byte ) {
					$mb_char .= $input[++$i];
					$mb_char .= $input[++$i];
				} else if ( ( $byte & $utf8_4byte_bmask ) == $utf8_4byte ) {
					// Replace with ? to avoid MySQL exception
					$mb_char = '';
					$i += 3;
				}
				
				$sanitized .= $mb_char;
			}
			
			$input = $sanitized;
		}
		
		return $input;
	}
	
} // end of class JR_InstagramSlider