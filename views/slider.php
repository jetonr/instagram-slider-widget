
<?php 

$image_url    = wp_get_attachment_image_src( get_the_id(), 'full' );
$all_metas    = get_post_custom( get_the_id() );

?>

	<?php 
		$link = '';


		echo '<li>'. "\n";
		echo '<a target="_blank" href=""><img src="'. $image_url[0] .'"></a>' . "\n";
		if ( $all_metas['jr_insta_timestamp'][0] ) {
			echo '<div class="instatime">'. human_time_diff( $all_metas['jr_insta_timestamp'][0] ) . ' ago</div>' . "\n";
		}
		echo '<div class="instadescription">' . "\n";
		echo '<p>by <a href="http://instagram.com/'. $all_metas['jr_insta_username'][0] .'">'. $all_metas['jr_insta_username'][0] .'</a></p>' . "\n";
		if ( get_the_excerpt() != ''  ) {
			echo '<p>'. get_the_excerpt() .'</p>' . "\n";
		}
		echo '</div>' . "\n";
		echo '</li>' . "\n";
	?>

