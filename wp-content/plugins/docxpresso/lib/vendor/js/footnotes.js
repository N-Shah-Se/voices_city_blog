(function( $ ) {
    //footnotes
    $('.defaultNote').children(":first").css('cursor', 'pointer');
    $('.defaultNote').children(":first").click(function(){
        var gotoID = '#' + $(this).parent().attr('id');
        $('html, body').animate(
            {
              scrollTop: $('a[href="' + gotoID + '"]').offset().top - 60,
            },
            250,
            'linear'
          )
    });
}(jQuery));


