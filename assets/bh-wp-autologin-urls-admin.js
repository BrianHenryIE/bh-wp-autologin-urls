/* global navigator, jQuery, autologin_url */
(function( $ ) {
	'use strict';

	/**
	 * TODO: Copy to clipboard on user edit screen.
	 * TODO: Send magic link on user edit screen.
	 */

	$( function() {
        $( '#autologin-url' ).click( function() {
            const url = autologin_url;

            console.log( url );

            // Copy it to the clipboard.
            navigator.clipboard.writeText( url );

            // Visual indication that the text has been copied.
            $( '.user-edit-single-use-login-url' ).css( 'display', 'none' );
            $( '.user-edit-single-use-login-url' ).fadeIn( 'slow' );
        } );
    });

})( jQuery );
