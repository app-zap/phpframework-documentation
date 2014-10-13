<?php
namespace AppZap\PHPFramework\Tests\Mvc;

use AppZap\PHPFramework\Mvc\BaseHttpHandler;
use AppZap\PHPFramework\Mvc\BaseHttpRequest;
use AppZap\PHPFramework\Mvc\View\AbstractView;

class TestResponse extends AbstractView {
  public function __construct() {
  }
}

class TestHandler extends BaseHttpHandler {

  /**
   * @return array
   */
  public function _get_implemented_methods() {
    return $this->get_implemented_methods();
  }

  public function get($params) {
  }

}

class BaseHttpHandlerTest extends \PHPUnit_Framework_TestCase {

  /**
   * @var TestHandler
   */
  protected $testHandler;

  public function setUp() {
    $this->testHandler = new TestHandler(new BaseHttpRequest('cli'), new TestResponse());
  }

  /**
   * @test
   */
  public function implementedMethods() {
    $implementedMethods = $this->testHandler->_get_implemented_methods();
    $this->assertTrue(is_array($implementedMethods));
    $this->assertTrue(in_array('get', $implementedMethods));
    $this->assertSame(1, count($implementedMethods));
  }

}