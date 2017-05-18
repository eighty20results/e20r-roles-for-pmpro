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
use Stripe\Card;

if ( ! class_exists( 'E20R\Roles_For_PMPro\Addon\Level_Capabilities' ) ) {
	
	class Level_Capabilities extends E20R_Roles_Addon {
		
		const CACHE_GROUP = 'level_capabilities';
		
		/**
		 * The name of this class
		 *
		 * @var string
		 */
		private $class_name;
		
		/**
		 * @var Level_Capabilities
		 */
		private static $instance;
		
		/**
		 * Name of the WordPress option key
		 *
		 * @var string $option_name
		 */
		private $option_name = 'e20r_ao_level_capabilities';
		
		public function set_stub_name( $name = null ) {
			
			$name = strtolower( $this->get_class_name() );
			
			return $name;
		}
		
		
		/**
		 *  Level_Capabilities constructor.
		 */
		protected function __construct() {
			
			parent::__construct();
			
			if ( is_null( self::$instance ) ) {
				self::$instance = $this;
			}
			$this->class_name = $this->maybe_extract_class_name( get_class( $this ) );
			$this->define_settings();
		}
		
		/**
		 * Get and return the product/add-on name
		 *
		 * @return string
		 */
		public function get_class_name() {
			
			if ( empty( $this->class_name ) ) {
				$this->class_name = $this->maybe_extract_class_name( get_class( self::$instance ) );
			}
			
			return $this->class_name;
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
			
			$utils->log( "Loading the {$class_name} class action(s) " );
			
			global $e20r_roles_addons;
			
			if ( is_null( $stub ) ) {
				$stub = $class_name;
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
			$utils = Utilities::get_instance();
			
			if ( ! isset( $settings['new_licenses'] ) ) {
				$settings['new_licenses'] = array();
				$utils->log("Init array of licenses entry");
			}
			
			$stub = strtolower( $this->get_class_name() );
			$utils->log("Have " . count( $settings['new_licenses'] ) . " new licenses to process already. Adding {$stub}... ");
			
			$settings['new_licenses'][ $stub ] = array(
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
			
			// TODO Check access?
			
			return $has_access;
		}
		
		/**
		 * Filter handler: Adds the configured level-specific capabilities to the Membership Level Role
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
			$stub  = strtolower( $this->get_class_name() );
			
			$utils->log( "Adding the Level Capabilities to the membership level capabilities? (Role: {$role_name})" );
			
			if ( false == $e20r_roles_addons[ $stub ]['is_active'] ) {
				return $capabilities;
			}
			
			$level_settings = $this->load_option( 'level_settings' );
			
			if ( isset( $level_settings[ $level_id ] ) ) {
				
				$preserve = array_diff_assoc( $level_settings[ $level_id ]['capabilities'], $capabilities );
				$utils->log( "Keeping the following capabilities: " . print_r( $preserve, true ) );
				$utils->log( "... for the existing level specific capabilities: " . print_r( $capabilities, true ) );
				
				$capabilities = $preserve + $level_settings[ $level_id ]['capabilities'];
			}
			
			$utils->log( "Returning the Level capabilities requested for {$level_id}/{$role_name}: " . print_r( $capabilities, true ) );
			
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
		 * TODO: Specify settings for this add-on
		 *
		 * @return array
		 *
		 * @access private
		 * @since  1.0
		 */
		private function load_defaults() {
			
			return array(
				'deactivation_reset' => false,
				'level_settings'     => array(
					- 1 => array(
						'capabilities'    => array(),
						'permission_name' => 'no_access',
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
		 *
		 * @return bool
		 */
		public function toggle_addon( $addon, $is_active = false ) {
			
			global $e20r_roles_addons;
			
			$self  = strtolower( $this->get_class_name() );
			$utils = Utilities::get_instance();
			
			if ( $self !== $addon ) {
				
				$utils->log( "Not running the expected {$e20r_roles_addons[$addon]['label']} add-on: {$addon} vs actual: {$self}" );
				
				return $is_active;
			}
			
			return parent::toggle_addon( $addon, $is_active );
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
			
			// TODO: Set the filter name to match the stub for this plugin.
			
			/**
			 * Toggle ourselves on/off, and handle any deactivation if needed.
			 */
			add_action( 'e20r_roles_addon_toggle_addon', array( self::get_instance(), 'toggle_addon' ), 10, 2 );
			add_action( 'e20r_roles_addon_deactivating_core', array( self::get_instance(), 'deactivate_addon' ), 10, 1 );
			
			/**
			 * Configuration actions & filters
			 */
			add_filter( 'e20r_roles_general_level_capabilities', array( self::get_instance(), 'add_capabilities_to_role' ), 10, 3 );
			add_filter( 'e20r-license-add-new-licenses', array( self::get_instance(), 'add_new_license_info' ), 10, 1 );
			add_filter( 'e20r_roles_addon_options_level_capabilities', array( self::get_instance(), 'register_settings', ), 10, 1 );
			
			$utils->log("Verify that the {$stub} add-on is configured as 'active' and licensed");
			
			if ( true === parent::is_enabled( $stub ) ) {
				
				$utils->log( "Loading other actions/filters for {$e20r_roles_addons[$stub]['label']}" );
				
				self::$instance->add_level_filters();
				
				/**
				 * Membership related settings for role(s) add-on
				 */
				add_action( 'e20r_roles_level_settings', array( self::get_instance(), 'load_level_settings' ), 10, 2 );
				add_action( 'e20r_roles_level_settings_save', array( self::get_instance(), 'save_level_settings' ), 10, 2 );
				add_action( 'e20r_roles_level_settings_delete', array( self::get_instance(), 'delete_level_settings' ), 10, 2 );
				
				add_action( 'e20r_roles_set_level_caps', array( self::get_instance(), 'configure_level_capabilities' ), 10, 2 );
			}
		}
		
		public function add_level_filters() {
			
			$utils          = Utilities::get_instance();
			$level_settings = $this->load_option( 'level_settings' );
			
			if ( empty( $level_settings ) ) {
			    $level_settings = array();
            }
            
			foreach ( $level_settings as $level_id => $settings ) {
				
				$utils->log( "Loading capability filter for level {$level_id}" );
				
				// "e20r_roles_level_{$level_id}_capabilities", $capabilities, $level_id, $role_name
				add_filter( "e20r_roles_level_{$level_id}_capabilities", array( $this, 'return_level_caps' ), 10, 3 );
			}
		}
		
		/**
		 * Update the level specific capabilities for the level role
		 *
		 * @filter e20r_roles_level_{$level_id}_capabilities
		 *
		 * @param array  $capabilities
		 * @param int    $level_id
		 * @param string $role_name
		 *
		 * @return array
		 */
		public function return_level_caps( $capabilities, $level_id, $role_name ) {
			
			$utils = Utilities::get_instance();
			
			$level_settings = $this->load_option( 'level_settings' );
			
			if ( $level_caps = $level_settings[ $level_id ]['capabilities'] ) {
				$capabilities = $capabilities + $level_caps;
			}
			
			$utils->log( "Returning updated capability list for level ID {$level_id}: " . count( $capabilities ) . " capabilities being added" );
			
			return $capabilities;
		}
		
		
		public function configure_level_capabilities( $level_id, $role_name ) {
			
			$role  = get_role( $role_name );
			$utils = Utilities::get_instance();
			
			if ( empty( $role ) && 0 !== $level_id ) {
				
				$level          = pmpro_getLevel( $level_id );
				$level_settings = $this->load_option( 'level_settings' );
				$capabilities   = $level_settings[ $level_id ]['capabilities'];
				
				if ( empty( $level ) ) {
					$utils->log( "Error: Roles for Level {$level_id} do not exists???" );
					
					return false;
				}
				
				$role_descr = "{$level->name} (Level)";
				
				return add_role( $role_name, $role_descr, $capabilities );
			}
			
			return false;
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
					'id'              => 'e20r_level_capabilities_role_global',
					'label'           => __( "E20R Roles: Level Capability Settings" ),
					'render_callback' => array( $this, 'render_settings_text' ),
					'fields'          => array(
						array(
							'id'              => 'deactivation_reset',
							'label'           => __( "Clean up on Deactivate", E20R_Roles_For_PMPro::plugin_slug ),
							'render_callback' => array( $this, 'render_cleanup' ),
						),
					),
				),
			);
			
			return $settings;
		}
		
		/**
		 * Checkbox for the role/capability cleanup option on the global settings page
		 */
		public function render_cleanup() {
			
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
			
			$utils = Utilities::get_instance();
            $utils->log( "Input for save in Level_Capabilities:: " . print_r( $input, true ) );
            
			$defaults = $this->load_defaults();
			
			foreach ( $defaults as $key => $value ) {
				
				if ( false !== stripos( 'level_settings', $key ) && isset( $input[ $key ] ) ) {
					
					foreach ( $input['level_settings'] as $level_id => $settings ) {
						
						$this->settings['level_settings'][ $level_id ]['capabilities'] = $this->select_capabilities( $settings['forum_permission'] );
					}
					
				} else if ( isset( $input[ $key ] ) ) {
					
					$this->settings[ $key ] = $input[ $key ];
				} else {
					$this->settings[ $key ] = $defaults[ $key ];
				}
				
			}
            
            $utils->log( "Level_Capabilities saving " . print_r( $this->settings, true ) );
			
			return $this->settings;
		}
		
		/**
		 * Informational text about the bbPress Role add-on settings
		 */
		public function render_settings_text() {
			?>
            <p class="e20r-example-global-settings-text">
				<?php _e( "Configure global settings for the E20R Roles: Example add-on", E20R_Roles_For_PMPro::plugin_slug ); ?>
            </p>
			<?php
		}
		
		/**
		 * Display the select option for the "Allow anybody to read forum posts" global setting (select)
		 */
		public function render_select() {
			
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
			
			$name = strtolower( $this->get_class_name() );
			
			if ( ! in_array( $name, $active_addons ) ) {
				if ( WP_DEBUG ) {
					error_log( "Configure Capabilities add-on is not active. Nothing to do!" );
				}
				
				return false;
			}
			
			if ( empty( $level_id ) ) {
				
				if ( WP_DEBUG ) {
					error_log( "Configure Capabilities:  No level ID specified!" );
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
			
			$stub  = strtolower( $this->get_class_name() );
			$utils = Utilities::get_instance();
			
			if ( ! in_array( $stub, $active_addons ) ) {
				
				$utils->log( "Level Capabilities add-on is not active. Nothing to do!" );
				
				return false;
			}
			
			if ( empty( $level_id ) ) {
				
				$utils->log( "Level Capabilities:  No level ID specified!" );
				
				return false;
			}
			
			$level_settings = $this->load_option( 'level_settings' );
			
			if ( ! isset( $level_settings[ $level_id ] ) ) {
				$level_settings[ $level_id ] = array(
					'capabilities' => array(),
				);
			}
			
			$desired      = $utils->get_variable( 'e20r_roles_capabilities', array() );
			$capabilities = array();
			
			foreach ( $desired as $cap_name ) {
				$capabilities[ $cap_name ] = true;
			}
			
			$utils->log( "Current capabilities for {$level_id}: " . print_r( $capabilities, true ) );
			
			if ( isset( $level_settings[ - 1 ] ) ) {
				unset( $level_settings[ - 1 ] );
			}
			
			$level_settings[ $level_id ]['capabilities'] = $capabilities;
			
			$this->settings['level_settings'] = $level_settings;
			
			$utils->log( "Saving settings: " . print_r( $this->settings, true ) );
			$this->save_settings();
		}
		
		/**
		 * Save the settings to the DB
		 */
		public function save_settings() {
			
			update_option( $this->option_name, $this->settings, true );
		}
		
		/**
		 * Adds the level specific Capability settings to the PMPro Membership Level settings page
		 *
		 * @access public
		 * @since  1.0
		 */
		public function load_level_settings( $level_id ) {
			
			$level_settings = $this->load_option( 'level_settings' );
			
			if ( ! isset( $level_settings[ $level_id ] ) ) {
				$level_settings[ $level_id ] = array(
					'capabilities' => array(),
				);
			}
			
			$selected_capabilities = empty( $level_settings[ $level_id ]['capabilities'] ) ? array() : $level_settings[ $level_id ]['capabilities'];
			$all_capabilities      = $this->get_defined_capabilities();
			ksort( $all_capabilities );
			
			$rows = floor( count( $all_capabilities ) / 3 )
			?>
            <h4><?php _e( 'Level Specific Capabilities', E20R_Roles_For_PMPro::plugin_slug ); ?></h4>
            <button class="e20r-show-table button-secondary"><?php _e( "Show Capability List", E20R_Roles_For_PMPro::plugin_slug ); ?></button>
            <table class="form-table e20r-hide-table">
                <thead>
                <tr>
                    <th colspan="3">
                        <small><?php printf( __( 'Active members with this membership level are given the following (checked) <a href="%s" target="_blank">capabilities</a> for this site...', E20R_Roles_For_PMPro::plugin_slug ), 'https://codex.wordpress.org/Roles_and_Capabilities' ); ?></small>
                    </th>
                </tr>
                </thead>
                <tbody class="e20r-roles-capability-selection">
                <tr class="e20r-roles-capability-settings">
                    <input type="hidden" name="e20r-roles-capability-capabilities_level"
                           value="<?php esc_attr_e( $level_id ) ?>"/>
                    <th scope="row" valign="top"><label
                                for="e20r-roles-capability-selected_capabilities_level"><?php _e( "Capability list", E20R_Roles_For_PMPro::plugin_prefix ); ?></label>
                        <br/>
                        <button class="e20r-clear-all button-secondary"><?php _e( "Clear all", E20R_Roles_For_PMPro::plugin_slug ); ?></button>
                    </th>
                    <td class="e20r-roles-capability-level-selection">
                        <table class="level_selection_table">
                            <tbody>
							<?php
							$col_counter = 1;
							
							foreach ( $all_capabilities as $cap ) {
								
								if ( 1 === $col_counter ) { ?>
                                    <tr class="e20r-roles-capability-row">
									<?php
								} ?>
                                <td class="e20r-roles-capability-checkbox">
                                    <input type="checkbox" name="e20r_roles_capabilities[]" class="e20r-role-checkbox"
                                           value="<?php esc_attr_e( $cap ); ?>" <?php echo $this->checked( $cap, $selected_capabilities ); ?> />
                                </td>
                                <td class="e20r-roles-capability-name"><?php esc_attr_e( $cap ); ?></td>
								<?php
								$col_counter ++;
								if ( 4 == $col_counter ) {
									
									// Reset column counter & add row termination
									$col_counter = 1; ?>
                                    </tr> <!-- End of row for 3 capabilities -->
								<?php }
							} ?>
                            </tr><!-- End of row in table -->
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>
			<?php
		}
		
		/**
		 * Set the selected capability as "checked" in the checkbox
		 *
		 * @param string $capability
		 * @param array  $selected_capabilities
		 *
		 * @return null|string
		 */
		private function checked( $capability, $selected_capabilities ) {
			
			return ( in_array( $capability, array_keys( $selected_capabilities ) ) ? 'checked="checked"' : null );
		}
		
		/**
		 * Get all defined capabilities for this environment
		 */
		private function get_defined_capabilities() {
			
			global $wp_roles;
			
			$utils = Utilities::get_instance();
			
			if ( WP_DEBUG ) {
				$utils->log( "Resetting capability cache as we're in DEBUG mode" );
				Cache::delete( 'e20r_all_capabilities', E20R_Roles_For_PMPro::cache_group );
			}
			
			if ( null === ( $capability_list = Cache::get( 'e20r_all_capabilities', E20R_Roles_For_PMPro::cache_group ) ) ) {
				
				$capability_list = array();
				
				foreach ( $wp_roles->role_objects as $role ) {
					
					if ( is_array( $role->capabilities ) ) {
						$capability_names = array_keys( $role->capabilities );
						$capability_list  = array_merge( $capability_list, $capability_names );
						$capability_list  = array_unique( $capability_list );
					}
				}
				
				if ( ! empty( $capability_list ) ) {
					Cache::set( 'e20r_all_capabilities', $capability_list, ( 5 * MINUTE_IN_SECONDS ), E20R_Roles_For_PMPro::cache_group );
				}
			}
			
			return $capability_list;
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
		 * Returns the capabilities for the specified membership level
		 *
		 * @param int $level_id
		 *
		 * @return array
		 *
		 * @since  1.0
		 * @access private
		 */
		private function select_capabilities( $level_id ) {
			
			$settings = $this->load_option( 'level_settings' );
			
			$capabilities = $settings[ $level_id ]['capabilities'];
			
			return $capabilities;
		}
		
		
		/**
		 * Fetch the properties for the Configure Capabilities add-on class
		 *
		 * @return Level_Capabilities
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
}

add_filter( "e20r_roles_addon_level_capabilities_name", array( Level_Capabilities::get_instance(), 'get_class_name', ) );

// Configure the add-on (global settings array)
global $e20r_roles_addons;
$stub = strtolower( apply_filters( "e20r_roles_addon_level_capabilities_name", null ) );

$e20r_roles_addons[ $stub ] = array(
	'class_name'            => 'Level_Capabilities',
	'is_active'             => ( get_option( "e20r_roles_{$stub}_enabled", false ) == 1 ? true : false ),
	'status'                => 'deactivated',
	'disabled'              => false,
	'label'                 => 'Level Capabilities',
	'admin_role'            => 'manage_options',
	'required_plugins_list' => array(
		'paid-memberships-pro/paid-memberships-pro.php' => array(
			'name' => 'Paid Memberships Pro',
			'url'  => 'https://wordpress.org/plugins/paid-memberships-pro/',
		),
	),
);