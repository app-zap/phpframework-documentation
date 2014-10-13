<?php
namespace AppZap\PHPFramework\Configuration;

class Configuration {

	/**
	 * @var array
	 */
	protected static $configuration = [];

	/**
	 * @param string $section
	 * @param string $key
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	public static function get($section, $key, $default_value = NULL) {
		if (isset(self::$configuration[$section]) && isset(self::$configuration[$section][$key])) {
			return self::$configuration[$section][$key];
		} else {
			return $default_value;
		}
	}

	/**
	 * @param $section
	 * @return array
	 */
	public static function getSection($section) {
		if (isset(self::$configuration[$section])) {
			return self::$configuration[$section];
		} else {
			return NULL;
		}
	}

	/**
	 * @param string $section
	 * @param string $key
	 * @param mixed $value
	 */
	public static function set($section, $key, $value = NULL) {
		if (!isset(self::$configuration[$section])) {
			self::$configuration[$section] = [];
		}
		self::$configuration[$section][$key] = $value;
	}

	/**
	 * @param string $section
	 * @param string $key
	 */
	public static function remove_key($section, $key) {
		if (isset(self::$configuration[$section]) && isset(self::$configuration[$section][$key])) {
			unset(self::$configuration[$section][$key]);
		}
	}

	/**
	 * @param string $section
	 */
	public static function remove_section($section) {
		if (isset(self::$configuration[$section])) {
			unset(self::$configuration[$section]);
		}
	}

	/**
	 *
	 */
	public static function reset() {
		self::$configuration = [];
	}

}