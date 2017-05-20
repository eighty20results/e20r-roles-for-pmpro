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

use E20R\Roles_For_PMPro\E20R_Roles_For_PMPro as E20R_Roles_For_PMPro;
use E20R\Licensing;
use E20R\Utilities\Utilities;

class E20R_Roles_Addon {
	
	/**
	 * @var E20R_Roles_Addon
	 */
	private static $instance = null;
	
	/**
	 * Name of the WordPress option key
	 *
	 * @var string $option_name
	 */
	private $option_name = 'e20r_ao_default';
	
	protected $settings = array();
	
	/**
	 * E20R_Roles_Addon constructor.
	 */
	protected function __construct() {
		
		self::$instance = $this;
		$this->define_settings();
	}
	
	private function define_settings() {
		
		$this->settings = get_option( $this->option_name, array() );
	}
	
	/**
	 * Fetch the properties for E20R_Roles_Addon
	 *
	 * @return E20R_Roles_Addon
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
	
	public static function load_addon( $stub = null ) {
		
		global $e20r_roles_addons;
		
		$utils = Utilities::get_instance();
		$utils->log("Stub is: {$stub}");
		
		if ( empty( $stub ) || 'roles_addon' === $stub ) {
			return;
		}
		
		self::check_requirements( $stub );
		
		/*
		$is_licensed = Licensing\Licensing::is_licensed( $stub );
		
		if ( false === $is_licensed ) {
			
			// Since there's no license, disable the add-on.
			if ( isset( $e20r_roles_addons[ $stub ]['is_active'] ) && true == $e20r_roles_addons[ $stub ]['is_active'] ) {
				$e20r_roles_addons[ $stub ]['is_active'] = false;
			}
		}
		*/
	}
	
	public static function is_enabled( $stub ) {
		
		$utils = Utilities::get_instance();
		
		$utils->log("Checking if {$stub} is enabled");
		
		if ( $stub === 'example_addon' ) {
			return false;
		}
		
		$enabled = false;
		$screen = null;
		
		global $e20r_roles_addons;
		
		$e20r_roles_addons[$stub]['is_active'] = get_option( "e20r_roles_{$stub}_enabled", false ) ? true : false;
		$e20r_roles_addons[$stub]['active_license'] = get_option( "e20r_roles_{$stub}_licensed", false ) ? true : false;
		
		$utils->log("is_active setting for {$stub}: " . ( $e20r_roles_addons[$stub]['is_active'] ? 'True' : 'False' ) );
		
		if ( true == $e20r_roles_addons[$stub]['is_active'] ) {
			$enabled = true;
		}
		
		$utils->log("The {$stub} add-on is enabled? " . ( $enabled ? 'Yes' : 'No') );

		/** Removed due to loop for intentionally disabled plugins/add-ons
		if ( false === $enabled && true === $e20r_roles_addons[$stub]['disabled'] ) {
			$utils->log("Forcing a remote check of the license" );
			$force = true;
		}
		*/
		
		if ( false === $e20r_roles_addons[ $stub ]['active_license'] || ( true === $e20r_roles_addons[ $stub ]['active_license'] && true === Licensing\Licensing::is_license_expiring( $stub ) ) ) {
			$e20r_roles_addons[ $stub ]['active_license'] = Licensing\Licensing::is_licensed( $stub, true );
			update_option( "e20r_roles_{$stub}_licensed", $e20r_roles_addons[ $stub ]['active_license'], false );
		}
		
		$utils->log("The {$stub} add-on is licensed? " . ( $e20r_roles_addons[$stub]['active_license'] ? 'Yes' : 'No') );
		
		$e20r_roles_addons[ $stub ]['is_active'] = ( $enabled && $e20r_roles_addons[$stub]['active_license'] );
		
		if ( $e20r_roles_addons[$stub]['is_active'] ) {
			$e20r_roles_addons[$stub]['status'] = 'active';
		} else {
			$e20r_roles_addons[$stub]['status'] = 'deactivated';
		}
		
		return $e20r_roles_addons[ $stub ]['is_active'];
	}
	
	/**
	 * Extract the class name (for classes with namespaces)
	 *
	 * @param $string
	 *
	 * @return mixed
	 */
	protected function maybe_extract_class_name( $string ) {
		
		$utils = Utilities::get_instance();
		$utils->log( "Supplied (potential) class name: {$string}" );
		
		$class_array = explode( '\\', $string );
		$name        = $class_array[ ( count( $class_array ) - 1 ) ];
		
		$utils->log("Using {$name} to load class");
		return $name;
	}
	
	/**
	 * Action Hook: Enable/disable this add-on. Will clean up if we're being deactivated & configured to do so
	 *
	 * @action e20r_roles_addon_toggle_addon
	 *
	 * @param string $addon
	 * @param bool   $is_active
	 *
	 * @return bool
	 */
	public function toggle_addon( $addon, $is_active = false ) {
		
		global $e20r_roles_addons;
		
		$utils = Utilities::get_instance();
		$licensed = true;
		
		$utils->log( "In toggle_addon action handler for the {$e20r_roles_addons[$addon]['label']} add-on" );
		
		if ( $is_active === false ) {
			
			$utils->log( "Deactivating the add-on so disable the license" );
			Licensing\Licensing::deactivate_license( $addon );
		}
		
		if ( $is_active === false && true == $this->load_option( 'deactivation_reset' ) ) {
			
			// TODO: During add-on deactivation, remove all capabilities for levels & user(s)
			// FixMe: Delete the option entry/entries from the Database
			
			$utils->log( "Deactivate the {$e20r_roles_addons[ $addon ]['label']} capabilities for all levels & all user(s)!" );
		}
		
		if ( true === $is_active && false === $e20r_roles_addons[$addon]['is_active'] ) {
			
			$e20r_roles_addons[ $addon ]['active_license'] = Licensing\Licensing::is_licensed( $addon, true );
			
			if ( true !== $e20r_roles_addons[ $addon ]['active_license'] && is_admin() ) {
				$utils->add_message(
					sprintf(
						__(
							'The %1$s add-on is <strong>currently disabled!</strong><br/>Using it requires a license key. Please <a href="%2$s">add your license key</a>.',
							E20R_Roles_For_PMPro::plugin_slug
						),
						$e20r_roles_addons[ $addon ]['label'],
						Licensing\Licensing::get_license_page_url( $addon )
					),
					'error',
					'backend'
				);
			}
		}
		
		$e20r_roles_addons[ $addon ]['is_active'] = $is_active && $e20r_roles_addons[ $addon ]['active_license'];
		
		$e20r_roles_addons[ $addon ]['status']    = ( $is_active ? 'active' : 'deactivated' );
		
		$utils->log( "Setting the {$addon} option to {$e20r_roles_addons[ $addon ]['status']}" );
		update_option( "e20r_roles_{$addon}_enabled", $is_active, true );
		update_option( "e20r_roles_{$addon}_licensed", $e20r_roles_addons[ $addon ]['active_license'], false );
		
		return $is_active;
	}
	
	public static function check_requirements( $stub ) {
		
		global $e20r_roles_addons;
		
		if ( null === $stub || 'roles_addon' == $stub  ) {
			return;
		}
		
		$utils = Utilities::get_instance();
		
		$utils->log("Checking requirements for {$stub}");
		
		$utils = Utilities::get_instance();
		
		$active_plugins   = get_option( 'active_plugins', array() );
		$required_plugins = $e20r_roles_addons[ $stub ]['required_plugins_list'];
		
		foreach ( $required_plugins as $plugin_file => $info ) {
			
			$is_active = in_array( $plugin_file, $active_plugins );
			
			if ( is_multisite() ) {
				
				$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );
				
				$is_active =
					$is_active ||
					key_exists( $plugin_file, $active_sitewide_plugins );
			}
			
			if ( false === $is_active ) {
				
				$utils->log( sprintf( "%s is not active!", $e20r_roles_addons[ $stub ]['label'] ) );
				$utils->add_message(
					sprintf(
						__(
							'Prequisite for %1$s: Please install and/or activate <a href="%2$s" target="_blank">%3$s</a> on your site',
							E20R_Roles_For_PMPro::plugin_slug
						),
						$e20r_roles_addons[ $stub ]['label'],
						$info['url'],
						$info['name']
					),
					'error',
					'roles_addon'
				);
			}
		}
	}
	
	/*
	 * Append this add-on to the list of configured & enabled add-ons
	 *
	 * @param array $addons
	 *
	 * @return mixed
	 */
	public function configure_this_addon( $addons ) {
		
		return $addons;
	}
	
	public function configure_menu( $menu_array ) {
		
		$menu_array[] = array(
			'page_title' => __( 'Default Roles add-on', E20R_Roles_For_PMPro::plugin_slug ),
			'menu_title' => __( 'Default Roles add-on', E20R_Roles_For_PMPro::plugin_slug ),
			'capability' => 'manage_options',
			'menu_slug'  => 'e20r-roles-addon-default',
			'function'   => array( $this, 'generate_option_menu' ),
		);
		
		return $menu_array;
	}
	
	public function generate_option_menu() {
	
	}
	
	/**
	 * Validate the option responses before saving them
	 *
	 * @param mixed $input
	 *
	 * @return mixed $validated
	 */
	public function validate_settings( $input ) {
		
		global $e20r_roles_addons;
		$utils = Utilities::get_instance();
		
		foreach( $input as $i_key => $i_value  ) {
			
			// Processing the 'is active' checkbox in the parent class only
			if ( false != preg_match( '/is_(.*)_active', $i_key, $matches ) ) {
				
				$utils->log("Processing checkbox for: " . print_r( $matches, true ));
				/*
				unset( $input[$i_key] );
				*/
				/*
				global $e20r_roles_addons;
				$e20r_roles_addons[$stub]['is_active'] = get_option( "e20r_addon_{$stub}_enabled", false );
				*/
			}
		}
		
		return $input;
	}
	
	/**
	 * Fetch the specific option.
	 *
	 * @param string $option_name
	 *
	 * @return bool
	 *
	 * @access  protected
	 * @version 1.0
	 */
	protected function load_option( $option_name ) {
		
		$options = get_option( "{$this->option_name}" );
		if ( isset( $options[ $option_name ] ) && ! empty( $options[ $option_name ] ) ) {
			
			return $options[ $option_name ];
		}
		
		return false;
	}
}