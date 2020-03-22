=== Autologin URLs ===
Contributors: BrianHenryIE
Donate link: https://BrianHenry.ie
Tags: login, email, links, users, newsletter, notification, simple, wp_mail
Requires at least: 4.5.0
Tested up to: 5.3.2
Requires PHP: 5.7
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Logs in users through URLs in emails sent from WordPress.

== Description ==

Users will be automatically logged in when clicking links in emails sent from WordPress:

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

= 1.1.2 =

* Improved dependency management with Mozart for Composer.

= 1.1.1 =
* Auto-deploying to WordPress.org

= 1.1.0 =
* Rate limiting added for multiple failed login attempts against user accounts and from IPs. This only affects Autologin URLs logins and does not affect other authentication. 2019-September-11.

= 1.0 =
* First release. 2019-September-01.