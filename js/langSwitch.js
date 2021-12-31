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