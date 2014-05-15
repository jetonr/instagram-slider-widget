
<div class="instag">
    <ul class="thumbnails no-bullet">
		<?php
			if ( isset( $data_arr ) && is_array( $data_arr ) ) {
				foreach ( $data_arr as $data ) {
					foreach ( $data as $k => $v ) {
						$$k = $v;
					}
					
					/* Set link to User Instagram Profile */
					if ( $link_to && 'user_url' == $link_to ) {
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
					echo '<a target="_blank" href="' . $link . '"><img src="' . $image . '" alt="' . $text . '"></a>' . "\n";
					echo '</li>' . "\n";
				}
			}
        ?>
    </ul>
</div>