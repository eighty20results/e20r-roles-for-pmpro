<?php
/**
 * Copyright (c) 2017 - Eighty / 20 Results by Wicked Strong Chicks.
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

namespace E20R\Roles_For_PMPro;

use E20R\Roles_For_PMPro\E20R_Roles_For_PMPro AS E20R_Roles_For_PMPro;
use E20R\Utilities\Utilities;
use E20R\Utilities\Cache as Cache;

// Deny direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
	wp_die( __( "Cannot access file directly", E20R_Roles_For_PMPro::plugin_slug ) );
}

if ( ! class_exists( 'E20R\Roles_For_PMPro\PMPro_Members' ) ) {
	
	class PMPro_Members {
		
		/**
		 * @var PMPro_Members   Instance of the member management class
		 */
		static private $instance;
		
		const admin_cancelled = 'admin_cancelled';
		const admin_changed = 'admin_changed';
		const expired = 'expired';
		const pending = 'active';
		const active = 'active';
		
		const cache_group = 'pmpro_members';
		
		
		/**
		 * The table name for the Paid Memberships Pro Users & Memberships list
		 *
		 * @var mixed|string
		 */
		private $table_name;
		
		/**
		 * PMPro_Members constructor.
		 */
		private function __construct() {
			
			global $wpdb;
			$this->table_name = isset( $wpdb->pmpro_memberships_users ) ? $wpdb->pmpro_memberships_users : "{$wpdb->prefix}pmpro_memberships_users";
		}
		
		public static function init() {
			
			$class = self::$instance;
			
			// Actions that require us to clear all of the PMPro Membership caches
			add_action( 'profile_update', 'E20R\Roles_For_PMPro\PMPro_Members::clear_all_caches' );
			add_action( 'edit_user_profile_update', 'E20R\Roles_For_PMPro\PMPro_Members::clear_all_caches' );
			
			// Actions that require us to clear membership level specific cache(s)
			
			// Actions that require us to update our cache(s)
			
		}
		
		/**
		 * Get members belonging to a specific membership level & with a specific status
		 *
		 * @param int|null $level_id The membership level ID
		 * @param string   $status   The status of the membership to find users for (default: active)
		 * @param bool $force
		 *
		 * @return array
		 */
		public static function get_members( $level_id = null, $status = 'active', $force = false ) {
			
			global $wpdb;
			
			$class         = self::$instance;
			$table_name    = ( isset( $wpdb->pmpro_memberships_users ) ? $wpdb->pmpro_memberships_users : "{$wpdb->prefix}pmpro_memberships_users" );
			$blog_id       = get_current_blog_id();
			$user_ids      = array();
			$cache_timeout = intval( apply_filters( 'e20r_pmpro_member_cache_timeout_mins', 30 ) ) * MINUTE_IN_SECONDS;
			
			if ( empty( $level_id ) ) {
				
				$key_name = "{$status}_{$blog_id}_all_users";
			} else {
				$key_name = "{$status}_{$blog_id}_{$level_id}_users";
			}
			
			// Get data from cache if possible
			if ( true === $force || null === ( $user_ids = Cache::get( $key_name, self::cache_group ) ) ) {
				
				if ( empty( $level_id ) ) {
					$sql = $wpdb->prepare( "SELECT DISTINCT user_id FROM {$table_name} WHERE status = %s", $status );
				} else {
					$sql = $wpdb->prepare( "SELECT DISTINCT user_id FROM {$table_name} WHERE membership_id = %d AND status = %s", $level_id, $status );
				}
				
				$user_ids = $wpdb->get_col( $sql );
				
				// Load the cache
				if ( ! empty( $user_ids ) ) {
					
					Cache::set( $key_name, $user_ids, $cache_timeout, self::cache_group );
				}
			}
			
			return $user_ids;
		}
		
		/**
		 * Find user and compare their status to a status code, and a specific - or their current - membership level
		 *
		 * @param int      $user_id
		 * @param array    $status
		 * @param int|null $level_id
		 *
		 * @return bool
		 */
		public static function is_user( $user_id, $status = array( 'active' ), $level_id = null, $force = false ) {
			
			$ret_val = false;
			
			if ( ! is_array( $status ) ) {
				
				$status = array( $status );
			}
			
			// Handle cases where the level isn't passed to us
			if ( empty( $level_id ) ) {
				
				$level    = pmpro_getMembershipLevelForUser( $user_id );
				$level_id = ( isset( $level->id ) ? $level->id : 0 );
			}
			
			// Can't be active without a membership level ID > 0
			if ( 0 === $level_id && in_array( 'active', $status ) ) {
				return $ret_val;
			}
			
			// Iterate through all of the statuses we've been given
			foreach ( $status as $s ) {
				
				// Grab the users of that membership level/status.
				$user_ids = self::get_members( $level_id, $s, $force );
				
				if ( in_array( $user_id, $user_ids ) ) {
					$ret_val = $ret_val || true;
				}
			}
			
			return $ret_val;
		}
		
		/**
		 * Locate the array of existing active memberships for the specified user
		 *
		 * @param int $user_id The WordPress User ID
		 *
		 * @return array List of active Membership Levels for the specified WordPress user ID
		 *
		 * @since  1.0
		 * @access public
		 */
		public static function get_memberships( $user_id ) {
			
			$class            = self::$instance;
			$blog_id          = get_current_blog_id();
			$user_level_array = array();
			$cache_timeout    = intval( apply_filters( 'e20r_pmpro_member_cache_timeout_mins', 30 ) ) * MINUTE_IN_SECONDS;
			
			$cache_key = "active_users_levels_{$blog_id}";
			
			// Get the data from the cache (is present)
			if ( null === ( $user_level_array = Cache::get( $cache_key, self::cache_group ) ) ) {
				
				// No cached data found. (re)Build the cache again
				global $wpdb;
				
				$table_name = ( isset( $wpdb->pmpro_memberships_users ) ? $wpdb->pmpro_memberships_users : "{$wpdb->prefix}pmpro_memberships_users" );
				$sql        = "SELECT user_id, membership_id FROM {$table_name} WHERE status = 'active'";
				
				$results = $wpdb->get_results( $sql );
				
				if ( ! empty( $results ) ) {
					
					// Process all member/level combinations
					foreach ( $results as $record ) {
						
						if ( ! isset( $user_level_array[ $record->user_id ] ) ) {
							$user_level_array[ $record->user_id ] = array();
						}
						
						// Add the active membership level to the list of existing membership levels for this user ID
						if ( ! in_array( $record->membership_id, $user_level_array[ $record->user_id ] ) ) {
							$user_level_array[ $record->user_id ][] = $record->membership_id;
						}
					}
					
					// Add the data to the cache
					if ( ! empty( $user_level_array ) ) {
						Cache::set( $cache_key, $user_level_array, $cache_timeout . self::cache_group );
					}
				}
			}
			
			// Return the list of membership levels for the specified WordPress user ID
			return $user_level_array[ $user_id ];
		}
		
		/**
		 * Clears all PMPro Membership related cache entries
		 *
		 * @action e20r_pmpro_member_cache_clear - Allows 3rd party plugins/code to trigger their own cache clearing
		 *
		 * @since  1.0
		 * @access public
		 */
		public static function clear_all_caches() {
			
			$blog_id                  = get_current_blog_id();
			$all_membership_cache_key = "active_users_levels_{$blog_id}";
			$statuses                 = PMPro_Members::get_saved_member_statuses();
			
			// Remove cached 'all memberships by user ID' data
			Cache::delete( $all_membership_cache_key, self::cache_group );
			
			$levels = pmpro_getAllLevels( true, true );
			
			// Iterate through all of the membership levels & clear their cache
			foreach ( $levels as $level ) {
				
				// Interate through available statuse(s)
				foreach ( $statuses as $status ) {
					
					$cache_key  = "{$status}_{$blog_id}_{$level->id}_users";
					$status_key = "{$status}_{$blog_id}_all_users";
					
					// Clear the cached data
					Cache::delete( $cache_key, self::cache_group );
					Cache::delete( $status_key, self::cache_group );
				}
			}
			
			// Action to clear any cache for a plugin(s)
			do_action( 'e20r_pmpro_member_cache_clear' );
		}
		
		/**
		 * Update the user from the list of members
		 *
		 * @param int $user_id
		 * @param int $new_level_id
		 * @param int $old_level_id
		 *
		 * @access public
		 * @since  1.0
		 */
		public static function change_user( $user_id, $new_level_id, $old_level_id = null ) {
		
		}
		
		public static function update_list_of_level_members( $level_id, $user_id, $cancel_level_id = null ) {
			
			$utils = Utilities::get_instance();
			
			self::clear_all_caches();
			
			$utils->log( "Cleared all member level/user id caches" );
			
			$all_level_statuses = apply_filters( 'e20r_roles_pmpro_member_statuses', self::get_saved_member_statuses() );
			
			foreach ( $all_level_statuses as $status ) {
				
				$utils->log( "Updating {$status} member cache for {$level_id}" );
				self::get_members( $level_id, $status );
			}
		}
		
		/**
		 * Return the previous membership level ID for the specified user ID
		 *
		 * @param int $user_id
		 *
		 * @return int
		 */
		public static function get_previous_membership_level( $user_id ) {
			
			$utils = Utilities::get_instance();
			
			$utils->log("Fetching previous membership level for {$user_id}");
			
			if ( null === ( $user_levels = Cache::get( "user_levels_{$user_id}", E20R_Roles_For_PMPro::cache_group ) ) ) {
				
				$utils->log("Cache is empty. Building new cache");
				
				global $wpdb;
				$sql = $wpdb->prepare( "SELECT id, membership_id, status FROM {$wpdb->pmpro_memberships_users} WHERE user_id = %d AND status <> %s ORDER BY id DESC",  $user_id, 'active' );
				
				$results = $wpdb->get_results( $sql );
				$user_levels = array();
				
				foreach( $results as $record ) {
					
					$user_levels[] = array( $record->id => $record->membership_id );
				}
				
				Cache::set( "user_levels_{$user_id}", $user_levels, 10 * MINUTE_IN_SECONDS, E20R_Roles_For_PMPro::cache_group );
			}
			
			$level = array_pop( $user_levels );
			return array_pop( $level );
		}
		/**
		 * Return all membership statuses currently in use in the pmpro_memmberships_users table
		 *
		 * @return array
		 *
		 * @filter e20r_pmpro_member_in_use_status_list Modifies the list of in-use statuses before returing to calling
		 *         function
		 * @since  1.0
		 * @access private
		 */
		private static function get_saved_member_statuses() {
			
			global $wpdb;
			$class      = self::$instance;
			$statuses   = array();
			$table_name = ( isset( $wpdb->pmpro_memberships_users ) ? $wpdb->pmpro_memberships_users : "{$wpdb->prefix}pmpro_memberships_users" );
			
			$sql      = "SELECT DISTINCT status FROM {$table_name}";
			$statuses = $wpdb->get_col( $sql );
			
			return apply_filters( 'e20r_pmpro_member_in_use_status_list', $statuses );
		}
		
		/**
		 * @return PMPro_Members
		 */
		static public function get_instance() {
			
			if ( is_null( self::$instance ) ) {
				self::$instance = new self;
			}
			
			return self::$instance;
		}
	}
}