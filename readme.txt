=== E20R Roles for Paid Memberships Pro ===
Contributors: eighty20results
Tags: pmpro, paid memberships pro, membership plugin, membership, wordpress roles, bbpress, buddypress, capabilities, membership capabilities, pmpro add-on, pmpro addon
Requires at least: 4.7
Tested up to: 4.8.1
Stable tag: 2.1.6
=========

Appends a WordPress Role to each user's account during checkout where the role uses the Membership Level as it's displayed name.

== Description ==
This plugin provides role support for Paid Memberships Pro. The basic plugin will add/remove level specific role(s)
as/when the user purchases a membership level. This can be used together with 3rd party plugins to manage navigation
menu visibility, etc.

*Unlike the original PMPro Roles add-on, this plugin will not be disruptive to previously assigned membership
role(s) for members/users of your service*

The plugin requires the free Membership plugin (Paid Memberships Pro)[https://www.paidmembershipspro.com].

Any enabled add-on modules for this plugin will require whatever plugin or functionality they support/list as required.

The plugin currently supports the following add-on modules:

* Level Capabilities - Allows the admin to assign specific WordPress capabilities to a membership level.
* bbPress Forum - Use the "Require Membership" forum protection settings, together with membership level specific role permissions to determine the level of access a member can have to any given forum or topic. In other words, you can configure a membership level to grant the member:
* 1. No access
* 1. Read Only access
* 1. Add reply access
* 1. Add Topic access
* 1. Support access
* 1. Admin access

You can also grant read-only access to all forums (both membership protected and not) for any user/entity, even when not logged in.

These add-on modules can be licensed & activated by purchasing the add-on use license at (the Eighty / 20 Results website)[link="https://eighty20results.com/wordpress-plugins/e20r-roles-licenses"]

Requires the bbPress Forum plugin if you enable the bbPress Roles module.
Requires the BuddyPress plugin if you enable the BuddyPress Roles module (Not yet available)

== Installation ==

1. Upload the `e20r-roles-for-pmpro` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. That's it. Settings are managed through the "Settings" -> "E20R Roles for PMPro" options/settings page.
1. Licenses for the add-on(s) can be purchased on the (Eighty/20 Results website)[link="https://eighty20results.com/wordpress-plugins/e20r-roles-licenses"]
 
== Frequently Asked Questions ==

= I found a bug in the plugin. =

 Please report your issue to us by using the (E20R Roles for PMPro)[link="https://eighty20results.com/support-forums/forum/support-forums/e20r-roles-for-pmpro/"] Support Forum on The Eighty / 20 Website, and we'll try to respond within 1 business day.

= Changelog =
== 2.1.6 ==

* BUG FIX: Didn't always load the licensing_page() handler
* ENHANCEMENT: Updated to version 1.4 of the Licensing class

== 2.1.5 ==

* BUG FIX: Fatal error during upgrade of the plugin supported by this add-on
* ENHANCEMENT: Update copyright notice
* ENHANCEMENT: Use defined variable for admin JavaScript

== 2.1.4 ==

* BUG/FIX: Didn't identify self-referential license check correctly

== 2.1.3 ==

* ENHANCEMENT: Would loop indefinitely if running on licensing server

== 2.1.2 ==

* BUG/FIX: Utilities attempted called w/o beind declared
* ENHANCEMENT/FIX: Don't reach out to remote server unless the license key is present
* ENHANCEMENT/FIX: Check requirements if the add-on is enabled/active
* ENHANCEMENT: Improved documentation
* ENHANCEMENT: Refactor & update version number
* ENHANCEMENT: Use the global variable to check whether add-on is supposed to be active or not

== 2.1.1 ==

* BUG/FIX: Attempted to verify invalid licenses
* BUG/FIX: Avoid unneeded license checks
* ENHANCEMENT/FIX: Trigger license check if license is inactive _or_ the saved license is expiring.
* ENHANCEMENT: Use filter to exclude add-ons or licenses from checks
* ENHANCEMENT: Additional function doc

== 2.1.0 ==

* ENHANCEMENT/FIX: Use default logging method

== 2.0.9 ==

* BUG/FIX: Bypassed error log variables

== 2.0.8 ==

* BUG/FIX: Would log to the debug log regardless of setting

== 2.0.7 ==

* BUG/FIX: Load a single instance of the License management page, regardless of number of E20R license class instances on the system
* BUG/FIX: Didn't handle missing PMPro plugin properly
* BUG/FIX: Updated URL for the account page for license status access
* BUG/FIX: Loading duplicate E20R License Settings menu entries
* BUG/FIX: Would not load E20R Licensing menu properly
* BUG/FIX: Incorrect class protection for maybe_extract_class_name() method
* ENHANCEMENT/FIX: Properly key the license info in the add-ons
* ENHANCEMENT: Update licensing code to v1.0 (support for multiple licenses)
* ENHANCEMENT: Skip enabling the example add-on
* ENHANCEMENT: Add check for previously loaded license settings menu/page
* ENHANCEMENT: Default access for administrator users for all protected content, regardless of the protected content's level requirement

== 2.0.6 ==

* BUG/FIX: White-screen if PMPro isn't active

== 2.0.5 ==

* ENHANCEMENT: Grant access to all content for users with Administrator role
* BUG/FIX: Not all versions of PHP allows expression as default argument

== 2.0.4 ==

* ENHANCEMENT: Removed duplicate functionality (configure_addon() method)
* ENHANCEMENT: Move toggle_addon() core logic to parent E20R_Roles_Addon class for add-ons
* ENHANCEMENT: Check that add-on is licensed before allowing user to toggle it on
* ENHANCEMENT: Add warning message w/link to license page when attempting to activate an unlicensed add-on
* ENHANCEMENT/FIX: Removed dead/unused code
* ENHANCEMENT/FIX: Only update license settings in the cache if the current product has data in it
* ENHANCEMENT/FIX: Leverage parent class toggle_addon() method in Level_Capabilities add-on
* ENHANCEMENT/FIX: Use debug logging method (Utilities::log()) in Level_Capabilities add-on
* ENHANCEMENT/FIX: Move core toggle_addon() method logic to parent class
* ENHANCEMENT/FIX: Use debug logging method (Utilities::log())
* ENHANCEMENT/FIX: Use method to validate whether bbPress add-on is enabled or not
* ENHANCEMENT/FIX: Leverage parent class toggle_addon() method in BuddyPress add-on
* ENHANCEMENT/FIX: Use debug logging method (Utilities::log()) in BuddyPress add-on
* ENHANCEMENT/FIX: Avoid duplicating the license check during add-on init
* BUG/FIX: Clean up data stored in cache for the is_licensed() method
* BUG/FIX: Assumes the add-on license data is stored in the cache if the cache is valid
* BUG/FIX: White screen due to missing method

== 2.0.3 ==

* ENHANCEMENT/FIX: Incorrect use of warning message about missing membership levels
* ENHANCEMENT: Update URL for link to purchase add-on licenses

== 2.0.2 ==

* ENHANCEMENT/FIX: Disable license check cache override

== 2.0.1 ==

* BUG/FIX: License check loop
* ENHANCEMENT: Refactored

== 2.0 ==

* BUG/FIX: Use Required Level info to determine access permissions
* BUG/FIX: Improved add-on identification for option loading
* BUG/FIX: Always check upstream license server when add-on is deactivated, yet not enabled
* BUG/FIX: Only attempt to enable/activate/load add-on settings/actions/filters if we could locate the class
* BUG/FIX: Didn't handle capability array properly
* BUG/FIX: Didn't use correct capability name when adding capabilities to existing level/role definition
* BUG/FIX: Would strip role names during processing/merge of existing capabilities to role
* BUG/FIX: Didn't identify the class correctly
* ENHANCEMENT/FIX: Add TLS 1.2 support/enforcement for license validation & upgrade checks
* ENHANCEMENT/FIX: Simplify option loading/setting for bbPress Roles add-on
* ENHANCEMENT/FIX: Change role assignment/definitions to better match WP Standards
* ENHANCEMENT/FIX: Ensure proper array merge for multiple types of capabilities in the role(s).
* ENHANCEMENT/FIX: Skip processing example add-ons
* ENHANCEMENT/FIX: Ensure the status for the add-on is shown
* ENHANCEMENT/FIX: List add-ons that aren't currently licensed/active
* ENHANCEMENT/FIX: Set Topic/Topics as defaults if not defined for replacement
* ENHANCEMENT/FIX: Move maybe_extract_class_name() to parent class
* ENHANCEMENT/FIX: Explicitly state access for force_tls_12() method
* ENHANCEMENT/FIX: Better handling of existing and new capabilities for the level during level save
* ENHANCEMENT/FIX: Clean up old (bad) capability definitions
* ENHANCEMENT: Refactor for WP code style requirements
* ENHANCEMENT: Refactor for readability (grouping some methods)
* ENHANCEMENT: Update/Improve PHP Doc
* ENHANCEMENT: Save new bbPress Roles settings to DB
* ENHANCEMENT: Upgrade/Fix capability definitions for level(s)
* ENHANCEMENT: WordPress style guide compliant refactoring
* ENHANCEMENT: Add support for JavaScript on Licensing settings page
* ENHANCEMENT: Use variable to select proper settings for bbPress Add-on
* ENHANCEMENT: Set the status for the add-on when activating/loading it.
* ENHANCEMENT: Use better debug logger
* ENHANCEMENT: Better label for new roles (bbPress forum related)
* ENHANCEMENT: Use class name for settings (default)
* ENHANCEMENT: Add setting to allow/deny enabling the add-on
* ENHANCEMENT: Use more descriptive add-on option name for enabled/disabled add-ons
* ENHANCEMENT: Infrastructure to handle changes to 'is_*_active' settings in parent roles-addon class
* ENHANCEMENT: Refactor the action hooks
* ENHANCEMENT: Add PHPDoc for check_admin_screen() and check_license_warnings() methods
* ENHANCEMENT: Disable add-on module activation checkbox if explictly configured to do so
* ENHANCEMENT: Various updates to logging for Example add-on class
* ENHANCEMENT: Update how to set the Example add-on active/status values.
* ENHANCEMENT: Include 'disabled' setting (manual code override)
* ENHANCEMENT: Renamed the Configure_Capabilities class to Level_Capabilities
* ENHANCEMENT: Add roles as they apply to a specific level ID
* ENHANCEMENT: Use the E20R Roles filters to configure level specific capabilities (selected from Membership Level settings page)
* ENHANCEMENT: Configure list (table) of available capabilities for admin to assign to users who belong to the level(s)
* ENHANCEMENT: Save level specific (selected) capabilities during Membership Level save operation
* ENHANCEMENT: Show the table of capabilities on the Membership Level edit page on button click
* ENHANCEMENT: Allow clearing all checked membership level capabilities w/single button push
* ENHANCEMENT: Use filter to set add-on specific capabilities for level roles
* ENHANCEMENT: Improved labeling for Level specific role.
* ENHANCEMENT: Hide level specific capability list (show when button is clicked)
* ENHANCEMENT: Clean up BuddyPress roles add-on
* ENHANCEMENT: Admin level disabled BuddyPress roles add-on
* ENHANCEMENT: Align BuddyPress template with example template for add-ons
* ENHANCEMENT: Refactor class/methods
* ENHANCEMENT: Temporary framework for License page JavaScript
* ENHANCEMENT: Sort capabilities

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

