<?php
/*
Plugin Name: E20R Roles for Paid Memberships Pro
Description: Manages membership roles & capabilities for Paid Memberships Pro users/members
Plugin URI: https://eighty20results.com/wordpress-plugins/e20r-roles-for-pmpro
Author: Thomas Sjolshagen <thomas@eighty20results.com>
Author URI: https://eighty20results.com/thomas-sjolshagen/
Version: 2.1.0
License: GPL2
Text Domain: e20r-roles-for-pmpro
Domain Path: /languages
*/

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

use E20R\Licensing\Licensing;
use E20R\Roles_For_PMPro\Manage_Roles;
use E20R\Roles_For_PMPro\Addon;
use E20R\Utilities\Cache;
use E20R\Utilities\PMPro_Members;
use E20R\Utilities\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die( __( "Cannot access", E20R_Roles_For_PMPro::plugin_slug ) );
}

if ( ! class_exists( 'E20R\Roles_For_PMPro\E20R_Roles_For_PMPro' ) ) {
	
	global $e20r_roles_addons;
	$e20r_roles_addons = array();
	
	class E20R_Roles_For_PMPro {
		
		/**
		 * Various constants for the class/plugin
		 */
		const plugin_prefix = 'e20r_roles_for_pmpro_';
		const plugin_slug = 'e20r-roles-for-pmpro';
		
		/**
		 * AJAX specific constants
		 */
		const ajax_fix_action = 'e20r-roles-repair';
		
		const cache_group = 'e20r_roles';
		
		/**
		 * Instance of this class (E20R_Roles_For_PMPro)
		 * @var E20R_Roles_For_PMPro
		 *
		 * @access private
		 * @since  1.0
		 */
		static private $instance = null;
		
		private $addon_settings = array();
		
		private $settings_page_hook = null;
		
		private $settings_name = 'e20r_roles';
		
		private $settings = array();
		
		
		/**
		 * E20R_Roles_For_PMPro constructor.
		 *
		 * @access private
		 * @since  1.0
		 */
		private function __construct() {
		}
		
		/**
		 * Returns the instance of this class (singleton pattern)
		 *
		 * @return E20R_Roles_For_PMPro
		 *
		 * @access public
		 * @since  1.0
		 */
		static public function get_instance() {
			
			if ( is_null( self::$instance ) ) {
				self::$instance = new self;
			}
			
			return self::$instance;
		}
		
		/**
		 * Configure actions & filters for this plugin
		 *
		 * @access public
		 * @since  1.0
		 */
		public function plugins_loaded() {
			
			$this->load_addon_settings();
			
			add_filter( 'e20r-licensing-text-domain', array( $this, 'set_translation_domain' ), 10, 1 );
			add_filter( 'pmpro_has_membership_access_filter', array( PMPro_Content_Access::get_instance(), 'grant_admin_access_to_content' ), 999, 4 );
			
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_register_scripts' ), 9 );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 20 );
			
			add_action( 'pmpro_save_membership_level', array( Role_Definitions::get_instance(), 'add_level_role' ), 20, 1 );
			add_action( 'pmpro_delete_membership_level', array( Role_Definitions::get_instance(), 'delete_level_role' ), 5, 1 );
			
			/*
			add_action( 'pmpro_after_checkout', array(
				Manage_Roles::get_instance(),
				'update_user_role_at_checkout',
			), 10, 2 );
			*/
			
			add_action( 'pmpro_after_change_membership_level', array( Manage_Roles::get_instance(), 'after_change_membership_level' ), 10, 2 );
			add_action( 'pmpro_after_change_membership_level', array( PMPro_Members::get_instance(), 'update_list_of_level_members' ), 99, 2 );
			
			add_action( 'wp_ajax_' . self::ajax_fix_action, array( Role_Definitions::get_instance(), 'repair_roles' ), 10 );
			add_action( 'wp_ajax_nopriv_' . self::ajax_fix_action, array( $this, 'unprivileged_error' ), 10 );
			
			add_action( 'pmpro_membership_level_after_other_settings', array( $this, 'level_settings_page' ), 10 );
			add_action( 'pmpro_save_membership_level', array( $this, 'save_level_settings' ), 10, 1 );
			add_action( 'pmpro_delete_membership_level', array( $this, 'delete_level_settings' ), 10, 1 );
			
			// add_action( 'pmpro_custom_advanced_settings', array( $this, 'global_settings_page' ), 10 );
			
			add_action( 'init', array( $this, 'load_translation' ) );
			add_action( 'plugins_loaded', array(PMPro_Content_Access::get_instance(), 'load' ), 10 );
   
			
			add_action( 'admin_menu', array( $this, 'load_admin_settings_page' ), 10 );
			add_action( 'admin_init', array( $this, 'check_license_warnings' ) );
			
			if ( ! empty ( $GLOBALS['pagenow'] )
			     && ( 'options-general.php' === $GLOBALS['pagenow']
			          || 'options.php' === $GLOBALS['pagenow']
			     )
			) {
				add_action( 'admin_init', array( $this, 'register_role_settings_page' ), 10 );
			}
			
			add_action( 'current_screen', array( $this, 'check_admin_screen' ), 10 );
			add_action( 'http_api_curl', array( $this, 'force_tls_12' ) );
		}
	
		/**
		 * Connect to the server using TLS 1.2
		 *
		 * @param $handle - File handle for the pipe to the CURL process
		 */
		public function force_tls_12( $handle ) {
			
			// set the CURL option to use.
			curl_setopt( $handle, CURLOPT_SSLVERSION, 6 );
		}
		
		/**
         * Clear the License cache (and force remote look-up) if the user is accessing the Settings page for E20R Roles
         *
		 * @param $current_screen
		 */
	    public function check_admin_screen( $current_screen ) {
			
		    $utils = Utilities::get_instance();
		    
			if ( !empty( $this->settings_page_hook ) && $this->settings_page_hook === $current_screen->id ) {
				$utils->log("Clear cache on register settings page");
				Cache::delete( Licensing::CACHE_KEY, Licensing::CACHE_GROUP );
			}
		}
		
		/**
		 * Configure the Language domain for the licensing class/code
		 *
		 * @param string $domain
		 *
		 * @return string
		 */
		public function set_translation_domain( $domain ) {
			
			return 'e20r-roles-for-pmpro';
		}
		
		/**
		 * Configure the default (global) settings for this add-on
         *
		 * @return array
		 */
		private function default_settings() {
			
			return array(
				'deactivation_reset' => false,
			);
		}
		
		/**
		 * Set the options for this plugin
		 */
		private function define_settings() {
			
			$this->settings = get_option( $this->settings_name, $this->default_settings() );
		}
		
		/**
		 * Validating the returned values from the Settings API page on save/submit
		 *
		 * @param array $input Changed values from the settings page
		 *
		 * @return array Validated array
		 *
		 * @since  1.0
		 * @access public
		 */
		public function validate_settings( $input ) {
			
			global $e20r_roles_addons;
			
		    $utils = Utilities::get_instance();
			
            $utils->log( "Roles_For_PMPro input settings: " . print_r( $input, true ) );
			
			foreach ( $e20r_roles_addons as $addon_name => $settings ) {
    
				$utils->log("Trigger local toggle_addon action for {$addon_name}: is_active = " . ( isset( $input["is_{$addon_name}_active"] ) ? 'Yes' : 'No') );
				
				do_action( 'e20r_roles_addon_toggle_addon', $addon_name, isset( $input["is_{$addon_name}_active"] ) );
			}
			
			$defaults = $this->default_settings();
			
			foreach ( $defaults as $key => $value ) {
				
				if ( isset( $input[ $key ] ) ) {
					$this->settings[ $key ] = $input[ $key ];
				} else {
					$this->settings[ $key ] = $defaults[ $key ];
				}
			}
			
			// Validated & updated settings
			return $this->settings;
		}
		
		/**
		 * Load add-on classes & configure them
		 */
		private function load_addon_settings() {
			
			global $e20r_roles_addons;
			$utils = Utilities::get_instance();
			
			$addon_directory_list = apply_filters( 'e20r_roles_addon_directory_path', array( plugin_dir_path( __FILE__ ) . "class/add-on/" ) );
			
			// Search through all of the addon directories supplied
			foreach ( $addon_directory_list as $addon_directory ) {
				
				if ( false !== ( $files = scandir( $addon_directory ) ) ) {
					
					foreach ( $files as $file ) {
						
						if ( '.' === $file || '..' === $file || 'e20r-roles-addon' === $file ) {
							if ( WP_DEBUG ) {
								error_log( "Skipping: {$file}" );
							}
							continue;
						}
						
						$parts      = explode( '.', $file );
						$class_name = $parts[ count( $parts ) - 2 ];
						$class_name = preg_replace( '/-/', '_', $class_name );
                        
                        $utils->log( "Searching for: {$class_name}" );
						
						
						if ( is_array( $e20r_roles_addons ) ) {
							$setting_names = array_map( 'strtolower', array_keys( $e20r_roles_addons ) );
						} else {
							$setting_names = array();
						}
						
						$excluded = apply_filters( 'e20r_licensing_excluded', array( 'e20r_default_license', 'example_addon', 'new_licenses' ) );
						
						if ( ! in_array( $class_name, $setting_names ) && ! in_array( $class_name, $excluded ) && false === strpos( $class_name, 'e20r_roles_addon' ) ) {
       
							$utils->log( "Found unlisted class: {$class_name}" );
       
							$var_name = 'class_name';
							$path     = $addon_directory . sanitize_file_name( $file );
                            
                            $utils->log( "Path to {$class_name}: {$path}" );
								
							// Include the add-on source file
							if ( file_exists( $path ) ) {
								
								require_once( $path );
								
								$class = $e20r_roles_addons[ $class_name ][ $var_name ];
								
								if ( !empty( $class ) ) {
								    
                                    $class = "E20R\\Roles_For_PMPro\\Addon\\{$class}" ;
									$utils->log( "Loading {$class}" );
									
									$enabled = $class::is_enabled( $class_name );
									
									if ( true == $enabled ) {
          
										$utils->log( "Loading the actions & hooks for {$class}" );
										$class::load_addon();
									}
								}
							}
						}
					}
				}
			}
		}
		
		/**
		 * Load settings/options for the plugin
		 *
		 * @param $option_name
		 *
		 * @return bool|mixed
		 */
		public function load_options( $option_name ) {
			
			$this->settings = get_option( "{$this->settings_name}", $this->default_settings() );
			
			if ( isset( $this->settings[ $option_name ] ) && ! empty( $this->settings[ $option_name ] ) ) {
				
				return $this->settings[ $option_name ];
			}
			
			return false;
		}
		
		/**
		 * Add & display license warnings
		 */
		public function check_license_warnings() {
		    
		    global $e20r_roles_addons;
		    $products = array_keys( $e20r_roles_addons );
		    $utils = Utilities::get_instance();
		    
		    foreach( $products as $stub ) {
		        
		        switch( Licensing::is_license_expiring( $stub ) ) {
		            
                    case true:
	                    $utils->add_message( sprintf( __( 'The license for %s will renew soon. As this is an automatic payment, you will not have to do anything. To modify <a href="%s" target="_blank">your license</a>, you will need to access <a href="%s" target="_blank">your account page</a>'), $e20r_roles_addons[$stub]['label'], 'https://eighty20results.com/licenses/', 'https://eighty20results.com/account/' ), 'info', 'backend' );
                        break;
                    case -1:
                        $utils->add_message(sprintf( __( 'Your add-on license has expired. For continued use of the E20R Roles: %s add-on, you will need to <a href="%s" target="_blank">purchase and install a new license</a>.'), $e20r_roles_addons[$stub]['label'], 'https://eighty20results.com/licenses/' ), 'error', 'backend' );
                        break;
                }
            }
        }
        
		/**
		 * Generate the options page for this plugin
		 */
		public function load_admin_settings_page() {
			
			$this->settings_page_hook = add_options_page(
				__( "Roles for Paid Memberships Pro", E20R_Roles_For_PMPro::plugin_slug ),
				__( "E20R PMPro Roles", E20R_Roles_For_PMPro::plugin_slug ),
				apply_filters( 'e20r_roles_min_capability_settings', 'manage_options' ),
				'e20r-roles-settings',
				array( $this, 'global_settings_page' )
			);
			
			Licensing::add_options_page();
		}
		
		/**
		 * Configure options page for the plugin and include any configured add-ons if needed.
		 */
		public function register_role_settings_page() {
			
		    $utils = Utilities::get_instance();
      
			// Configure our own settings
			register_setting( 'e20r_role_options', "{$this->settings_name}", array( $this, 'validate_settings' ) );
			
			add_settings_section(
				'e20r_role_global',
				__( 'Global Settings: E20R Roles for PMPro', E20R_Roles_For_PMPro::plugin_slug ),
				array( $this, 'render_global_settings_text', ),
				'e20r-roles-settings'
			);
			
			add_settings_field(
				'e20r_role_global_reset',
				__( "Reset roles on deactivation", E20R_Roles_For_PMPro::plugin_slug ),
				array( $this, 'render_checkbox' ),
				'e20r-roles-settings',
				'e20r_role_global',
				array( 'option_name' => 'deactivation_reset' )
			);
			
			add_settings_section(
				'e20r_role_addons',
				__( 'Add-ons', E20R_Roles_For_PMPro::plugin_slug ),
				array( $this, 'render_addon_header' ),
				'e20r-roles-settings'
			);
			
			global $e20r_roles_addons;
   
			foreach ( $e20r_roles_addons as $addon => $settings ) {
				
				add_settings_field(
					"e20r_role_addons_{$addon}",
					$settings['label'],
					array( $this, 'render_addon_entry' ),
					'e20r-roles-settings',
					'e20r_role_addons',
					$settings
				);
			}
			
			// Load/Register settings for all active add-ons
			foreach ( $e20r_roles_addons as $name => $info ) {
			    
				if ( true == $info['is_active'] ) {
					
					$addon_fields = apply_filters( "e20r_roles_addon_options_{$name}", array() );
					
					foreach ( $addon_fields as $type => $config ) {
						
						if ( 'setting' === $type ) {
							$utils->log( "Loading: e20r_role_options/{$config['option_name']}" );
							register_setting( 'e20r_role_options', $config['option_name'], $config['validation_callback'] );
						}
						
						if ( 'section' === $type ) {
							
							$utils->log( "Processing " . count( $config ) . " sections" );
							
							// Iterate through the section(s)
							foreach ( $config as $section ) {
								
								$utils->log( "Loading: {$section['id']}/{$section['label']}" );
								add_settings_section( $section['id'], $section['label'], $section['render_callback'], 'e20r-roles-settings' );
								
								$utils->log( "Processing " . count( $section['fields'] ) . " fields" );
								
								foreach ( $section['fields'] as $field ) {
									
								    $utils->log( "Loading: {$field['id']}/{$field['label']}" );
								    
									add_settings_field( $field['id'], $field['label'], $field['render_callback'], 'e20r-roles-settings', $section['id'] );
								}
							}
						}
					}
				} else {
					$utils->log( "Addon settings are disabled for {$name}" );
				}
			}
			
			// Load settings for the Licensing code
			Licensing::register_settings();
		}
		
		/**
		 * Loads the text for the add-on list (to enable/disable add-ons)
		 */
		public function render_addon_header() {
			?>
            <p class="e20r-roles-addon-header-text">
			<?php _e( "Use checkbox to enable/disable the included add-ons", E20R_Roles_For_PMPro::plugin_slug ); ?>
            </p><?php
		}
		
		/**
		 * Render the checkbox for the specific add-on (based on passed config)
		 *
		 * @param array $config
		 */
		public function render_addon_entry( $config ) {
			
			if ( ! empty( $config ) ) {
				$is_active  = $config['is_active'];
				$disable = $config['disabled'];
				$addon_name = strtolower( $config['class_name'] );
				?>
                <input id="<?php esc_attr_e( $addon_name ); ?>-checkbox" type="checkbox"
                       name="<?php echo $this->settings_name; ?>[<?php echo "is_{$addon_name}_active"; ?>]"
                       value="1" <?php checked( $is_active, true ); ?> <?php echo ( $disable ? 'disabled="disabled"' : null ); ?>/>
				<?php
			}
		}
		
		/**
		 * Render description for the global plugin settings
		 */
		public function render_global_settings_text() {
			?>
            <p class="e20r-roles-global-settings-text">
				<?php _e( "Configure deactivation settings", E20R_Roles_For_PMPro::plugin_slug ); ?>
            </p>
			<?php
		}
		
		/**
		 * Render a checkbox for the Settings page
		 */
		public function render_checkbox( $settings ) {
			
			$role_reset = $this->load_options( $settings['option_name'] );
			?>
            <input type="checkbox"
                   name="<?php esc_attr_e( $this->settings_name ); ?>[<?php esc_html_e( $settings['option_name'] ); ?>]"
                   value="1" <?php checked( 1, $role_reset ); ?> />
			<?php
		}
		
		/**
		 * Generates the Settings API compliant option page
		 */
		public function global_settings_page() {
			?>
            <div class="e20r-roles-settings">
                <div class="wrap">
                    <h2 class="e20r-roles-for-pmpro-settings"><?php _e( 'Settings: Eighty / 20 Results - Roles for Paid Memberships Pro', E20R_Roles_For_PMPro::plugin_slug ); ?></h2>
                    <p class="e20r-roles-for-pmpro-settings">
						<?php _e( "Configure global 'E20R Roles for Paid Memberships Pro' settings", E20R_Roles_For_PMPro::plugin_slug ); ?>
                    </p>
                    <form method="post" action="options.php">
						<?php settings_fields( 'e20r_role_options' ); ?>
						<?php do_settings_sections( 'e20r-roles-settings' ); ?>
                        <p class="submit">
                            <input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>"/>
                        </p>
                    </form>

                </div>
            </div>
			<?php
		}
		
		/**
		 * Generates the PMPro Membership Level Settings section
		 */
		public function level_settings_page() {
			
			$active_addons = $this->get_active_addons();
			$level_id      = isset( $_REQUEST['edit'] ) ? intval( $_REQUEST['edit'] ) : ( isset( $_REQUEST['copy'] ) ? intval( $_REQUEST['copy'] ) : null );
			?>
            <div class="e20r-roles-for-pmpro-level-settings">
                <h3 class="topborder"><?php _e( 'Roles for Paid Memberships Pro (by Eighty/20 Results)', self::plugin_slug ); ?></h3>
                <hr style="width: 90%; border-bottom: 2px solid #c5c5c5;"/>
                <h4 class="e20r-roles-for-pmpro-section"><?php _e( 'Default user role settings', E20R_Roles_For_PMPro::plugin_slug ); ?></h4>
				<?php do_action( 'e20r_roles_level_settings', $level_id, $active_addons ); ?>
            </div>
			<?php
		}
		
		/**
		 * Global save_level_settings function (calls add-on specific save code)
		 *
		 * @param $level_id
		 */
		public function save_level_settings( $level_id ) {
			
			$active_addons = $this->get_active_addons();
			
			do_action( 'e20r_roles_level_settings_save', $level_id, $active_addons );
		}
		
		/**
		 * Global delete membership level function (calls add-on specific save code)
		 *
		 * @param int $level_id
		 */
		public function delete_level_settings( $level_id ) {
			
			$active_addons = $this->get_active_addons();
			
			do_action( 'e20r_roles_level_settings_delete', $level_id, $active_addons );
		}
		
		/**
		 * Returns an array of add-ons that are active for this plugin
		 *
		 * @return array
		 */
		private function get_active_addons() {
			
			global $e20r_roles_addons;
			
			if ( null === ( $active = Cache::get( 'active_addons', E20R_Roles_For_PMPro::cache_group ) ) ) {
				
				$active = array();
				
				foreach ( $e20r_roles_addons as $addon => $settings ) {
					
					if ( true == $settings['status'] ) {
						$active[] = $addon;
					}
				}
				
				Cache::set( 'active_addons', $active, ( 10 * MINUTE_IN_SECONDS ), E20R_Roles_For_PMPro::cache_group );
			}
			
			return $active;
		}
		
		/**
		 * Default response to unprivileged AJAX calls
		 *
		 * @access public
		 * @since  1.0
		 */
		public function unprivileged_error() {
			
			wp_send_json_error( array( 'error_message' => __( 'Error: You do not have access to this feature', self::plugin_slug ) ) );
			wp_die();
		}
		
		/**
		 * Load Admin page JavaScript
		 *
		 * @param $hook
		 *
		 * @since  1.0
		 * @access public
		 */
		public function admin_register_scripts( $hook ) {
			
			if ( 'toplevel_page_pmpro-membershiplevels' != $hook ) {
				return;
			}
			
			wp_enqueue_style( self::plugin_slug . '-admin', plugins_url( 'css/e20r-roles-for-pmpro-admin.css', __FILE__ ) );
			wp_register_script( self::plugin_slug . '-admin', plugins_url( 'javascript/e20r-roles-for-pmpro-admin.js', __FILE__ ) );
			
			$vars = array(
				'desc'       => __( 'Levels not matching up, or missing?', E20R_Roles_For_PMPro::plugin_slug ),
				'repair'     => __( 'Repair', E20R_Roles_For_PMPro::plugin_slug ),
				'working'    => __( 'Working...', E20R_Roles_For_PMPro::plugin_slug ),
				'done'       => __( 'Done!', E20R_Roles_For_PMPro::plugin_slug ),
				'fixed'      => __( ' role connections were needed/repaired.', E20R_Roles_For_PMPro::plugin_slug ),
				'failed'     => __( 'An error occurred while repairing roles.', E20R_Roles_For_PMPro::plugin_slug ),
				'ajaxaction' => E20R_Roles_For_PMPro::ajax_fix_action,
				'ajaxurl'    => admin_url( 'admin-ajax.php' ),
				'timeout'    => intval( apply_filters( 'e20r_roles_for_pmpro_ajax_timeout_secs', 10 ) * 1000 ),
				'nonce'      => wp_create_nonce( E20R_Roles_For_PMPro::ajax_fix_action ),
			);
			
			$key = self::plugin_prefix . 'vars';
			
			wp_localize_script( self::plugin_slug . '-admin', $key, $vars );
		}
		
		/**
		 * Class auto-loader for this plugin
		 *
		 * @param string $class_name Name of the class to auto-load
		 *
		 * @since  1.0
		 * @access public
		 */
		public static function auto_loader( $class_name ) {
			
			if ( false === strpos( $class_name, 'E20R' ) ) {
				return;
			}
			
			$parts = explode( '\\', $class_name );
			$name  = strtolower( preg_replace( '/_/', '-', $parts[ ( count( $parts ) - 1 ) ] ) );
			
			$base_path = plugin_dir_path( __FILE__ ) . 'class';
			$filename  = "class.{$name}.php";
			
			if ( file_exists( "{$base_path}/{$filename}" ) ) {
				require_once( "{$base_path}/{$filename}" );
			}
			
			if ( file_exists( "{$base_path}/add-on/{$filename}" ) ) {
				require_once( "{$base_path}/add-on/{$filename}" );
			}
		}
		
		/**
		 * Delayed enqueue of wp-admin JavasScript (allow unhook)
		 *
		 * @param $hook
		 *
		 * @since  1.0
		 * @access public
		 */
		public function admin_enqueue_scripts( $hook ) {
			
			if ( 'toplevel_page_pmpro-membershiplevels' != $hook ) {
				return;
			}
			
			wp_enqueue_script( self::plugin_slug . '-admin' );
		}
		
		/**
		 * Load language/translation file(s)
		 */
		public function load_translation() {
			
			$locale = apply_filters( "plugin_locale", get_locale(), E20R_Roles_For_PMPro::plugin_slug );
			$mo     = E20R_Roles_For_PMPro::plugin_slug . "-{$locale}.mo";

// Paths to local (plugin) and global (WP) language files
			$local_mo  = plugin_dir_path( __FILE__ ) . "/languages/{$mo}";
			$global_mo = WP_LANG_DIR . "/" . E20R_Roles_For_PMPro::plugin_slug . "/{$mo}";

// Load global version first
			load_textdomain( E20R_Roles_For_PMPro::plugin_slug, $global_mo );

// Load local version second
			load_textdomain( E20R_Roles_For_PMPro::plugin_slug, $local_mo );
			
		}
	}
}

/**
 * Register the auto-loader and the activation/deactiviation hooks.
 */
spl_autoload_register( 'E20R\\Roles_For_PMPro\\E20R_Roles_For_PMPro::auto_loader' );

register_activation_hook( __FILE__, array( Role_Definitions::get_instance(), 'activate' ) );
register_deactivation_hook( __FILE__, array( Role_Definitions::get_instance(), 'deactivate' ) );

// Load this plugin
add_action( 'plugins_loaded', array( E20R_Roles_For_PMPro::get_instance(), 'plugins_loaded' ), 10 );

// One-click update handler
if ( ! class_exists( '\\Puc_v4_Factory' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'plugin-updates/plugin-update-checker.php' );
}

$plugin_updates = \Puc_v4_Factory::buildUpdateChecker(
	'https://eighty20results.com/protected-content/e20r-roles-for-pmpro/metadata.json',
	__FILE__,
	'e20r-roles-for-pmpro'
);
