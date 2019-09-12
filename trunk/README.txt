=== BH WP Autologin URLs ===
Contributors: BrianHenryIE
Donate link: https://BrianHenry.ie
Tags: login, email, links, users, newsletter, notification, simple, wp_mail
Requires at least: 5.0.0
Tested up to: 5.2.2
Requires PHP: 5.7
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Logs in users through URLs in emails sent from WordPress.

== Description ==

Autologin URLs adds login codes to emails sent to registered users and automatically logs the user in when the link is clicked.

Users are automatically logged in after receiving:

* Comment reply notifications
* Abandoned cart emails
* Membership reminder emails
* etc.

No configuration is required. Default settings:

* Autologin URLs work for one week
* Emails to administrators are excluded
* User emails on exclusion shortlist are not modified

An API is available for developers to use autologin codes elsewhere in WordPress, e.g. push notifications. Code is
published on GitHub, uses WordPress Plugin Boilerplate, conforms (mostly) to WordPress Coding Standards, and is
unit & integration tested.

== Screenshots ==

1. Example email sent via Comment Reply Email Notification plugin.
2. The settings interface.

== Changelog ==

= 1.0 =
* First release. 2019-September-01.