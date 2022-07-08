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