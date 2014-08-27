<?php
namespace AppZap\PHPFramework\Mvc;

use AppZap\PHPFramework\Cache\CacheFactory;
use AppZap\PHPFramework\Configuration\Configuration;

/**
 * Main entrance class for the framework / application
 *
 * @author Knut Ahlers
 */
class Dispatcher {

  /**
   * @var \Nette\Caching\Cache
   */
  protected $cache;

  /**
   * @var string
   */
  protected $routefile;

  /**
   * @throws ApplicationPartMissingException
   */
  public function __construct() {
    $this->cache = CacheFactory::getCache();
    $application_configuration = Configuration::getSection('application');

    if (!is_dir($application_configuration['application_directory'])) {
      throw new ApplicationPartMissingException('Application directory "' . $application_configuration['application_directory'] . '" does not exist.');
    }

    if (!is_dir($application_configuration['templates_directory'])) {
      throw new ApplicationPartMissingException('Template directory "' . $application_configuration['templates_directory'] . '" does not exist.');
    }
  }

  public function dispatch($uri) {

    $request_method = $this->determineRequestMethod();
    $cache_identifier = 'router_' . $uri . '_' . $request_method;
    $router = $this->cache->load($cache_identifier, function () use ($uri, $cache_identifier) {
      return new Router($uri);
    });
    $responder_class = $router->get_responder_class();
    $parameters = $router->get_parameters();

    $request = new BaseHttpRequest($request_method);
    $response = new BaseHttpResponse();

    $default_template_name = $this->determineDefaultTemplateName($responder_class);
    if ($default_template_name) {
      $response->set_template_name($default_template_name);
    }

    /** @var BaseHttpHandler $request_handler */
    $request_handler = new $responder_class($request, $response);

    if (!method_exists($request_handler, $request_method)) {
      throw new InvalidHttpResponderException('Method ' . $request_method . ' is not valid for ' . $responder_class);
    }

    $request_handler->initialize($parameters);
    $output = $request_handler->$request_method($parameters);
    if (is_null($output)) {
      $output = $response->render();
    }
    echo $output;
  }

  /**
   * @return string
   */
  protected function determineRequestMethod() {
    if (php_sapi_name() == 'cli') {
      $method = 'cli';
      return $method;
    } else {
      $method = strtolower($_SERVER['REQUEST_METHOD']);
      return $method;
    }
  }

  /**
   * @param $responder_class
   */
  protected function determineDefaultTemplateName($responder_class) {
    if (preg_match('|\\\\([a-zA-Z0-9]{2,50})Handler$|', $responder_class, $matches)) {
      return $matches[1];
    }
    return NULL;
  }

}

class ApplicationPartMissingException extends \Exception {
}

class InvalidHttpResponderException extends \Exception {
}
