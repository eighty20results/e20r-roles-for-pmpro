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

namespace E20R\Roles_For_PMPro;


use E20R\Roles_For_PMPro\Addon\E20R_Roles_Addon;
use E20R\Utilities\Cache;

class PMPro_Content_Access {
	
	private static $instance = null;
	
	private function __construct() {
	
	}
	
	public static function load() {
		
		add_filter( 'pmpro_has_membership_access_filter', array(
			self::get_instance(),
			'has_membership_access',
		), 99, 4 );
		
		add_filter( 'pmpro_has_membership_access_filter', array( self::get_instance(), 'grant_admin_access_to_content' ), 999, 4 );
	}
	
	/**
	 * Override PMPro based access permissions based on role/capabilities
	 *
	 * @param bool     $has_access
	 * @param \WP_Post $post
	 * @param \WP_User $user
	 * @param array    $levels_for_post
	 *
	 * @return bool
	 *
	 * @since  1.0
	 * @access public
	 */
	public function has_membership_access( $has_access, $post, $user, $levels_for_post ) {
		
		if ( WP_DEBUG ) {
			error_log( "Processing the addon has_access filter(s)" );
		}
		
		return apply_filters( 'e20r_roles_addon_has_access', $has_access, $post, $user, $levels_for_post );
	}
	
	/**
	 * Return all level IDs that protect the post ID
	 *
	 * @param int  $post_id
	 * @param bool $force
	 *
	 * @return array
	 */
	public static function get_post_levels( $post_id, $force = false ) {
		
		$post_levels = array();
		
		if ( WP_DEBUG || true === $force ) {
			Cache::delete( "post_levels_{$post_id}", E20R_Roles_For_PMPro::cache_group );
		}
		
		if ( null === ( $post_levels = Cache::get( "post_levels_{$post_id}", E20R_Roles_For_PMPro::cache_group ) ) ) {
			
			global $wpdb;
			
			if ( ! isset( $wpdb->pmpro_memberships_pages ) ) {
				return $post_levels;
			}
			
			$sql         = $wpdb->prepare( "SELECT membership_id FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = %d", $post_id );
			$post_levels = $wpdb->get_col( $sql );
			
			if ( ! empty( $post_levels ) ) {
				Cache::set( "post_levels_{$post_id}", $post_levels, HOUR_IN_SECONDS, E20R_Roles_For_PMPro::cache_group );
			}
		}
		
		return $post_levels;
	}
	
	public static function level_has_post_access( $level_id, $post_id ) {
		
		$level_map = array();
		
		if ( WP_DEBUG ) {
			Cache::delete( "has_access_{$level_id}", E20R_Roles_For_PMPro::cache_group );
		}
		
		if ( null === ( $level_map = Cache::get( "has_access_{$level_id}", E20R_Roles_For_PMPro::cache_group ) ) ) {
			
			global $wpdb;
			
			$sql     = $wpdb->prepare( "SELECT COUNT(membership_id) AS ids FROM {$wpdb->pmpro_memberships_pages} WHERE membership_id = %d AND page_id = %d", $level_id, $post_id );
			$results = $wpdb->get_var( $sql );
			
			if ( 0 != $results ) {
				
				$level_map[ $level_id ]   = array();
				$level_map[ $level_id ][] = $post_id;
				
				Cache::set( "has_access_{$level_id}", $level_map, ( 10 * 60 ), E20R_Roles_For_PMPro::cache_group );
			}
		}
		
		return isset( $level_map[ $level_id ] ) ? in_array( $post_id, $level_map[ $level_id ] ) : false;
	}
	
	/**
	 * Grants access to the content if the user has the administrator role
	 *
	 * @param bool $has_access
	 * @param \WP_Post $post
	 * @param \WP_User $user
	 * @param array $levels_for_post
	 *
	 * @return bool
	 */
	public function grant_admin_access_to_content( $has_access, $post, $user, $levels_for_post ) {
		
		if ( user_can( $user, 'manage_options' ) || $user->has_cap('administrator' ) ) {
			$has_access = true;
		}
		
		return $has_access;
	}
	
	/**
	 * Returns an instance of the PMPro Content Access handler
	 *
	 * @return PMPro_Content_Access|null
	 */
	public static function get_instance() {
		
		if ( null === self::$instance ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
}