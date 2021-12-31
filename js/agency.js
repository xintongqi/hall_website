/*!
 * Start Bootstrap - Agency Bootstrap Theme (http://startbootstrap.com)
 * Code licensed under the Apache License v2.0.
 * For details, see http://www.apache.org/licenses/LICENSE-2.0.
 */

/*
// Language Switching
$(function() {
   // Hide zh when the web page loads
   $('.zh').hide();
   $('#enBtn').addClass('active');
    

   $('#enBtn').on("click", function () {
       
       if (!$('#enBtn').hasClass('active')){
           $('#enBtn').addClass('active');
           // find all content with .languageA under the div post-content and hide it
           $('.zh').fadeToggle('slow',function() {
                // find all content with .languageB under the div post-content and show it
                $('.zh').hide();
                $('.en').show();
           });
           
           $('#zhBtn').removeClass('active');
       }
   });
    
   $('#zhBtn').on("click", function () {
       
       if (!$('#zhBtn').hasClass('active')){
           $('#zhBtn').addClass('active');
           // find all content with .languageB under the div post-content and hide it
           $('.en').fadeToggle('slow',function() {
                // find all content with .languageA under the div post-content and show it
                $('.en').hide();
                $('.zh').show();
                 
           });
           
           $('#enBtn').removeClass('active');
       }
   });
});   
*/


// jQuery for page scrolling feature - requires jQuery Easing plugin
$(function() {
    $('a.page-scroll').bind('click', function(event) {
        var $anchor = $(this);
        $('html, body').stop().animate({
            scrollTop: $($anchor.attr('href')).offset().top
        }, 1500, 'easeInOutExpo');
        event.preventDefault();
    });
});

// Highlight the top nav as scrolling occurs
$('body').scrollspy({
    target: '.navbar-fixed-top'
})

// Closes the Responsive Menu on Menu Item Click
$('.navbar-collapse ul li a').click(function() {
    $('.navbar-toggle:visible').click();
});

//Stop all video when any model is being closed
$('body').on('hidden.bs.modal', '.modal', function () {
    $('video').trigger('pause');
    var iframe = document.getElementById('videoIframe1');
    var tmp_src = iframe.src;
    iframe.src = '';
    iframe.src = tmp_src;
    var iframe2 = document.getElementById('videoIframe2');
    var tmp_src = iframe2.src;
    iframe2.src = '';
    iframe2.src = tmp_src;
});


/*
$(function() {
   // Hide Language B when the web page loads
   $('.en').hide();
   $('.enBtn').click(function () {
       // find all content with .languageA under the div post-content and hide it
       $('.zh').fadeToggle('slow',function() {
             // find all content with .languageB under the div post-content and show it
             $('.en').show();});
   });
   $('.zhBtn').click(function () {
       // find all content with .languageB under the div post-content and hide it
       $('.en').fadeToggle('slow',function() {
             // find all content with .languageA under the div post-content and show it
             $('.zh').show(); });
   });
});    */

