<?php

namespace Nova\Core;

/**
 * Class Registry
 * @package Nova
 * @author "eddo81 <eduardo_jonnerstig@live.com>"
 */

class Registry {

  protected static $registry = [];

  public static function set(string $key, $value) {
    static::$registry[$key] = $value;
  }

  public static function get(string $key) {
  if(!self::key_exists($key)) {
    throw new \Exception(__("No {$key} is set in the registry!"));
  }
    return static::$registry[$key];
  }

  public static function key_exists(string $key) : bool {
    return array_key_exists($key, static::$registry);
  }
}