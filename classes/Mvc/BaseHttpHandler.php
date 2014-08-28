<?php
namespace AppZap\PHPFramework\Mvc;

use AppZap\PHPFramework\Authentication\BaseHttpAuthentication;

abstract class BaseHttpHandler {

  /**
   * @var BaseHttpRequest
   */
  protected $request = null;

  /**
   * @var BaseHttpResponse
   */
  protected $response = null;

  /**
   * @var bool
   */
  protected $require_http_authentication = FALSE;

  /**
   * @param BaseHttpRequest $request
   * @param BaseHttpResponse $response
   */
  public function __construct(BaseHttpRequest $request, BaseHttpResponse $response) {
    $this->request = $request;
    $this->response = $response;
  }

  /**
   * @param array $params
   */
  public function initialize($params) {
    if ($this->require_http_authentication) {
      $base_http_authentication = new BaseHttpAuthentication();
      $base_http_authentication->check_authentication();
    }
  }

  /**
   * Handler for GET requests
   *
   * @param array $params Selections from the url defined in urls.php are passed to this
   * @throws MethodNotImplementedException When not implemented in child class
   */
  public function get($params) { throw new MethodNotImplementedException('Method GET not implemented for ' . get_class($this)); }

  /**
   * Handler for HEAD requests
   *
   * @param array $params Selections from the url defined in urls.php are passed to this
   * @throws MethodNotImplementedException When not implemented in child class
   */
  public function head($params) { throw new MethodNotImplementedException('Method HEAD not implemented for ' . get_class($this)); }

  /**
   * Handler for POST requests
   *
   * @param array $params Selections from the url defined in urls.php are passed to this
   * @throws MethodNotImplementedException When not implemented in child class
   */
  public function post($params) { throw new MethodNotImplementedException('Method POST not implemented for ' . get_class($this)); }


  /**
   * Handler for CLI requests
   *
   * @param array $params Selections from the url defined in urls.php are passed to this
   * @throws MethodNotImplementedException When not implemented in child class
   */
  public function cli($params) { throw new MethodNotImplementedException('Method CLI not implemented for ' . get_class($this)); }
}

class MethodNotImplementedException extends \Exception {}
