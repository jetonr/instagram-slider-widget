<?php
/*
Plugin Name: Instagram Slider Widget
Plugin URI: http://jrwebstudio.com/instagram-slider/
Version: 1.1.4
Description: Instagram Slider Widget is a responsive slider widget that shows 20 latest images from a public instagram user.
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
	const VERSION = '1.1.4';

	/**
	 * Errors html
	 * @var     string
	 */
	private $errors;


	/**
	 * Initialize the plugin by registering widget and loading public scripts
	 *
	 */
	public function __construct() {

		// Widget ID and Class Setup
		$widget_options = array(
			 'classname' => 'jr-insta-slider',
			'description' => __( 'A widget that displays a slider with instagram images ', 'jrinstaslider' )
		);
		parent::__construct( 'jr_insta_slider', __( 'Instagram Slider', 'jrinstaslider' ), $widget_options );

		add_image_size( 'jr_insta_small', 150 );
		add_image_size( 'jr_insta_medium', 300 );

		// Enqueue Plugin Styles and scripts
		add_action( 'wp_enqueue_scripts', array( $this,	'public_enqueue' ) );

		// Enqueue Plugin Styles and scripts for admin pages
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );

		add_action( 'jr_instagram_images', array( $this, 'instagram_images' ) );

		add_shortcode( 'jr_instagram_images', array( $this,	'shortcode' ) );

	}

	/**
	 * [instagram_images description]
	 * @param  [type] $args [description]
	 * @return [type]       [description]
	 */
	public function instagram_images( $args ) {
		echo $this->display_images( $args );
	}

	/**
	 * Register widget on windgets init
	 *
	 * @return void
	 */
	public static function register_widget() {
		register_widget( __CLASS__ );
	}

	/**
	 * Enqueue public-facing Scripts and style sheet.
	 *
	 * @return void
	 */
	public function public_enqueue() {

		wp_enqueue_style( 'instag-slider', plugins_url( 'assets/css/instag-slider.css', __FILE__ ), array(), self::VERSION );

		wp_enqueue_script( 'jquery-pllexi-slider', plugins_url( 'assets/js/jquery.flexslider-min.js', __FILE__ ), array( 'jquery' ), '2.2', false );
		wp_enqueue_script( 'jr-insta-public', plugins_url( 'assets/js/jr-insta-public.js', __FILE__ ), array( 'jquery' ), false, true );
	}

	/**
	 * Enqueue admin side scripts and styles
	 * @param  string $hook
	 * @return void
	 */
	public function admin_enqueue( $hook ) {

		if ( 'widgets.php' != $hook )
			return;

		wp_enqueue_script( 'jr-insta-admin-script', plugins_url( 'assets/js/jr-insta-admin.js', __FILE__ ), array( 'jquery' ), self::VERSION, true );

		wp_localize_script( 'jr-insta-admin-script', 'jr_insta_admin', ( true === is_admin_bar_showing() ? 'true' : 'false' ) );

		wp_enqueue_style( 'jr-insta-admin-styles', plugins_url( 'assets/css/jr-insta-admin.css', __FILE__ ), array ());
	}

	/**
	 * The Public view of the Widget
	 *
	 * @return mixed
	 */
	public function widget( $args, $instance ) {

		extract( $args );

		//Our variables from the widget settings.
		$title        = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;

		// Display the widget title
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		do_action( 'jr_instagram_images', $instance );

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
	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		$instance['title']         = strip_tags( $new_instance['title'] );
		$instance['username']      = $new_instance['username'];
		$instance['source']        = $new_instance['source'];
		$instance['template']      = $new_instance['template'];
		$instance['images_link']   = $new_instance['images_link'];
		$instance['custom_url']    = $new_instance['custom_url'];
		$instance['orderby']       = $new_instance['orderby'];
		$instance['images_number'] = absint($new_instance['images_number'] );
		$instance['refresh_hour']  = absint($new_instance['refresh_hour']);

		if ( $this->input_errors( $instance ) ) {

			add_action( 'jr_insta_error_messges', array( $this, 'display_errors' ) );

		} else {

			if ( $instance['source'] == 'instagram' ) {
				$this->instagram_data( $instance['username'], $instance['refresh_hour'], $instance['images_number'] );
			}

			return $instance;
		}
	}

	/**
	 * Widget Settings Form
	 *
	 * @return mixed
	 */
	public function form( $instance ) {

		$defaults = array(
			'title'         => __('Instagram Slider', 'jrinstaslider'),
			'username'      => '',
			'source'        => 'instagram',
			'template'      => 'slider',
			'images_link'   => 'image_url',
			'custom_url'    => '',
			'orderby'       => 'rand',
			'images_number' => 5,
			'refresh_hour'  => 5
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<div class="jr-container">

			<?php echo do_action( 'jr_insta_error_messges' ); ?>

			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'jrinstaslider'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'username' ); ?>"><?php _e('Instagram Username:', 'jrinstaslider'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" value="<?php echo $instance['username']; ?>" />
			</p>
			<p>
				<?php _e( 'Source:', 'jrinstaslider' ); ?><br>
				<label class="jr-radio"><input type="radio" id="<?php echo $this->get_field_id( 'source' ); ?>" name="<?php echo $this->get_field_name( 'source' ); ?>" value="instagram" <?php checked( 'instagram', $instance['source'] ); ?> /> <?php _e( 'Instagram', 'jrinstaslider' ); ?></label>
				<label class="jr-radio"><input type="radio" id="<?php echo $this->get_field_id( 'source' ); ?>" name="<?php echo $this->get_field_name( 'source' ); ?>" value="media_library" <?php checked( 'media_library', $instance['source'] ); ?> /> <?php _e( 'WP Media Library', 'jrinstaslider' ); ?></label>
				<br><small class="description"><?php _e( '* WP Media Library will display previously saved instagram images!', 'jrinstaslider') ?></small>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template', 'jrinstaslider' ); ?>
					<select class="widefat" name="<?php echo $this->get_field_name( 'template' ); ?>" id="<?php echo $this->get_field_id( 'template' ); ?>">
						<option value="slider" <?php echo ($instance['template'] == 'slider') ? ' selected="selected"' : ''; ?>><?php _e( 'Slider - Normal', 'jrinstaslider' ); ?></option>
						<option value="slider-overlay" <?php echo ($instance['template'] == 'slider-overlay') ? ' selected="selected"' : ''; ?>><?php _e( 'Slider - Overlay Text', 'jrinstaslider' ); ?></option>
						<option value="thumbs" <?php echo ($instance['template'] == 'thumbs') ? ' selected="selected"' : ''; ?>><?php _e( 'Thumbnails', 'jrinstaslider' ); ?></option>
					</select>
				</label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'orderby' ); ?>"><?php _e( 'Order', 'jrinstaslider' ); ?>
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
				<label for="<?php echo $this->get_field_id( 'images_link' ); ?>"><?php _e( 'Link to', 'jrinstaslider' ); ?>
					<select class="widefat" name="<?php echo $this->get_field_name( 'images_link' ); ?>" id="<?php echo $this->get_field_id( 'images_link' ); ?>">
						<option value="image_url" <?php selected( $instance['images_link'], 'image_url', true); ?>><?php _e( 'Instagram Image', 'jrinstaslider' ); ?></option>
						<option value="user_url" <?php selected( $instance['images_link'], 'user_url', true); ?>><?php _e( 'Instagram Profile', 'jrinstaslider' ); ?></option>
						<option value="local_image_url" <?php selected( $instance['images_link'], 'local_image_url', true); ?>><?php _e( 'Locally Saved Image', 'jrinstaslider' ); ?></option>
						<option value="custom_url" <?php selected( $instance['images_link'], 'custom_url', true); ?>><?php _e( 'Custom Link', 'jrinstaslider' ); ?></option>
					</select>
				</label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'custom_url' ); ?>"><?php _e( 'Custom link:', 'jrinstaslider'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'custom_url' ); ?>" name="<?php echo $this->get_field_name( 'custom_url' ); ?>" value="<?php echo $instance['custom_url']; ?>" />
				<small><?php _e('* use this field only if the above option is set to <strong>Custom Link</strong>', 'jrinstaslider'); ?></small>
			</p>
			<p>
				<label  for="<?php echo $this->get_field_id( 'images_number' ); ?>"><?php _e( 'Number of images to show:', 'jrinstaslider' ); ?>
					<input  class="small-text" id="<?php echo $this->get_field_id( 'images_number' ); ?>" name="<?php echo $this->get_field_name( 'images_number' ); ?>" value="<?php echo $instance['images_number']; ?>" />
					<small><?php _e( 'limit is 20 if <strong>Source</strong> is Instagram', 'jrinstaslider' ); ?></small>
				</label>
			</p>
			<p>
				<label  for="<?php echo $this->get_field_id( 'refresh_hour' ); ?>"><?php _e( 'Check for new images every:', 'jrinstaslider' ); ?>
					<input  title="Please provide also your lastname." class="small-text" id="<?php echo $this->get_field_id( 'refresh_hour' ); ?>" name="<?php echo $this->get_field_name( 'refresh_hour' ); ?>" value="<?php echo $instance['refresh_hour']; ?>" />
					<small><?php _e('hours', 'jrinstaslider'); ?></small>
				</label>
			</p>
			<p class="pressthis"><a target="_blank" title="Donate, It Feels Great" href="http://goo.gl/RZiu34"><span>Donate, It Feels Great!</span></a></p>
		</div>
		<?php
	}

	/**
	 * Runs the query for images and returns the html
	 * @param  array  $args
	 * @return string
	 */
	public function display_images( $args ) {

		$username      = isset( $args['username'] ) && !empty( $args['username'] ) ? $args['username'] : false;
		$source        = isset( $args['source'] ) && !empty( $args['source'] ) ? $args['source'] : 'instagram';
		$images_link   = isset( $args['images_link'] ) ? $args['images_link'] : 'image_url';
		$custom_url    = isset( $args['custom_url'] ) ? $args['custom_url'] : '';
		$orderby       = isset( $args['orderby'] ) ? $args['orderby'] : 'rand';
		$images_number = isset( $args['images_number'] ) ? absint( $args['images_number'] ) : 5;
		$refresh_hour  = isset( $args['refresh_hour'] ) ? absint( $args['refresh_hour'] ) : 5;
		$template      = isset( $args['template'] ) ? $args['template'] : 'slider';

		if ( false == $username ) {
			return false;
		}

		$template_args = array(
			'images_link' => $images_link,
			'custom_url'  => $custom_url
		);

		$query_args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'posts_per_page' => -1,
			'orderby'		 => 'rand',
			'no_found_rows'  => true
		);

		if ( $orderby != 'rand' ) {

			$orderby = explode( '-', $orderby );
			$meta_key = $orderby[0] == 'date' ? 'jr_insta_timestamp' : 'jr_insta_popularity';

			$query_args['meta_key'] = $meta_key;
			$query_args['orderby']  = 'meta_value_num';
			$query_args['order']    = $orderby[1];
		}

		if ( $source != 'instagram' ) {
			$query_args['posts_per_page'] = $images_number;
			$query_args['meta_query'] = array(
				array(
					'key'     => 'jr_insta_username',
					'value'   => $username,
					'compare' => '='
				)
			);
		} else {
			$query_args['post__in'] = $this->instagram_data( $username, $refresh_hour, $images_number );
		}

		$instagram_images = new WP_Query( $query_args );

		ob_start();
		if ( $instagram_images->have_posts() ) {
			?>
			<script type="text/javascript">

			</script>
			<div class="pllexislider normal">
				<ul class="no-bullet slides">
			<?php
				while ( $instagram_images->have_posts() ) : $instagram_images->the_post();

					$this->get_template( $template, $template_args );

				endwhile;
			?>
				</ul>
			</div>
			<?php
		} else {
			_e( 'No Images Yet', 'jrinstaslider' );
		}

		wp_reset_postdata();

		return ob_get_clean();
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
	private function instagram_data( $username, $cache_hours, $nr_images ) {

		$opt_name  = 'jr_insta_' . md5( $username );
		$instaData = get_transient( $opt_name );
		$user_opt  = (array) get_option( $opt_name );

		if ( false === $instaData || $user_opt['username'] != $username || $user_opt['cache_hours'] != $cache_hours || $user_opt['nr_images'] != $nr_images ) {

			$instaData    = array();
			$user_options = compact( 'username', 'cache_hours', 'nr_images' );
			$insta_url    = 'http://instagram.com/' . $username;

			$json = wp_remote_get( $insta_url, array(
				'sslverify' => false,
				'timeout' => 60
			) );

			if ( $json['response']['code'] == 200 ) {

				$json = $json['body'];
				$json = strstr( $json, 'window._sharedData = ' );
				$json = str_replace( 'window._sharedData = ', '', $json );

				// Compatibility for version of php where strstr() doesnt accept third parameter
				if ( version_compare( PHP_VERSION, '5.3.10', '>=' ) ) {
					$json = substr( $json, 0, strpos( $json, '</script>' ) );
				} else {
					$json = strstr( $json, '</script>', true );
				}

				$json = rtrim( $json, ';' );

				// Function json_last_error() is not available before PHP * 5.3.0 version
				if ( function_exists( 'json_last_error' ) ) {

					( $results = json_decode( $json, true ) ) && json_last_error() == JSON_ERROR_NONE;

				} else {

					$results = json_decode( $json, true );
				}

				if ( ( $results ) && is_array( $results ) ) {

					foreach ( $results['entry_data']['UserProfile'][0]['userMedia'] as $current => $result ) {

						if ( $result['type'] != 'image' ) {
							$nr_images++;
							continue;
						}

						if ( $current >= $nr_images ) {
							break;
						}

						$image_data['username']   = $result['user']['username'];
						$image_data['url']        = $result['images']['standard_resolution']['url'];
						$image_data['caption']    = $this->utf8_4byte_to_3byte( $result['caption']['text'] );
						$image_data['id']         = $result['id'];
						$image_data['link']       = $result['link'];
						$image_data['popularity'] = (int) ( $result['comments']['count'] ) + ( $result['likes']['count'] );
						$image_data['timestamp']  = $result['created_time'];

						if ( isset( $user_opt['saved_images'][$image_data['id']] ) ) {

							if ( is_string( get_post_status( $user_opt['saved_images'][$image_data['id']] ) ) ) {

								$this->update_wp_attachment( $user_opt['saved_images'][$image_data['id']], $image_data );

								$instaData[$image_data['id']] = $user_opt['saved_images'][$image_data['id']];

							} else {

								$user_opt['deleted_images'][$image_data['id']] = $image_data['url'];
							}

						} else {

							$id = $this->save_wp_attachment( $image_data );

							if ( $id ) {

								$user_opt['saved_images'][$image_data['id']] = $id;

								$instaData[$image_data['id']] = $id;
							}

						} // end isset $saved_images

					} // end -> foreach

				} // end -> ( $results ) && is_array( $results ) )

			} // end -> $json['response']['code'] === 200 )

			update_option( $opt_name, array_merge( $user_options, $user_opt ) );

			if ( $instaData ) {
				set_transient( $opt_name, $instaData, $cache_hours * 60 * 60 );
			}

		} // end -> false === $instaData

		return $instaData;
	}

	/**
	 * Function to display Templates for widget
	 *
	 * @param    string    $template
	 * @param    array	   $args
	 *
	 * @include file templates
	 *
	 * return void
	 */
	private function get_template( $template, $args ) {

		$filename = plugin_dir_path( __FILE__ ) . "views/" . $template . '.php';

		if ( file_exists( $filename ) ) {

			require( $filename );

		} else {

			_e( sprintf( 'Template not found<br>%s', $filename ), 'jrinstaslider' );
		}
	}

	/**
	 * Updates attachment using the id
	 * @param     int      $attachment_ID
	 * @param     array    image_data
	 * @return    void
	 */
	private function update_wp_attachment( $attachment_ID, $image_data ) {

		update_post_meta( $attachment_ID, 'jr_insta_popularity', $image_data['popularity'] );
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

		if ( !in_array( $image_info['extension'], array( 'jpg', 'jpe', 'jpeg', 'gif', 'png' ) ) ) {
			return false;
		}

		// These files need to be included as dependencies when on the front end.
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );

		$tmp = download_url( $image_data['url'] );

		$file_array             = array();
		$file_array['name']     = $image_info['basename'];
		$file_array['tmp_name'] = $tmp;

		// If error storing temporarily, unlink
		if ( is_wp_error( $tmp ) ) {
			@unlink( $file_array['tmp_name'] );
			$file_array['tmp_name'] = '';
		}

		$id = media_handle_sideload( $file_array, 0, NULL, array(
			 'post_excerpt' => $image_data['caption']
		) );

		// If error storing permanently, unlink
		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			return $id;
		}

		unset( $image_data['caption'] );

		foreach ( $image_data as $meta_key => $meta_value ) {
			update_post_meta( $id, 'jr_insta_' . $meta_key, $meta_value );
		}

		return $id;
	}

	/**
	 * Add shorcode fnction
	 * @param  [type] $atts [description]
	 * @return [type]       [description]
	 */
	public function shortcode( $atts ) {
		return $this->display_images( $atts );
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
	private function utf8_4byte_to_3byte( $input ) {

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
					$mb_char = '?';
					$i += 3;
				}

				$sanitized .= $mb_char;
			}

			$input = $sanitized;
		}

		return $input;
	}

	/**
	 * Echoes the html the the $error contains
	 * @return [type] [description]
	 */
	public function display_errors() {
		echo $this->errors;
	}

	/**
	 * Creates the errors to be displayed in widget form
	 * @param  array $inputs
	 * @return mixed
	 */
	private function input_errors( $inputs ) {

		$error_string = '';
		$before_text  = '<p class="jr_insta_error">';
		$after_text   = '</p>';

		if ( empty( $inputs['username'] ) )
			$error_string .= $before_text . __( 'The username cannot be empty!', 'jrinstaslider' ) . $after_text;

		if ( $inputs['images_number'] > 20 && $inputs['source'] == 'instagram' )
			$error_string .= $before_text . __( 'Please set a number lower than 20', 'jrinstaslider' ) . $after_text;

		if ( $inputs['images_number'] == '' )
			$error_string .= $before_text . __( 'Number of images to show cannot be empty', 'jrinstaslider' ) . $after_text;

		if ( $inputs['refresh_hour'] == '' || $inputs['refresh_hour'] == 0 )
			$error_string .= $before_text . __( 'Enter a valid hour. From 1 to any other positive number', 'jrinstaslider' ) . $after_text;

		if ( str_word_count( $error_string ) < 2 ) {
			return $this->errors = false;
		} else {
			return $this->errors = $error_string;
		}
	}

} // end of class JR_InstagramSlider