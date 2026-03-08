/*
 * Tee Up - Golf Template
 *
 * Copyright (c) 2014 F²
 * 
 * Main Javascript
 */
(function($) {
    "use strict";
    //LOADER
    $(window).load(function () {
		$('#loader').fadeOut();
	});
	//SEARCH FORM
	$("#search-toggle").click(function(){
		$("#navigation .search-container").fadeToggle();
		$("#navigation #s").focus();
	});
	//ANIMATIONS
    $(".animate_bottom_top, .animate_top_bottom, .animate_left_right, .animate_right_left,.animate_fade").waypoint(function () {
        if (!$(this).hasClass("animate_go")) {
            var e = $(this);
            setTimeout(function () {
                e.addClass("animate_go")
            }, 30)
        }
    }, {
        offset: "80%"
    });
	//ADD TABLE CLASS TO VC TABLE FOR BOOTSTRAP
	$('.wpb_vc_table table').addClass("table");
	//SUBMENU
	$('.menu-item-has-children').hover(
		function(){
			$(this).find('.sub-menu').fadeIn();
		}, function(){
		    $(this).find('.sub-menu').fadeOut("slow");
		}
	);
    //LANGUAGES & BUDDYPRESS $ WOOCOMMERCE SUBMENU
    $('#open-languages').click(function (){
	    $("#languages").fadeToggle();
    });
    $('#buddypress-submenu').mouseover(function (){
	    $("#member-links").fadeToggle();
    });
    $('#tee_cart').mouseover(function (){
	    $("#tee_mini_cart").fadeToggle();
    });
    $('#tee_mini_cart').mouseleave(function (){
	    $("#tee_mini_cart").fadeOut();
    });
    //RESPONSIVE MENU REPLACED IN 1.6.2
    //$("#mobile-menu").click(function(){
	    //$("#navigation").fadeToggle();
    //});
	$(document).ready ( function () {
		$('#navigation-mobile #close-navigation-mobile').click(function (){
	    	$('body').css('overflow', '');
	        $('body').css({'right': '0'});
	        $('body').css({'position': 'inherit'});
	        $('#navigation-mobile').removeClass('display-nav-menu');
	        $('#navigation #show-mobile-menu').removeClass('mobile-button-left');
	    });
    });
    $('#show-mobile-menu').on('click',function(){
        $('#close-navigation-mobile').fadeIn();
        if($('#navigation-mobile').hasClass('display-nav-menu')){
            $('body').css('overflow', '');
            $('body').css({'right': '0'});
            $('body').css({'position': 'inherit'});
            $('#navigation-mobile').removeClass('display-nav-menu');
            $('#navigation #show-mobile-menu').removeClass('mobile-button-left');
        } else {
            $('body').css({'overflow': 'hidden'});
            $('body').css({'right': '-200px'});
            $('body').css({'position': 'relative'});
            $('#navigation-mobile').addClass('display-nav-menu');
            $('#navigation #show-mobile-menu').addClass('mobile-button-left');
        }
    });
    
    var data = $('#navigation').html();
	$('#navigation-mobile').html(data);
	//FANCYOX
	$('.fancybox').fancybox({
        openEffect  : 'elastic'
    });
    $('.sample-flexslider').flexslider({
	    animation: "slide",
    });
    //CAROUSELS
	$('.content-carousel').flexslider({
		animation: "slide",
		animationLoop: false,
		itemWidth: 380,
		slideshow: false
	});
	$(window).load(function() {
	  $('#gallery-slider').flexslider({
	    animation: "slide",
	    animationLoop: false,
	    itemWidth: 300,
	    itemMargin: 0,
	    slideshow: false
	  });
	});
    //BOOTSTRAP ALERTS FOR FORM
	$(".alert").alert();
	//GALLERY
	$(window).load(function(){
		var $container = $('#gallery-container');
		$container.isotope({
		  itemSelector: '.item',
		  layoutMode: 'fitRows'
		});
	});
	$('#filters').on( 'click', 'button', function( event ) {
	  var filtr = $(this).attr('data-filter');
	  $('#gallery-container').isotope({ filter: filtr });
	});
	//EVENTS
	$(window).load(function(){
		var $container = $('#events-container');
		$container.isotope({
		  itemSelector: '.slide',
		  layoutMode: 'fitRows'
		});
	});
	//COURSES
	$(window).load(function(){
		var $container = $('#courses-container');
		$container.isotope({
		  itemSelector: '.slide',
		  layoutMode: 'fitRows'
		});
	});
	//SHOP
	$(window).load(function(){
		var $container = $('#shop-container .products');
		$container.isotope({
		  itemSelector: '.product',
		  layoutMode: 'fitRows'
		});
	});
	//MEMBERS
	var $container = $('#members-container');
	$container.isotope({
	  itemSelector: '.slide',
	  layoutMode: 'fitRows'
	});
	//DROPDOWN TOGGLE
	$('.dropdown-toggle').dropdown();
	//CONTACT
	$("#open-address, #close-address").click(function(){
		$("#address").fadeToggle();
	});
	//SIGN IN
	$("#open-signin, #close-signin").click(function(){
		$("#signin-container").fadeToggle();
	});
	//FORM SUBSCRIBE
	$("#open-subscribe, #close-subscribe").click(function(){
		$("#subscribe-container").fadeToggle();
	});

})(jQuery);