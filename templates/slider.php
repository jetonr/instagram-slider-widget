<script type="text/javascript">
jQuery(document).ready(function($) {
	$('.pllexislider').pllexislider({
		animation: "slide",
		directionNav: false,
	});
});
</script>
<div class="pllexislider">
    <ul class="no-bullet slides">
		<?php 
			if ( isset($data_arr) && is_array($data_arr) ) {
				foreach ($data_arr as $data) {
					foreach ( $data as $k => $v) {
						$$k = $v;
					}
					echo '<li>'. "\n";
					echo '<a target="_blank" href="'.$link.'"><img src="'.$image.'" alt="'.$text.'"></a>' . "\n";
					if ( $created_time ) {
						echo '<div class="instatime">'. human_time_diff( $created_time ) . ' ago</div>' . "\n";
					}
					echo '<div class="instadescription">' . "\n";
					echo '<p>by <a href="'. $user_url .'">'. $user_name .'</a></p>' . "\n";
					if ($text) {
						echo '<p>'.$text.'</p>' . "\n";
					}
					echo '</div>' . "\n";
					echo '</li>' . "\n";
				
				}
			}
        ?>
    </ul>
</div>
