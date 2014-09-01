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

}

class MethodNotImplementedException extends \Exception {}
