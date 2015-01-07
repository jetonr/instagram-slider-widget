(function($) {

	function jr_on_form_update( e, widget_el ) {
		var error = widget_el.find( '.jr_insta_error' );
		var adminbar_height = jr_insta_admin == 'true' ? 32 : 0;
		if ( error.length ) {
			$('html, body').animate({ scrollTop: widget_el.find('.widget-inside').offset().top - adminbar_height }, 'slow');
		}
	}

	function jr_on_image_link_change(e, widget_el) {
		
		if ( typeof widget_el == "undefined" ) {
			$( '.jr-container select[id$="images_link"]' ).change(function(e) {
				var images_link = $(this).val(),
				regex = /^(.*)(\d)+/i,
				matches = this.id.match(regex);	
				
				if( matches != null ) {
					if ( images_link != 'custom_url' ) {
						$('#widget-jr_insta_slider-' + matches[2] +'-custom_url').val('').parent().animate({opacity: 'hide' , height: 'hide'}, 200);
					} else {
						$('#widget-jr_insta_slider-' + matches[2] +'-custom_url').parent().animate({opacity: 'show' , height: 'show'}, 200);
					}
				}
			}).change();

		} else {

			var images_link = widget_el.find( 'select[id$="images_link"]' );

			images_link.change(function(e) {

				if ( $( images_link ).val() != 'custom_url' ) {
					widget_el.find( 'input[id$="custom_url"]' ).val('').parent().animate({opacity: 'hide' , height: 'hide'}, 200);
				} else {
					widget_el.find( 'input[id$="custom_url"]' ).parent().animate({opacity: 'show' , height: 'show'}, 200);
				}
			}).change();;			
		}
	}
	
	$( document ).on( 'widget-updated', jr_on_image_link_change );
	$( document ).on( 'widget-added', jr_on_image_link_change );
	$( document ).on( 'ready', jr_on_image_link_change );
	
	$( document ).on( 'widget-updated', jr_on_form_update );
	
})(jQuery);