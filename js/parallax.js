var scrollPositionX;

var delta;

var percant;

var scroll;

var work_num;

var scrolled;

var pos;

var linkhover;



$(document).ready(function(){

	//$('.bgallery-wrap > div a').fancybox2();
		
    var scpane = $('.nav-scroll');

    var navul = $('#nav ul').width();

	var panul = $('#pan ul.main').width()-300;

	var	wrapw = $('#nav').width();

	var	winwi = $(window).width();


	$(window).resize(function(){

        winwi = $(window).width();

	});


    // trees
	$('#pan ul.grass-3, #pan ul.grass-2, #pan ul.grass-1').width(panul);


 	scpane.bind('jsp-scroll-x', function(event, scrollPositionX)

		{

			delta = scrollPositionX * (panul - winwi) / (wrapw - navul);

			percant = delta / (panul - winwi) * 100;

		    $('#pan .sec').css({left: delta});

			$('#pan .first').css({marginLeft:percant*5, left: delta});

			$('#pan ul.grass-3').css({marginLeft:-delta + percant*10, left: delta });

			$('#pan ul.grass-1').css({marginLeft:percant*40, left: delta});


		}

	).jScrollPane({

		animateScroll: true,

		horizontalDragMinWidth: 200,

		horizontalDragMaxWidth: 200,

		keyboardSpeed: 10
	});



	var api = scpane.data('jsp');
	
	//$.cookie('main_scroll', null);
	if (!$.cookie('main_scroll')) {
		$.cookie('main_scroll', '1', { expires: 30, path: '/'}); // minutes
		api.scrollToX(200, false);
		api.scrollToFlatkoX(0, true, 4000);
	}
	

  $(".parallax").parallax(

  	{xparallax:'0px', ytravel:0},

  	{xparallax:'0px', ytravel:0},

  	{xparallax:'500px', ytravel:0},

  	{xparallax:'500px', ytravel:0}

	);

	$('.moveleft').hover(function(){

	    curulleft = parseInt($('.first').css('left'));

	    curpos = (wrapw - navul ) / (panul - winwi) * curulleft;

		moveleft = setInterval(function(){

			curpos = curpos - 0.4;

			api.scrollToX(curpos,false)

		},10)

	},function(){

		clearInterval(moveleft);

	})



	$('.moveright').hover(function(){

	    curulleft = parseInt($('.first').css('left'));

	    curpos = (wrapw - navul ) / (panul - winwi) * curulleft;

		moveright = setInterval(function(){

			curpos = curpos + 0.4;

			api.scrollToX(curpos,false)

		},10)

	},function(){

		clearInterval(moveright);

	})





	$('.main.link li').hover(function(){

		linkhover = $(this).attr('class');

		$('.main.bg li.'+linkhover).addClass('hover');

		$('.front[rel='+linkhover+'] span').addClass('hover');

	}, function(){

		$('.main.bg li.'+linkhover).removeClass('hover');

		$('.front[rel='+linkhover+'] span').removeClass('hover');

	});



	$('.second.link li').hover(function(){

		linkhover = $(this).attr('class');

		$('.second.bg li.'+linkhover).addClass('hover');

		$('.back[rel='+linkhover+'] span').addClass('hover');

	}, function(){

		$('.second.bg li.'+linkhover).removeClass('hover');

		$('.back[rel='+linkhover+'] span').removeClass('hover');

	});



	$('.link li').hover(function(){

		if ($('.bubble',this).is(':visible')) {return false}

	    thistip = $('.tip', this);

	    if ($.browser.msie) {

	        thistip.show();

	    } else {

            thistip.show().css('opacity','0').animate({bottom:'+=10', opacity:1}, 200);

		}

		tipwidth = thistip.width();

		thistip.css('margin-left',tipwidth/2*(-1));

	},function(){

		if ($('.bubble',this).is(':visible')) {return false}

		if ($.browser.msie) {

		    $('.tip', this).hide();

		} else {

            $('.tip', this).animate({bottom:'-=10', opacity:0}, 200,function(){

	            $(this).hide();

			});

		}

		

	});


    // bubble popup on click over the items
    /*
    $('.link li a.move').click(function(){

		linkul = $(this).closest('ul').attr('class');

		linkli = $(this).parent().attr('class');

		if (linkul == 'main link') {

			curlinkul = 'front'

		} else {
			curlinkul = 'back'
		}

		$('li.'+curlinkul+'[rel='+linkli+']').click();

		return false;

	});
	*/
	
	/*$('.parallax-2 .link li.house-4 a.move, .parallax-2 .link li.house-6 a.move, .parallax-2 .link li.house-9 a.move').unbind('click').click(function(){
			return false;	
	});*/


	$('.tip').click(function(){
		$(this).next('.move').click();

	});


	$('#nav li').hover(function(){

		navliclass = $(this).attr('class');

		navlirel = $(this).attr('rel');

		if (navliclass == 'front') {

			curul = 'main';

		} else {

			curul = 'second';

		}

		$('#pan .'+curul+'.bg li.'+navlirel).addClass('hover');

		$('span', this).addClass('hover');

	}, function(){

		$('span', this).removeClass('hover');

		$('#pan .'+curul+'.bg li.'+navlirel).removeClass('hover');

	});



	$('.nav-right').click('click',function(){

    	api.scrollBy(10, 0);

		return false;

	});



	$('.nav-left').click(function(){

    	api.scrollBy(-10, 0);

		return false;

	});



	$('#nav li').live('click', function(){



        if ($(this).attr('id') == 'active') {return false}

        $('#nav li').attr('id','');

        $(this).attr('id','active');





		navliclass = $(this).attr('class');

		navlirel = $(this).attr('rel');

		if (navliclass == 'front') {

			curul = 'main';

		} else {

			curul = 'second';

		}



		$('#nav li span').removeClass('active');

		$(this).find('span').addClass('active');



		curlayer = $('#pan .'+curul+'.bg li.'+navlirel);


		navliwid = curlayer.width();


		parmar = parseInt($('#pan .'+curul+'.bg').closest('.parallax').css('margin-left'));


		navlioff = curlayer.offset().left - parmar -(winwi/2 - navliwid/2);

		$('#pan ul li').removeClass('active');

		curlayer.addClass('active');

		scrollPositionXnew = navlioff * (wrapw - navul ) / (panul - winwi);

		newnum = (panul - winwi) / 500; // 500 = percant * множитель

		if (navliclass == 'front') {

			api.scrollBy((scrollPositionXnew - scrollPositionXnew/newnum)*(-1), 0);

		} else {

   			api.scrollBy((scrollPositionXnew)*(-1), 0);

		}



		curbubbleg = $('#pan .'+curul+'.link li.'+navlirel);

		curbubble =  curbubbleg.find('.bubble');

		curbubblehouse = curbubble.prev('a.move').height();

		


		$('.tip', curbubbleg).animate({bottom:'-=10', opacity:0}, 200,function(){

            $(this).hide();

		});



		// Big tips


        // y pos
		curfield = 810 - $(window).scrollTop();

		curbubleheight = curbubble.height();

		curbublemin = curfield - curbubleheight;



		if (curbublemin < curbubblehouse) {

			if (curfield > curbubleheight) {

			    setbubble = curbublemin;

				if ($(window).scrollTop() < 80) {

					if (curbubblehouse + curbubleheight > 700) {

					    setbubble = curbublemin - 110 + $(window).scrollTop();

					}

				}

			} else{

			    setbubble = 0;

			}

		} else {

			setbubble = curbubblehouse;

			if ($(window).scrollTop() < 80) {

				if (curbubblehouse + curbubleheight > 700) {

					setbubble = curbublemin - 110 + $(window).scrollTop();

				}

			}

		}

	    curbubble.css('bottom', setbubble - 30);




		if ($.browser.msie) {

		    $('#pan li .bubble:visible').hide();

		    curbubble.show().css({bottom:setbubble});

		} else {

		    $('#pan li .bubble:visible').animate({opacity:0,bottom:'-=30'},300, function(){

				$(this).hide();

			});

			curbubble.stop().show().css('opacity','0').animate({opacity:1, bottom:setbubble-50},300);

		}



		$('#pan .link').css('z-index','');

		$('#pan .'+curul+'.link').css('z-index','15');



		/**/



		scrolled = $('.bgallery-wrap', curbubbleg);

		scroll = $('.bgallery-wrap div', curbubbleg).width();

		work_num = 	$('.bgallery-wrap div', curbubbleg).length;

		pos = 100;

		scrolled.css({'left':'-'+ pos +'px'});

		total_width = work_num * scroll;

		refreshgallery();
		
		
		$('#wrapper').bind('click', function(event){
			//alert(1);
			var $target = $(event.target);
			if ($target.parents('.bubble').html() == null) {
				$('.bubble .bubble-c').click();
				$('#wrapper').unbind('click');
			}
				
		});

	});



	$('.bubble .bubble-c').click(function(){

		$(this).closest('.bubble').animate({opacity:0,bottom:'-=30'},300, function(){

			$(this).hide();

		});

		$('#pan li, #nav span').removeClass('active');

		$('#pan li, #nav li').attr('id','');

		$('#pan .link').css('z-index','');

		

	});



	$('#nav li').tipsy();


	$("a[rel='blank']").click(function(){

		window.open($(this).attr('href'));

		return false

	});
});



function refreshgallery(){

scrolled.parent().children('.bgallery-r').click(function() {

		if (!scrolled.is(':animated'))

		{

      		scrolled.animate({'left':- (pos + scroll )+'px'}, 300,function(){

				$('div:eq(0)',scrolled).appendTo(scrolled);

				scrolled.css({'left': pos*(-1)});

			});

		}

		return false;

	});



	scrolled.parent().children('.bgallery-l').click(function() {

		if (!scrolled.is(':animated'))

		{

			$('div:eq('+(work_num-1)+')', scrolled).prependTo(scrolled);

			scrolled.css({'left':- (pos + scroll )+'px'}).animate({'left':'-'+pos +'px'},200);

		}

		return false;

	});

}

