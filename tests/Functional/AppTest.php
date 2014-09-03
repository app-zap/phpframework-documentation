<?php
namespace AppZap\PHPFramework\Tests\Functional;

use AppZap\PHPFramework\Bootstrap;

class AppTest extends \PHPUnit_Framework_TestCase {

  public function setUp() {
    $_ENV['project_root'] = dirname(__FILE__);
  }

  /**
   * @test
   */
  public function index() {
    Bootstrap::bootstrap('testapp');
  }

}