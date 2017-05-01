=== E20R Roles for PMPro ===
Contributors: eighty20results
Tags: pmpro, membership, wordpress roles, bbpress, buddypress
Requires at least: 4.7
Tested up to: 4.7.4
Stable tag: 1.9.1
=========

Adds a WordPress Role for each Membership Level with Display Name = Membership Level Name and Role Name = 'e20r_roles_level_X' (where X is the Membership Level's ID).


== Description ==
This plugin requires the free Membership plugin Paid Memberships Pro. Any add-on modules for this plugin will require whatever plugin or functionality they list.

Requires the bbPress Forum plugin if you enable the bbPress Roles module.
Requires the BuddyPress plugin if you enable the BuddyPress Roles module (Support forthcoming)

== Installation ==

1. Upload the `e20r-roles-for-pmpro` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. That's it. Settings are managed through the "Settings" -> "E20R Roles for PMPro" options/settings page.
1. Licenses for the add-on(s) can be purchased on the Eighty/20 Results website (https://eighty20results.com/licenses)
 
== Frequently Asked Questions ==

= I found a bug in the plugin. =

 Please report your issue to us by using the (E20R Roles for PMPro)[link="https://eighty20results.com/support-forums/forum/support-forums/e20r-roles-for-pmpro/"] Support Forum on The Eighty / 20 Website, and we'll try to respond within 1 business day.

= Changelog =
== 1.9.1 ==

* ENHANCEMENT/FIX: Change priority to load the PMPro/Roles access filter handler
* ENHANCEMENT: Add cache support to the level_has_posts_access() method
* ENHANCEMENT: Checks whether a level has access to a post ID (without user info)
* ENHANCEMENT/FIX: No need to check if the forum should be closed by the non-existent permissions of a user who isn't logged in
* BUG/FIX: Didn't open/close the forum/topic/reply correctly for some users
* ENHANCEMENT: Add custom filter removal function
* ENHANCEMENT: Add support for replacing default 'topic(s)' with user specified word(s) in remove_replies_text() and remove_topics_text() methods
* BUG/FIX: Don't process the post list if the user isn't logged in and they're supposed to be able to see any posts anonymously
* BUG/FIX: Didn't grant everybody access to the forum & topics when global read access was enabled
* BUG/FIX: Couldn't always save the correct 'Hide Forums' setting
* ENHANCEMENT: Refactor 'e20r_roles_addon_has_access' filter handler for bbPress Roles


== 1.9 ==

* ENHANCEMENT: Didn't permit search/view of protected forum if global read access was granted
* ENHANCEMENT: Didn't allow non-members/logged out users to see forums/topics/replies even if anonymous read is permitted
* ENHANCEMENT: Would sometimes incorrectly deny forum access

== 1.8 ==

* ENHANCEMENT: Add test to avoid redirect loops for forum archive when no access is granted
* ENHANCEMENT: Now excludes/includes forums, topics and replies from searches if PMPro is configured to do so
* ENHANCEMENT: Load metabox for PMPro membership levels to require for forum(s)

flofl== 1.7 ==

* ENHANCEMENT: Documentation for get_memberships() member function
* ENHANCEMENT/FIX: Fixed a PHP Warning in License code
* ENHANCEMENT: Reformatted for WordPress Codex style
* ENHANCEMENT: Adding `e20r_roles_add_level_role` and `e20r_roles_delete_level_role` actions to allow user/level specific settings/roles to be defined (used by bbPress add-on)
* ENHANCEMENT/FIX: Load PMPro content check filter early
* ENHANCEMENT: Add case insensitive search with case specific replace to Utilities
* ENHANCEMENT: Use PMPro_Members class members to look up member levels for users, etc
* BUG/FIX: Use 'spectate' capability for read_* permissions
* BUG/FIX: Rename 'threads' to 'topics' in variable names (bbPress standard naming)
* ENHANCEMENT: Allow admin to replace 'topic|topics' with own label(s)
* ENHANCEMENT: Add restrict_forums() method to redirect user to forum summary/archive page if they do not have access to the forum(s)
* ENHANCEMENT/FIX: Improved check_access() method (faster)
* ENHANCEMENT: Use un-cached data whenever in DEBUG mode
* ENHANCEMENT: Add test for global read permission in user_can_read() method
* ENHANCEMENT: Add allow_anon_read() check for global read access (regardless of membership level(s))
* ENHANCEMENT: Simplify the check_forum_access_for() method
* ENHANCEMENT: Remove duplicate user access check method
* ENHANCEMENT: Stop checking access rights if we're processing something other than a bbPress post type
* ENHANCEMENT: Would sometimes block access for members who were supposed to have access to a forum/topic/reply
* ENHANCEMENT: Use post meta to grant/deny overall PMPro Control for a membership level
* ENHANCEMENT: Configure membership level capabilities when saving level definition
* ENHANCEMENT: Added per-level bbPress roles & management
* ENHANCEMENT: Include protected member forums on user's account page
* ENHANCEMENT: Add is_topic(), is_reply(), is_forum methods
* ENHANCEMENT: Use standard bbPress forum roles & capabilities for each membership level that's been configured for forum use
* ENHANCEMENT: BuddyPress support would sometimes interfere with bbPress (forum) support

== 1.6 ==

* ENHANCEMENT: Clear cache when user updates their profile
* ENHANCEMENT: Add Framework for BuddyPress support
* ENHANCEMENT: Add User list column update for E20R Role assignment

== 1.5 ==

* BUG/FIX: Whitescreen during PMPro Checkout

== 1.4 ==

* BUG/FIX: Whitescreen when deleting PMPro Membership Level(s)
* ENHANCEMENT: Avoid confusion in namespace(s)
* ENHANCEMENT: Clean up Namespace

== 1.3 ==

* ENH/FIX: Reordered Option filters

== 1.2 ==

* BUG/FIX: Didn't include new license settings on load
* BUG/FIX: Missing source files in build process
* ENHANCEMENT: Plugin Updates to v4.x

== 1.0 ==

* Adds support for role management by membership level & infrastructure for modules like a bbPress forum access module.
* Initial Release (v1.0) - Trial version (Beta).

