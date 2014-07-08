<?php
namespace AppZap\PHPFramework;

use AppZap\PHPFramework\StaticConfiguration as Configuration;

/**
 * Main entrance class for the framework / application
 *
 * @author Knut Ahlers
 */
class Dispatcher {
  private $config = NULL;
  private $application_directory = NULL;

  /**
   * @param \ConfigIni $config
   * @param string $application_directory
   * @throws ApplicationPartMissingException
   */
  public function __construct($config = NULL, $application_directory = NULL) {
    if (is_null($config)) {
      $config = Configuration::getConfigurationObject();
    }
    if (is_null($application_directory)) {
      $application_directory = $config->get('application_directory');
    }
    if (is_null(\BaseExceptionVisualizer::get_display_template())) {
      \BaseExceptionVisualizer::set_display_template(dirname(__FILE__) . '/resources/exception_template.html');
    }
    $logging_conf = $config->getSection('logging');
    if ($logging_conf['phpframework_exception_visualizer']) {
      set_exception_handler('BaseExceptionVisualizer::render_exception');
    }

    $this->config = $config;
    $this->application_directory = realpath($application_directory);

    if (!is_dir($this->application_directory)) {
      throw new ApplicationPartMissingException('Application directory "' . $application_directory . '" does not exist.');
    }

    $routefile = rtrim($this->application_directory, '/') . '/routes.php';
    if (!file_exists($routefile)) {
      throw new ApplicationPartMissingException('Routes file "' . $routefile . '" does not exist.');
    }

    $template_dir = rtrim($this->application_directory, '/') . '/templates/';
    if (!is_dir($template_dir)) {
      throw new ApplicationPartMissingException('Template directory "' . $template_dir . '" does not exist.');
    }

    \BaseAutoLoader::register_app_path($application_directory);
  }

  public function dispatch($uri) {
    $routes = array();
    require_once(rtrim($this->application_directory, '/') . '/routes.php');

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

    // If the defined class does not match PHP class guidelines throw an exception
    if (!preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $responder_class)) {
      if (is_null($responder_class)) {
        $message = 'Routing failed. No matching responder class found for uri "' . $uri . '".';
      } else {
        $message = 'Responder class "' . $responder_class . '" does not match PHP class naming conventions';
      }
      throw new InvalidHttpResponderException($message);
    }

    // If the class does not exist throw an exception
    if (class_exists($responder_class, TRUE)) {
      if (php_sapi_name() == 'cli') {
        $method = 'cli';
      } else {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
      }

      $responder = new $responder_class(
          new \BaseHttpRequest($method)
          , new \BaseHttpResponse($this->config, rtrim($this->application_directory, '/') . '/templates/')
          , $this->config
      );

      if (method_exists($responder, $method)) {
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