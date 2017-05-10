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
		
		if (WP_DEBUG) {
			error_log("Stub is: {$stub}");
		}
		
		if ( empty( $stub ) || 'roles_addon' === $stub ) {
			return;
		}
		
		$utils = Utilities::get_instance();
		
		self::check_requirements( $stub );
		
		$is_licensed = Licensing\Licensing::is_licensed( $stub );
		
		if ( false === $is_licensed ) {
			
			// Since there's no license, disable the add-on.
			if ( isset( $e20r_roles_addons[ $stub ]['is_active'] ) && true == $e20r_roles_addons[ $stub ]['is_active'] ) {
				$e20r_roles_addons[ $stub ]['is_active'] = false;
			}
			
			$utils->add_message(
				sprintf(
					__(
						'The %1$s add-on is <strong>currently disabled!</strong><br/>It requires an update and usage license key. Please <a href="%2$s">add your license key</a>.',
						E20R_Roles_For_PMPro::plugin_slug
					),
					$e20r_roles_addons[ $stub ]['label'],
					Licensing\Licensing::get_license_page_url( $stub )
				),
				'error'
			);
		}
		
	}
	
	public static function is_enabled( $stub ) {
		
		$utils = Utilities::get_instance();
		
		$utils->log("Checking if {$stub} is enabled");
		
		$enabled = false;
		$licensed = false;
		
		global $e20r_roles_addons;
		$e20r_roles_addons[$stub]['is_active'] = get_option( "e20r_roles_{$stub}_enabled", false );
		
		$utils->log("is_active setting for {$stub}: " . ( $e20r_roles_addons[$stub]['is_active'] ? 'True' : 'False' ) );
		
		if ( true == $e20r_roles_addons[$stub]['is_active'] ) {
			$enabled = true;
		}
		
		$utils->log("The {$stub} add-on is enabled? {$enabled}");
		$force = false;

		/** Removed due to loop for intentionally disabled plugins/add-ons
		if ( false === $enabled && true === $e20r_roles_addons[$stub]['disabled'] ) {
			$utils->log("Forcing a remote check of the license" );
			$force = true;
		}
		*/
		$licensed = Licensing\Licensing::is_licensed( $stub, $force );
		
		$utils->log("The {$stub} add-on is licensed? {$licensed}");
		
		$e20r_roles_addons[ $stub ]['is_active'] = ( $enabled && $licensed );
		
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
	
	public static function check_requirements( $stub ) {
		
		global $e20r_roles_addons;
		
		if ( null === $stub || 'roles_addon' == $stub  ) {
			return;
		}
		
		if (WP_DEBUG) {
			error_log("Checking requirements for {$stub}");
		}
		
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
				
				if ( WP_DEBUG ) {
					error_log( sprintf( "%s is not active!", $e20r_roles_addons[ $stub ]['label'] ) );
				}
				
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