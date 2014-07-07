<?php
namespace AppZap\PHPFramework;

/**
 * Trait Singleton
 *
 * This trait can be used to implement a singleton class. Singletons are classes which are only
 * instanciated once. This instance should be reused through the whole code cycle.
 *
 * Usage example:
 *
 * class MySingletonClass {
*  use Singleton;
 *  // here go the class members..
 * }
 *
 * $my_singleton_object = MySingletonClass::get_instance();
 *
 * using traits requires PHP 5.4 or higher
 */
trait Singleton {

  public static function get_instance() {
    static $_instance = NULL;
    $class = get_called_class();
    return $_instance ?: $_instance = new $class;
  }

  public function __clone() {
    trigger_error('Cloning ' . __CLASS__ . ' is not allowed.', E_USER_ERROR);
  }

  public function __wakeup() {
    trigger_error('Unserializing ' . __CLASS__ . ' is not allowed.', E_USER_ERROR);
  }

}