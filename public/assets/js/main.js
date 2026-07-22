jQuery(document).ready(function ($) {

    jQuery('.f-p-carousel').owlCarousel({
        loop: true,
        margin: 30,
        nav: true,
        dots: false,
        navText: ['<i class="fa fa-arrow-left"></i>', '<i class="fa fa-arrow-right"></i>'],
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
        }
    });
    $('#english').on('click', function (e) {
        $('html').attr('dir', 'ltr');
        $('.english').css({
            "display": "block"
        });
        $('.urdu').css({
            "display": "none"
        });
    });
    $('#urdu').on('click', function (e) {
        $('html').attr('dir', 'rtl');
        $('.english').css({
            "display": "none"
        });
        $('.urdu').css({
            "display": "block"
        });

    })


}); // End of document ready function
