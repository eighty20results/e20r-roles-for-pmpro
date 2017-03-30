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


class PMPro_Content_Access {
	
	private static $instance = null;
	
	private function __construct() {
	
	}
	
	public static function load() {
		
		add_filter( 'pmpro_has_membership_access_filter', array( self::get_instance(), 'has_membership_access' ), 99, 4 );
	}
	
	/**
	 * Override PMPro based access permissions based on role/capabilities
	 *
	 * @param bool $has_access
	 * @param \WP_Post $post
	 * @param \WP_User $user
	 * @param array $levels_for_post
	 *
	 * @return bool
	 *
	 * @since 1.0
	 * @access public
	 */
	public function has_membership_access( $has_access, $post, $user, $levels_for_post ) {
		
		if (WP_DEBUG) {
			error_log("Processing the addon has_access filter(s)");
		}
		
		return apply_filters( 'e20r_roles_addon_has_access', $has_access, $post, $user, $levels_for_post );
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