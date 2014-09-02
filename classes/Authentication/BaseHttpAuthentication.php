<?php
namespace AppZap\PHPFramework\Authentication;

use AppZap\PHPFramework\Configuration\Configuration;
use AppZap\PHPFramework\Mvc\HttpStatus;

class BaseHttpAuthentication {

  /**
   * @var string
   */
  protected $name;

  /**
   * @var string
   */
  protected $password;

  public function __construct() {
    if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
      $this->name = $_SERVER['PHP_AUTH_USER'];
      $this->password = $_SERVER['PHP_AUTH_PW'];
    } elseif (isset($_ENV['HTTP_AUTHORIZATION'])) {
      if (preg_match('/^Basic\s+(.+)/i', $_ENV['HTTP_AUTHORIZATION'], $matches)) {
        $vals = explode(':', base64_decode($matches[1]), 2);
        $this->name = $vals[0];
        $this->password = $vals[1];
      }
    }
  }

  /**
   * @throws \Exception
   */
  public function check_authentication() {
    $http_authentication = Configuration::getSection('http_authentication');
    if (is_array($http_authentication) && !$this->is_authenticated()) {
      HttpStatus::set_status(HttpStatus::STATUS_401_UNAUTHORIZED);
      header('WWW-Authenticate: Basic realm="Login"');
      echo('Login required!');
      exit;
    }
  }

  /**
   * @return bool
   */
  protected function is_authenticated() {
    $http_authentication = Configuration::getSection('http_authentication');
    return
        $this->name !== NULL &&
        $this->password !== NULL &&
        array_key_exists($this->name, $http_authentication) &&
        sha1($this->password) === $http_authentication[$this->name];
  }

}