<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! function_exists( 'instag_images_data' ) ) :
/**
 * Stores the fetched data from instagram in WordPress DB using transients
 *
 * @return array of localy saved instagram data
 */
function instag_images_data( $username, $cache_hours, $nr_images ) {
	
	$opt_name    = 'jr_insta_'.md5( $username );
    $instaData 	 = get_transient( $opt_name );
	$user_opt    = get_option( $opt_name );

	if ( 
		false === $instaData 
		|| $user_opt['username']    != $username 
		|| $user_opt['cache_hours'] != $cache_hours 
		|| $user_opt['nr_images']   != $nr_images 
	   ) 
	{
		$instaData    = array();
		$insta_url    = 'http://instagram.com/';
		$user_profile = $insta_url.$username;
		$json     	  = wp_remote_get( $user_profile, array( 'sslverify' => false, 'timeout'=> 60 ) );
		$user_options = compact('username', 'cache_hours', 'nr_images');
		update_option($opt_name, $user_options);
		if ( $json['response']['code'] === 200 ) {
			
			$json 	  = $json['body'];
			$json     = strstr( $json, '{"entry_data"' );
			$json     = strstr( $json, '</script>', true );
			$json     = rtrim( $json, ';' );
			preg_match_all( "#(\"userMedia\"\:)(\[)(.*?)(\]\,\"prerelease\")#isU", $json, $matches );
			$json     = isset($matches[3][0]) ? $matches[3][0] : null;
			$json  	  = "[".$json."]";
			( $results = json_decode( $json, true ) ) && json_last_error() == JSON_ERROR_NONE;
		
			if ( ( $results ) && is_array( $results ) ) {
				foreach( $results as $current => $result ) {
		
					if( $current >= $nr_images ) break;
					$caption      = $result['caption'];
					$image        = $result['images']['standard_resolution'];
					$id           = $result['id'];
					$image        = $image['url'];
					$link         = $result['link'];
					$created_time = $caption['created_time'];
					$text         = utf8_4byte_to_3byte($caption['text']);
									
					$filename_data= explode('.',$image);
	
					if ( is_array( $filename_data ) ) {

						$fileformat   = end( $filename_data );

						if ( $fileformat !== false ){
							
							$image = download_insta_image( $image, md5( $id ) . '.' . $fileformat );
							array_push( $instaData, array(
								'id'          => $id,
								'user_name'	  => $username,
								'user_url'	  => $user_profile,
								'created_time'=> $created_time,
								'text'        => $text,
								'image'       => $image,
								'link'        => $link
							));
							
						} // end -> if $fileformat !== false
					
					} // end -> is_array( $filename_data )
					
				} // end -> foreach
			
			} // end -> ( $results ) && is_array( $results ) )
		
		} // end -> $json['response']['code'] === 200 )

		if ( $instaData ) {
			set_transient( $opt_name, $instaData, $cache_hours * 60 * 60 );
		} // end -> true $instaData
	
	} // end -> false === $instaData
	
	return $instaData;
}
endif; // insta_images

if ( ! function_exists( 'download_insta_image' ) ) :
/**
 * Save Instagram images to upload folder and ads to media.
 * If the upload fails it returns the remote image url. 
 *
 * @return url to image
 */
function download_insta_image( $url , $file ){
	
	$local_file = JR_INSTAGWP_UPLOAD_PATH . $file; 
	
	if ( file_exists( $local_file ) ) {
		return JR_INSTAGWP_UPLODAD_URL . $file;
	}		
	
	$get 	   = wp_remote_get( $url, array( 'sslverify' => false ) );
	$body      = wp_remote_retrieve_body( $get );
	$upload	   = wp_upload_bits( $file, '', $body );
		
	if ( $upload ) {
		return $upload['url'];
	}
	
	return $url;
}
endif; // download_insta_image

if ( ! function_exists( 'utf8_4byte_to_3byte' ) ) :
/**
 * Sanitize 4-byte UTF8 chars; no full utf8mb4 support in drupal7+mysql stack.
 * This solution runs in O(n) time BUT assumes that all incoming input is
 * strictly UTF8.
 *
 * @return the sanitized input
 */
function utf8_4byte_to_3byte($input) {
  
  if (!empty($input)) {
    $utf8_2byte = 0xC0 /*1100 0000*/; $utf8_2byte_bmask = 0xE0 /*1110 0000*/;
    $utf8_3byte = 0xE0 /*1110 0000*/; $utf8_3byte_bmask = 0XF0 /*1111 0000*/;
    $utf8_4byte = 0xF0 /*1111 0000*/; $utf8_4byte_bmask = 0xF8 /*1111 1000*/;
 
    $sanitized = "";
    $len = strlen($input);
    for ($i = 0; $i < $len; ++$i) {
      $mb_char = $input[$i]; // Potentially a multibyte sequence
      $byte = ord($mb_char);
      if (($byte & $utf8_2byte_bmask) == $utf8_2byte) {
        $mb_char .= $input[++$i];
      }
      else if (($byte & $utf8_3byte_bmask) == $utf8_3byte) {
        $mb_char .= $input[++$i];
        $mb_char .= $input[++$i];
      }
      else if (($byte & $utf8_4byte_bmask) == $utf8_4byte) {
        // Replace with ? to avoid MySQL exception
        $mb_char = '?';
        $i += 3;
      }
 
      $sanitized .=  $mb_char;
    }
 
    $input= $sanitized;
  }
 
  return $input;
}
endif; // utf8_4byte_to_3byte

if ( ! function_exists( 'instag_templates' ) ) :
/**
 * Helper Function to insert Templates for widget
 *
 * @include file templates
 */
function instag_templates( $template, $data_arr ){

	$filename = JR_INSTAGWP_PATH_TEMPLATE . $template . '.php';

	if(file_exists( $filename )){

		include $filename;

	} else {
		
		echo __( sprintf('Template not found<br>%s' , $filename), 'example' );
	}
}
endif; // instag_templates