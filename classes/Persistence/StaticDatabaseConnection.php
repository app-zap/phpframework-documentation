<?php
namespace AppZap\PHPFramework\Persistence;

class StaticDatabaseConnection {

  /**
   * @var DatabaseConnection
   */
  protected static $instance;

  /**
   * @return DatabaseConnection
   * @throws DBConnectionException
   */
  public static function getInstance() {
    if(!self::$instance) {
      self::$instance = new DatabaseConnection();
    }
    return self::$instance;
  }

  /**
   *
   */
  public static function reset() {
    self::$instance = NULL;
  }
}
