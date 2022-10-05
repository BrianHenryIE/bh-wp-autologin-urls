(function( $ ) {
    'use strict';

    $(function() {

        // Copy to clipboard.
        $('.wc-order-status a').click(function(e){

            // Since we're logged in as an admin, and this is now a login link,
            // prevent the link from working as normal, otherwise the admin gets logged out.
            e.preventDefault();

            // Get the URL.
            var url = $(this)[0].href;

            // Copy it to the clipboard.
            navigator.clipboard.writeText(url);

            // Visual indication that the text has been copied.
            $(this).css('display', 'none');
            $(this).fadeIn('slow');
        });

    });

})( jQuery );
