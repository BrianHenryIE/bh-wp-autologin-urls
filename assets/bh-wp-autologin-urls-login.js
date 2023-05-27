(function( $ ) {
	'use strict';

	$(function() {

		/**
		 * TODO: Add spinner during request.
		 * TODO: If it's a gmail address, link to gmail. etc. (only if explicitly entered in the form)
		 * TODO: Set timer to check if the magic link logged in the user in in another tab, then refresh.
		 * TODO: If the password field is empty when 'enter' is pressed, send the autologin email.
		 */

		var input = $('<input type="button" name="autologin-magic-link" id="autologin-magic-link" class="button button-large" value="Email Magic Link">');
		$('.submit').prepend(input);
		// Set the button to disabled by default. When the page is reloaded and the input already has text, it should be enabled.
		$('#autologin-magic-link').prop('disabled', $('#user_login').val().trim().length === 0);

		// Enable/disable the button when the username is filled/empty.
		$('#user_login').on('change paste keyup', function() {

			// TODO: If Enter is pressed and the password field is empty, send the email.
			// TODO: preventDefault isn't working here.
			// var key = e.which;
			// if(key === 13 && $('#user_pass').val().trim().length === 0) {
			// 	e.preventDefault();
			// 	$('#autologin-magic-link').click();
			// 	return;
			// }

			var magicLinkSendButton = $('#autologin-magic-link');

			var username = $('#user_login').val().trim();

			magicLinkSendButton.prop('disabled', (username.length === 0));
		});

		$('#autologin-magic-link').click(function(e){

			// Prevent the button from working as normal, otherwise the login form gets submitted.
			e.preventDefault();

			// TODO: Add spinner
			// $('#autologin-magic-link-spinner').css('display', 'inline');

			var ajaxurl = bh_wp_autologin_urls.ajaxurl;
			var nonce = bh_wp_autologin_urls._wp_nonce;
			var action = 'bh_wp_autologin_urls_send_magic_link';
			var username = $('#user_login').val().trim();
			// Maybe get the redirect_to URL parameter.
			// https://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
			const params = new Proxy(new URLSearchParams(window.location.search), {
				get: (searchParams, prop) => searchParams.get(prop),
			});
			var url = params.redirect_to;

			var data = {
				'_wpnonce': nonce,
				'action': action,
				'username': username,
				'url': url
			};

			// Clear previous notice/error.
			$('#login_error').remove();
			$('.message').remove();

			$.post(ajaxurl, data, function (response) {

				$('<div id="message" class="message"></div>').insertAfter('h1');
				$('#message').html('<strong>Success</strong>: ' + response.message);

				// TODO Spinner.
				// $('#autologin-magic-link-spinner').css('display', 'none');

			}).fail(function(response) {

				$('<div id="login_error"></div>').insertAfter('h1');

				var responseMessage = response.responseJSON.data.message;

				$('#login_error').html('<strong>Error</strong>: ' + responseMessage);

			});

		});
	});

})( jQuery );
