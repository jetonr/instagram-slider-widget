jQuery(document).ready(function($) {
	
	$('.pllexislider.normal').pllexislider({
		animation: "slide",
		directionNav: false,
	});

	$('.pllexislider.overlay').pllexislider({
		animation: "fade",
		directionNav: false,
		start: function(slider){
			slider.hover(
				function () {
					slider.find('.jr-insta-datacontainer, .pllex-control-nav').stop(true,true).fadeIn();
				}, 
				function () {
					slider.find('.jr-insta-datacontainer, .pllex-control-nav').stop(true,true).fadeOut();
				}
			);  
		}
	});
	
});