<?php
/**
 * Copyright (c) $today.year. - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace E20R\Roles_For_PMPro\Addon;

use E20R\Roles_For_PMPro\E20R_Roles_For_PMPro;
use E20R\Roles_For_PMPro\Role_Definitions;
use E20R\Utilities\Cache;
use E20R\Utilities\Utilities;
use E20R\Licensing;

define( 'ADDON_STUB', 'buddypress_roles' );
define( 'ADDON_NAME', 'BuddyPress Roles' );

if ( ! class_exists( 'E20R\\Roles_For_PMPro\\Addon\\BuddyPress_Roles' ) ) {
	
	class BuddyPress_Roles extends E20R_Roles_Addon {
		
		const CAN_ACCESS = 'can_access';
		const CAN_READ = 'can_read';
		const CAN_EDIT = 'can_edit';
		const CAN_ADD = 'can_add';
		
		const CACHE_GROUP = 'buddypress_roles';
		
		private $_add_topic_perm = 'publish_topics';
		private $_add_forum_perm = 'publish_forums';
		private $_add_reply_perm = 'publish_replies';
		private $_edit_reply_perm = 'edit_replies';
		private $_edit_topic_perm = 'edit_topics';
		private $_edit_forum_perm = 'edit_forums';
		private $_read_topic_perm = 'read_topics';
		private $_read_reply_perm = 'read_replies';
		private $_read_forum_perm = 'read_forums';
		
		/**
		 * The name of this class
		 *
		 * @var string
		 */
		private $class_name;
		
		/**
		 * @var BuddyPress_Roles
		 */
		private static $instance;
		
		/**
		 * Name of the WordPress option key
		 *
		 * @var string $option_name
		 */
		private $option_name = 'e20r_ao_buddypress';
		
		/**
		 * @var array List of capabilities to grant zero access to bbPress forum/thread/replies
		 */
		private $_no_access_capabilities = array();
		
		/**
		 * @var array List of read capabilities for bbPress
		 */
		private $_read_only_capabilities = array();
		
		/**
		 * @var array List of capabilities needed to add a reply in bbPress
		 */
		private $_add_replies_capabilities = array();
		
		/**
		 * @var array List of capabilities needed to add a thread in bbPress
		 */
		private $_add_threads_capabilities = array();
		
		/**
		 * @var array List of capabilities needed to add a forum in bbPress
		 */
		private $_add_forum_capabilities = array();
		
		/**
		 * @var array List of capabilities needed to perform support activities in bbPress
		 */
		private $_forum_support_capabilities = array();
		
		/**
		 * @var array List of capabilities needed to perform admin activities for bbPress
		 */
		private $_forum_admin_capabilities = array();
		
		/**
		 * BuddyPress_Roles constructor.
		 */
		protected function __construct() {
			
			parent::__construct();
			
			if ( is_null( self::$instance ) ) {
				self::$instance = $this;
			}
			$this->class_name = $this->maybe_extract_class_name( get_class( $this ) );
			$this->define_settings();
		}
		
		private function get_class_name() {
			
			if ( empty( $this->class_name ) ) {
				$this->class_name = $this->maybe_extract_class_name( get_class( self::$instance ) );
			}
			
			return $this->class_name;
		}
		
		private function maybe_extract_class_name( $string ) {
			
			if ( WP_DEBUG ) {
				error_log( "Supplied (potential) class name: {$string}" );
			}
			
			$class_array = explode( '\\', $string );
			$name        = $class_array[ ( count( $class_array ) - 1 ) ];
			
			return $name;
		}
		
		/**
		 * Load actions & hooks for the add-on
		 *
		 * @param   null|string $stub
		 */
		public static function load_addon( $stub = null ) {
			
			$class      = self::get_instance();
			$utils      = Utilities::get_instance();
			$class_name = $class->get_class_name();
			
			if ( WP_DEBUG ) {
				error_log( "Loading the {$class_name} class action(s) " );
			}
			
			global $e20r_roles_addons;
			
			if ( is_null( $stub ) ) {
				$stub = $class_name;
			}
			
			// Check license status & prerequisites for this add-on
			parent::load_addon( $stub );
			
			/**
			 * // Only load the actions if we're active
			 * if ( true == $e20r_roles_addons[ $stub ]['is_active'] ) {
			 *
			 * $utils->log( "Loading actions/filters for {$e20r_roles_addons[$stub]['label']}" );
			 *
			 *
			 * // Membership related settings for role(s) add-on
			 *
			 * add_action( 'e20r_roles_level_settings', array( $class, 'load_level_settings' ), 10, 2 );
			 * add_action( 'e20r_roles_level_settings_save', array( $class, 'save_level_settings' ), 10, 2 );
			 * add_action( 'e20r_roles_level_settings_delete', array( $class, 'delete_level_settings' ), 10, 2 );
			 *
			 * add_filter( 'e20r-license-add-new-licenses', array( $class, 'add_new_license_info' ), 10, 1 );
			 *
			 * // Configuration actions & filters
			 * add_filter( 'e20r_roles_general_level_capabilities', array(
			 * $class,
			 * 'add_capabilities_to_role',
			 * ), 10, 3 );
			 *
			 * // Access filters for the add-on to use/leverage
			 * add_filter( 'e20r_roles_addon_has_access', array( $class, 'has_access' ), 10, 4 );
			 * add_filter( 'the_posts', array( $class, 'check_access' ), 10, 2 );
			 *
			 * add_filter( 'bbp_is_forum_closed', array( $class, 'close_forum' ), 10, 3 );
			 *
			 * $class->configure_forum_admin_capabilities();
			 * }
			 */
		}
		
		/**
		 * Filter Handler: Add the 'add bbPress add-on license' settings entry
		 *
		 * @filter e20r-license-add-new-licenses
		 *
		 * @param array $settings
		 *
		 * @return array
		 */
		public function add_new_license_info( $settings ) {
			
			global $e20r_roles_addons;
			
			if ( ! isset( $settings['new_licenses'] ) ) {
				$settings['new_licenses'] = array();
			}
			
			if ( WP_DEBUG ) {
				error_log( "Have " . count( $settings['new_licenses'] ) . " new to process already" );
			}
			
			$stub = strtolower( $this->get_class_name() );
			
			$settings['new_licenses'][] = array(
				'label_for'     => $stub,
				'fulltext_name' => $e20r_roles_addons[ $stub ]['label'],
				'new_product'   => $stub,
				'option_name'   => "e20r_license_settings",
				'name'          => 'license_key',
				'input_type'    => 'password',
				'value'         => null,
				'email_field'   => "license_email",
				'email_value'   => null,
				'placeholder'   => sprintf( __( "Paste the purchased E20R Roles %s key here", "e20r-licensing" ), $e20r_roles_addons[ $stub ]['label'] ),
			);
			
			return $settings;
		}
		
		/**
		 * Deny edit access to the forum if the user isn't supposed to have access to it
		 *
		 * @param $closed
		 * @param $forum_id
		 * @param $check_ancestors
		 *
		 * @return bool
		 */
		public function close_forum( $closed, $forum_id, $check_ancestors ) {
			
			$utils = Utilities::get_instance();
			
			$utils->log( "Ancestors argument: " . print_r( $check_ancestors, true ) );
			$utils->log( "Forum status: {$closed}" );
			$user_id     = get_current_user_id();
			$permissions = $this->get_user_level_perms( $user_id );
			
			// Can't be closed to support & admins
			if ( in_array( $permissions, array( 'forum_support', 'forum_admin' ) ) ) {
				return false;
			}
			
			if ( bbp_is_single_topic() ) {
				$utils->log( "Processing for a topic: {$forum_id}" );
				
				$closed = ( ! user_can( $user_id, 'publish_topics' ) );
				add_filter( 'gettext', array( $this, 'remove_replies_text' ), 10, 3 );
			}
			
			if ( bbp_is_single_forum() ) {
				
				$utils->log( "Processing for a forum: {$forum_id}" );
				$closed = ( ! user_can( $user_id, 'publish_forums' ) );
				add_filter( 'gettext', array( $this, 'remove_topics_text' ), 10, 3 );
			}
			
			if ( bbp_is_single_reply() ) {
				
				$utils->log( "Processing for a reply: {$forum_id}" );
				$closed = ( ! user_can( $user_id, 'publish_replies' ) );
				add_filter( 'gettext', array( $this, 'remove_replies_text' ), 10, 3 );
			}
			
			
			// Block outgoing notification email(s)
			if ( true === $closed ) {
				add_filter( 'bbp_subscription_mail_message', array(
					self::get_instance(),
					'prevent_subscr_mail',
				), 9999999, 3 );
			}
			
			$utils->log( "Forum status: {$closed}" );
			
			return $closed;
		}
		
		public function remove_topics_text( $translated, $text, $domain ) {
			global $pmpro_pages;
			
			if ( $domain === 'buddypress' && false !== strpos( $text, 'is closed to new topics and replies' ) ) {
				$translated = sprintf( 'Log in, join, or upgrade your membership level to <a href="%s" target="_blank">add new questions to the forum</a>', get_permalink( $pmpro_pages['levels'] ) );
			}
			
			return $translated;
		}
		
		public function remove_replies_text( $translated, $text, $domain ) {
			
			global $pmpro_pages;
			
			if ( $domain === 'buddypress' && false !== strpos( $text, 'is closed to new topics and replies' ) ) {
				$translated = sprintf( 'Log in or become a member to <a href="%s" target="_blank">ask questions or post replies</a>', get_permalink( $pmpro_pages['levels'] ) );
			}
			
			return $translated;
		}
		
		/**
		 * Don't send email notice(s)
		 *
		 * @param string $message
		 * @param int    $reply_id
		 * @param int    $topic_id
		 *
		 * @return mixed Email message or null
		 */
		public function prevent_subscr_mail( $message, $reply_id, $topic_id ) {
			
			return false;
		}
		
		/**
		 * TODO: Add functionality to restrict bbPress forum(s) (port pmpro-bbPress add-on functionality)
		 */
		public function restrict_forums() {
		
		}
		
		/**
		 * TODO: Set the Membership Level when a pmpro_changeMembershipLevel() call is made
		 */
		public function set_role_on_level_change( $user_id, $level_id ) {
		
		}
		
		/**
		 * Can the user access the post(s) in the forum(s)
		 *
		 * @param array     $posts
		 * @param \WP_Query $query
		 *
		 * @return array
		 */
		public function check_access( $posts, $query ) {
			
			$utils = Utilities::get_instance();
			
			$filtered_posts = array();
			$user_id        = get_current_user_id();
			
			foreach ( $posts as $post ) {
				
				switch ( $post->post_type ) {
					case 'forum':
					case 'topic':
					case 'reply':
						$can_access = $this->user_can_read( $post->ID, $user_id );
						
						if ( true === $can_access || ( true == $this->load_option( 'global_anon_read' ) && false === $can_access ) ) {
							$utils->log( "Allowing inclusion of {$post->post_type} for {$user_id} to {$post->ID}" );
							
							$filtered_posts[] = $post;
						} else {
							$utils->log( "Denying inclusion of {$post->post_type} for {$user_id} to {$post->ID}" );
						}
						
						break;
					
					default:
						$filtered_posts[] = $post;
				}
			}
			
			return $filtered_posts;
			
		}
		
		/**
		 * Check for edit permissions to the forum/thread/reply
		 *
		 * @param int $post_id
		 * @param int $user_id
		 *
		 * @return bool
		 *
		 * @access public
		 * @since  1.0
		 */
		public function user_can_edit( $post_id, $user_id ) {
			
			$result = false;
			$utils  = Utilities::get_instance();
			
			if ( ! empty( $post_id ) ) {
				
				if ( null === $user_id ) {
					$user_id = get_current_user_id();
				}
				
				if ( null === ( $result = Cache::get( self::CAN_EDIT . "_{$user_id}_{$post_id}", self::CACHE_GROUP ) ) ) {
					
					$result = $this->check_forum_access_for( $user_id, $post_id, 'edit' );
					
					$utils->log( "User {$user_id} " . ( $result ? 'can' : 'can\'t' ) . " edit {$post_id}" );
					
					Cache::set( self::CAN_EDIT . "_{$user_id}_{$post_id}", $result, ( 10 * MINUTE_IN_SECONDS ), self::CACHE_GROUP );
				}
			}
			
			return $result;
		}
		
		/**
		 * Checks whether the user has the right(s) to add to the forum/topic/reply
		 *
		 * @param int $post_id
		 * @param int $user_id
		 *
		 * @return bool
		 */
		public function user_can_add( $post_id, $user_id ) {
			
			$result = false;
			$utils  = Utilities::get_instance();
			
			if ( ! empty( $post_id ) ) {
				
				if ( null === $user_id ) {
					$user_id = get_current_user_id();
				}
				
				if ( null === ( $result = Cache::get( self::CAN_ADD . "_{$user_id}_{$post_id}", self::CACHE_GROUP ) ) ) {
					
					$result = $this->check_forum_access_for( $user_id, $post_id, 'add' );
					
					$utils->log( "User {$user_id} " . ( $result ? 'can' : 'can\'t' ) . " add {$post_id}" );
					
					Cache::set( self::CAN_ADD . "_{$user_id}_{$post_id}", $result, ( 10 * MINUTE_IN_SECONDS ), self::CACHE_GROUP );
				}
			}
			
			return $result;
		}
		
		/**
		 * Check for read permissions to forum/topic/reply
		 *
		 * @param          $post_id
		 * @param null|int $user_id
		 *
		 * @return bool|mixed|null
		 */
		public function user_can_read( $post_id, $user_id = null ) {
			
			$result = false;
			$utils  = Utilities::get_instance();
			
			if ( ! empty( $post_id ) ) {
				
				if ( null === $user_id ) {
					$user_id = get_current_user_id();
				}
				
				if ( null === ( $result = Cache::get( self::CAN_READ . "_{$user_id}_{$post_id}", self::CACHE_GROUP ) ) ) {
					
					$result = $this->check_forum_access_for( $user_id, $post_id, 'read' );
					
					$utils->log( "User {$user_id} " . ( $result ? 'can' : 'can\'t' ) . " read {$post_id}" );
					
					Cache::set( self::CAN_READ . "_{$user_id}_{$post_id}", $result, ( 10 * MINUTE_IN_SECONDS ), self::CACHE_GROUP );
				}
			}
			
			return $result;
		}
		
		/**
		 * Check access permissions based on access type (read/add/edit)
		 *
		 * @param int    $user_id
		 * @param int    $post_id
		 * @param string $access_type
		 *
		 * @return bool
		 */
		private function check_forum_access_for( $user_id, $post_id, $access_type ) {
			
			$utils = Utilities::get_instance();
			
			$or_var     = ( true == $this->load_option( 'global_anon_read' ) ? true : false );
			$override   = ( $or_var && $access_type == 'read' );
			$permission = $this->get_user_level_perms( $user_id );
			$result     = false;
			
			$prefix = '_read';
			
			switch ( $access_type ) {
				case 'edit':
					$prefix = '_edit';
					break;
				case 'add':
					$prefix = '_publish';
					break;
			}
			
			$utils->log( "Access type is {$access_type} and override for read is {$override}" );
			
			if ( 'no_access' !== $permission || ( 'no_access' === $permission && true == $override ) ) {
				
				$capabilities = $this->select_capabilities( $permission );
				$post_type    = get_post_type( $post_id );
				
				$forum_perm = $this->{"${prefix}_forum_perm"};
				$topic_perm = $this->{"{$prefix}_topic_perm"};
				
				$utils->log( "Checking {$post_type} for {$forum_perm} or {$topic_perm}" );
				switch ( $post_type ) {
					
					case 'topic':
						if ( $topic = bbp_get_topic( $post_id ) ) {
							
							$forum_id = $topic->post_parent;
							
							if ( ! empty( $forum_id ) ) {
								$result = ( in_array( $forum_perm, $capabilities ) && user_can( $user_id, $forum_perm ) );
								$utils->log( " Forum permission for {$user_id} is " . ( $result ? 'Yes' : 'No' ) );
							}
						}
						break;
					
					case 'reply':
						if ( $reply = bbp_get_reply( $post_id ) ) {
							
							$topic_id = $reply->post_parent;
							
							if ( ! empty( $topic_id ) && ( in_array( $topic_perm, $capabilities ) && user_can( $user_id, $topic_perm ) ) ) {
								
								if ( $topic = bbp_get_topic( $topic_id ) ) {
									
									$forum_id = $topic->post_parent;
									
									if ( ! empty( $forum_id ) ) {
										$result = ( in_array( $forum_perm, $capabilities ) && user_can( $user_id, $forum_perm ) );
										$utils->log( " Topic permission for {$user_id} is " . ( $result ? 'Yes' : 'No' ) );
									}
								}
							}
						}
						break;
				}
			}
			
			return $result;
		}
		
		/**
		 * Return the permission (forum_permission) stub for the specified user ID
		 *
		 * @param int $user_id
		 *
		 * @return string
		 */
		private function get_user_level_perms( $user_id ) {
			
			$utils      = Utilities::get_instance();
			$permission = 'no_access'; // Default
			
			if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
				
				$utils->log( "Checking user permissions based on membership level" );
				
				$level = pmpro_getMembershipLevelForUser( $user_id );
				
				if ( isset( $level->id ) ) {
					
					$level_options = $this->load_option( 'level_settings' );
					
					if ( isset( $level_options[ $level->id ] ) ) {
						
						$permission = $level_options[ $level->id ]['forum_permission'];
						$utils->log( "User with ID {$user_id} belongs to level {$level->id} and has {$permission} permissions" );
					}
				}
			}
			
			return $permission;
			
		}
		
		/**
		 * Remove cache
		 */
		public function change_membership_level() {
			
			Cache::delete( self::CAN_ACCESS );
		}
		
		/**
		 * Check if the user have read access to the forum/topic/reply (post id)
		 *
		 * @param int $post_id
		 * @param int $user_id
		 *
		 * @return bool
		 */
		public function user_can_access( $post_id, $user_id = null ) {
			
			$result = false;
			$utils  = Utilities::get_instance();
			
			if ( ! empty( $post_id ) ) {
				
				if ( null === $user_id ) {
					$user_id = get_current_user_id();
				}
				
				if ( null === ( $result = Cache::get( self::CAN_ACCESS . "_{$user_id}_{$post_id}", self::CACHE_GROUP ) ) ) {
					
					if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
						$utils->log( "Checking user access permissions" );
						
						$level = pmpro_getMembershipLevelForUser( $user_id );
						
						if ( isset( $level->id ) ) {
							
							$level_options = $this->load_option( 'level_settings' );
							
							if ( isset( $level_options[ $level->id ] ) ) {
								
								$permission = $level_options[ $level->id ]['forum_permission'];
								$utils->log( "User with ID {$user_id} belongs to level {$level->id} and has {$permission} permissions" );
							}
						}
					}
					
					$required = true;
					$type     = get_post_type( $post_id );
					
					switch ( $type ) {
						case 'forum':
							$forum = bbp_get_forum( $post_id );
							break;
						case 'topic':
							if ( false !== ( $topic = bbp_get_topic( $post_id ) ) ) {
								$forum_id = $topic->post_parent;
								if ( ! empty( $forum_id ) ) {
								
								}
							}
					}
					$capabilities = $this->select_capabilities( $permission );
					
					$utils->log( "Starting with " . count( $capabilities ) . " to check: " . print_r( $capabilities, true ) );
					$capabilities = $this->caps_of_type( $type, $capabilities, $permission );
					
					$function = "bbp_is_single_{$type}";
					
					$utils->log( "Now have " . count( $capabilities ) . " to check" );
					
					foreach ( $capabilities as $cap ) {
						
						$utils->log( "Checking if user {$user_id} is allowed to {$cap} with {$function}" );
						$required = $required && ( $function() && user_can( $user_id, $cap ) );
					}
					
					$result = $result || $required;
					
					$utils->log( "User {$user_id} " . ( $result ? 'can' : 'can\'t' ) . " read/access {$post_id}/{$type}" );
					
					Cache::set( self::CAN_ACCESS . "_{$user_id}_{$post_id}", $result, ( 10 * MINUTE_IN_SECONDS ), self::CACHE_GROUP );
				}
			}
			
			return $result;
		}
		
		/**
		 * Return a specific type of capabilities
		 *
		 * @param        $type
		 * @param        $capabilities
		 * @param string $perm
		 *
		 * @return array
		 */
		private function caps_of_type( $type, $capabilities, $perm = 'no_access' ) {
			
			$utils = Utilities::get_instance();
			
			$utils->log( "Will test {$type} for {$perm} capabilities" );
			
			$perm               = $this->map_perm( $perm, $type );
			$typed_capabilities = array();
			$return             = array();
			
			if ( null !== $perm ) {
				
				foreach ( $capabilities as $cap ) {
					
					if ( false !== strpos( $cap, $type ) ) {
						$utils->log( "Adding {$cap} for post type {$type}" );
						$typed_capabilities[] = $cap;
					}
				}
				
				foreach ( $typed_capabilities as $cap ) {
					
					if ( false !== strpos( $cap, $perm ) ) {
						$utils->log( "Adding {$cap} for {$perm}" );
						$return[] = $cap;
					}
				}
			}
			
			return $return;
		}
		
		/**
		 * Map/Remap permission strings to the requested type (add/read permissions)
		 *
		 * @param string $permission
		 * @param string $type
		 *
		 * @return null|string
		 */
		private function map_perm( $permission, $type ) {
			
			$mapped = null;
			
			if ( false !== strpos( $permission, 'add_' ) ) {
				$mapped = "publish_{$type}s";
			}
			
			if ( false !== strpos( $permission, 'read_' ) ) {
				$mapped = "read_{$type}s";
			}
			
			return $mapped;
		}
		
		/**
		 * Is the post we've been passed a bbPress forum/topic/reply?
		 *
		 * @param \WP_Post $post
		 *
		 * @return bool
		 */
		public function is_forum_post( $post ) {
			
			return in_array( $post->post_type, array( 'forum', 'topic', 'reply' ) );
		}
		
		/**
		 * Filter Handler: Access filter for bbPress/PMPro memership (override)
		 *
		 * @filter e20r_roles_addon_has_access
		 *
		 * @param bool     $has_access
		 * @param \WP_Post $post
		 * @param \WP_User $user
		 * @param array    $levels
		 *
		 * @return bool
		 */
		public function has_access( $has_access, $post, $user, $levels ) {
			
			global $e20r_roles_addons;
			
			$stub = strtolower( $this->get_class_name() );
			// Are we supposed to be active?
			if ( false == $e20r_roles_addons[ $stub ]['is_active'] ) {
				
				if ( WP_DEBUG ) {
					error_log( "The {$e20r_roles_addons[$stub]['label']} add-on is disabled" );
				}
				
				return $has_access;
			}
			
			// Not for bbPress content
			if ( false === $this->is_forum_post( $post ) ) {
				
				if ( WP_DEBUG ) {
					error_log( "We're not processing a forum post, skipping {$post->ID}" );
				}
				
				return $has_access;
			}
			
			// Anybody can read the content
			if ( true == $this->load_option( 'global_anon_read' ) ) {
				
				if ( WP_DEBUG ) {
					error_log( "Anybody can view the forum post(s)" );
				}
				
				$has_access = true;
			}
			
			// Do we need to override the access value?
			
			if ( WP_DEBUG ) {
				error_log( "User {$user->ID} is currently not allowed to access the page/post/forum/thread/reply ({$post->ID})" );
			}
			
			return $has_access;
		}
		
		/**
		 * Filter handler: Adds the configured level-specific Forum capabilities to the Membership Level Role
		 *
		 * @filter e20r_roles_general_level_capabilities
		 *
		 * @param array   $capabilities
		 * @param string  $role_name
		 * @param integer $level_id
		 *
		 * @return array
		 *
		 * @access public
		 * @since  1.0
		 */
		public function add_capabilities_to_role( $capabilities, $role_name, $level_id ) {
			
			global $e20r_roles_addons;
			
			$stub = strtolower( $this->get_class_name() );
			
			if ( WP_DEBUG ) {
				error_log( "Adding the BuddyPress capabilities to the membership level capabilities?" );
			}
			
			if ( false == $e20r_roles_addons[ $stub ]['is_active'] ) {
				return $capabilities;
			}
			
			$level_settings = $this->load_option( 'level_settings' );
			$preserve       = array_diff( $this->select_capabilities( $level_settings[ $level_id ]['forum_permission'] ), $capabilities );
			
			if ( WP_DEBUG ) {
				error_log( "Keeping the following capabilities: " . print_r( $preserve, true ) );
			}
			
			if ( isset( $level_settings[ $level_id ] ) ) {
				
				if ( WP_DEBUG ) {
					error_log( "Adding/Removing the {$level_settings[$level_id]['forum_permission']} capabilities: " . print_r( $level_settings[ $level_id ]['capabilities'], true ) );
					error_log( "... for the existing level specific capabilities: " . print_r( $capabilities, true ) );
				}
				
				$capabilities = array_merge( $preserve, $level_settings[ $level_id ]['capabilities'] );
			}
			
			// Clear up the array
			$capabilities = array_unique( $capabilities );
			
			if ( WP_DEBUG ) {
				error_log( "Loaded the bbPress Forum roles required for {$level_id}: " . print_r( $capabilities, true ) );
			}
			
			return $capabilities;
		}
		
		/**
		 * Action handler: Core E20R Roles for PMPro plugin's deactivation hook
		 *
		 * @action e20r_roles_addon_deactivating_core
		 *
		 * @param bool $clear
		 *
		 * @access public
		 * @since  1.0
		 */
		public function deactivate_addon( $clear = false ) {
			
			if ( true == $clear ) {
				// TODO: During core plugin deactivation, remove all capabilities for levels & user(s)
				// FixMe: Delete all option entries from the Database for this add-on
				error_log( "Deactivate the capabilities for all levels & all user(s)!" );
			}
		}
		
		/**
		 * Loads the default settings (keys & values)
		 *
		 * @return array
		 *
		 * @access private
		 * @since  1.0
		 */
		private function load_defaults() {
			
			return array(
				'global_anon_read'   => false,
				'deactivation_reset' => false,
				'level_settings'     => array(
					- 1 => array(
						'capabilities'     => array(),
						'forum_permission' => 'no_access',
					),
				),
			);
			
		}
		
		/**
		 * Load the saved options, or generate the default settings
		 */
		private function define_settings() {
			
			$this->settings = get_option( $this->option_name, $this->load_defaults() );
			$defaults       = $this->load_defaults();
			
			foreach ( $defaults as $key => $dummy ) {
				$this->settings[ $key ] = isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $defaults[ $key ];
			}
		}
		
		/**
		 * Action Hook: Enable/disable this add-on. Will clean up if we're being deactivated & configured to do so
		 *
		 * @action e20r_roles_addon_toggle_addon
		 *
		 * @param string $addon
		 * @param bool   $is_active
		 */
		public function toggle_addon( $addon, $is_active = false ) {
			
			global $e20r_roles_addons;
			
			$utils = Utilities::get_instance();
			$utils->log( "In toggle_addon action handler for the {$e20r_roles_addons[$addon]['label']} add-on" );
			
			if ( ADDON_STUB !== $addon ) {
				$utils->log( "Not processing the {$e20r_roles_addons[$addon]['label']} add-on: {$addon}" );
				
				return;
			}
			
			if ( $is_active === false ) {
				
				$utils->log( "Deactivating the add-on so disable the license" );
				Licensing\Licensing::deactivate_license( $addon );
			}
			
			if ( $is_active === false && true == $this->load_option( 'deactivation_reset' ) ) {
				
				// TODO: During add-on deactivation, remove all capabilities for levels & user(s)
				// FixMe: Delete the option entry/entries from the Database
				
				$utils->log( "Deactivate the capabilities for all levels & all user(s)!" );
			}
			
			$e20r_roles_addons[ $addon ]['is_active'] = $is_active;
			
			$utils->log( "Setting the {$addon} option to {$is_active}" );
			update_option( "e20r_{$addon}_enabled", $is_active, true );
		}
		
		/**
		 * Load the specific option from the option array
		 *
		 * @param string $option_name
		 *
		 * @return bool
		 */
		public function load_option( $option_name ) {
			
			$this->settings = get_option( "{$this->option_name}" );
			
			if ( isset( $this->settings[ $option_name ] ) && ! empty( $this->settings[ $option_name ] ) ) {
				
				return $this->settings[ $option_name ];
			}
			
			return false;
			
		}
		
		/**
		 * Load add-on actions/filters when the add-on is active & enabled
		 *
		 * @param string $stub Lowercase Add-on class name
		 */
		final public static function is_enabled( $stub ) {
			
			$utils = Utilities::get_instance();
			global $e20r_roles_addons;
			
			/**
			 * Toggle ourselves on/off, and handle any deactivation if needed.
			 */
			add_action( 'e20r_roles_addon_toggle_addon', array( self::get_instance(), 'toggle_addon' ), 10, 2 );
			add_action( 'e20r_roles_addon_deactivating_core', array(
				self::get_instance(),
				'deactivate_addon',
			), 10, 1 );
			
			/**
			 * Configuration actions & filters
			 */
			add_filter( 'e20r_roles_general_level_capabilities', array(
				self::get_instance(),
				'add_capabilities_to_role',
			), 10, 3 );
			add_filter( 'e20r-license-add-new-licenses', array(
				self::get_instance(),
				'add_new_license_info',
			), 10, 1 );
			add_filter( 'e20r_roles_addon_options_bbPress_Roles', array(
				self::get_instance(),
				'register_settings',
			), 10, 1 );
			
			if ( true === parent::is_enabled( $stub ) ) {
				
				$utils->log( "Loading other actions/filters for {$e20r_roles_addons[$stub]['label']}" );
				
				/**
				 * Membership related settings for role(s) add-on
				 */
				add_action( 'e20r_roles_level_settings', array( self::get_instance(), 'load_level_settings' ), 10, 2 );
				add_action( 'e20r_roles_level_settings_save', array(
					self::get_instance(),
					'save_level_settings',
				), 10, 2 );
				add_action( 'e20r_roles_level_settings_delete', array(
					self::get_instance(),
					'delete_level_settings',
				), 10, 2 );
				
				/** Access filters for the add-on to use/leverage */
				add_filter( 'e20r_roles_addon_has_access', array( self::get_instance(), 'has_access' ), 10, 4 );
				add_filter( 'the_posts', array( self::get_instance(), 'check_access' ), 10, 2 );
				
				add_filter( 'bbp_is_forum_closed', array( self::get_instance(), 'close_forum' ), 10, 3 );
				add_filter( 'bbp_get_reply_excerpt', array( self::get_instance(), 'hide_forum_entry' ), 999, 2 );
				add_filter( 'bbp_get_reply_content', array( self::get_instance(), 'hide_forum_entry' ), 999, 2 );
				
				add_filter( 'the_content', array( self::get_instance(), 'hide_forum_entry' ), 999, 2 );
				add_filter( 'the_excerpt', array( self::get_instance(), 'hide_forum_entry' ), 999, 2 );
				
				self::get_instance()->configure_forum_admin_capabilities();
			}
		}
		
		/**
		 * @param string $content
		 * @param int    $reply_id
		 *
		 * @return string
		 */
		public function hide_forum_entry( $content = '', $reply_id = 0 ) {
			
			$utils = Utilities::get_instance();
			
			if ( empty( $reply_id ) ) {
				$reply_id = bbp_get_reply_id( $reply_id );
			}
			
			$utils->log( "Reply ID: " . print_r( $reply_id, true ) );
			
			
			return $content;
		}
		
		/**
		 * Append this add-on to the list of configured & enabled add-ons
		 */
		public static function configure_addon() {
			
			parent::is_enabled( ADDON_STUB );
		}
		
		/**
		 * Configure the settings page/component for this add-on
		 *
		 * @param array $settings
		 *
		 * @return array
		 */
		public function register_settings( $settings = array() ) {
			
			$settings['setting'] = array(
				'option_group'        => "{$this->option_name}_settings",
				'option_name'         => "{$this->option_name}",
				'validation_callback' => array( $this, 'validate_settings' ),
			);
			
			$settings['section'] = array(
				array(
					'id'              => 'e20r_buddypress_role_global',
					'label'           => __( "E20R Roles: BuddyPress Settings" ),
					'render_callback' => array( $this, 'render_buddypress_settings_text' ),
					'fields'          => array(
						array(
							'id'              => 'global_anon_read',
							'label'           => __( "Non-member access", E20R_Roles_For_PMPro::plugin_slug ),
							'render_callback' => array( $this, 'render_forum_read_select' ),
						),
						array(
							'id'              => 'deactivation_reset',
							'label'           => __( "Clean up on Deactivate", E20R_Roles_For_PMPro::plugin_slug ),
							'render_callback' => array( $this, 'render_forum_cleanup' ),
						),
					),
				),
			);
			
			return $settings;
		}
		
		/**
		 * Checkbox for the role/capability cleanup option on the global settings page
		 */
		public function render_forum_cleanup() {
			
			$cleanup = $this->load_option( 'deactivation_reset' );
			
			if ( WP_DEBUG ) {
				error_log( "Do we need to remove roles if we deactivate the plugin? " . ( $cleanup == true ? 'Yes' : 'No' ) );
			}
			?>
            <input type="checkbox" id="<?php esc_attr_e( $this->option_name ); ?>-deactivation_reset"
                   name="<?php esc_attr_e( $this->option_name ); ?>[deactivation_reset]"
                   value="1" <?php checked( 1, $cleanup ); ?> />
			<?php
		}
		
		/**
		 * Validate the option responses before saving them
		 *
		 * @param mixed $input
		 *
		 * @return mixed $validated
		 */
		public function validate_settings( $input ) {
			
			if ( WP_DEBUG ) {
				error_log( "Input for save in BuddyPress_Roles:: " . print_r( $input, true ) );
			}
			
			$defaults = $this->load_defaults();
			
			foreach ( $defaults as $key => $value ) {
				
				if ( false !== stripos( 'level_settings', $key ) && isset( $input[ $key ] ) ) {
					
					foreach ( $input['level_settings'] as $level_id => $settings ) {
						
						if ( isset( $this->settings['level_settings'][ $level_id ]['capabilitiies'] ) ) {
							unset( $this->settings['level_settings'][ $level_id ]['capabilitiies'] );
						}
						
						$this->settings['level_settings'][ $level_id ]['capabilities'] = $this->select_capabilities( $settings['forum_permission'] );
					}
					
				} else if ( isset( $input[ $key ] ) ) {
					
					$this->settings[ $key ] = $input[ $key ];
				} else {
					$this->settings[ $key ] = $defaults[ $key ];
				}
				
			}
			
			if ( WP_DEBUG ) {
				error_log( "bbPress_Roles saving " . print_r( $this->settings, true ) );
			}
			
			return $this->settings;
		}
		
		/**
		 * Informational text about the bbPress Role add-on settings
		 */
		public function render_buddypress_settings_text() {
			?>
            <p class="e20r-buddypress-global-settings-text">
				<?php _e( "Configure global settings for the E20R Roles: BuddyPress add-on", E20R_Roles_For_PMPro::plugin_slug ); ?>
            </p>
			<?php
		}
		
		/**
		 * Display the select option for the "Allow anybody to read forum posts" global setting (select)
		 */
		public function render_forum_read_select() {
			
			$allow_anon_read = $this->load_option( 'global_anon_read' );
			
			if ( WP_DEBUG ) {
				error_log( "Can non-members read the forum? " . ( $allow_anon_read == 1 ? 'Yes' : 'No' ) );
			}
			
			?>
            <select name="<?php esc_attr_e( $this->option_name ); ?>[global_anon_read]"
                    id="<?php esc_attr_e( $this->option_name ); ?>_global_anon_read">
                <option value="0" <?php selected( $allow_anon_read, 0 ); ?>>
					<?php _e( 'Disabled', E20R_Roles_For_PMPro::plugin_slug ); ?>
                </option>
                <option value="1" <?php selected( $allow_anon_read, 1 ); ?>>
					<?php _e( 'Read Only', E20R_Roles_For_PMPro::plugin_slug ); ?>
                </option>
            </select>
			<?php
		}
		
		/**
		 * Action Hook triggered when deleting a membership level in Paid Memberships Pro
		 *
		 * @param int   $level_id
		 * @param array $active_addons
		 *
		 * @return bool
		 */
		public function delete_level_settings( $level_id, $active_addons ) {
			
			if ( ! in_array( 'buddypress_roles', $active_addons ) ) {
				if ( WP_DEBUG ) {
					error_log( "bbPress Roles add-on is not active. Nothing to do!" );
				}
				
				return false;
			}
			
			if ( empty( $level_id ) ) {
				
				if ( WP_DEBUG ) {
					error_log( "bbPress Roles:  No level ID specified!" );
				}
				
				return false;
			}
			
			$options = $this->load_option( 'level_settings' );
			
			if ( isset( $options[ $level_id ] ) ) {
				unset( $options[ $level_id ] );
				$this->settings['level_settings'] = $options;
				$this->save_settings();
			}
		}
		
		/**
		 * Save the level specific settings during Membership Level save operation.
		 *
		 * @param $active_addons
		 * @param $level_id
		 *
		 * @return bool
		 */
		public function save_level_settings( $level_id, $active_addons ) {
			
			$stub = strtolower( $this->get_class_name() );
			if ( ! in_array( $stub, $active_addons ) ) {
				if ( WP_DEBUG ) {
					error_log( "BuddyPress Roles add-on is not active. Nothing to do!" );
				}
				
				return false;
			}
			
			if ( empty( $level_id ) ) {
				
				if ( WP_DEBUG ) {
					error_log( "BuddyPress Roles:  No level ID specified!" );
				}
				
				return false;
			}
			
			$utils          = Utilities::get_instance();
			$level_settings = $this->load_option( 'level_settings' );
			
			if ( ! isset( $level_settings[ $level_id ] ) ) {
				$level_settings[ $level_id ] = array(
					'capabilities'     => array(),
					'forum_permission' => 'no_access',
				);
			}
			
			$level_settings[ $level_id ]['forum_permission'] = $utils->get_variable( 'e20r_buddypress_settings-forum_permission', array() );
			
			if ( WP_DEBUG ) {
				error_log( "Current forum permissions for {$level_id}: {$level_settings[$level_id]['forum_permission']}" );
			}
			
			if ( isset( $level_settings[ - 1 ] ) ) {
				unset( $level_settings[ - 1 ] );
			}
			
			$level_settings[ $level_id ]['capabilities'] = $this->select_capabilities( $level_settings[ $level_id ]['forum_permission'] );
			
			$this->settings['level_settings'] = $level_settings;
			$this->save_settings();
			
			if ( WP_DEBUG ) {
				error_log( "Current settings: " . print_r( $this->settings, true ) );
			}
		}
		
		/**
		 * Save the settings to the DB
		 */
		public function save_settings() {
			
			update_option( $this->option_name, $this->settings, true );
		}
		
		/**
		 * Adds the membership level specific bbPress role settings
		 *
		 * @access public
		 * @since  1.0
		 */
		public function load_level_settings( $level_id ) {
			
			$level_settings = $this->load_option( 'level_settings' );
			
			if ( ! isset( $level_settings[ $level_id ] ) ) {
				$level_settings[ $level_id ] = array(
					'capabilities'     => array(),
					'forum_permission' => 'no_access',
				);
			}
			
			$forum_permission = $level_settings[ $level_id ]['forum_permission'];
			
			if ( is_array( $forum_permission ) ) {
				$forum_permission = $forum_permission[0];
			}
			
			?>
            <h4><?php _e( 'BuddyPress Configuration', E20R_Roles_For_PMPro::plugin_slug ); ?></h4>
            <table class="form-table">
                <tbody>
                <tr class="e20r-buddypress-settings">
                    <th scope="row" valign="top"><label
                                for="e20r-roles-buddypress-permissions"><?php _e( "Forum access", E20R_Roles_For_PMPro::plugin_prefix ); ?></label>
                    </th>
                    <td class="e20r-buddypress-settings-select">
                        <select name="e20r_buddypress_settings-forum_permission" id="e20r-roles-buddypress-permissions">
                            <option value="no_access" <?php selected( 'no_access', $forum_permission ); ?>><?php _e( "No Access", E20R_Roles_For_PMPro::plugin_slug ); ?></option>

                            <option value="read_only" <?php selected( 'read_only', $forum_permission ); ?>><?php _e( "Read Only", E20R_Roles_For_PMPro::plugin_slug ); ?></option>
                            <option value="add_replies" <?php selected( 'add_replies', $forum_permission ); ?>><?php _e( "Can reply to existing topic(s)", E20R_Roles_For_PMPro::plugin_slug ); ?></option>
                            <option value="add_threads" <?php selected( 'add_threads', $forum_permission ); ?>><?php _e( "Can create new topic(s), reply, and read", E20R_Roles_For_PMPro::plugin_slug ); ?></option>
                            <option value="add_forum" <?php echo selected( 'add_forum', $forum_permission ); ?>><?php _e( "Can create new forum(s), topic(s), reply, and read", E20R_Roles_For_PMPro::plugin_slug ); ?></option>
                            <option value="forum_support" <?php selected( 'forum_support', $forum_permission ); ?>><?php _e( "Has support rights to forum(s)", E20R_Roles_For_PMPro::plugin_slug ); ?></option>
                            <option value="forum_admin" <?php selected( 'forum_admin', $forum_permission ); ?>><?php _e( "Has full admin rights for bbPress", E20R_Roles_For_PMPro::plugin_slug ); ?></option>
                        </select><br/>
                        <small><?php _e( "This membership level grants an active member one or more of the following rights to the bbPress forum(s) on this site...", E20R_Roles_For_PMPro::plugin_slug ); ?></small>
                    </td>
                </tr>
                </tbody>
            </table>
			<?php
		}
		
		/**
		 * Add the specified bbPress forum capabilities to the role
		 *
		 * @param string $role_name
		 * @param string $type
		 *
		 * @return bool
		 *
		 * @access public
		 * @since  1.0
		 */
		public function add_access_to_role( $role_name, $type = 'read_only' ) {
			
			$role = get_role( $role_name );
			
			if ( empty( $role ) ) {
				return false;
			}
			
			// Get the type specific capabilities
			$capabilities = $this->select_capabilities( $type );
			
			foreach ( $capabilities as $cap ) {
				
				if ( false === $role->has_cap( $cap ) ) {
					$role->add_cap( $cap );
				}
			}
			
			return true;
		}
		
		/**
		 * Remove the specified bbPress forum capabilities from the role
		 *
		 * @param string $role_name
		 * @param string $type
		 *
		 * @return bool
		 *
		 * @access public
		 * @since  1.0
		 */
		public function remove_access_from_role( $role_name, $type = 'read_only' ) {
			
			$role = get_role( $role_name );
			
			if ( empty( $role ) ) {
				return false;
			}
			
			// Get the type specific capabilities
			$capabilities = $this->select_capabilities( $type );
			
			// Strip the capabilities
			foreach ( $capabilities as $cap ) {
				
				if ( true === $role->has_cap( $cap ) ) {
					$role->remove_cap( $cap );
				}
			}
			
			return true;
		}
		
		/**
		 * Configure forum access for a specific WordPress user ID
		 *
		 * @param int    $user_id
		 * @param string $role_name
		 * @param string $type
		 *
		 * @return bool
		 *
		 * @since  1.0
		 * @access public
		 */
		public function forum_assign_access_to_user( $user_id, $role_name, $type = null ) {
			
			$user = get_user_by( 'ID', $user_id );
			$role = get_role( $role_name );
			
			if ( empty( $role ) ) {
				return false;
			}
			
			// Do we need to add the capabilities to the user directly?
			if ( false === $this->role_has_capabilities( $role_name, $type ) ) {
				
				// Get the type specific capabilities
				$capabilities = $this->select_capabilities( $type );
				
				foreach ( $capabilities as $cap ) {
					$user->add_cap( $cap );
				}
				
			} else {
				// Simply add the role
				$user->add_role( $role_name );
			}
			
			return true;
		}
		
		/**
		 * Remove access capabilities to a forum for a specific user
		 *
		 * @param int    $user_id
		 * @param string $role_name
		 * @param null   $type
		 *
		 * @return bool
		 *
		 * @access public
		 * @since  1.0
		 */
		public function forum_remove_access_from_user( $user_id, $role_name, $type = null ) {
			
			$user = get_user_by( 'ID', $user_id );
			$role = get_role( $role_name );
			
			// Get the type specific capabilities
			$capabilities = $this->select_capabilities( $type );
			
			if ( false === $this->role_has_capabilities( $role_name, $type ) ) {
				
				foreach ( $capabilities as $cap ) {
					$user->remove_cap( $cap );
				}
				
			} else {
				
				// Does the role contain other non-standard capabilities?
				$remaining_caps = array_intersect( $role->capabilities, array_merge( $capabilities, Role_Definitions::default_capabilities() ) );
				
				if ( empty( $remaining_caps ) ) {
					
					// Nope. Remove the role
					$user->remove_role( $role_name );
					
				} else {
					// Yes... Can't remove the user specific capabilities when the whole role would be affected!
					$utils = Utilities::get_instance();
					$utils->add_message( __( "Unable to remove the expected capabilities for the user!", E20R_Roles_For_PMPro::plugin_slug ), 'error' );
				}
			}
			
			return true;
			
		}
		
		/**
		 * Test whether the specified role has all of the required capabilities (based on the type)
		 *
		 * @param string $role_name Name of the role to test
		 * @param string $type      One of 6 possible: 'read_all', 'add_replies', 'add_threads', 'add_forums', 'support', 'admin'
		 *
		 * @return bool
		 *
		 * @since  1.0
		 * @access private
		 */
		private function role_has_capabilities( $role_name, $type ) {
			
			$has_all_capabilities = true;
			$role                 = get_role( $role_name );
			
			if ( empty( $role ) ) {
				return false;
			}
			
			// Get the type specific capabilities
			$capabilities = $this->select_capabilities( $type );
			
			if ( empty( $capabilites ) ) {
				return false;
			}
			
			// Test the array of capabilities
			foreach ( $capabilities as $cap ) {
				
				// If one capability is missing, set the variable to false
				$has_all_capabilities = $has_all_capabilities && $role->has_cap( $cap );
			}
			
			return $has_all_capabilities;
		}
		
		/**
		 * Selects the capabilities for the specified access type
		 *
		 * @param string $type
		 *
		 * @return array
		 *
		 * @since  1.0
		 * @access private
		 */
		private function select_capabilities( $type ) {
			
			switch ( $type ) {
				
				case 'read_only':
					$this->configure_forum_read_capabilities();
					$capabilities = $this->_read_only_capabilities;
					break;
				
				case 'add_replies':
					$this->configure_forum_reply_capabilities();
					$capabilities = $this->_add_replies_capabilities;
					break;
				
				case 'add_threads':
					$this->configure_forum_reply_capabilities();
					$capabilities = $this->_add_threads_capabilities;
					break;
				
				case 'add_forum':
					$this->configure_forum_reply_capabilities();
					$capabilities = $this->_add_forum_capabilities;
					break;
				
				case 'forum_support':
					$this->configure_forum_support_capabilities();
					$capabilities = $this->_forum_support_capabilities;
					break;
				
				case 'forum_admin':
					$this->configure_forum_admin_capabilities();
					$capabilities = $this->_forum_admin_capabilities;
					break;
				
				default:
					$capabilities = $this->_no_access_capabilities;
			}
			
			return $capabilities;
		}
		
		/**
		 * Set the capabilities needed/used when given read access to the forum(s)
		 *
		 * @access private
		 * @since  1.0
		 */
		private function configure_forum_read_capabilities() {
			
			error_log( "Loading read capabilities" );
			
			$default_capabilities = array(
				'spectate',
				'read_private_replies',
				'read_private_topics',
				'read_private_forums',
			);
			
			$this->_read_only_capabilities = apply_filters( 'e20r_roles_buddypress_read_capabilities', $default_capabilities );
		}
		
		/**
		 * Set the different types of reply capabilities needed/used when given specific reply access to the forum(s)
		 *
		 * @access private
		 * @since  1.0
		 */
		private function configure_forum_reply_capabilities() {
			
			$this->configure_forum_read_capabilities();
			
			error_log( "Loading various reply capabilities" );
			
			$default_reply_capabilities  = array( 'publish_replies', 'edit_replies', 'participate', );
			$default_thread_capabilities = array( 'publish_topics', 'edit_topics', 'assign_topic_tags', );
			$default_forum_capabilities  = array( 'publish_forums', 'edit_forums', 'read_hidden_forums', );
			
			$this->_add_replies_capabilities = apply_filters(
				'e20r_roles_buddypress_add_reply_capabilities',
				array_merge( $default_reply_capabilities, $this->_read_only_capabilities )
			);
			
			$this->_add_threads_capabilities = apply_filters(
				'e20r_roles_buddypress_add_thread_capabilities',
				array_merge( $default_thread_capabilities, $this->_add_replies_capabilities )
			);
			
			$this->_add_forum_capabilities = apply_filters(
				'e20r_roles_buddypress_add_forum_capabilities',
				array_merge( $default_forum_capabilities, $this->_add_threads_capabilities )
			);
			
		}
		
		/**
		 * Set the capabilities needed/used when given support role in the forum(s)
		 *
		 * @access private
		 * @since  1.0
		 */
		private function configure_forum_support_capabilities() {
			
			// Configure all related capabilities
			$this->configure_forum_reply_capabilities();
			
			error_log( "Loading support capabilities" );
			
			$default_capabilities = array(
				'edit_others_topics',
				'delete_topics',
				'delete_others_topics',
				'read_private_topics',
				'edit_others_replies',
				'delete_replies',
				'delete_others_replies',
				'read_private_replies',
				'manage_topic_tags',
				'edit_topic_tags',
				'delete_topic_tags',
				'moderate',
				'throttle',
				'view_trash',
			);
			
			$this->_forum_support_capabilities = apply_filters(
				'e20r_roles_buddypress_forum_support_capabilities',
				array_merge( $default_capabilities, $this->_add_forum_capabilities )
			);
		}
		
		/**
		 * Set the capabilities needed/used when given admin access to the forum(s)
		 *
		 * @access private
		 * @since  1.0
		 */
		private function configure_forum_admin_capabilities() {
			
			// Configure all related capabilities
			$this->configure_forum_support_capabilities();
			
			error_log( "Loading admin capabilities" );
			
			$default_capabilities = array(
				'publish_forums',
				'edit_forums',
				'edit_others_forums',
				'delete_forums',
			);
			
			$this->_forum_admin_capabilities = apply_filters(
				'e20r_roles_buddypress_forum_admin_capabilities',
				array_merge( $default_capabilities, $this->_forum_support_capabilities )
			);
		}
		
		/**
		 * Fetch the properties for BuddyPress
		 *
		 * @return BuddyPress_Roles
		 *
		 * @since  1.0
		 * @access public
		 */
		public static function get_instance() {
			
			if ( is_null( self::$instance ) ) {
				
				self::$instance = new self;
			}
			
			return self::$instance;
		}
	}
	
	// Configure the add-on (global settings array)
	global $e20r_roles_addons;
	$stub = strtolower( ADDON_STUB );
	
	$e20r_roles_addons[ $stub ] = array(
		'class_name'            => 'BuddyPress_Roles',
		'is_active'             => false, // ( get_option( "e20r_{$stub}_enabled", false ) == 1 ? true : false ),
		'status'                => 'deactivated',
		'label'                 => 'BuddyPress Roles',
		'admin_role'            => 'manage_options',
		'required_plugins_list' => array(
			'buddypress/buddypress.php'                     => array(
				'name' => 'BuddyPress',
				'url'  => 'https://wordpress.org/plugins/buddypress/',
			),
			'paid-memberships-pro/paid-memberships-pro.php' => array(
				'name' => 'Paid Memberships Pro',
				'url'  => 'https://wordpress.org/plugins/paid-memberships-pro/',
			),
		),
	);
	
}