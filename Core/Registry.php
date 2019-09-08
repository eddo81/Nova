<?php

namespace Nova\Core;

/**
 * Class Registry.
 *
 * @package Nova
 * @author "eddo81 <eduardo_jonnerstig@live.com>"
 */
class Registry {
	/**
	 * Control variable to store the values of the registry.
	 *
	 * @var array
	 */
	protected static $registry = [];

	/**
	 * Stores a value in the registry.
	 *
	 * @param string $key The key to use for access of the stored value in the registry.
	 * @param string $value The value to store in the registry.
	 * @return void
	 */
	public static function set( string $key, $value ) : void {
		static::$registry[ $key ] = $value;
	}

	/**
	 * Returns a value that has been stored in the registry.
	 *
	 * @param string $key The key to use for access of the stored value in the registry.
	 * @throws \Exception Base Exception.
	 * @return mixed
	 */
	public static function get( string $key ) : mixed {
		if ( ! self::key_exists( $key ) ) {
			throw new \Exception( "No {$key} is set in the registry!" );
		}
		return static::$registry[ $key ];
	}

	/**
	 * Check if the registry contains a certain key.
	 *
	 * @param string $key The key to check.
	 * @return bool
	 */
	public static function key_exists( string $key ) : bool {
		return array_key_exists( $key, static::$registry );
	}
}
