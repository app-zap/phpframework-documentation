<?php
namespace AppZap\PHPFramework\Persistence;

/**
 * MySQL database wrapper class
 */
class StaticMySQL {

  /**
   * @var MySQL
   */
  protected static $instance;

  /**
   * @return MySQL
   * @throws DBConnectionException
   */
  public static function getInstance() {
    if(!self::$instance) {
      self::$instance = new MySQL();
    }
    return self::$instance;
  }
}
