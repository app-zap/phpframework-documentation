<?php
namespace AppZap\PHPFramework\Cache;

use AppZap\PHPFramework\Configuration\Configuration;
use AppZap\PHPFramework\Mvc\ApplicationPartMissingException;
use Nette\Caching\Storages\DevNullStorage;
use Nette\Caching\Storages\FileJournal;
use Nette\Caching\Storages\FileStorage;

/**
 * This factory instanciates and configures a Nette Cache
 */
class CacheFactory {

  /**
   * @var Cache
   */
  protected static $cache;

  /**
   * @return Cache
   */
  public static function getCache() {
    if (!self::$cache instanceof Cache) {
      $cacheSettings = Configuration::getSection('cache');
      if ($cacheSettings['enable']) {
        if (isset($cacheSettings['cache_folder'])) {
          $cache_folder = $cacheSettings['cache_folder'];
        } else {
          $cache_folder = './cache';
        }
        $cache_folder_path = realpath($cache_folder);
        if (!is_dir($cache_folder_path)) {
          if (!mkdir($cache_folder)) {
            throw new ApplicationPartMissingException('Cache folder "' . $cache_folder . '" is missing and could not be created.');
          }
          $cache_folder_path = realpath($cache_folder);
        }
        $storage = new FileStorage($cache_folder_path, new FileJournal($cache_folder_path));
      } else {
        $storage = new DevNullStorage();
      }
      self::$cache = new Cache($storage, Configuration::get('application', 'application'));
    }
    return self::$cache;
  }

}