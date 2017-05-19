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

namespace E20R\Utilities;

// use E20R\Utilities\Utilities as Utilities;
use E20R\Roles_For_PMPro\E20R_Roles_For_PMPro as Roles_For_PMPro;

// Disallow direct access to the class definition
if ( ! defined( 'ABSPATH' ) ) {
	wp_die( __( "Cannot access file directly", Roles_For_PMPro::plugin_slug ) );
}

if ( ! class_exists( 'E20R\\Utilities\\Utilities' ) ) {
	
	class Utilities {
		
		/**
		 * @var null|Utilities
		 */
		private static $instance = null;
		
		/**
		 * Array of error messages
		 *
		 * @var array $msg
		 */
		private $msg = array();
		
		/**
		 * Array of message types (notice, warning, error)
		 *
		 * @var array $msgt
		 */
		private $msgt = array();
		
		/**
		 * Array of message sources (unlimited)
		 * @var array $msg_source
		 */
		private $msg_source = array();
		
		private $blog_id = null;
		
		/**
		 * Utilities constructor.
		 */
		private function __construct() {
			
			$this->blog_id = get_current_blog_id();
			
			if ( null !== ( $tmp = Cache::get( "err_info", "e20r_utils_{$this->blog_id}" ) ) ) {
				
				$this->msg        = is_array( $tmp['msg'] ) ? $tmp['msg'] : array( $tmp['msg'] );
				$this->msgt       = is_array( $tmp['msgt'] ) ? $tmp['msgt'] : array( $tmp['msgt'] );
				$this->msg_source = is_array( $tmp['msg_source'] ) ? $tmp['msg_source'] : array( $tmp['msg_source'] );
			}
			
			if ( ! has_action( 'admin_notices', array( $this, 'display_messages' ) ) ) {
				add_action( 'admin_notices', array( $this, 'display_messages' ), 10 );
			}
		}
		
		/**
		 * Add error message to the list of messages to display on the back-end
		 *
		 * @param string $message    The message to save/add
		 * @param string $type       The type of message (notice, warning, error)
		 * @param string $msg_source The source of the error message
		 *
		 * @return bool
		 */
		public function add_message( $message, $type = 'notice', $msg_source = 'default' ) {
			
			// Grab from the cache (if it exists)
			if ( null !== ( $tmp = Cache::get( 'err_info', "e20r_utils_{$this->blog_id}" ) ) ) {
				
				$this->msg        = $tmp['msg'];
				$this->msgt       = $tmp['msgt'];
				$this->msg_source = $tmp['msg_source'];
			}
			
			if ( is_array( $this->msg ) && ! in_array( $message, $this->msg ) ) {
				
				$this->log( "Adding a message to the admin errors: {$message}" );
				
				// Save the new message
				$this->msg[]        = $message;
				$this->msgt[]       = $type;
				$this->msg_source[] = $msg_source;
				
				$values = array(
					'msg'        => $this->msg,
					'msgt'       => $this->msgt,
					'msg_source' => $this->msg_source,
				);
				
				Cache::set( 'err_info', $values, DAY_IN_SECONDS, "e20r_utils_{$this->blog_id}" );
			}
		}
		
		/**
		 * Display the error message as HTML when called
		 *
		 * @param string $source - The error source to show.
		 */
		public function display_messages( $source = 'default' ) {
			
			// Load any cached error message(s)
			if ( null !== ( $msgs = Cache::get( 'err_info', "e20r_utils_{$this->blog_id}" ) ) ) {
				
				$this->msg        = array_merge( $this->msg, $msgs['msg'] );
				$this->msgt       = array_merge( $this->msgt, $msgs['msgt'] );
				$this->msg_source = array_merge( $this->msg_source, $msgs['msg_source'] );
			}
			
			if ( ! empty( $this->msg ) && ! empty( $this->msgt ) ) {
				
				$this->log( "Have " . count( $this->msg ) . " admin message(s) to display" );
				
				foreach ( $this->msg as $key => $notice ) { ?>
                    <div class="notice notice-<?php esc_html_e( $this->msgt[ $key ] ); ?> is-dismissible <?php esc_html_e( $this->msg_source[ $key ] ); ?>">
                        <p><?php echo $notice; ?></p>
                    </div>
					<?php
				}
			}
			
			// Clear the error message list
			$this->msg        = array();
			$this->msgt       = array();
			$this->msg_source = array();
			
			Cache::delete( 'err_info', "e20r_utils_{$this->blog_id}" );
		}
		
		/**
		 * @return array|string
		 */
		private function _who_called_me() {
			
			$trace  = debug_backtrace();
			$caller = $trace[2];
			
			if ( isset( $caller['class'] ) ) {
				$trace = "{$caller['class']}::{$caller['function']}()";
			} else {
				$trace = "Called by {$caller['function']}()";
			}
			
			return $trace;
		}
		
		public function log( $msg ) {
			
			$tid = sprintf( "%08x", abs( crc32( $_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME'] ) ) );
			
			$from = $this->_who_called_me();
			
			if ( ! defined( "WP_DEBUG" ) ) {
				esc_attr_e( "[{$tid}] {$from}: {$msg}" );
			} else if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
				error_log( "[{$tid}] {$from} - {$msg}" );
			}
			
		}
		
		public function nc_replace( $search, $replacer, $input ) {
			
			return preg_replace_callback( "/\b{$search}\b/i", function ( $matches ) use ( $replacer ) {
				return ctype_lower($matches[0][0]) ? strtolower( $replacer ) : $replacer;
			}, $input );
		}
		
		
		/**
		 * Process REQUEST variable: Check for presence and sanitize it before returning value or default
		 *
		 * @param string     $name    Name of the variable to return
		 * @param null|mixed $default The default value to return if the REQUEST variable doesn't exist or is empty.
		 *
		 * @return bool|float|int|null|string  Sanitized value from the front-end.
		 */
		public function get_variable( $name, $default = null ) {
			
			return isset( $_REQUEST[ $name ] ) && ! empty( $_REQUEST[ $name ] ) ? $this->_sanitize( $_REQUEST[ $name ] ) : $default;
		}
		
		/**
		 * Sanitizes the passed field/value.
		 *
		 * @param array|int|null|string|\stdClass $field The value to sanitize
		 *
		 * @return array|int|string     Sanitized value
		 */
		public function _sanitize( $field ) {
			
			if ( ! is_numeric( $field ) ) {
				
				if ( is_array( $field ) ) {
					
					foreach ( $field as $key => $val ) {
						$field[ $key ] = $this->_sanitize( $val );
					}
				}
				
				if ( is_object( $field ) ) {
					
					foreach ( $field as $key => $val ) {
						$field->{$key} = $this->_sanitize( $val );
					}
				}
				
				if ( ( ! is_array( $field ) ) && ctype_alpha( $field ) ||
				     ( ( ! is_array( $field ) ) && strtotime( $field ) ) ||
				     ( ( ! is_array( $field ) ) && is_string( $field ) )
				) {
					
					if ( strtolower( $field ) == 'yes' ) {
						$field = true;
					} else if ( strtolower( $field ) == 'no' ) {
						$field = false;
					} else {
						$field = sanitize_text_field( $field );
					}
				}
				
				if ( is_array( $field ) ) {
					$field = array_map( 'sanitize_text_field', $field );
				}
				
			} else {
				
				if ( is_float( $field + 1 ) ) {
					
					$field = sanitize_text_field( $field );
				}
				
				if ( is_int( $field + 1 ) ) {
					
					$field = intval( $field );
				}
			}
			
			return $field;
		}
		
		/**
		 * Test whether the value is an integer
		 *
		 * @param string $val
		 *
		 * @return bool|int
		 */
		final static function is_integer( $val ) {
			if ( ! is_scalar( $val ) || is_bool( $val ) ) {
				return false;
			}
			
			if ( is_float( $val + 0 ) && ( $val + 0 ) > PHP_INT_MAX ) {
				return false;
			}
			
			return is_float( $val ) ? false : preg_match( '~^((?:\+|-)?[0-9]+)$~', $val );
		}
		
		/**
		 * Test if the value is a floating point number
		 *
		 * @param string $val
		 *
		 * @return bool
		 */
		final static function is_float( $val ) {
			if ( ! is_scalar( $val ) ) {
				return false;
			}
			
			return is_float( $val + 0 );
		}
		
		/**
		 * The current instance of the Utilities class
		 *
		 * @return Utilities|null
		 */
		public static function get_instance() {
			
			if ( is_null( self::$instance ) ) {
				self::$instance = new self;
			}
			
			return self::$instance;
		}
	}
}