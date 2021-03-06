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

namespace E20R\Licensing;

use E20R\Utilities as Utilities;

if ( ! class_exists( 'E20R\\Licensing\\Licensing' ) ) {
	
	class Licensing {
		
		const CACHE_KEY = 'active_licenses';
		const CACHE_GROUP = 'e20r_licensing';
		const E20R_LICENSE_SECRET_KEY = '5687dc27b50520.33717427';
		const E20R_LICENSE_SERVER_URL = 'https://eighty20results.com';
		
		const E20R_LICENSE_MAX_DOMAINS = 0x10000;
		const E20R_LICENSE_REGISTERED = 0x20000;
		const E20R_LICENSE_ERROR = 0x01000;
		
		private static $instance = null;
		
		private static $text_domain;
		
		/**
		 * Set the text domain to use, dynamically
		 * Licensing constructor.
		 */
		private function __construct() {
			
			self::$text_domain = apply_filters( 'e20r-licensing-text-domain', self::$text_domain );
		}
		
		/**
		 * Is the specified product licensed for use/updates (check against cached value, if possible)
		 * The Ccache is configured to time out every 24 hours (or so)
		 *
		 * @param string $product_stub Name of the product/component to test the license for
		 * @param bool   $force        Whether to force the plugin to connect with the license server, regardless of cache value(s)
		 *
		 * @return bool
		 */
		public static function is_licensed( $product_stub = null, $force = false ) {
			
			$utils       = Utilities\Utilities::get_instance();
			$is_licensed = false;
			
			if ( empty( $product_stub ) ) {
				return false;
			}
			
			$utils->log( "Checking license for {$product_stub}" );
			
			if ( null === ( $license_settings = Utilities\Cache::get( self::CACHE_KEY, self::CACHE_GROUP ) ) || true === $force ) {
				
				$utils->log( "Invalid cache for " . self::CACHE_KEY );
				
				$is_licensed = self::get_license_status_from_server( $product_stub );
				
				// Get new/existing settings
				$license_settings = self::get_license_settings();
				$license_settings = $license_settings[ $product_stub ];
				
				// Update the local cache for the license
				Utilities\Cache::set( self::CACHE_KEY, $license_settings, DAY_IN_SECONDS, self::CACHE_GROUP );
			}
			
			$utils->log( "License status for {$product_stub}: {$license_settings['status']}" );
			
			return ( 'active' == $license_settings['status'] ) ? true : false;
		}
		
		/**
		 * Activate the license key on the remote server
		 *
		 * @param string $product
		 * @param array  $settings
		 *
		 * @return array
		 */
		public static function activate_license( $product, $settings ) {
			
			global $current_user;
			
			$state = null;
			$utils = Utilities\Utilities::get_instance();
			
			$utils->log( "Attempting to activate {$product} on remote server: " . print_r( $settings, true ) );
			
			if ( empty( $settings ) ) {
				
				$settings        = self::default_settings( $product );
				$settings['key'] = $product;
			}
			
			$api_params = array(
				'slm_action'        => 'slm_activate',
				'license_key'       => $settings['key'],
				'secret_key'        => self::E20R_LICENSE_SECRET_KEY,
				'registered_domain' => $_SERVER['SERVER_NAME'],
				'item_reference'    => urlencode( $product ),
				'first_name'        => $settings['first_name'],
				'last_name'         => $settings['last_name'],
				'email'             => $settings['email'],
			);
			
			$utils->log( "Transmitting...: " . print_r( $api_params, true ) );
			
			// Send query to the license manager server
			$decoded = self::send_to_license_server( $api_params );
			
			if ( isset( $decoded->result ) ) {
				
				$utils->log( "Decoded JSON and received a status... ({$decoded->result})" );
				
				switch ( $decoded->result ) {
					
					case 'success':
						$settings['status'] = 'active';
						$utils->log( "Added {$product} to license list" );
						$utils->log( "Activated {$product} on the remote server." );
						$utils->log( "Saving activated license information" );
						
						$state = true;
						break;
					
					case 'error':
						
						$msg = $decoded->message;
						
						if ( false !== stripos( $msg, 'maximum' ) ) {
							$state = self::E20R_LICENSE_MAX_DOMAINS;
						} else {
							$state = self::E20R_LICENSE_ERROR;
						}
						
						$settings['status'] = 'blocked';
						
						$utils->add_message( $decoded->message, $decoded->result );
						$utils->log( "{$decoded->message}" );
						
						// unset( $settings[ $product ] );
						break;
				}
				
				$settings['timestamp'] = current_time( 'timestamp' );
			}
			
			return array( 'status' => $state, 'settings' => $settings );
		}
		
		/**
		 * Connect to license server and check status for the current product/server
		 *
		 * @param string $product
		 *
		 * @return bool
		 */
		private static function get_license_status_from_server( $product ) {
			
			$utils = Utilities\Utilities::get_instance();
			
			$utils->log( "Connecting to license server to validate license for {$product}" );
			
			// Default value for the license (it's not active)
			$license_status = false;
			global $current_user;
			
			$settings     = self::get_license_settings( $product );
			$product_name = $settings['fulltext_name'];
			
			// Configure request for license check
			$api_params = array(
				'slm_action'  => 'slm_check',
				'secret_key'  => self::E20R_LICENSE_SECRET_KEY,
				'license_key' => $settings['key'],
				// 'registered_domain' => $_SERVER['SERVER_NAME']
			);
			
			$decoded = self::send_to_license_server( $api_params );
			
			// License not validated
			if ( ! isset( $decoded->result ) || 'success' != $decoded->result ) {
				
				$msg = sprintf( __( "Sorry, no valid license found for: %s", self::$text_domain ), $settings['fulltext_name'] );
				$utils->log( $msg );
				$utils->add_message( $msg, 'error' );
				
				return $license_status;
			}
			
			if ( is_array( $decoded->registered_domains ) ) {
				
				$utils->log( "Processing license data for (count: " . count( $decoded->registered_domains ) . " domains )" );
				
				foreach ( $decoded->registered_domains as $domain ) {
					
					if ( isset( $domain->registered_domain ) && $domain->registered_domain == $_SERVER['SERVER_NAME'] ) {
						
						if ( '0000-00-00' != $decoded->date_renewed ) {
							$settings['renewed'] = strtotime( $decoded->date_renewed, current_time( 'timestamp' ) );
						} else {
							$settings['renewed'] = current_time( 'timestamp' );
						}
						$settings['domain']        = $domain->registered_domain;
						$settings['fulltext_name'] = $product_name;
						$settings['expires']       = isset( $decoded->date_expiry ) ? strtotime( $decoded->date_expiry, current_time( 'timestamp' ) ) : null;
						$settings['status']        = $decoded->status;
						$settings['first_name']    = $current_user->user_firstname;
						$settings['last_name']     = $current_user->user_lastname;
						$settings['email']         = $decoded->email;
						$settings['timestamp']     = current_time( 'timestamp' );
						
						if ( false === self::update_license_settings( $product, $settings ) ) {
							$utils->add_message( sprintf( __( "Unable to save license settings for %s", self::$text_domain ), $product ), 'error' );
						}
						
						$license_status = ( 'active' === $settings['status'] ? true : false );
					}
				}
			} else {
				
				$utils->log( "The {$product} license is on the server, but not active for this domain" );
				$license_status = false;
			}
			
			if ( $settings['expires'] < current_time( 'timestamp' ) || 'active' !== $settings['status'] ) {
				
				$msg = sprintf(
					__( "Your update license has expired for the %s add-on!", self::$text_domain ),
					$settings['fulltext_name']
				);
				
				$utils->log( $msg );
				$utils->add_message( $msg, 'error' );
				$license_status = false;
			}
			
			return $license_status;
		}
		
		/**
		 * @param $product
		 *
		 * @return array
		 */
		private static function get_license_settings( $product = 'e20r_default_license' ) {
			
			$settings = get_option( 'e20r_license_settings', self::default_settings( $product ) );
			
			if ( 'e20r_default_license' == $product || empty( $product ) ) {
				
				error_log( "Returning ALL license settings" );
				
				return $settings;
			}
			
			return isset( $settings[ $product ] ) ? $settings[ $product ] : null;
		}
		
		/**
		 * Save the license settings
		 *
		 * @param string $product
		 * @param array  $settings
		 *
		 * @return bool
		 */
		private static function update_license_settings( $product = 'e20r_default_license', $settings ) {
			
			$utils            = Utilities\Utilities::get_instance();
			$license_settings = self::get_license_settings();
			
			if ( ! empty( $settings ) && 'e20r_default_license' !== $product && ! empty( $product ) ) {
				
				$license_settings[ $product ] = $settings;
				$utils->log( "Saving license settings for {$product}" );
				
			} else if ( empty( $settings ) && ( 'e20r_default_license' !== $product && ! empty( $product ) ) ) {
				
				$utils->log( "Removing license settings for {$product}" );
				unset( $license_settings[ $product ] );
				
			} else {
				
				return false;
			}
			
			// $utils->log( "Saving license settings: " . print_r( $license_settings, true ) );
			update_option( 'e20r_license_settings', $license_settings, true );
		}
		
		public static function deactivate_license( $product ) {
			
			$utils    = Utilities\Utilities::get_instance();
			$settings = self::get_license_settings( $product );
			
			$utils->log( "Attempting to deactivate {$product} on remote server" );
			
			$api_params = array(
				'slm_action'        => 'slm_deactivate',
				'license_key'       => $settings['key'],
				'secret_key'        => self::E20R_LICENSE_SECRET_KEY,
				'registered_domain' => $_SERVER['SERVER_NAME'],
				'status'            => 'pending',
			);
			
			$decoded = self::send_to_license_server( $api_params );
			
			if ( false === $decoded ) {
				return $decoded;
			}
			
			if ( 'success' !== $decoded->result ) {
				return false;
			}
			
			$utils->log( "Removing license {$product}..." );
			self::update_license_settings( $product, null );
			
			return true;
			
		}
		
		/**
		 * Settings array for the License(s) on this system
		 *
		 * @param string $product
		 *
		 * @return array
		 */
		private static function default_settings( $product = 'e20r_default_license' ) {
			
			return array(
				$product => array(
					'key'           => null,
					'renewed'       => null,
					'domain'        => $_SERVER['SERVER_NAME'],
					'product'       => $product,
					'fulltext_name' => '',
					'expires'       => '',
					'status'        => '',
					'first_name'    => '',
					'last_name'     => '',
					'email'         => '',
					'timestamp'     => current_time( 'timestamp' ),
				),
			);
		}
		
		/**
		 * Transmit Request to the Licensing server
		 *
		 * @param array $api_params
		 *
		 * @return bool
		 */
		private static function send_to_license_server( $api_params ) {
			
			$utils = Utilities\Utilities::get_instance();
			
			// Send query to the license manager server
			$response = wp_remote_get(
				add_query_arg( $api_params, self::E20R_LICENSE_SERVER_URL ),
				array(
					'timeout'     => apply_filters( 'e20r-license-remote-server-timeout', 30 ),
					'sslverify'   => true,
					'httpversion' => '1.1',
					'decompress'  => true,
				)
			);
			
			// Check for error in the response
			if ( is_wp_error( $response ) ) {
				
				$msg = sprintf( __( "E20R Licensing: %s", self::$text_domain ), $response->get_error_message() );
				
				$utils->log( $msg );
				$utils->add_message( $msg, 'error' );
				
				return false;
			}
			
			$license_data = stripslashes( wp_remote_retrieve_body( $response ) );
			
			$bom          = pack( 'H*', 'EFBBBF' );
			$license_data = preg_replace( "/^$bom/", '', $license_data );
			$decoded      = json_decode( $license_data );
			
			if ( null === $decoded && json_last_error() !== JSON_ERROR_NONE ) {
				
				switch ( json_last_error() ) {
					case JSON_ERROR_DEPTH:
						$error = __( 'Maximum stack depth exceeded', self::$text_domain );
						break;
					case JSON_ERROR_STATE_MISMATCH:
						$error = __( 'Underflow or the modes mismatch', self::$text_domain );
						break;
					case JSON_ERROR_CTRL_CHAR:
						$error = __( 'Unexpected control character found', self::$text_domain );
						break;
					case JSON_ERROR_SYNTAX:
						$error = __( 'Syntax error, malformed JSON', self::$text_domain );
						break;
					case JSON_ERROR_UTF8:
						$error = __( 'Malformed UTF-8 characters, possibly incorrectly encoded', self::$text_domain );
						break;
					default:
						$error = sprintf( __( "No error, supposedly? %s", self::$text_domain ), print_r( json_last_error(), true ) );
				}
				
				$utils->log( "Response from remote server: <" . $license_data . ">" );
				$utils->log( "JSON decode error: " . $error );
				
				return false;
			}
			
			$utils->log( "License data received: (" . print_r( $decoded, true ) . ")" );
			
			if ( $decoded->result === 'success' ) {
				return $decoded;
			} else {
				return false;
			}
		}
		
		/**
		 * Add the options section for the Licensing Options page
		 */
		public static function add_options_page() {
			
			add_options_page(
				__( "E20R Licensing", self::$text_domain ),
				__( "E20R Licensing", self::$text_domain ),
				'manage_options',
				'e20r-licensing',
				'E20R\\Licensing\\Licensing::licensing_page'
			);
		}
		
		/**
		 * Register all Licensing settings
		 */
		static public function register_settings() {
			
			$utils = Utilities\Utilities::get_instance();
			
			register_setting(
				"e20r_license_settings", // group, used for settings_fields()
				"e20r_license_settings",  // option name, used as key in database
				'E20R\\Licensing\\Licensing::validate_settings'     // validation callback
			);
			
			add_settings_section(
				'e20r_licensing_section',
				__( "Configure Licenses", self::$text_domain ),
				'E20R\\Licensing\\Licensing::show_licensing_section',
				'e20r-licensing'
			);
			
			$settings     = self::get_license_settings();
			$license_list = array_keys( $settings );
			
			// $utils->log( "License list: " . print_r( $license_list, true ) );
			
			$settings = apply_filters( 'e20r-license-add-new-licenses', $settings );
			
			$utils->log( "Found " . count( $settings ) . " licenses" );
			
			foreach ( $settings as $k => $license ) {
				
				
				// Skip and clean up.
				if ( isset( $license['key'] ) && empty( $license['key'] ) ) {
					
					$utils->log( "Not processing: {$k}" );
					
					unset( $settings[ $k ] );
					update_option( 'e20r_license_settings', $settings, 'yes' );
					continue;
				}
				
				$utils->log( "Generate settings fields for {$k}" );
				
				// Clean up
				
				if ( $k !== 'new_licenses' && isset( $license['key'] ) && $license['key'] != 'e20r_default_license' && ! empty( $license['key'] ) ) {
					
					$utils->log( "Already activated license: {$k}" );
					
					add_settings_field(
						"{$license['key']}",
						"{$license['fulltext_name']} (" . ucfirst( $license['status'] ) . ")",
						'E20R\\Licensing\\Licensing::show_input',
						'e20r-licensing',
						'e20r_licensing_section',
						array(
							'label_for'     => $license['key'],
							'product'       => $k,
							'option_name'   => "e20r_license_settings",
							'fulltext_name' => $license['fulltext_name'],
							'name'          => 'license_key',
							'input_type'    => 'password',
							'value'         => $license['key'],
							'email_field'   => "license_email",
							'email_value'   => ! empty( $license['email'] ) ? $license['email'] : null,
							'placeholder'   => __( "Paste the purchased key here", self::$text_domain ),
						)
					);
					
				}
				
				if ( 'new_licenses' === $k ) {
					
					$utils->log( "Processing: {$k}" );
					
					$new_licenses = $license;
					
					foreach ( $new_licenses as $nk => $new ) {
						
						$utils->log( "Processing new license field # {$nk} : {$new['new_product']}" );
						
						// Skip if we've got this one in the list of licenses already.
						
						if ( ! in_array( $new['new_product'], $license_list ) ) {
							
							$utils->log( "Including new license field for {$new['new_product']}" );
							
							add_settings_field(
								"e20r_license_new_{$nk}",
								sprintf( __( "Add %s license", self::$text_domain ), $new['fulltext_name'] ),
								'E20R\\Licensing\\Licensing::show_input',
								'e20r-licensing',
								'e20r_licensing_section',
								array(
									'label_for'     => $new['new_product'],
									'fulltext_name' => $new['fulltext_name'],
									'option_name'   => "e20r_license_settings",
									'new_product'   => $new['new_product'],
									'name'          => "new_license",
									'input_type'    => 'text',
									'value'         => null,
									'email_field'   => "new_email",
									'email_value'   => null,
									'placeholder'   => $new['placeholder'],
								)
							);
						}
					}
				}
			}
		}
		
		public static function show_licensing_section() {
			
			$pricing_page = apply_filters( 'e20r-license-pricing-page-url', 'https://eighty20results.com/account/service-plan-levels/' );
			?>
            <p class="e20r-licensing-section"><?php _e( "This add-on is distributed under version 2 of the GNU Public License (GPLv2). One of the things the GPLv2 license grants is the right to use this software on your site, free of charge.", self::$text_domain ); ?></p>
            <p class="e20r-licensing-section">
                <a href="<?php echo esc_url_raw( $pricing_page ); ?>"
                   target="_blank"><?php _e( "Purchase Licenses/Add-ons &raquo;", self::$text_domain ); ?></a>
            </p>
            <table class="form-table">
                <tr>
                    <th><?php _e( "Name", self::$text_domain ); ?></th>
                    <th><?php _e( "Key", self::$text_domain ); ?></th>
                    <th><?php _e( "Email", self::$text_domain ); ?></th>
                    <th><?php _e( "Deactivate", self::$text_domain ); ?></th>
                </tr>
            </table>
			<?php
		}
		
		public static function show_input( $args ) {
			
			global $current_user;
			
			printf( '<input type="hidden" name="%1$s" value="%2$s" />', "{$args['option_name']}[fieldname][]", $args['value'] );
			printf( '<input type="hidden" name="%1$s" value="%2$s" />', "{$args['option_name']}[fulltext_name][]", $args['fulltext_name'] );
			if ( isset( $args['product'] ) ) {
				
				$product  = $args['product'];
				$var_name = "{$args['option_name']}[product][]";
				
			} else if ( isset( $args['new_product'] ) ) {
				
				$product             = $args['new_product'];
				$var_name            = "{$args['option_name']}[new_product][]";
				$args['email_value'] = $current_user->user_email;
			}
			
			printf( '<input type="hidden" name="%1$s" value="%2$s" />', $var_name, $product );
			printf(
				'<input name="%1$s[%2$s][]" type="%3$s" id="%4$s" value="%5$s" placeholder="%6$s" class="regular_text">',
				$args['option_name'],
				$args['name'],
				$args['input_type'],
				$args['label_for'],
				$args['value'],
				$args['placeholder']
			); ?>
            </td>
            <td>
				<?php
				printf(
					'<input name="%1$s[%2$s][]" type="email" id=%3$s_email value="%4$s" placeholder="%5$s" class="email_address" style="width: 200px;">',
					$args['option_name'],
					$args['email_field'],
					$args['label_for'],
					$args['email_value'],
					__( "Email used to buy license", "e20rlicense" )
				); ?>
            </td>
            <td>
			<?php if ( $args['name'] != 'new_key' ) { ?>
				<?php
				printf(
					'<input type="checkbox" name="%1$s[delete][]" class="clear_license" style="float: left;" value="%2$s">',
					$args['option_name'],
					$args['value']
				);
			} ?>
            </td><?php
		}
		
		/**
		 * The page content for the E20R Licensing section
		 */
		public static function licensing_page() {
			
			if ( ! function_exists( "current_user_can" ) || ( ! current_user_can( "manage_options" ) && ! current_user_can( "e20r_license_admin" ) ) ) {
				wp_die( __( "You are not permitted to perform this action.", self::$text_domain ) );
			}
			
			$utils = Utilities\Utilities::get_instance();
			?>
			<?php $utils->display_messages(); ?>
            <br/>
            <h2><?php echo $GLOBALS['title']; ?></h2>
            <form action="options.php" method="POST">
				<?php
				settings_fields( "e20r_license_settings" );
				do_settings_sections( 'e20r-licensing' );
				submit_button();
				?>
            </form>
			<?php
			
			$settings            = self::get_license_settings();
			$support_account_url = apply_filters( 'e20r-license-support-account-url', 'https://eighty20results.com/login/?redirect_to=/accounts/' );
			
			foreach ( $settings as $prod => $license ) {
				
				if ( in_array( $prod, array( 'e20r_default_license', 'new_licenses' ) ) ) {
					continue;
				}
				
				$license_valid = self::is_licensed( $prod ) && ( isset( $license['status'] ) && 'active' === $license['status'] );
				
				?>

                <div class="wrap"><?php
					if ( false === $license_valid && ( isset( $license['expires'] ) && $license['expires'] <= current_time( 'timestamp' ) || empty( $license['expires'] ) ) ) {
						?>
                        <div class="notice notice-error inline">
                        <p>
                            <strong><?php printf( __( 'Your <em>%s</em> license is invalid or has expired.', self::$text_domain ), $license['fulltext_name'] ); ?></strong>
							<?php printf( __( 'Visit your Eighty / 20 Results <a href="%s" target="_blank">Support Account</a> page to confirm that your account is active and to locate your license key.', self::$text_domain ), $support_account_url ); ?>
                        </p>
                        </div><?php
					}
					
					if ( $license_valid ) {
						?>
                        <div class="notice notice-info inline">
                        <p>
                            <strong><?php _e( 'Thank you!', self::$text_domain ); ?></strong>
							<?php printf( __( "A valid %s license key is being used on this site.", self::$text_domain ), $license['fulltext_name'] ); ?>
                        </p>
                        </div><?php
						
					} ?>
                </div> <!-- end wrap -->
				<?php
			}
			
		}
		
		
		public static function validate_settings( $input ) {
			
			global $current_user;
			
			$utils = Utilities\Utilities::get_instance();
			$utils->log( "Validation input: " . print_r( $input, true ) );
			
			$licenses = self::get_license_settings();
			
			/*if ( ! empty( $input['fieldname'][0] ) && is_array( $input['fieldname'] ) ) {
				
				// Process all values received
				foreach ( $input['fieldname'] as $key => $name ) {
					
					if ( false === stripos( $name, 'new_license' ) && isset( $input['license_email'][ $key ] ) ) {
						$license_key   = $name;
						$license_email = $input['license_email'][ $key ];
					} else {
						$license_key   = $input['new_key'][0];
						$license_email = $input['new_email'][0];
					}
					
					$product = $input['product'][ $key ];
					
					$licenses[ $product ]['first_name'] = $current_user->first_name;
					$licenses[ $product ]['last_name']  = $current_user->last_name;
					$licenses[ $product ]['email']      = $license_email;
					$licenses[ $product ]['key']        = $license_key;
			        $licenses[ $product ]['fulltext_name'] = $input['fulltext_name'][$key];
					
					if ( ! empty( $license_email ) && ! empty( $license_key ) ) {
						
						$utils->log( "Processing {$license_key} with email {$license_email} for {$product}" );
						
						$result                            = self::activate_license( $product, $licenses[ $product ] );
						$licenses[ $product ]              = $results['settings'];
                    }
					
					if ( 'e20r_default_license' === $license_key && is_array( $licenses['e20r_default_license']['key'] ) ) {
						$utils->log( "Resetting the default key." );
						$licenses['e20r_default_license'] = self::default_settings();
					}
				}
				
			} else */
			if ( isset( $input['new_product'] ) && ! empty( $input['new_product'][0] ) ) {
				
				$utils->log( "Processing a new license activation" );
				
				foreach ( $input['new_product'] as $nk => $product ) {
					
					$license_key   = $input['new_license'][ $nk ];
					$license_email = $input['new_email'][ $nk ];
					$product       = $input['new_product'][ $nk ];
					
					$licenses[ $product ]['first_name']    = $current_user->first_name;
					$licenses[ $product ]['last_name']     = $current_user->last_name;
					$licenses[ $product ]['fulltext_name'] = $input['fulltext_name'][ $nk ];
					$licenses[ $product ]['email']         = $license_email;
					$licenses[ $product ]['key']           = $license_key;
					
					if ( ! empty( $license_email ) && ! empty( $license_key ) ) {
						
						$utils->log( "Attempting remote activation for {$product}: " . print_r( $licenses[ $product ], true ) );
						$result = self::activate_license( $product, $licenses[ $product ] );
						
						$licenses[ $product ] = $result['settings'];
					}
				}
				
			}
			
			$utils->log( "Returning after validation: " . print_r( $licenses, true ) );
			
			if ( isset( $input['delete'] ) && ! empty( $input['delete'][0] ) ) {
				
				foreach ( $input['delete'] as $dk => $l ) {
					
					$lk = array_search( $l, $input['license_key'] );
					
					$utils->log( "License to deactivate: {$input['product'][$lk]}" );
					$product = $input['product'][ $lk ];
					
					$result = self::deactivate_license( $product );
					
					if ( false !== $result ) {
						
						$utils->log( "Successfully deactivated {$input['product'][ $lk ]} on remote server" );
						
						unset( $input['license_key'][ $lk ] );
						unset( $input['license_email'][ $lk ] );
						unset( $input['fieldname'][ $lk ] );
						unset( $input['fulltext_name'][ $lk ] );
						unset( $licenses[ $product ] );
						unset( $input['product'][ $lk ] );
					}
				}
			} else {
				
				if ( empty( $licenses ) ) {
					$licenses = self::get_license_settings();
				}
				
				if ( isset( $product ) ) {
					self::update_license_settings( $product, $licenses[ $product ] );
				}
			}
			
			return $licenses;
		}
		
		/**
		 * Get the license page URL for the local admin/options page
		 *
		 * @param string $stub
		 *
		 * @return string
		 */
		public static function get_license_page_url( $stub ) {
			
			$license_page_url = add_query_arg(
				array(
					'page'         => 'e20r-licensing',
					'license_stub' => $stub,
				),
				admin_url( 'options-general.php' )
			);
			
			return $license_page_url;
		}
		
		public static function get_instance() {
			
			if ( null === self::$instance ) {
				self::$instance = new self;
			}
			
			return self::$instance;
		}
	}
	
}