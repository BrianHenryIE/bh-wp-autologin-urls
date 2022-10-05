(function( $ ) {
	'use strict';

	$(function() {

		var input = $('<button type="button" class="woocommerce button woocommerce-button button woocommerce-form-login__send_magic_link " name="send-magic-link" id="autologin-magic-link" value="Send Magic Link">Email Magic Link</button>');
		input.insertBefore($('#woocommerce-login-nonce'));

		var usernameInput = $('#username');
		var magicLinkButton = $('#autologin-magic-link');

		// Set the button to disabled by default. When the page is reloaded and the input already has text, it should be enabled.
		magicLinkButton.prop('disabled', usernameInput.val().trim().length === 0);

		// Enable/disable the button when the username is filled/empty.
		usernameInput.keyup(function(e) {
			var magicLinkSendButton = $('#autologin-magic-link');
			var username = $('#username').val().trim();
			magicLinkSendButton.prop('disabled', (username.length === 0));
		});

		magicLinkButton.click(function(e){

			// TODO: Add spinner? Where?
			// $('#autologin-magic-link-spinner').css('display', 'inline');

			var ajaxurl = bh_wp_autologin_urls.ajaxurl;
			var nonce = bh_wp_autologin_urls._wp_nonce;
			var action = 'bh_wp_autologin_urls_send_magic_link';
			var username = $('#username').val().trim();

			var url = $("input[name='_wp_http_referer']").val();

			var data = {
				'_wpnonce': nonce,
				'action': action,
				'username': username,
				'url': url
			};

			$.post(ajaxurl, data, function (response) {

				var responseMessage = response.message;
				var responseHtml = '<ul class="woocommerce-info" role="alert"><li><strong>Success:</strong> '+responseMessage+'</li></ul>';

				var noticesWrapper = $('.woocommerce-notices-wrapper').first();
				noticesWrapper.first().html(responseHtml);

				$.scroll_to_notices(noticesWrapper);

				// TODO Spinner.
				// $('#autologin-magic-link-spinner').css('display', 'none');

			}).fail(function(response) {

				var responseMessage = response.responseJSON.data.message;

				var responseHtml = '<ul class="woocommerce-error" role="alert"><li><strong>Error:</strong> '+responseMessage+'</li></ul>';

				var noticesWrapper = $('.woocommerce-notices-wrapper').first();
				noticesWrapper.first().html(responseHtml);

				// @see woocommerce.js:74 scroll_to_notices.
				$.scroll_to_notices(noticesWrapper);
			});

		});
	});

})( jQuery );
