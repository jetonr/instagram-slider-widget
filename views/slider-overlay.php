<div class="pllexislider overlay">
    <ul class="no-bullet slides">
		<?php 
			if ( isset($data_arr) && is_array($data_arr) ) {
				foreach ($data_arr as $data) {
					foreach ( $data as $k => $v) {
						$$k = $v;
					}
					
					/* Set link to User Instagram Profile */
					if ( $link_to && ( 'user_url' == $link_to ) ) {
						$link = $user_url;
					}
					
					/* Set link to Locally saved image */
					if ( $link_to && 'local_image_url' == $link_to ) {
						$link = $image;
					}

					/* Set link to Custom URL */
					if ( ( $link_to && 'custom_url' == $link_to ) && ( isset( $custom_url ) && $custom_url != '' ) ) {
						$link = $custom_url;
					}

					echo '<li>'. "\n";
					echo '<a target="_blank" href="'.$link.'"><img src="'.$image.'" alt="'.$text.'"></a>' . "\n";
					echo '<div class="jr-insta-wrap">' . "\n";					
					echo '<div class="jr-insta-datacontainer">' . "\n";
					if ( $created_time ) {
						echo '<span class="jr-insta-time">'. human_time_diff( $created_time ) . ' ago</span>' . "\n";
					}
					echo '<span class="jr-insta-username">by <a target="_blank" href="'. $user_url .'">'. $user_name .'</a></span>' . "\n";
					if ($text) {
						echo '<span class="jr-insta-desc">'.$text.'</span>' . "\n";
					}
					echo '</div>' . "\n";
					echo '</div>' . "\n";
					echo '</li>' . "\n";
				
				}
			}
        ?>
    </ul>
</div>
