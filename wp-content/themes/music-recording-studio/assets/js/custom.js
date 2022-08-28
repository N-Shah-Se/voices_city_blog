function music_recording_studio_menu_open_nav() {
	window.music_recording_studio_responsiveMenu=true;
	jQuery(".sidenav").addClass('show');
}
function music_recording_studio_menu_close_nav() {
	window.music_recording_studio_responsiveMenu=false;
 	jQuery(".sidenav").removeClass('show');
}

jQuery(function($){
 	"use strict";
 	jQuery('.main-menu > ul').superfish({
		delay: 500,
		animation: {opacity:'show',height:'show'},  
		speed: 'fast'
 	});

	new WOW().init();
});

jQuery(document).ready(function () {
	window.music_recording_studio_currentfocus=null;
  	music_recording_studio_checkfocusdElement();
	var music_recording_studio_body = document.querySelector('body');
	music_recording_studio_body.addEventListener('keyup', music_recording_studio_check_tab_press);
	var music_recording_studio_gotoHome = false;
	var music_recording_studio_gotoClose = false;
	window.music_recording_studio_responsiveMenu=false;
 	function music_recording_studio_checkfocusdElement(){
	 	if(window.music_recording_studio_currentfocus=document.activeElement.className){
		 	window.music_recording_studio_currentfocus=document.activeElement.className;
	 	}
 	}
 	function music_recording_studio_check_tab_press(e) {
		"use strict";
		// pick passed event or global event object if passed one is empty
		e = e || event;
		var activeElement;

		if(window.innerWidth < 999){
		if (e.keyCode == 9) {
			if(window.music_recording_studio_responsiveMenu){
			if (!e.shiftKey) {
				if(music_recording_studio_gotoHome) {
					jQuery( ".main-menu ul:first li:first a:first-child" ).focus();
				}
			}
			if (jQuery("a.closebtn.mobile-menu").is(":focus")) {
				music_recording_studio_gotoHome = true;
			} else {
				music_recording_studio_gotoHome = false;
			}

		}else{

			if(window.music_recording_studio_currentfocus=="responsivetoggle"){
				jQuery( "" ).focus();
			}}}
		}
		if (e.shiftKey && e.keyCode == 9) {
		if(window.innerWidth < 999){
			if(window.music_recording_studio_currentfocus=="header-search"){
				jQuery(".responsivetoggle").focus();
			}else{
				if(window.music_recording_studio_responsiveMenu){
				if(music_recording_studio_gotoClose){
					jQuery("a.closebtn.mobile-menu").focus();
				}
				if (jQuery( ".main-menu ul:first li:first a:first-child" ).is(":focus")) {
					music_recording_studio_gotoClose = true;
				} else {
					music_recording_studio_gotoClose = false;
				}
			
			}else{

			if(window.music_recording_studio_responsiveMenu){
			}}}}
		}
	 	music_recording_studio_checkfocusdElement();
	}
});

jQuery('document').ready(function($){
  setTimeout(function () {
		jQuery("#preloader").fadeOut("slow");
  },1000);
});

jQuery(document).ready(function () {
	jQuery(window).scroll(function () {
    if (jQuery(this).scrollTop() > 100) {
      jQuery('.scrollup i').fadeIn();
    } else {
      jQuery('.scrollup i').fadeOut();
    }
	});
	jQuery('.scrollup i').click(function () {
    jQuery("html, body").animate({
      scrollTop: 0
    }, 600);
    return false;
	});
});

jQuery('document').ready(function(){
  var owl = jQuery('#service-section .owl-carousel');
    owl.owlCarousel({
    margin:20,
    nav: true,
    autoplay : true,
    lazyLoad: true,
    autoplayTimeout: 3000,
    loop: true,
    dots:false,
    navText : ['<i class="fas fa-arrow-left"></i>','<i class="fas fa-arrow-right"></i>'],
    responsive: {
      0: {
        items: 1
      },
      600: {
        items: 2
      },
      1000: {
        items: 3
      }
    },
    autoplayHoverPause : true,
    mouseDrag: true
  });
});