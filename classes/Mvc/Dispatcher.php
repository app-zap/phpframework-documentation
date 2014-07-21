<?php
namespace AppZap\PHPFramework\Mvc;

use AppZap\PHPFramework\Configuration\Configuration;
use AppZap\PHPFramework\Mvc\BaseHttpHandler;

/**
 * Main entrance class for the framework / application
 *
 * @author Knut Ahlers
 */
class Dispatcher {

  /**
   * @var string
   */
  protected $routefile;

  /**
   * @throws ApplicationPartMissingException
   */
  public function __construct() {
    $application_configuration = Configuration::getSection('application');

    if (!is_dir($application_configuration['application_directory'])) {
      throw new ApplicationPartMissingException('Application directory "' . $application_configuration['application_directory'] . '" does not exist.');
    }

    if (!is_readable($application_configuration['routes_file'])) {
      throw new ApplicationPartMissingException('Routes file "' . $application_configuration['routes_file'] . '" does not exist.');
    }

    if (!is_dir($application_configuration['templates_directory'])) {
      throw new ApplicationPartMissingException('Template directory "' . $application_configuration['templates_directory'] . '" does not exist.');
    }
  }

  public function dispatch($uri) {
    $routes = include(Configuration::get('application', 'routes_file'));

    $uri = preg_replace('/\?.*$/', '', $uri);

    $responder_class = NULL;
    $params = array();
    foreach ($routes as $regex => $class) {
      if (preg_match($regex, $uri, $matches)) {
        $responder_class = $class;
        for ($i = 1; $i < count($matches); $i++) {
          $params[] = $matches[$i];
        }
        break;
      }
    }

    // If the class does not exist throw an exception
    if (class_exists($responder_class, TRUE)) {
      if (php_sapi_name() == 'cli') {
        $method = 'cli';
      } else {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
      }

      /** @var BaseHttpHandler $responder */
      $responder = new $responder_class(new BaseHttpRequest($method), new BaseHttpResponse());

      if (method_exists($responder, $method)) {
        if (method_exists($responder, 'initialize')) {
          $responder->initialize($params);
        }
        $responder->$method($params);
      } else {
        throw new InvalidHttpResponderException('Method ' . $method . ' is not valid for ' . $responder_class);
      }
    } else {
      throw new InvalidHttpResponderException('Handler class ' . $responder_class . ' for uri ' . $uri . ' not found!');
    }
  }

}

class ApplicationPartMissingException extends \Exception {
}

class InvalidHttpResponderException extends \Exception {
}
