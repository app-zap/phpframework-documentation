<?php
namespace AppZap\PHPFramework\Authentication;

use AppZap\PHPFramework\StaticConfiguration as Configuration;

abstract class AuthenticationService {

  /**
   * @var \BaseSessionInterface
   */
  protected $session;

  /**
   *
   */
  public function __construct() {
    try {
      $session_class = Configuration::get('session.class');
      if(class_exists($session_class, true)) {
        $this->session = new $session_class(Configuration::getConfigurationObject());
        if(!($this->session instanceof \BaseSessionInterface)) {
          $this->session = null;
          throw new \BaseSessionException($session_class . ' is not a instance of BaseSessionInterface');
        }
      } else {
        throw new \BaseSessionException('Session class ' . $session_class . ' not found');
      }
    } catch (\BaseSessionException $e) {}
  }

}