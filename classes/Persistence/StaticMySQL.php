<?php
namespace AppZap\PHPFramework\Persistence;

/**
 * MySQL database wrapper class
 */
class StaticMySQL {

  /**
   * @var MySQL
   */
  private static $instance;

  /**
   * @param array $config
   * @param string $connection_target
   * @return MySQL
   * @throws DBConnectionException
   */
  public static function getInstance($config = NULL, $connection_target = 'default') {
    if(!self::$instance) {
      self::$instance = new MySQL($config, $connection_target);
      self::$instance->connect();
    }
    return self::$instance;
  }
}
