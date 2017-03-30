<?php

/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 3/22/17
 * Time: 2:25 PM
 */
namespace E20R\Utilities;

// Deny direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
	wp_die( __( "Cannot access file directly", 'e20r-roles-for-pmpro' ) );
}

if ( ! class_exists( 'E20R\\Utilities\\Cache_Object' ) ) {
	
	class Cache_Object {
		
		/**
		 * The Cache Key
		 * @var string
		 */
		private $key = null;
		
		/**
		 * The Cached value
		 * @var mixed
		 */
		private $value = null;
		
		/**
		 * Cache_Object constructor.
		 *
		 * @param string $key
		 * @param mixed  $value
		 */
		public function __construct( $key, $value ) {
			
			$this->key   = $key;
			$this->value = $value;
		}
		
		/**
		 * Setter for the key and value properties
		 *
		 * @param string $name
		 * @param mixed  $value
		 */
		public function __set( $name, $value ) {
			
			switch ( $name ) {
				case 'key':
				case 'value':
					$this->{$name} = $value;
					break;
			}
		}
		
		/**
		 * Getter for the key and value properties
		 *
		 * @param string $name
		 *
		 * @return mixed|null - Property value (for Key or Value property)
		 */
		public function __get( $name ) {
			
			$result = null;
			
			switch ( $name ) {
				case 'key':
				case 'value':
					
					$result = $this->{$name};
					break;
			}
			
			return $result;
		}
		
		public function __isset( $name ) {
			
			return isset( $this->{$name} );
		}
	}
}