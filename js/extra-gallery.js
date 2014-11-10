$(document).ready(function(){
    //fixPopupTopPosition();
});

function initExtraGallery(type, id, section, index){
    var galleryContainer = $('.extra-gallery-container');


    if(galleryContainer.length){
        $.post('../lib/extra_gallery_onpage.php', {
            type: type,
            id: id,
            section: section,
            index: index
        }, function(response){
            if(response.html){
                galleryContainer.html(response.html);

                var stage = galleryContainer.find('.stage');

                stage.click(function(){
                    window.setTimeout('showGalleryInfo()', 500);
                });

                showExtraGalleryItemInfo(index);

                runJCarousel(index);
            }
        }, 'json');
    }
}

function hideGalleryInfo(){
    var galleryContainer = $('.extra-gallery-container');
    var infoContainer = galleryContainer.find('.extra-gallery-info');
    infoContainer.fadeOut('fast');

}
function showGalleryInfo(){
    var galleryContainer = $('.extra-gallery-container');
    var infoContainer = galleryContainer.find('.extra-gallery-info');
    infoContainer.fadeIn('fast');

}

function expandExtraGallery(type, id, section, index){
    var galleryContainer = $('.extra-gallery');

    if(galleryContainer.length){
        galleryContainer.slideDown('fast', function(){
            $.post('../lib/extra_gallery.php', {
                type: type,
                id: id,
                section: section,
                index: index
            }, function(response){
                if(response.html){
                    galleryContainer.html(response.html);

                    showExtraGalleryItemInfo(index);

                    runJCarousel(index);
                }
            }, 'json');
        });
    }
}

function fixPopupTopPosition(){
    var needleOffsetY = 15; // %
    var offsetY = 0;
    var documentHeight = $(window).height();
    var container = $('.extra-gallery');

    offsetY = Math.round((documentHeight/100)*needleOffsetY);
    container.css('top', offsetY+'px');
}

function collapseExtraGallery(pointer){
    var galleryContainer = $('.'+pointer);

    galleryContainer.slideUp('fast');
}

function showExtraGalleryItemInfo(index){
    var items = $('.extra-gallery-info .items');

    items.hide();
    items.eq(index).show();
}


function runJCarousel(index){
    // This is the connector function.
    // It connects one item from the navigation carousel to one item from the
    // stage carousel.
    // The default behaviour is, to connect items with the same index from both
    // carousels. This might _not_ work with circular carousels!
    var connector = function(itemNavigation, carouselStage) {
        return carouselStage.jcarousel('items').eq(itemNavigation.index());
    };

    // Setup the carousels. Adjust the options for both carousels here.
    var carouselStage      = $('.carousel-stage').on('jcarousel:createend', function(event, carousel) {
        $(this).jcarousel('scroll', index, false);
        showExtraGalleryItemInfo(index);
    }).on('jcarousel:animateend', function(event, carousel) {

            var activeIndex, i = 0;
            $('.carousel-navigation ul li').each(function(){
                if($(this).hasClass('active')) activeIndex = i;
                i++;
            });

            showExtraGalleryItemInfo(activeIndex);
    }).jcarousel({
        wrap: 'circular'
    });

    var carouselNavigation = $('.carousel-navigation').on('jcarousel:createend', function(event, carousel) {
        //$(this).jcarousel('scroll', index, false);
    }).jcarousel();


    // We loop through the items of the navigation carousel and set it up
    // as a control for an item from the stage carousel.
    carouselNavigation.jcarousel('items').each(function() {
        var item = $(this);

        // This is where we actually connect to items.
        var target = connector(item, carouselStage);

        item
            .on('jcarouselcontrol:active', function() {
                carouselNavigation.jcarousel('scrollIntoView', this);
                item.addClass('active');
            })
            .on('jcarouselcontrol:inactive', function() {
                item.removeClass('active');
            })
            .jcarouselControl({
                target: target,
                carousel: carouselStage
            });
    });

    // Setup controls for the stage carousel
    $('.prev-stage')
        .on('jcarouselcontrol:inactive', function() {
            $(this).addClass('inactive');
        })
        .on('jcarouselcontrol:active', function() {
            $(this).removeClass('inactive');
        })
        .jcarouselControl({
            target: '-=1'
        });

    $('.next-stage')
        .on('jcarouselcontrol:inactive', function() {
            $(this).addClass('inactive');
        })
        .on('jcarouselcontrol:active', function() {
            $(this).removeClass('inactive');
        })
        .jcarouselControl({
            target: '+=1'
        });

    // Setup controls for the navigation carousel
    $('.prev-navigation')
        .on('jcarouselcontrol:inactive', function() {
            $(this).addClass('inactive');
        })
        .on('jcarouselcontrol:active', function() {
            $(this).removeClass('inactive');
        })
        .jcarouselControl({
            target: '-=1'
        });

    $('.next-navigation')
        .on('jcarouselcontrol:inactive', function() {
            $(this).addClass('inactive');
        })
        .on('jcarouselcontrol:active', function() {
            $(this).removeClass('inactive');
        })
        .jcarouselControl({
            target: '+=1'
        });
}