<?php
namespace AppZap\PHPFramework\Configuration\Parser;

use AppZap\PHPFramework\Configuration\Configuration;

class IniParser {

  /**
   * @param string $application
   * @param string $config_file_path
   * @param string $overwrite_file_path
   */
  static public function init($application, $config_file_path = NULL, $overwrite_file_path = NULL) {
    $application_directory = realpath($_SERVER['HOME'] . '/' . $application) . '/';
    if (!is_dir($application_directory)) {
      throw new \Exception('Application folder ' . htmlspecialchars($application_directory) . ' not found');
    }
    if (is_null($config_file_path)) {
      $config_file_path = $application_directory . 'settings.ini';
    }
    if (is_null($overwrite_file_path)) {
      $overwrite_file_path = $application_directory . 'settings_local.ini';
    }
    Configuration::reset();
    Configuration::set('application', 'application', $application);
    Configuration::set('application', 'application_directory', $application_directory);
    Configuration::set('application', 'migration_directory', $application_directory . '_sql/');
    Configuration::set('application', 'routes_file', $application_directory . 'routes.php');
    Configuration::set('application', 'templates_directory', $application_directory . 'templates/');
    self::parse($config_file_path, $overwrite_file_path);
  }

  /**
   * @param string $config_file
   * @param string $overwrite_file
   */
  protected static function parse($config_file, $overwrite_file = NULL) {
    if (is_readable($config_file)) {
      self::parse_file($config_file);
    }
    if (is_readable($overwrite_file)) {
      self::parse_file($overwrite_file);
    }
  }

  /**
   * @param string $file
   */
  protected static function parse_file($file) {
    $config = parse_ini_file($file, TRUE);
    foreach ($config as $section => $sectionConfiguration) {
      foreach ($sectionConfiguration as $key => $value) {
        Configuration::set($section, $key, $value);
      }
    }
  }

}