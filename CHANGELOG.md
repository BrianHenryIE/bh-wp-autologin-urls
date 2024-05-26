### 2.4.2

* Fix: fatal error with User Switching plugin – firing `wp_login` too early

### 2.4.1

* Fix fatal error with WooCommerce HPOS meta boxes hook – strict typing issue

### 2.4.0

* Add: REST API
* Add/fix: prefill WooCommerce checkout with user details from The Newsletter Plugin, Klaviyo, Mailpoet
* Fix: fatal error on first request after WooCommerce is deleted from filesystem
* Fix: broken WooCommerce orders page
* Fix: strpos() null error when HTTP_USER_AGENT missing. Thanks @sisaacrussell
* Improve: logging
* Improve: don't add autologin codes to The Newsletter Plugin emails' URLs

### 2.3.0

* Add: "Send magic link email" button on users list table
* Fix: bug with bh-wp-logger – thanks @Amit-Biswas
* Add: screenshots to .org plugin page
* Add: CLI documentation
* Fix: minor wording
* Dev: add Playwright tests

### 2.2.0

* Add: configurable template for `user-edit.php` autologin URL
* Add: `user-edit.php` autologin URL click to copy to clipboard
* Fix: "Email Magic Link" on `wp-login.php` was disabled when Firefox autofilled the username
* Fix: JS for logs page were excluded from the plugin archive

### 2.1.1

* Fix: Default expiry time when omitted in CLI was parsing as 0
* Add: Warning that logs may contain autologin codes

### 2.1.0

* Add: CLI commands `wp autologin-urls get-url` and `wp autologin-urls send-magic-link`
* Fix: Links to `/wp-admin` were redirecting to wp-login screen because `$_COOKIE` was not yet set
* Performance: Return early when no querystring set
* Fix: `wp_safe_redirect()` `exit()` is now conditional

### 2.0.0

* Breaking: UI for regex subject filters removed (functionality still exists through filters)
* Fix: Use correct `determine_current_user` filter for login

* Update library: RateLimit library has bugfix to handle `false` returned from transients for expected `array`
* Update library: bh-wp-logger library has performance and feature improvements

### 1.10.0

* Add `bh_wp_autologin_urls_should_delete_code_after_use` filter
* Improved logging

### 1.9.0

* Add: checkbox to enable/disable magic links
* Add: magic link button on WooCommerce checkout
* Add: enable overriding the settings page template
* Improve: logging
* Dev: use Alley Interactive autoloader

### 1.8.0

* Add: ignore requests from bots (check HTTP_USER_AGENT for "bot")
* Fix: do not redirect_to wp-login.php, unwrap the inner redirect_to and use that
* Fix: unprefixed Klaviyo\ApiException.
    
### 1.7.1

* Fix: set content type on HTML emails
* Fix: do not generate autologin URLs when serving WC_Orders over REST API
* Improve: hyperlinks in log table

### 1.7.0

* Add: Magic-link emails on wp-login.php and WooCommerce login forms
* Add: Git Updater
* Improved PHPCS and logging

### 1.6.3

* Fix: Catch Klaviyo API errors
* Language: Include generated .pot file

### 1.6.2

* Dependency: updated logger library for performance

### 1.6.1

Fix: Fatal error when Klaviyo querystring was defined but empty
Fix: fatal error when `plugin_action_links_{}` filter called with null as parameter values (Jetpack)
Fix: (temp) Error when WooCommerce shuts down due to virtual WC_Customer
Fix: error when wp_mail's $to is an array

### 1.6.0

* Add: Settings and logs link on plugin install confirmation page
* Fix: Prefilling WooCommerce customer data when no WP_User available: missing function parameter, wc_get_orders called too soon 

### 1.5.0

* Add: Support for using Klaviyo tracking links as autologin links
* Change: Use library nikolaposa/rate-limit for rate limiting rather than internal code
* Add: interface for integrations
* Fix: Return early when user is already logged in
* Fix: Error when Guzzle not prefixed
* Dev: Project structure changed

### 1.4.0

* Add: On admin UI single order page, add the autologin code to the "Customer payment page" link
* Security: Exclude emails with multiple recipients