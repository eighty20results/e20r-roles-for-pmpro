=== E20R Roles for PMPro ===
Contributors: eighty20results
Tags: pmpro, membership, wordpress roles, bbpress, buddypress
Requires at least: 4.7
Tested up to: 4.7.4
Stable tag: 1.9.9
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
== 1.9.9 ==

* BUG/FIX: Fatal error during processing of new membership level

== 1.9.8 ==

* BUG/FIX: Fatal error during activation of plugin

== 1.9.7 ==

* ENHANCEMENT/FIX: PHP Warning during license check

== 1.9.6 ==

* ENHANCEMENT/FIX: Didn't load the product name correctly in licensing check

== 1.9.5 ==

* BUG/FIX: Hide/Show member forums for anonymous users (not logged in or non-members).
* BUG/FIX: Redirecting member forums even if read access is permitted
* BUG/FIX: Translation missing

== 1.9.4 ==

* ENHANCEMENT/FIX: Didn't show the license name correctly in error/warning message
* ENHANCEMENT/FIX: License status check was borked
* ENHANCEMENT/FIX: Didn't always correctly identify previous membership level(s) for user in PMPRO change level filter handlers
* ENHANCEMENT/FIX: Don't try to add role(s) during level cancellations
* ENHANCEMENT/FIX: Attempted to deactivate licenses that hadn't been activated (had no key)
* ENHANCEMENT/FIX: Redirect if the 'hide forums' setting is enabled _and_ the forum ID is protected
* ENHANCEMENT/FIX: Set the 'forum role' on change of level
* ENHANCEMENT/FIX: Simplify config & settings for bbPress add-on
* ENHANCEMENT/FIX: PHP Warning if there were no membership levels defined
* ENHANCEMENT: More precise missing license warning message
* ENHANCEMENT: Clear and refresh the user/member level cache on user update/change
* ENHANCEMENT: Adding debug logging to after_change_membership_level() method in Manage_Roles
* ENHANCEMENT: Fixed documentation for Cache::set()
* ENHANCEMENT: Clear the license cache whenever we access the admin options page (force license check)
* ENHANCEMENT: Don't pass-through the add-on toggle
* ENHANCEMENT: Only use the change_membership_level actions to modify roles/etc
* ENHANCEMENT: Use the standard debug logging function
* ENHANCEMENT: Convert e20r_roles[add|delete]_level_role to a filter (allows status return)
* ENHANCEMENT: Support cancelling multiple membership levels at once
* ENHANCEMENT: Do not close forums that are public (not protected)
* ENHANCEMENT: Add infrastructure to simplify allowing edit for any logged in user to a non-protected forum.
* ENHANCEMENT: Show message if attempting to define settings for a Membership level and there's no level defined on the system
* ENHANCEMENT: Adding documentation for maybe_extract_class_name() in BuddyPress add-on class
* ENHANCEMENT: Label for licensed add-on/product was incorrect
* ENHANCEMENT: Allow bypass of cached value (force DB look-up)
* ENHANCEMENT: Look-up and cache previous membership levels & statuses for a user ID
* ENHANCEMENT: Use default/standard error/debug logging function (Utilities::log())
* BUG/FIX: Incorrect order for pmpro_after_change_membership_level handler arguments
* BUG/FIX: Didn't load add-on specific filter & hook handlers for bbPress add-on
* BUG/FIX: Swap order of before & after change membership level hooks (delete & clean up old levels, _then_ add new ones)
* BUG/FIX: Didn't use the correct update_list_of_level_members() method during handling of 'pmpro_after_change_membership_level'

== 1.9.3 ==

* ENHANCEMENT: Add function to retrieve all membership levels that are required by the post ID
* ENHANCEMENT: Refactor for WordPress code style
* ENHANCEMENT: Reduce code duplication
* ENHANCEMENT: Make add-on load/init/handling more generic for BuddyPress add-on
* ENHANCEMENT: Adding an example add-on module
* ENHANCEMENT/FIX: Make example add-on more generic in class/config handling
* ENHANCEMENT/FIX: Load level info from the PMPro table for the forum/level column
* BUG/FIX: Error while configuring the BuddyPress add-on

== 1.9.2 ==

* ENHANCEMENT: Add column showing membership level required to perform member based permissions

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

