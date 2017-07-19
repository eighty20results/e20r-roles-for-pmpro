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

use E20R\Roles_For_PMPro\E20R_Roles_For_PMPro as Roles_For_PMPro;
use E20R\Roles_For_PMPro\Manage_Roles as Manage_Roles;
use E20R\Utilities\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die( __( "Cannot access file directly", Roles_For_PMPro::plugin_slug ) );
}

if ( ! class_exists( 'E20R\\Roles_For_PMPro\\Role_Definitions' ) ) {
	
	
	class Role_Definitions {
		
		/**
		 * @var Role_Definitions
		 */
		static private $instance = null;
		
		/**
		 * Membership Level specific settings
		 *
		 * @var array
		 */
		private $level_settings = array();
		
		/**
		 * All the role settings for the membership levels
		 *
		 * @var array
		 */
		private $all_settings = array( 'clear_on_deactivate' => false );
		
		/**
		 * Role_Definitions constructor.
		 *
		 * @access private
		 * @since  1.0
		 */
		private function __construct() {
			
			$this->all_settings = get_option( Roles_For_PMPro::plugin_slug, array() );
		}
		
		/**
		 * Save the level specific settings for the role definition(s)
		 *
		 * @param int  $level_id
		 * @param null $settings
		 *
		 * @return bool
		 *
		 * @access private
		 * @since  1.0
		 */
		private function save_level_settings( $level_id, $settings = null ) {
			
			if ( is_null( $settings ) ) {
				$settings = $this->level_settings;
			}
			
			if ( empty( $settings ) ) {
				return false;
			}
			
			$this->all_settings[ $level_id ] = $settings;
			
			return $this->save_settings();
		}
		
		/**
		 * Save all level settings for the roles plugin
		 *
		 * @param array|null $settings Array of levels & their role settings
		 *
		 * @return bool
		 *
		 * @since  1.0
		 * @access private
		 */
		private function save_settings( $settings = null ) {
			
			if ( is_null( $settings ) ) {
				$settings = $this->all_settings;
			}
			
			// Save the plugin options and ensure they're always pre-loaded
			return update_option( Roles_For_PMPro::plugin_slug, $settings, true );
		}
		
		/**
		 * TODO: Implement get_role_capabilities() function
		 *
		 * @param string $role_name Name of the system role
		 * @param int    $level_id  Membership level ID
		 *
		 * @access public
		 * @since  TBD
		 */
		public function get_role_capabilities( $role_name, $level_id ) {
		
		}
		
		/**
		 * Returns the default capabilities used for any membership level role
		 *
		 * @return array
		 *
		 * @access public
		 * @since  1.0
		 */
		public static function default_capabilities() {
			
			// Always allow members to read posts
			return apply_filters( 'e20_pmpro_roles_default_capabilities', array( 'read' => true ) );
		}
		
		/**
		 * Reset the capabilities for the membership level role
		 * Will not destroy any prior existing role definitions
		 *
		 * @param int $level_id The membership level ID
		 *
		 * @return false|\WP_Role
		 *
		 * @since  1.0
		 * @access public
		 */
		public function reset_level_role( $level_id ) {
			
			$utils = Utilities::get_instance();
			
			$role_name = Manage_Roles::role_key . $level_id;
			$role_def  = false;
			
			// Get the current settings (if they exist).
			$this->level_settings = $this->get_level_options( $level_id );
			$utils->log( "Current Level settings for {$level_id}: " . print_r( $this->level_settings, true ) );
			
			$default_capabilities = self::default_capabilities();
			
			$capabilities = apply_filters( "e20r_roles_general_level_capabilities", $default_capabilities, $role_name, $level_id );
			$capabilities = apply_filters( "e20r_roles_level_{$level_id}_capabilities", $capabilities, $level_id, $role_name );
			
			// Save the capabilities
			$this->level_settings['capabilities'] = $capabilities;
			
			$utils->log( "Testing if {$role_name} exists/is defined" );
			
			if ( false === $this->role_exists( $role_name ) ) {
				
				$level    = pmpro_getLevel( $level_id );
				$role_def = add_role( $role_name, "{$level->name} (level)", $capabilities );
			} else {
				$role_def = get_role( $role_name );
			}
			
			if ( empty( $role_def ) ) {
				
				$utils->log( "Unable to add role {$role_name} while in reset_level_role()" );
				return false;
			}
			
			$existing_caps = array_keys( $role_def->capabilities );
			
			$utils->log( "Current capabilities assigned to {$role_name}: " . print_r( $existing_caps, true ) );
			
			// Removing all existing capabilities from the role (we're resetting, remember!?!)
			foreach ( $existing_caps as $cap ) {
				
				$utils->log("Processing (removing?) {$cap} from {$role_name}");
				
				$defaults = self::default_capabilities();
				
				if ( is_numeric( $cap ) || ! in_array( $cap, array_keys( $defaults ) ) ) {
					
					$utils->log( "Removing {$cap} from role {$role_name}" );
					
					$role_def->remove_cap( $cap );
				}
			}
			
			// Add the required/requested capabilities
			foreach ( array_keys( $this->level_settings['capabilities'] ) as $capability ) {
				
				$utils->log( "Adding {$capability} to role {$role_name}" );
				
				if ( !is_numeric( $capability ) ) {
					$role_def->add_cap( $capability );
				}
			}
			
			// Save settings & return the role definition if successful
			if ( true === $this->save_level_settings( $level_id, $this->level_settings ) ) {
				return $role_def;
			} else {
				return false;
			}
		}
		
		/**
		 * Create and add the role(s) for the membership level ID
		 *
		 * @param $level_id
		 *
		 * @return bool
		 *
		 * @access public
		 * @since  1.0
		 */
		public function add_level_role( $level_id ) {
			
			$utils = Utilities::get_instance();
			$utils->log( "Adding role to level {$level_id} if needed" );
			
			$this->level_settings = $this->get_level_options( $level_id );
			$manager              = Manage_Roles::get_instance();
			$role_name            = Manage_Roles::role_key . $level_id;
			$level                = pmpro_getLevel( $level_id );
			$role_display_name    = $level->name;
			
			$role = $this->reset_level_role( $level_id );
			
			if ( false === $this->role_exists( $role_name ) ) {
				
				$role = get_role( $role_name );
				
				if ( false === $role ) {
					
					$error_message = sprintf( __( "Error Adding roles & permissions for %s", Roles_For_PMPro::plugin_slug ), $role_display_name );
					
					// Error while adding the role to the system
					$utils = Utilities::get_instance();
					$utils->add_message( $error_message, 'error' );
					
					$utils->log( "Unable to add {$role_name} to system for {$level_id}: {$error_message}" );
					
					return false;
				}
			} else {
				
				$manager->add_role_to_all_level_users( $role_name, $level_id );
				
				return true;
			}
		}
		
		/**
		 * Remove the role from all users on deletion of the membership level
		 *
		 * @param $level_id
		 *
		 * @access public
		 * @since  1.0
		 */
		public function delete_level_role( $level_id ) {
			
			$utils = Utilities::get_instance();
			$role_name = Manage_Roles::role_key . $level_id;
			
			// Find users with the role name assigned to them
			$user_query = new \WP_User_Query(
				array(
					'role' => $role_name,
				)
			);
			
			$utils->log( 'Result: ' . $user_query->get_total() . " users found" );
			
			if ( ! empty( $user_query->get_results() ) ) {
				
				$users = $user_query->get_results();
				// Remove the role from all users
				foreach ( $users as $user ) {
					
					$utils->log("Removing {$role_name} for {$user->ID}");
					$user->remove_role( $role_name );
				}
			}
			
			// Delete the role from the system
			remove_role( $role_name );
		}
		
		/**
		 * Activate the plugin by creating/adding the existing membership level roles
		 *
		 * @access public
		 * @since  1.0
		 */
		public function activate() {
			
			global $wpdb;
			
			$level_ids = $wpdb->get_col( "SELECT id FROM {$wpdb->pmpro_membership_levels}" );
			
			if ( ! empty( $level_ids ) ) {
				
				foreach ( $level_ids as $level_id ) {
					
					$role_name = Manage_Roles::role_key . $level_id;
					
					if ( false === $this->role_exists( $role_name ) ) {
						// Add as a new role
						$this->add_level_role( $level_id );
					} else {
						
						// Reset the role (capabilities)
						$this->reset_level_role( $level_id );
					}
				}
			}
		}
		
		/**
		 * Deactivate the plugin & clean up if the admin has configured us to
		 *
		 * @since  1.0
		 * @access public
		 */
		public function deactivate() {
			
			// Only clean up if the admin wants us to.
			if ( false != $this->all_settings['clear_on_deactivate'] ) {
				
				// Grab all existing membership levels
				$levels = pmpro_getAllLevels( true, true );
				
				// Process them and remove the roles (and role assignments for users on the system)
				foreach ( $levels as $level ) {
					
					$this->delete_level_role( $level->id );
				}
			}
			
			do_action( 'e20r_roles_addon_deactivating_core', $this->all_settings['clear_on_deactivate'] );
		}
		
		/**
		 * AJAX handler for repair request
		 *
		 * @since  1.0
		 * @access public
		 */
		public function repair_roles() {
			
			check_ajax_referer( E20R_Roles_For_PMPro::ajax_fix_action );
			
			$levels  = pmpro_getAllLevels( true, true );
			$ret_val = false;
			
			if ( empty( $levels ) ) {
				wp_die( 'failed' );
			}
			
			// Process all defined roles on the system
			foreach ( $levels as $level ) {
				
				$role_name = Manage_Roles::role_key . $level->id;
				
				if ( false === $this->role_exists( $role_name ) ) {
					
					// Add as a new role
					$this->add_level_role( $level->id );
				} else {
					
					// Reset the role (capabilities)
					$this->reset_level_role( $level->id );
				}
			}
			
			wp_send_json_success();
		}
		
		/**
		 * Test whether the named role exists on the system.
		 *
		 * @param string $role_name Role name to test for
		 *
		 * @return bool
		 *
		 * @since  1.0
		 * @access public
		 */
		public function role_exists( $role_name ) {
			
			$role = get_role( $role_name );
			$utils = Utilities::get_instance();
			
			if ( ! empty( $role ) ) {
				
				$utils->log( "Role {$role_name} exists in the system" );
				$utils->log( "Capabilities: " . print_r( $role->capabilities, true ) );
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * Get the configured options for the membership level role
		 *
		 * @param int $level_id The Membership Level ID
		 *
		 * @return array|boolean
		 *
		 * @access private
		 * @since  1.0
		 */
		private function get_level_options( $level_id ) {
			
			$this->all_settings = get_option( E20R_Roles_For_PMPro::plugin_slug, array(
				$level_id => $this->default_role_settings(),
			) );
			
			// Add default settings for level ID if it's not currently configured.
			if ( ! isset( $this->all_settings[ $level_id ] ) ) {
				$this->all_settings[ $level_id ] = $this->default_role_settings();
			}
			
			return $this->all_settings[ $level_id ];
		}
		
		private function default_role_settings() {
			
			return array(
				'role_slug'    => '',
				'capabilities' => array(),
				'nice_name'    => '',
				'cpt_support'  => true,
			);
		}
		
		/**
		 * @return Role_Definitions|null
		 *
		 * @access public
		 * @since  1.0
		 */
		public static function get_instance() {
			
			if ( is_null( self::$instance ) ) {
				self::$instance = new self;
			}
			
			return self::$instance;
		}
	}
}