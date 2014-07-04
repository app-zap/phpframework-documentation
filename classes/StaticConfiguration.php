<?php
namespace AppZap\PHPFramework;

/**
 * The configuration (settings.ini and settings_local.ini) is always available as $this->config in all Handler classes
 * of the PHPFramework.
 * To have easy access to the configuration in other classes this class can be used as a static wrapper for ConfigIni.
 */
class StaticConfiguration {

  /**
   * @var \ConfigIni
   */
  static protected $configuration;

  /**
   * @param string $config_file_path
   * @param string $local_override_config
   */
  static public function init($config_file_path, $local_override_config = null) {
    self::$configuration = new \ConfigIni($config_file_path, $local_override_config);
  }

  /**
   * @param $key
   * @param null $default_value
   * @return mixed
   * @throws \Exception
   */
  static public function get($key, $default_value = null) {
    if (!self::$configuration instanceof \ConfigIni) {
      throw new \Exception('Configuration is not available', 1395227253);
    }
    return self::$configuration->get($key, $default_value);
  }

  /**
   * @param $section
   * @return array
   * @throws \Exception
   */
  static public function getSection($section) {
    if (!self::$configuration instanceof \ConfigIni) {
      throw new \Exception('Configuration is not available', 1395227253);
    }
    return self::$configuration->getSection($section);
  }

  /**
   * @param string $key
   * @param mixed $value
   */
  static public function set($key, $value) {
    self::$configuration->set($key, $value);
  }

  /**
   * @return \ConfigIni
   */
  static public function getConfigurationObject() {
    return self::$configuration;
  }

}