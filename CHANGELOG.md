

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