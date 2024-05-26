=== Magic Emails & Autologin URLs ===
Contributors: BrianHenryIE
Donate link: https://BrianHenry.ie
Tags: login, email, links, users, newsletter, notification, simple, wp_mail
Requires at least: 4.5.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds magic email link to login screen. Adds single-use passwords to WordPress emails' URLs for frictionless login.

== Description ==

A new "Email Magic Link" button is added to the standard WordPress and WooCommerce login screens. If there is a user
account for the username filled out, they will receive an email with a link to log them in without a password.

All emails sent from WordPress will contain login codes in links pointing back to the website:

* Comment reply emails
* Abandoned cart emails
* Membership reminder emails
* etc.

No configuration is required, by default:

* Autologin URLs work for one week
* Emails to administrators are excluded
* Emails on exclusion shortlist are not modified

An API is available for developers to use autologin codes elsewhere in WordPress, e.g. push notifications, and to conditionally disable the plugin's use. Code is published on GitHub, uses WordPress Plugin Boilerplate, conforms (mostly) to WordPress Coding Standards, and is unit & integration tested.

== Screenshots ==

1. Example email sent via Comment Reply Email Notification plugin.
2. The settings interface.

== Changelog ==

= 2.4.2 =

* Fix: fatal error with User Switching plugin – firing `wp_login` too early

= 2.4.1 =

* Fix fatal error with WooCommerce HPOS meta boxes hook – strict typing issue

= 2.4.0 =

* Add: REST API
* Add/fix: prefill WooCommerce checkout with user details from The Newsletter Plugin, Klaviyo, Mailpoet
* Fix: fatal error on first request after WooCommerce is deleted from filesystem
* Fix: broken WooCommerce orders page
* Fix: strpos() null error when HTTP_USER_AGENT missing. Thanks @sisaacrussell
* Improve: logging
* Improve: don't add autologin codes to The Newsletter Plugin emails' URLs

= 2.3.0 =

* Add: "Send magic link email" button on users list table
* Fix: bug with bh-wp-logger – thanks @Amit-Biswas
* Add: screenshots to .org plugin page
* Add: CLI documentation
* Fix: minor wording
* Dev: add Playwright tests

= 2.2.0 =

* Add: configurable template for `user-edit.php` autologin URL
* Add: `user-edit.php` autologin URL click to copy to clipboard
* Fix: "Email Magic Link" on `wp-login.php` was disabled when Firefox autofilled the username
* Fix: JS for logs page were excluded from the plugin archive

= 2.1.1 =

* Fix: Default expiry time when omitted in CLI was parsing as 0
* Add: Warning that logs may contain autologin codes

= 2.1.0 =

* Add: CLI commands `wp autologin-urls get-url` and `wp autologin-urls send-magic-link`
* Fix: Links to `/wp-admin` were redirecting to wp-login screen because `$_COOKIE` was not yet set
* Performance: Return early when no querystring set
* Fix: `wp_safe_redirect()` `exit()` is now conditional

= 2.0.0 =

* Breaking: UI for regex subject filters removed (functionality still exists through filters)
* Fix: Use correct `determine_current_user` filter for login
* Update library: RateLimit library has bugfix to handle `false` returned from transients for expected `array`
* Update library: bh-wp-logger library has performance and feature improvements

= 1.10.0 =

* Add `bh_wp_autologin_urls_should_delete_code_after_use` filter
* Improved logging

= 1.9.0 =

* Add: checkbox to enable/disable magic links
* Add: magic link button on WooCommerce checkout
* Add: enable overriding the settings page template
* Improve: logging
* Dev: use Alley Interactive autoloader

= 1.8.0 =

* Add: ignore requests from bots (check HTTP_USER_AGENT for "bot")
* Fix: do not redirect_to wp-login.php, unwrap the inner redirect_to and use that
* Fix: unprefixed Klaviyo\ApiException.

= 1.7.1 =

* Fix: set content type on HTML emails
* Fix: do not generate autologin URLs when serving WC_Orders over REST API
* Improve: hyperlinks in log table

= 1.7.0 =

* Add: Magic-link emails on wp-login.php and WooCommerce login forms
* Add: Git Updater
* Improved PHPCS and logging

= 1.6.3 =

* Fix: Catch Klaviyo API errors
* Language: Include generated .pot file

= 1.6.2 =

* Dependency: updated logger library for performance

= 1.6.1 =

* Fix: Fatal error when Klaviyo querystring was defined but empty
* Fix: fatal error when `plugin_action_links_{}` filter called with null as parameter values (Jetpack)
* Fix: (temp) Error when WooCommerce shuts down due to virtual WC_Customer
* Fix: error when wp_mail's $to is an array

= 1.6.0 =

* Add: Settings and logs link on plugin install confirmation page
* Fix: Prefilling WooCommerce customer data when no WP_User available: missing function parameter, wc_get_orders called too soon

= 1.5.0 =

* Add: Support for using Klaviyo tracking links as autologin links
* Change: Use library nikolaposa/rate-limit for rate limiting rather than internal code
* Add: interface for integrations
* Fix: Return early when user is already logged in
* Fix: Error when Guzzle not prefixed
* Dev: Project structure changed

= 1.4.0 =

* Add: On admin UI single order page, add the autologin code to the "Customer payment page" link
* Security: Exclude emails with multiple recipients

= 1.3.3 =

* Add: Use custom table for storing codes, because transients are too ephemeral
* Add: Option to bounce users through wp-login.php, to avoid caching issues
* Add: Logs users in from links in The Newsletter Plugin
* Add: Logs users in from links in MailPoet
* Add: Prefill WooCommerce checkout data for mailing list users without accounts
* Add: More secure verification via hash_equals
* Add: Rate limiting bad login attempts
* Add: Logging (mostly off by default)

= 1.1.2 =

* Improved dependency management with Mozart for Composer.

= 1.1.1 =
* Auto-deploying to WordPress.org

= 1.1.0 =
* Rate limiting added for multiple failed login attempts against user accounts and from IPs. This only affects Autologin URLs logins and does not affect other authentication. 2019-September-11.

= 1.0 =
* First release. 2019-September-01.