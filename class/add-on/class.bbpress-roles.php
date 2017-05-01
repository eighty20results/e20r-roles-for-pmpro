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

use Braintree\Util;
use E20R\Roles_For_PMPro\E20R_Roles_For_PMPro;
use E20R\Roles_For_PMPro\Role_Definitions;
use E20R\Roles_For_PMPro\PMPro_Content_Access;
use E20R\Utilities\Cache;
use E20R\Utilities\PMPro_Members;
use E20R\Utilities\Utilities;
use E20R\Licensing;

if ( ! class_exists( 'E20R\\Roles_For_PMPro\\Addon\\bbPress_Roles' ) ) {
	
	class bbPress_Roles extends E20R_Roles_Addon {
		
		const CAN_ACCESS = 'can_access';
		const CAN_READ = 'can_read';
		const CAN_EDIT = 'can_edit';
		const CAN_ADD = 'can_add';
		const CANNOT_ACCESS = 'no_access';
		
		const CACHE_GROUP = 'bbpress_roles';
		
		private $_add_topic_perm = 'publish_topics';
		private $_add_forum_perm = 'publish_forums';
		private $_add_reply_perm = 'publish_replies';
		private $_edit_reply_perm = 'edit_replies';
		private $_edit_topic_perm = 'edit_topics';
		private $_edit_forum_perm = 'edit_forums';
		private $_read_topic_perm = 'spectate';
		private $_read_reply_perm = 'spectate';
		private $_read_forum_perm = 'spectate';
		private $_read_perm = 'spectate';
		
		/**
		 * Configured list of labels for forum access mapped against constant(s)
		 *
		 * @var array
		 */
		private $labels = array();
		
		/**
		 * @var bbPress_Roles
		 */
		private static $instance;
		
		/**
		 * Name of the WordPress option key
		 *
		 * @var string $option_name
		 */
		private $option_name = 'e20r_ao_bbpress';
		
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
		private $_add_topics_capabilities = array();
		
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
		 * bbPress_Roles constructor.
		 */
		protected function __construct() {
			
			parent::__construct();
			
			self::$instance = $this;
			
			$this->labels = array(
				'no_access'     => array(
					'level_settings' => __( "No Access", E20R_Roles_For_PMPro::plugin_slug ),
					'summary'        => __( "None", E20R_Roles_For_PMPro::plugin_slug ),
				),
				'read_only'     => array(
					'level_settings' => __( "Read Only", E20R_Roles_For_PMPro::plugin_slug ),
					'summary'        => __( 'Read', E20R_Roles_For_PMPro::plugin_slug ),
				),
				'add_replies'   => array(
					'level_settings' => __( "Can reply to existing topic(s)", E20R_Roles_For_PMPro::plugin_slug ),
					'summary'        => __( 'Replies', E20R_Roles_For_PMPro::plugin_slug ),
				),
				'add_topics'    => array(
					'level_settings' => __( "Can create new topic(s), reply, and read", E20R_Roles_For_PMPro::plugin_slug ),
					'summary'        => __( "Threads/Replies", E20R_Roles_For_PMPro::plugin_slug ),
				),
				'add_forum'     => array(
					'level_settings' => __( "Can create new forum(s), topic(s), reply, and read", E20R_Roles_For_PMPro::plugin_slug ),
					'summary'        => __( "Forums/Threads/Replies", E20R_Roles_For_PMPro::plugin_slug ),
				),
				'forum_support' => array(
					'level_settings' => __( "Has support rights to forum(s)", E20R_Roles_For_PMPro::plugin_slug ),
					'summary'        => __( "Support", E20R_Roles_For_PMPro::plugin_slug ),
				),
				'forum_admin'   => array(
					'level_settings' => __( "Has full admin rights for bbPress", E20R_Roles_For_PMPro::plugin_slug ),
					'summary'        => __( "Admin", E20R_Roles_For_PMPro::plugin_slug ),
				),
			);
			
			$this->define_settings();
		}
		
		/**
		 * Load actions & hooks for the add-on
		 *
		 * @param   null|string $stub
		 */
		public static function load_addon( $stub = null ) {
			
			if ( WP_DEBUG ) {
				error_log( "Loading the bbPress_Roles class action(s) " );
			}
			
			global $e20r_roles_addons;
			
			$class = self::get_instance();
			$utils = Utilities::get_instance();
			
			if ( is_null( $stub ) ) {
				$stub = 'bbpress_roles';
			}
			
			// Check license status & prerequisites for this add-on
			parent::load_addon( $stub );
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
			
			$settings['new_licenses'][] = array(
				'label_for'     => 'bbpress_roles',
				'fulltext_name' => $e20r_roles_addons['bbpress_roles']['label'],
				'new_product'   => 'bbpress_roles',
				'option_name'   => "e20r_license_settings",
				'name'          => 'license_key',
				'input_type'    => 'password',
				'value'         => null,
				'email_field'   => "license_email",
				'email_value'   => null,
				'placeholder'   => sprintf( __( "Paste the purchased E20R Roles %s key here", "e20r-licensing" ), $e20r_roles_addons['bbpress_roles']['label'] ),
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
			
			$utils->log( "Forum is already closed? " . ( $closed ? 'Yes' : 'No' ) );
			
			if ( ! is_user_logged_in() ) {
			    return $closed;
            }
            
			$user             = wp_get_current_user();
			$level_permission = $this->get_user_level_perms( $user->ID );
			$level            = pmpro_getMembershipLevelForUser( $user->ID );
			
			$forum_permission = apply_filters( 'e20r_roles_addon_bbpress_default_add_forum_perm', 'publish_forums' );
			$topic_permission = apply_filters( 'e20r_roles_addon_bbpress_default_add_topic_perm', 'publish_topics' );
			$reply_permission = apply_filters( 'e20r_roles_addon_bbpress_default_add_reply_perm', 'publish_replies' );
			
			// Can't be closed to support & admins
			if ( in_array( $level_permission, array(
					'forum_support',
					'forum_admin',
				) ) && ( user_can( $user, 'forum_admin' ) || user_can( $user, 'forum_support' ) )
			) {
				return false;
			}
   
			$user_config_meets_requirement = in_array( $level_permission, $user->get_role_caps() );
			
			// User level assigned to the membership level allows add_replies $permission.
			// Need to make sure the forum is available to the specified user ID
			
			$utils->log( "Permission {$level_permission} required for {$forum_id} when user ({$user->ID}) has level {$level->id}. Meets requirement: " . ( $user_config_meets_requirement ? 'Yes' : 'No' ) );
			
			/*
			$this->remove_from_filters( 'pmpro_has_membership_access_filter', 20 );
			$this->remove_from_filters( 'e20r_roles_addon_has_access', 10 );
			*/
			// $member_access = pmpro_has_membership_access( $forum_id );
			$member_access = isset( $level->id ) ? PMPro_Content_Access::level_has_post_access( $level->id, $forum_id ) : false;
			
			/*
			add_filter( 'pmpro_has_membership_access_filter', array( $this, 'has_access' ), 20, 4 );
			add_filter( 'e20r_roles_addon_has_access', array( $this, 'has_access' ), 10, 4 );
			*/
			$utils->log( "User {$user->ID} has member access to {$forum_id}? " . ( 1 == $member_access ? 'Yes' : 'No' ) );
			
			if ( bbp_is_single_forum() ) {
				
				$has_perms = in_array( $topic_permission, $user->get_role_caps() );
				$closed    = ( ( $member_access && $user_config_meets_requirement ) || ( $member_access  && $has_perms ) ? false : true );
				
				$utils->log( "Closing topic creation ({$topic_permission}) for {$forum_id}: " . ( $closed ? 'Yes' : 'No' ) );
				add_filter( 'gettext', array( $this, 'remove_topics_text' ), 10, 3 );
				
			} else if ( bbp_is_single_topic() ) {
				
				$has_perms = in_array( $reply_permission, $user->get_role_caps() );
				// $closed    = ( ( $has_perms && $member_access && $user_config_meets_requirement ) ? false : true );
				$closed    = ( ( $member_access && $user_config_meets_requirement ) || ( $member_access  && $has_perms ) ? false : true );
				
				$utils->log( "Closing reply creation ({$reply_permission}) for forum: {$forum_id}: " . ( $closed ? 'Yes' : 'No' ) );
				add_filter( 'gettext', array( $this, 'remove_replies_text' ), 10, 3 );
				
			} /* else if ( bbp_is_single_reply() ) {
				
				$utils->log( "Should close for new replies in {$forum_id} " );
				
				$closed = ( user_can( $user, $level_permission ) ? false : true );
				$utils->log( "Closing reply creation for forum: {$forum_id}: " . ( $closed ? 'Yes' : 'No' ) );
				add_filter( 'gettext', array( $this, 'remove_replies_text' ), 10, 3 );
			}*/
			
			// && false === $user->has_cap( 'publish_replies' )
			
			// Block outgoing notification email(s)
			if ( true === $closed ) {
				add_filter( 'bbp_subscription_mail_message', array(
					self::get_instance(),
					'prevent_subscr_mail',
				), 9999999, 3 );
			}
			
			$utils->log( "Forum status: " . ( $closed ? 'Closed' : 'Open' ) );
			
			return $closed;
		}
		
		private function remove_from_filters( $hook, $priority ) {
   
			$utils = Utilities::get_instance();
   
			global $wp_filter;
			
			if ( ! isset( $wp_filter[ $hook ] ) ) {
			    return;
            }
            
			$pmpro_access_funcs = $wp_filter[ $hook ]->callbacks[ $priority ];
			
			foreach ( $pmpro_access_funcs as $key => $data ) {
				
				$class_name = get_class( $pmpro_access_funcs[ $key ]['function'][0] );
    
				if ( false != preg_match( '/bbPress_Roles/', $class_name ) ) {
     
					unset( $wp_filter[ $hook ]->callbacks[ $priority ][ $key ] );
					if ( empty( $wp_filter[ $hook ]->callbacks[ $priority ] ) ) {
					    unset( $wp_filter[ $hook ]->callbacks[ $priority ] );
                    }
                    
                    if ( empty( $wp_filter[ $hook ]->callbacks ) ) {
					    unset( $wp_filter[$hook] );
                    }
                }
				
				// $utils->log( "Removed {$hook} [{$priority}] hook(s) for {$class_name}/{$key}? " . ( empty( $wp_filter[ $hook ]->callbacks[ $priority ][ $key ] ) ? 'Yes' : 'No' ) );
			}
			
			// $utils->log( print_r( $wp_filter[ $hook ], true ) );
		}
		
		/**
		 * Change the 'topic'|'topics' string to the specified singular or plural word from settings
		 *
		 * @param string $translated
		 * @param string $text
		 * @param string $domain
		 *
		 * @return string
		 */
		public function replace_topic_labels( $translated, $text, $domain ) {
			
			if ( 'bbpress' === $domain ) {
				
				$utils = Utilities::get_instance();
				
				$replacement_plural   = $this->load_option( 'topic_label_plural' );
				$replacement_singular = $this->load_option( 'topic_label' );
				
				if ( preg_match( "/topic/i", $text ) ) {
					
					$text       = $utils->nc_replace( 'topics', $replacement_plural, $text );
					$text       = $utils->nc_replace( 'topic', $replacement_singular, $text );
					$translated = $text;
				}
			}
			
			return $translated;
		}
		
		public function remove_topics_text( $translated, $text, $domain ) {
			
			global $pmpro_pages;
			
			if ( $domain === 'bbpress' && false !== strpos( $text, 'is closed to new topics and replies' ) ) {
				
				$utils              = Utilities::get_instance();
				$topic_label        = apply_filters( 'e20r-roles-set-topic-label-plural', __( 'Topics', 'e20r-roles-for-pmpro' ) );
				$replacement_plural = $this->load_option( 'topic_label_plural' );
				
				if ( preg_match( "/topic/i", $topic_label ) ) {
					
					$topic_label = $utils->nc_replace( 'topics', $replacement_plural, $topic_label );
				}
				
				$translated = sprintf( __( 'Log in, join, or upgrade your membership level to <a href="%s" target="_blank">add new %s to the forum</a>', 'e20r-roles-for-pmpro' ), get_permalink( $pmpro_pages['levels'] ), strtolower( $topic_label ) );
			}
			
			return $translated;
		}
		
		public function remove_replies_text( $translated, $text, $domain ) {
			
			global $pmpro_pages;
			
			if ( $domain === 'bbpress' && false !== strpos( $text, 'is closed to new topics and replies' ) ) {
				
				$utils       = Utilities::get_instance();
				$topic_label = apply_filters( 'e20r-roles-set-topic-label-plural', __( 'Topics', 'e20r-roles-for-pmpro' ) );
				$reply_label = apply_filters( 'e20r-roles-set-reply-label-plural', __( 'Replies', 'e20r-roles-for-pmpro' ) );
				
				$replacement_plural = $this->load_option( 'topic_label_plural' );
				
				if ( preg_match( "/topic/i", $topic_label ) ) {
					
					$topic_label = $utils->nc_replace( 'topics', $replacement_plural, $topic_label );
				}
				
				$translated = sprintf( __( 'Log in or become a member to <a href="%s" target="_blank">add %s or post %s</a>', 'e20r-roles-for-pmpro' ),
					get_permalink( $pmpro_pages['levels'] ),
					strtolower( $topic_label ),
					strtolower( $reply_label )
				);
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
		 * TODO: Remove dependency on PMPro bbPress add-on forum(s) (port pmpro-bbPress add-on functionality)
		 */
		public function restrict_forums() {
			
			if ( false === $this->is_bbPress_active() ) {
				return;
			}
			
			global $current_user;
			global $post;
			
			$utils    = Utilities::get_instance();
			$forum_id = bbp_get_forum_id();
			
			$is_forum_entity = ( $this->is_forum() || $this->is_topic() || $this->is_reply() );
			
			if ( ( ! bbp_is_forum_archive() && false === empty( $forum_id ) && true === $is_forum_entity ) && ( bbp_is_forum_archive() && ! $this->user_can_read( $post->ID, $current_user->ID ) ) ) {
				
				$utils->log( "ID {$forum_id} is a valid forum entity? " . ( $is_forum_entity ? 'Yes' : 'No' ) );
				
				if ( false === $this->allow_anon_read() || ! in_array( $forum_id, $this->get_forums_for_user( $current_user->ID ) ) ) {
					
					$_SESSION['pmpro_bbp_redirected_from'] = $_SERVER['REQUEST_URI'];
					
					wp_redirect( add_query_arg( 'noaccess', true, get_post_type_archive_link( 'forum' ) ) );
					exit();
				}
			}
		}
		
		/**
		 * Test whether the current forum is a sub-post of the received Forum ID
		 * If there's no forum ID received, we'll return true for $post->ID that's a forum.
		 *
		 * @param null $forum_id
		 *
		 * @return bool
		 */
		private function is_forum( $forum_id = null ) {
			
			global $post;
			$utils = Utilities::get_instance();
			
			if ( null === $forum_id ) {
				global $post;
				
				if ( empty( $post->ID ) ) {
					return false;
				} else {
					$forum_id = $post->ID;
				}
			}
			// False if bbPress is inactive or not installed, or we're not in the loop
			if ( false === $this->is_bbPress_active() ) {
				$utils->log( "bbPress inactive (Forum) ???" );
				
				return false;
			}
			
			$is_forum = bbp_is_forum( $forum_id );
			$utils->log( "Checking whether post ID ({$forum_id}) is a forum or not: " . ( $is_forum ? 'Yes' : 'No' ) );
			
			return $is_forum;
		}
		
		/**
		 * Tests whether the supplied Forum ID and/or the current $post is a topic
		 * If there's no forum ID received, we'll return true for $post->ID that's a forum.
		 *
		 * @param null|int $forum_id
		 *
		 * @return bool
		 */
		private function is_topic( $forum_id = null ) {
			
			$utils = Utilities::get_instance();
			
			if ( null === $forum_id ) {
				global $post;
				
				if ( empty( $post->ID ) ) {
					return false;
				} else {
					$forum_id = $post->ID;
				}
			}
			
			// False if bbPress is inactive or not installed, or we're not in the loop
			if ( false === $this->is_bbPress_active() ) {
				$utils->log( "bbPress inactive (Topic)???" );
				
				return false;
			}
			
			$is_topic = bbp_is_topic( $forum_id );
			$utils->log( "Checking whether post ID ({$forum_id}) is a topic or not: " . ( $is_topic ? 'Yes' : 'No' ) );
			
			return $is_topic;
		}
		
		/**
		 * Tests whether the supplied Forum ID and/or the current $post is a reply
		 * If there's no forum ID received, we'll return true for $post->ID that's a forum.
		 *
		 * @param null|int $forum_id
		 *
		 * @return bool
		 */
		private function is_reply( $forum_id = null ) {
			
			global $post;
			$utils = Utilities::get_instance();
			
			if ( null === $forum_id ) {
				global $post;
				
				if ( empty( $post->ID ) ) {
					return false;
				} else {
					$forum_id = $post->ID;
				}
			}
			
			// False if bbPress is inactive or not installed, or we're not in the loop
			if ( false === $this->is_bbPress_active() ) {
				return false;
			}
			
			$is_reply = bbp_is_reply( $forum_id );
			$utils->log( "Checking whether post ID ({$forum_id}) is a reply or not: " . ( $is_reply ? 'Yes' : 'No' ) );
			
			return $is_reply;
		}
		
		/**
		 * Allow styling based on the membership level for the thread(s)/user
		 *
		 * @param array $classes
		 *
		 * @return array
		 */
		public function set_reply_post_class( $classes ) {
			
			if ( false === $this->is_bbPress_active() ) {
				return $classes;
			}
			
			global $reply_id;
			
			if ( empty( $reply_id ) ) {
				$reply_id = bbp_get_reply_id( $reply_id );
			}
			
			$author_id                   = bbp_get_reply_author_id( $reply_id );
			$membership_level_for_author = pmpro_getMembershipLevelForUser( $author_id );
			
			if ( false === empty( $membership_level_for_author ) ) {
				$classes[] = "e20r-roles-bbpress-level-{$membership_level_for_author->id}";
			}
			
			return $classes;
		}
		
		/**
		 * Tests whether bbPress is installed and activated
		 *
		 * @return bool
		 */
		private function is_bbPress_active() {
			
			// Will return true if bbPress is installed and active
			return ( function_exists( 'bbp_is_forum' ) ? true : false );
		}
		
		
		/**
		 * If configured, add all available forum(s) for the current user to their account page.
		 */
		public function add_topics_as_pmpro_account_links() {
			
			$utils = Utilities::get_instance();
			
			global $current_user;
			
			$on_account_page = $this->load_option( 'on_account_page' );
			
			if ( true == is_user_logged_in() && true == $on_account_page ) {
				
				$utils->log( "Loading forum(s) for {$current_user->ID}'s account page? " . ( $on_account_page ? 'Yes' : 'No' ) );
				
				$forum_id_list = $this->get_forums_for_user( $current_user->ID );
				
				// List all forum(s) this user has access to.
				foreach ( $forum_id_list as $forum_id ) {
					printf( '<li><a href="%1$s">%2$s</a></li>', get_permalink( $forum_id ), get_the_title( $forum_id ) );
				}
			}
		}
		
		/**
		 * Find all forum(s) the user has access to.
		 *
		 * @param int $user_id
		 *
		 * @return \WP_Post[]
		 */
		private function get_forums_for_user( $user_id ) {
			
			$forums = array();
			$levels = PMPro_Members::get_memberships( $user_id );
			$utils  = Utilities::get_instance();
			
			if ( ! empty( $levels ) ) {
				
				$query = array(
					'post_type'   => 'forum',
					'post_status' => 'publish',
					'fields'      => 'ids',
					'meta_query'  => array(
						array(
							'key'        => 'e20r_bbpress_access_levels',
							'value'      => $levels,
							'comparison' => 'IN',
						),
					),
				);
				
				// Load the forums
				$result = new \WP_Query( $query );
				
				$utils->log( "Found " . $result->post_count . " forums for {$user_id}" );
				
				// Grab any found forum(s)
				if ( $result->have_posts() ) {
					$forums = $result->get_posts();
				}
			}
			
			// Return the found forum(s) to calling function
			return $forums;
		}
		
		/**
		 * Update the post meta for protecting the forum(s) by specified membership level IDs
		 *
		 * @param int $forum_id
		 *
		 * @return int
		 */
		public function save_forum_protection( $forum_id ) {
			
			$utils = Utilities::get_instance();
			
			if ( defined( 'DOING_AUTOSAVE' ) && true === DOING_AUTOSAVE ) {
				return $forum_id;
			}
			
			if ( isset( $_POST['post_type'] ) && 'page' === $_POST['post_type'] ) {
				
				if ( ! current_user_can( 'edit_page', $forum_id ) ) {
					return $forum_id;
				}
				
			} else {
				if ( ! current_user_can( 'edit_post', $forum_id ) ) {
					return $forum_id;
				}
			}
			
			if ( isset( $_POST['pmpro_noncename'] ) ) {
				
				$new_levels = $utils->get_variable( 'page_levels', array() );
				$utils->log( "New levels: " . print_r( $new_levels, true ) );
				
				if ( ! empty( $new_levels ) ) {
					
					delete_post_meta( $forum_id, 'e20r_bbpress_access_levels' );
					
					foreach ( $new_levels as $level_id ) {
						
						$utils->log( "Adding level {$level_id} as 'protected' by the {$forum_id} forum" );
						add_post_meta( $forum_id, 'e20r_bbpress_access_levels', $level_id );
					}
				}
			}
			
			return $forum_id;
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
			
			if ( !is_user_logged_in() && $this->allow_anon_read() ) {
			    return $posts;
            }
            
			$filtered_posts = array();
			$user_id        = get_current_user_id();
			$utils->log( "Checking access for " . count( $posts ) . " posts" );
			
			foreach ( $posts as $post ) {
				
				$is_forum_post = $this->is_forum_post( $post );
				
				// Only check access for the post if it's one of the bbPress post types
				if ( false === $is_forum_post ) {
					$filtered_posts[] = $post;
					continue;
				}
				
				$can_see = $this->user_can_read( $post->ID, $user_id ) || $this->allow_anon_read();
				
				if ( true === $can_see && true === $is_forum_post ) {
					
					$utils->log( "Allowing inclusion of {$post->post_type} for {$user_id} to {$post->ID}" );
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
				
				if ( WP_DEBUG ) {
					Cache::delete( self::CAN_EDIT . "_{$user_id}_{$post_id}", self::CACHE_GROUP );
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
				
				if ( WP_DEBUG ) {
					Cache::delete( self::CAN_ADD . "_{$user_id}_{$post_id}", self::CACHE_GROUP );
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
				
				$utils->log( "Checking read access to {$post_id}" );
				
				if ( null === $user_id ) {
					$user_id = get_current_user_id();
				}
				
				if ( ! is_user_logged_in() && $this->allow_anon_read() ) {
					return true;
				}
				
				if ( WP_DEBUG ) {
					Cache::delete( self::CAN_READ . "_{$user_id}_{$post_id}", self::CACHE_GROUP );
				}
				
				if ( null === ( $result = Cache::get( self::CAN_READ . "_{$user_id}_{$post_id}", self::CACHE_GROUP ) ) ) {
					
					$result = $this->check_forum_access_for( $user_id, $post_id, 'read' ) || $this->allow_anon_read();
					
					$utils->log( "User {$user_id} " . ( $result ? 'can' : 'can\'t' ) . " read {$post_id}" );
					
					Cache::set( self::CAN_READ . "_{$user_id}_{$post_id}", $result, ( 10 * MINUTE_IN_SECONDS ), self::CACHE_GROUP );
				}
			}
			
			return $result || $this->allow_anon_read();
		}
		
		private function allow_anon_read() {
			
			return ( true == $this->load_option( 'global_anon_read' ) );
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
			
			$anon_read  = $this->allow_anon_read();
			$override   = ( $anon_read && $access_type == 'read' );
			$permission = $this->get_user_level_perms( $user_id );
			$result     = false;
			
			if ( $override ) {
				
				$utils->log( "Override enabled. Returning true for forum access to {$post_id}" );
				
				return true;
			}
			
			switch ( $access_type ) {
				case 'edit':
					$prefix = '_edit';
					break;
				case 'add':
					$prefix = '_publish';
					break;
				default:
					$prefix = '_read';
			}
			
			$utils->log( "Requested access type is {$access_type} and override for forum/system is: " . ( $override ? 'Yes' : 'No' ) );
			
			if ( 'no_access' !== $permission || ( 'no_access' === $permission && true == $override ) ) {
				
				$capabilities = $this->select_capabilities( $permission );
				$post_type    = get_post_type( $post_id );
				$perm_var     = "${prefix}_perm";
				$user         = new \WP_User( $user_id );
				
				if ( $this->is_forum( $post_id ) ) {
					$perm_var = "${prefix}_forum_perm";
				} else if ( $this->is_topic( $post_id ) ) {
					$perm_var = "{$prefix}_topic_perm";
				} else if ( $this->is_reply( $post_id ) ) {
					$perm_var = "{$prefix}_reply_perm";
				}
				
				$required_perm = $this->{$perm_var};
				$utils->log( "Checking {$post_type} for {$required_perm}" );
				
				$result = in_array( $perm_var, $user->get_role_caps() );
				
				$utils->log( " {$required_perm} permission for {$user_id} is: " . ( $result ? 'Granted' : 'Denied' ) );
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
		 * Check if the user has access to the forum/topic/reply (post id)
		 *
		 * @param int $post_id
		 * @param int $user_id
		 *
		 * @return bool
		 */
		/*
		public function user_can_access( $post_id, $user_id = null ) {
			
			$result  = false;
			$allowed = false;
			$type    = null;
			
			$utils = Utilities::get_instance();
			
			if ( ! empty( $post_id ) ) {
				
				if ( null === $user_id ) {
					$user_id = get_current_user_id();
				}
				
				if ( WP_DEBUG ) {
					Cache::delete( self::CAN_ACCESS . "_{$user_id}_{$post_id}", self::CACHE_GROUP );
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
						
						$allowed = pmpro_has_membership_access( $post_id, $user_id );
					}
					
					$required = true;
					
					$post = get_post( $post_id );
					$this->is_forum_post( $post );
					$capabilities = $this->select_capabilities( $permission );
					
					$utils->log( "Starting with " . count( $capabilities ) . " to check: " . print_r( $capabilities, true ) );
					
					switch ( true ) {
						case $this->is_forum():
							$type = 'forum';
							break;
						case $this->is_topic():
							$type = 'topic';
							break;
						case $this->is_reply():
							$type = 'reply';
							break;
					}
					
					$utils->log( "Found the type: {$type}" );
					
					if ( ! empty( $type ) ) {
						$capabilities = $this->caps_of_type( $type, $capabilities, $permission );
						
						$function = "bbp_is_single_{$type}";
						
						$utils->log( "Now have " . count( $capabilities ) . " to check" );
						
						foreach ( $capabilities as $cap ) {
							
							$utils->log( "Checking if user {$user_id} is allowed to {$cap} with {$function}" );
							$required = $required && ( $function() && user_can( $user_id, $cap ) );
						}
						
						$result = ( $result || $required ) && ( function_exists( 'pmpro_getMembershipLevelForUser' ) ? $allowed : true );
						
						$utils->log( "User {$user_id} " . ( $result ? 'can' : 'can\'t' ) . " read/access {$post_id}/{$type}" );
						
						Cache::set( self::CAN_ACCESS . "_{$user_id}_{$post_id}", $result, ( 10 * MINUTE_IN_SECONDS ), self::CACHE_GROUP );
					}
				}
			}
			
			return $result;
		}
		*/
		
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
			
			$utils = Utilities::get_instance();
			
			$utils->log( "Checking access rights for {$user->ID} to {$post->ID}" );
			
			// Not for bbPress content
			if ( false === $this->is_forum_post( $post ) ) {
				
				$utils->log( "We're not processing a forum post, skipping access check for {$post->ID}" );
				
				return $has_access;
			}
			
			global $e20r_roles_addons;
			
			// Are we supposed to be active?
			if ( false == $e20r_roles_addons['bbpress_roles']['is_active'] ) {
				
				$utils->log( "The bbPress Roles add-on is disabled" );
				
				return $has_access;
			}
			
			if ( false === $this->should_be_accessible() ) {
				
				$utils->log( "{$post->ID} is inaccessible to {$user->ID}" );
				
				return false;
				
			} else if ( is_user_logged_in() ) {
				$this->clear_blocked( $user );
			}
			
			// Do we need to override the access value?
			$access_for_levels = get_post_meta( $post->ID, 'e20r_bbpress_access_levels' );
			
			if ( ! is_user_logged_in() ) {
				return $has_access || $this->allow_anon_read();
			}
			
			if ( ! isset( $user->membership_level ) ) {
				$user->membership_level = pmpro_getMembershipLevelForUser( $user->ID );
			}
			
			if ( isset( $user->membership_level->id ) && in_array( $user->membership_level->id, $access_for_levels ) ) {
				
				$utils->log( "User {$user->ID} has level (meta) access to forum {$post->ID}" );
				$has_access = true;
			}
			
			// Anybody can read the content
			if ( true === $this->should_be_accessible() ) {
				
				if ( in_array( $post->post_type, array( 'forum', 'topic' ) ) ) {
					$utils->log( "Can view the {$post->post_type}!" );
					
					$has_access = true;
				}
			}
			
			$utils->log( "Including ({$post->ID}) in list? " . ( $has_access ? 'Yes' : 'No' ) );
			
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
			$utils = Utilities::get_instance();
			
			$utils->log( "Adding the bbPress Forum capabilities to the membership level capabilities?" );
			
			if ( false == $e20r_roles_addons['bbpress_roles']['is_active'] ) {
				return $capabilities;
			}
			
			$level_settings = $this->load_option( 'level_settings' );
			$preserve       = array_diff( $this->select_capabilities( $level_settings[ $level_id ]['forum_permission'] ), $capabilities );
			
			$utils->log( "Keeping the following capabilities: " . print_r( $preserve, true ) );
			
			if ( isset( $level_settings[ $level_id ] ) ) {
				
				$utils->log( "Adding/Removing the {$level_settings[$level_id]['forum_permission']} capabilities: " . print_r( $level_settings[ $level_id ]['capabilities'], true ) );
				$utils->log( "... for the existing level specific capabilities: " . print_r( $capabilities, true ) );
				
				$capabilities = array_merge( $preserve, $level_settings[ $level_id ]['capabilities'] );
			}
			
			// Clear up the array
			$capabilities = array_unique( $capabilities );
			
			$utils->log( "Loaded the bbPress Forum roles required for {$level_id}: " . print_r( $capabilities, true ) );
			
			return $capabilities;
		}
		
		/**
		 * Apply the default forum role for users who have the membership level.
		 *
		 * @param string   $role_name
		 * @param int      $level_id
		 * @param \WP_User $user
		 */
		public function add_level_forum_role( $role_name, $level_id, $user ) {
			
			$utils = Utilities::get_instance();
			$role  = get_role( "e20r_bbpress_level_{$level_id}_access" );
			
			if ( ! $role->has_cap( 'spectate' ) ) {
				
				$utils->log( "Adding 'spectate' capability from e20r_bbpress_level_{$level_id}_access" );
				$role->add_cap( 'spectate' );
			}
			
			$utils->log( "Adding default role 'e20r_bbpress_level_access' to {$user->ID} for {$level_id}" );
			$user->add_role( "e20r_bbpress_level_{$level_id}_access" );
		}
		
		/**
		 * @param string   $role_name
		 * @param int      $level_id
		 * @param string   $status
		 * @param \WP_User $user
		 */
		public function remove_level_forum_role( $role_name, $level_id, $status, $user ) {
			
			$utils = Utilities::get_instance();
			$role  = get_role( "e20r_bbpress_level_{$level_id}_access" );
			
			if ( $role->has_cap( 'spectate' ) ) {
				$utils->log( "Removing 'spectate' capability from e20r_bbpress_level_{$level_id}_access" );
				$role->remove_cap( 'spectate' );
			}
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
				'topic_label'        => __( 'Topic', 'bbpress' ),
				'topic_label_plural' => __( 'Topics', 'bbpress' ),
				'deactivation_reset' => false,
				'on_account_page'    => false,
				'hide_forums'        => false,
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
			$utils->log( "In toggle_addon action handler for the bbPress add-on" );
			
			if ( 'bbpress_roles' !== $addon ) {
				$utils->log( "Not processing the bbPress add-on: {$addon}" );
				
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
		 * @return bool|array
		 */
		public function load_option( $option_name ) {
			
			$this->settings = get_option( "{$this->option_name}" );
			
			if ( isset( $this->settings[ $option_name ] ) && ! empty( $this->settings[ $option_name ] ) ) {
				
				return $this->settings[ $option_name ];
			}
			
			return false;
			
		}
		
		/**
		 * Should the forum/thread/reply be visible for the current user
		 *
		 * @return bool
		 */
		public function should_be_accessible() {
			
			$utils = Utilities::get_instance();
			
			$user_id     = get_current_user_id();
			$level       = pmpro_getMembershipLevelForUser();
			$level_id    = null;
			$level_perms = null;
			
			if ( ! empty( $level ) ) {
				$level_id    = $level->id;
				$level_perms = $this->get_user_level_perms( $user_id );
			}
			
			$grant_access = ( true === $this->allow_anon_read() ) || ( ! empty( $user_id ) && ! empty( $level_id ) && ( 'no_access' !== $level_perms ) );
			
			$utils->log( "Level perms for {$user_id}: " . print_r( $level_perms, true ) );
			$utils->log( "Granting access to the forum? " . ( $grant_access ? 'Yes' : 'No' ) );
			
			return $grant_access;
		}
		
		/**
		 * We will set new users to "blocked", unless the level definition suggests otherwise
		 *
		 * @param \WP_User|null
		 *
		 */
		public function clear_blocked( $user = null ) {
			
			$utils = Utilities::get_instance();
			
			if ( ! is_user_logged_in() ) {
				$utils->log( "Nothing to do since the user isn't logged in" );
				
				return;
			}
			
			if ( ! $this->is_bbPress_active() ) {
				$utils->log( "Nothing to do since bbPress is deactivated" );
				
				return;
			}
			
			if ( false === $this->should_be_accessible() ) {
				$utils->log( "Nothing to do since the forum is inaccessible" );
				
				return;
			}
			
			if ( empty( $user ) ) {
				$user_id = get_current_user_id();
				$user    = new \WP_User( $user_id );
			} else {
				$user_id = $user->ID;
			}
			
			$blocked_role = bbp_get_blocked_role();
			$default_role = bbp_get_default_role();
			
			$is_blocked   = $user->has_cap( $blocked_role );
			$has_default  = $user->has_cap( $default_role );
			$is_admin     = $user->has_cap( 'administrator' );
			$is_spectator = $user->has_cap( bbp_get_spectator_role() );
			
			// user is a current member,
			if ( ( true === $is_admin && $user->has_cap( $blocked_role ) ) || ( false != pmpro_getMembershipLevelForUser( $user->ID ) && true == $is_blocked ) ) {
				
				$utils->log( "Attempting to remove blocked ({$blocked_role}) role for {$user->ID}..." );
				$user->remove_role( $blocked_role );
				$user->remove_cap( $blocked_role );
				
				if ( $has_default ) {
					
					$user->remove_role( $default_role );
					$user->remove_cap( $default_role );
				}
				
			} else if ( false === $is_admin && ( ! pmpro_getMembershipLevelForUser( $user->ID ) ) ) {
				
				$utils->log( "Attempting to add blocked ({$blocked_role}) role for {$user->ID}..." );
				$user->add_role( $blocked_role );
				$user->remove_role( bbp_get_spectator_role() );
				
				if ( false === $has_default ) {
					$user->add_role( $default_role );
				}
			}
		}
		
		/**
		 * Adds the "Required Membership" checkbox list for all membership levels on the Forum page(s).
		 */
		public function add_pmpro_metabox() {
			
			$utils = Utilities::get_instance();
			
			if ( $this->is_bbPress_active() && function_exists( 'pmpro_page_meta' ) ) {
				
				$utils->log( "Loading the meta box for PMPro" );
				add_meta_box( 'pmpro_page_meta', __( 'Require Membership', 'paid-memberships-pro' ), 'pmpro_page_meta', 'forum', 'side' );
			}
		}
		
		/**
		 * Add the forum post type to the searchable list of Custom Post Types for PMPro
		 *
		 * @param $post_types
		 *
		 * @return array
		 */
		public function post_types_for_pmpro_search( $post_types ) {
			
			$hide_member_forums = $this->load_option( 'hide_forums' );
			
			if ( ! empty( $hide_member_forums ) ) {
				$post_types[] = 'forum';
				
				array_unique( $post_types );
			}
			
			return $post_types;
		}
		
		/**
		 * Returns the column name for the user(s) permission to the forum (based on membership level)
		 *
		 * @param string $retval
		 * @param string $column_name
		 * @param int    $user_id
		 *
		 * @return mixed|string
		 */
		public function add_user_role_to_list( $retval = '', $column_name = '', $user_id = 0 ) {
			
			if ( 'bbp_user_role' === $column_name ) {
				
				$perms = $this->get_user_level_perms( $user_id );
				$level = pmpro_getMembershipLevelForUser( $user_id );
				
				if ( isset( $level->name ) && 'no_access' !== $perms ) {
					$retval = " {$level->name} ({$this->labels[ $perms ]['summary']})";
				}
				
				if ( WP_DEBUG ) {
					error_log( "User permissions: {$retval}" );
				}
			}
			
			return $retval;
		}
		
		/**
		 * @param \WP_Query $query
		 *
		 * @return \WP_Query
		 */
		public function pre_get_posts( $query ) {
			
			global $wpdb;
			$utils = Utilities::get_instance();
			
			if ( is_admin() ) {
				$utils->log( "Not on front-end of site" );
				
				return $query;
			}
			
			if ( false === $query->is_search ) {
				$utils->log( "Not a search operation" );
				
				return $query;
			}
			
			if ( bbp_is_single_topic() ) {
				$utils->log( "Not a list of topics" );
				
				return $query;
			}
			
			if ( $this->allow_anon_read() ) {
				$utils->log( "Anybody can read, so allow search as well!" );
				
				return $query;
			}
			
			$utils->log( "Processing WP Query... " );
			
			$sql                  = $wpdb->prepare( "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", 'e20r_bbpress_access_levels' );
			$restricted_forum_ids = $wpdb->get_col( $sql );
			
			if ( empty( $forum_ids ) ) {
				$utils->log( "No protected forum(s) found!" );
				
				return $query;
			}
			
			$utils->log( "Found " . count( $restricted_forum_ids ) . " forums: " . print_r( $restricted_forum_ids, true ) );
			
			if ( ! empty( $restricted_forum_ids ) ) {
				
				$excluded_ids = esc_sql( implode( ',', $restricted_forum_ids ) );
				$sql          = $wpdb->prepare(
					"SELECT DISTINCT post_id
                                  FROM {$wpdb->postmeta}
                                  WHERE meta_key = %s AND meta_value IN ( {$excluded_ids} )",
					'_bbp_forum_id'
				);
				
				$exclude_topics   = $wpdb->get_col( $sql );
				$already_excluded = $query->get( 'post__not_in' );
				
				if ( ! empty( $already_excluded ) && ! is_array( $already_excluded ) ) {
					
					$already_excluded = array( $already_excluded );
				}
				
				if ( empty( $already_excluded ) ) {
					$skip_all = array_merge( $restricted_forum_ids, $exclude_topics );
				} else {
					$skip_all = array_merge( $already_excluded, $restricted_forum_ids, $exclude_topics );
				}
				
				$utils->log( "List of post IDs to skip: " . print_r( $skip_all, true ) );
				$query->set( 'post__not_in', $skip_all );
			}
			
			return $query;
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
			
			parent::is_enabled( 'bbpress_roles' );
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
					'id'              => 'e20r_bbpress_role_global',
					'label'           => __( "E20R Roles: bbPress Settings" ),
					'render_callback' => array( $this, 'render_bbpress_settings_text' ),
					'fields'          => array(
						array(
							'id'              => 'global_anon_read',
							'label'           => __( "Non-member access", E20R_Roles_For_PMPro::plugin_slug ),
							'render_callback' => array( $this, 'render_forum_read_select' ),
						),
						array(
							'id'              => 'topic_label',
							'label'           => __( "Replace 'Topic' with", E20R_Roles_For_PMPro::plugin_slug ),
							'render_callback' => array( $this, 'render_topic_label_input' ),
						),
						array(
							'id'              => 'topic_label_plural',
							'label'           => __( "Replace 'Topics' with", E20R_Roles_For_PMPro::plugin_slug ),
							'render_callback' => array( $this, 'render_topic_label_plural_input' ),
						),
						array(
							'id'              => 'deactivation_reset',
							'label'           => __( "Clean up on Deactivate", E20R_Roles_For_PMPro::plugin_slug ),
							'render_callback' => array( $this, 'render_forum_cleanup' ),
						),
						array(
							'id'              => 'on_account_page',
							'label'           => __( "Show forums on account page", E20R_Roles_For_PMPro::plugin_slug ),
							'render_callback' => array( $this, 'render_account_page_setting' ),
						),
						array(
							'id'              => 'hide_forums',
							'label'           => __( "Hide Member Forums", E20R_Roles_For_PMPro::plugin_slug ),
							'render_callback' => array( $this, 'render_hide_setting' ),
						),
					
					),
				),
			);
			
			return $settings;
		}
		
		public function render_topic_label_plural_input() {
			
			$topic_replacement = $this->load_option( 'topic_label_plural' );
			?>
            <input type="text" id="<?php esc_attr_e( $this->option_name ); ?>-topic_label_plural"
                   name="<?php esc_attr_e( $this->option_name ); ?>[topic_label_plural]"
                   value="<?php esc_attr_e( $topic_replacement ); ?>"/>
			<?php
		}
		
		public function render_topic_label_input() {
			
			$topic_replacement = $this->load_option( 'topic_label' );
			?>
            <input type="text" id="<?php esc_attr_e( $this->option_name ); ?>-topic_label"
                   name="<?php esc_attr_e( $this->option_name ); ?>[topic_label]"
                   value="<?php esc_attr_e( $topic_replacement ); ?>"/>
			<?php
		}
		
		/**
		 * Checkbox to include/not include list of forums the user has access to on their Membership account page
		 */
		public function render_hide_setting() {
			
			$hide_forums = $this->load_option( 'hide_forums' );
			
			if ( WP_DEBUG ) {
				error_log( "Do we need hide member forums? " . ( $hide_forums == true ? 'Yes' : 'No' ) );
			}
			?>
            <input type="checkbox" id="<?php esc_attr_e( $this->option_name ); ?>-hide_forums"
                   name="<?php esc_attr_e( $this->option_name ); ?>[hide_forums]"
                   value="1" <?php checked( true, $hide_forums ); ?> />
			<?php
		}
		
		/**
		 * Checkbox to include/not include list of forums the user has access to on their Membership account page
		 */
		public function render_account_page_setting() {
			
			$on_account_page = $this->load_option( 'on_account_page' );
			
			if ( WP_DEBUG ) {
				error_log( "Do we need to add links for forums on user Account page? " . ( $on_account_page == true ? 'Yes' : 'No' ) );
			}
			?>
            <input type="checkbox" id="<?php esc_attr_e( $this->option_name ); ?>-on_account_page"
                   name="<?php esc_attr_e( $this->option_name ); ?>[on_account_page]"
                   value="1" <?php checked( true, $on_account_page ); ?> />
			<?php
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
				error_log( "Input for save in bbPress_Roles:: " . print_r( $input, true ) );
			}
			
			$defaults = $this->load_defaults();
			
			foreach ( $defaults as $key => $value ) {
				
				if ( 'level_settings' == $key && isset( $this->settings[ $key ] ) ) {
					
					$level_settings = $this->settings['level_settings'];
					
					if ( ( isset( $level_settings[ - 1 ] ) && count( $level_settings ) <= 1 ) || empty( $level_settings ) ) {
						$level_settings = $defaults['level_settings'];
					}
					
					foreach ( $level_settings as $level_id => $settings ) {
						
						if ( isset( $this->settings['level_settings'][ $level_id ]['capabilitiies'] ) ) {
							unset( $this->settings['level_settings'][ $level_id ]['capabilitiies'] );
						}
						
						if ( true == $input['global_anon_read'] && ( 'no_access' === $this->settings['level_settings'][ $level_id ]['forum_permission'] ) ) {
							$this->settings['level_settings'][ $level_id ]['forum_permission'] = 'read_only';
							$settings['forum_permission']                                      = 'read_only';
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
		public function render_bbpress_settings_text() {
			?>
            <p class="e20r-bbpress-global-settings-text">
				<?php _e( "Configure global settings for the E20R Roles: bbPress add-on", E20R_Roles_For_PMPro::plugin_slug ); ?>
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
			
			if ( ! in_array( 'bbpress_roles', $active_addons ) ) {
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
			
			if ( ! in_array( 'bbpress_roles', $active_addons ) ) {
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
			
			$utils          = Utilities::get_instance();
			$level_settings = $this->load_option( 'level_settings' );
			
			if ( ! isset( $level_settings[ $level_id ] ) ) {
				$level_settings[ $level_id ] = array(
					'capabilities'     => array(),
					'forum_permission' => 'no_access',
				);
			}
			
			$level_settings[ $level_id ]['forum_permission'] = $utils->get_variable( 'e20r_bbpress_settings-forum_permission', array() );
			
			if ( WP_DEBUG ) {
				error_log( "Current forum permissions for {$level_id}: {$level_settings[$level_id]['forum_permission']}" );
			}
			
			if ( isset( $level_settings[ - 1 ] ) ) {
				unset( $level_settings[ - 1 ] );
			}
			
			if ( $this->allow_anon_read() && 'no_access' === $level_settings[ $level_id ]['forum_permission'] ) {
				
				$utils->log( "Overriding the configured forum permission (bumped from no_access to read only)" );
				$level_settings[ $level_id ]['forum_permission'] = 'read_only';
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
			
			if ( 'no_access' === $forum_permission && $this->allow_anon_read() ) {
				$forum_permission = 'read_only';
			}
			?>
            <h4><?php _e( 'bbPress Forum Access', E20R_Roles_For_PMPro::plugin_slug ); ?></h4>
            <table class="form-table">
                <tbody>
                <tr class="e20r-bbpress-settings">
                    <th scope="row" valign="top"><label
                                for="e20r-roles-bbpress-permissions"><?php _e( "Forum access", E20R_Roles_For_PMPro::plugin_prefix ); ?></label>
                    </th>
                    <td class="e20r-bbpress-settings-select">
                        <select name="e20r_bbpress_settings-forum_permission" id="e20r-roles-bbpress-permissions">
                            <option value="no_access" <?php selected( 'no_access', $forum_permission ); ?>><?php esc_html_e( $this->labels['no_access']['level_settings'] ); ?></option>
                            <option value="read_only" <?php selected( 'read_only', $forum_permission ); ?>><?php esc_html_e( $this->labels['read_only']['level_settings'] ); ?></option>
                            <option value="add_replies" <?php selected( 'add_replies', $forum_permission ); ?>><?php esc_html_e( $this->labels['add_replies']['level_settings'] ); ?></option>
                            <option value="add_topics" <?php selected( 'add_topics', $forum_permission ); ?>><?php esc_html_e( $this->labels['add_topics']['level_settings'] ); ?></option>
                            <option value="add_forum" <?php echo selected( 'add_forum', $forum_permission ); ?>><?php esc_html_e( $this->labels['add_forum']['level_settings'] ); ?></option>
                            <option value="forum_support" <?php selected( 'forum_support', $forum_permission ); ?>><?php esc_html_e( $this->labels['forum_support']['level_settings'] ); ?></option>
                            <option value="forum_admin" <?php selected( 'forum_admin', $forum_permission ); ?>><?php esc_html_e( $this->labels['forum_admin']['level_settings'] ); ?></option>
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
			
			$utils = Utilities::get_instance();
			$user  = get_user_by( 'ID', $user_id );
			$role  = get_role( $role_name );
			
			if ( empty( $role ) ) {
				return false;
			}
			
			/**
			 * if ( false === user_can( $user, 'e20r_bbpress_level_access' ) ) {
			 * $utils->log( "Adding the default E20R Roles - bbPress Forum role" );
			 * $user->add_role( 'e20r_bbpress_level_access' );
			 * }
			 */
			
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
		 * @param string $type      One of 6 possible: 'read_all', 'add_replies', 'add_topics', 'add_forums', 'support', 'admin'
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
				
				case 'add_topics':
					$this->configure_forum_reply_capabilities();
					$capabilities = $this->_add_topics_capabilities;
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
			
			$this->_read_only_capabilities = apply_filters( 'e20r_roles_bbpress_read_capabilities', $default_capabilities );
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
				'e20r_roles_bbpress_add_reply_capabilities',
				array_merge( $default_reply_capabilities, $this->_read_only_capabilities )
			);
			
			$this->_add_topics_capabilities = apply_filters(
				'e20r_roles_bbpress_add_thread_capabilities',
				array_merge( $default_thread_capabilities, $this->_add_replies_capabilities )
			);
			
			$this->_add_forum_capabilities = apply_filters(
				'e20r_roles_bbpress_add_forum_capabilities',
				array_merge( $default_forum_capabilities, $this->_add_topics_capabilities )
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
				'e20r_roles_bbpress_forum_support_capabilities',
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
				'e20r_roles_bbpress_forum_admin_capabilities',
				array_merge( $default_capabilities, $this->_forum_support_capabilities )
			);
		}
		
		/**
		 * Fetch the properties for bbPress
		 *
		 * @return bbPress_Roles
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
		
		/**
		 * Add an add-on specific bbPress role
		 *
		 * @param array $bbp_role_defs
		 *
		 * @return array
		 */
		public function configure_addon_bbpress_role( $bbp_role_defs ) {
			
			$level_settings = $this->load_option( 'level_settings' );
			$utils          = Utilities::get_instance();
			$utils->log( "Configure default role capabilities for bbPress" );
			
			if ( true == $this->load_option( 'global_anon_read' ) ) {
				$utils->log( "Global anonymous read access is enabled" );
				$use_caps = array( 'spectate' );
			} else {
				$utils->log( "Global anonymous read access is NOT enabled" );
				$use_caps = array();
			}
			
			$bbp_role_defs["e20r_bbpress_default_access"] = array(
				'name'         => 'Default for Member Roles',
				'capabilities' => $use_caps,
			);
			
			foreach ( $level_settings as $level_id => $settings ) {
				
				$level = pmpro_getLevel( $level_id );
				
				if ( ! empty( $level ) ) {
					$utils->log( "Adding role definition for {$level->name}" );
					
					$bbp_role_defs["e20r_bbpress_level_{$level_id}_access"] = array(
						'name'         => $level->name,
						'capabilities' => $settings['capabilities'],
					);
				}
			}
			
			return $bbp_role_defs;
		}
		
		/**
		 * Define the capabilities for the default e20r_bbpress_level_access forum role
		 *
		 * @param array  $caps
		 * @param string $role
		 *
		 * @return array
		 */
		public function configure_addon_bbpress_role_caps( $caps, $role ) {
			
			$utils = Utilities::get_instance();
			
			if ( false != preg_match( '/e20r_bbpress_level_(.*)_access/', $role, $level_ids ) ) {
				
				if ( count( $level_ids ) > 1 ) {
					
					$utils->log( "Configure {$role} capabilities..." );
					
					$level_settings = $this->load_option( 'level_settings' );
					$level_id       = $level_ids[ ( count( $level_ids ) - 1 ) ];
					
					$caps = $level_settings[ $level_id ]['capabilities'];
					$utils->log( "Set the {$role} with the following capabilities: " . print_r( $caps, true ) );
				}
			}
			
			return $caps;
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
			
			
			global $current_user;
			
			if ( true === parent::is_enabled( $stub ) ) {
				
				$utils->log( "Loading other actions/filters for {$e20r_roles_addons[$stub]['label']}" );
				
				add_filter( 'e20r_roles_addon_has_access', array( self::get_instance(), 'has_access' ), 10, 4 );
				
				// add_filter( 'pmpro_has_membership_access_filter', array( PMPro_Content_Access::get_instance(), 'has_membership_access' ), 99, 4 );
				
				$can_global_post = get_option( '_bbp_allow_global_access' );
				$current_default = bbp_get_default_role();
				
				if ( false == $can_global_post ) {
					$utils->log( "Setting the global access flag & configuring the default role to 'blocked'" );
					update_option( '_bbp_allow_global_access', true );
				}
				
				if ( false === strpos( $current_default, 'e20r_bbpress_default_access' ) ) {
					$utils->log( "Updating the default role for bbPress for this add-on" );
					update_option( '_bbp_default_role', 'e20r_bbpress_default_access' );
				}
				
				add_action( 'admin_menu', array( self::get_instance(), 'add_pmpro_metabox' ), 10 );
				
				add_action( 'init', array( self::get_instance(), 'clear_blocked' ) );
				add_action( 'template_redirect', array( self::get_instance(), 'restrict_forums' ) );
				
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
				
				add_action( 'e20r_roles_add_level_role', array(
					self::get_instance(),
					'add_level_forum_role',
				), 10, 3 );
				add_action( 'e20r_roles_delete_level_role', array(
					self::get_instance(),
					'remove_level_forum_role',
				), 10, 4 );
				
				/** Access filters for the add-on to use/leverage */
				add_filter( 'the_posts', array( self::get_instance(), 'check_access' ), 99, 2 );
				add_filter( 'gettext', array( self::get_instance(), 'replace_topic_labels' ), 10, 3 );
				
				add_filter( 'bbp_is_forum_closed', array( self::get_instance(), 'close_forum' ), 10, 3 );
				add_filter( 'bbp_get_reply_excerpt', array( self::get_instance(), 'hide_forum_entry' ), 999, 2 );
				add_filter( 'bbp_get_reply_content', array( self::get_instance(), 'hide_forum_entry' ), 999, 2 );
				
				add_filter( 'bbp_get_reply_class', array( self::get_instance(), 'set_reply_post_class' ), 10, 1 );
				add_filter( 'bbp_get_dynamic_roles', array( self::get_instance(), 'configure_addon_bbpress_role' ), 1 );
				add_action( 'bbp_get_caps_for_role', array(
					self::get_instance(),
					'configure_addon_bbpress_role_caps',
				), 99, 2 );
				
				
				add_filter( 'pmpro_member_links_bottom', array(
					self::get_instance(),
					'add_topics_as_pmpro_account_links',
				), 10, 0 );
				
				add_filter( 'pmpro_has_membership_access_filter', array( self::get_instance(), 'has_access' ), 99, 4 );
				
				// Load the filtering logic for PMPro/Forums if set on PMPro's advanced settings page
				if ( function_exists( 'pmpro_getOption' ) ) {
					
					$filter_queries = pmpro_getOption( 'filterqueries' );
					$utils->log("Filter searches/queries? " . ( $filter_queries ? 'Yes' : 'No') );
					
					if ( true == $filter_queries ) {
						add_filter( 'pre_get_posts', array( self::get_instance(), 'pre_get_posts' ) );
					}
				}
				
				add_filter( 'manage_users_custom_column', array(
					self::get_instance(),
					'add_user_role_to_list',
				), 20, 3 );
				
				// add_filter( 'the_content', array( self::get_instance(), 'hide_forum_entry' ), 999, 2 );
				// add_filter( 'the_excerpt', array( self::get_instance(), 'hide_forum_entry' ), 999, 2 );
				
				$hide_member_forums = self::get_instance()->load_option( 'hide_member_forums' );
				
				$utils->log("Hide member forums? " . ( $hide_member_forums ? 'Yes' : 'No') );
				
				if ( true == $hide_member_forums ) {
					
					add_filter( 'pre_get_posts', 'pmpro_search_filter' );
					add_filter( 'pmpro_search_filter_post_types', array(
						self::get_instance(),
						'post_types_for_pmpro_search',
					) );
				}
				
				add_action( 'save_post_forum', array( self::get_instance(), 'save_forum_protection' ), 15 );
				
				self::get_instance()->configure_forum_admin_capabilities();
			}
		}
	}
	
	// Configure the add-on (global settings array)
	global $e20r_roles_addons;
	
	$e20r_roles_addons['bbpress_roles'] = array(
		'class_name'            => 'bbPress_Roles',
		'is_active'             => ( get_option( 'e20r_bbpress_roles_enabled', false ) == 1 ? true : false ),
		'status'                => 'deactivated',
		'label'                 => 'bbPress Roles',
		'admin_role'            => 'manage_options',
		'required_plugins_list' => array(
			'bbpress/bbpress.php'                           => array(
				'name' => 'bbPress Forum',
				'url'  => 'https://wordpress.org/plugins/bbpress/',
			),
			'paid-memberships-pro/paid-memberships-pro.php' => array(
				'name' => 'Paid Memberships Pro',
				'url'  => 'https://wordpress.org/plugins/paid-memberships-pro/',
			),
			'pmpro-bbpress/pmpro-bbpress.php'               => array(
				'name' => 'Paid Memberships Pro - bbPress Add-on',
				'url'  => 'https://wordpress.org/plugins/pmpro-bbpress/',
			),
		
		),
	);
	
}