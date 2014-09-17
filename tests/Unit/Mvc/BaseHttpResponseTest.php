<?php
namespace AppZap\PHPFramework\Tests\Unit\Mvc;

use AppZap\PHPFramework\Mvc\BaseHttpResponse;

class BaseHttpResponseTest extends \PHPUnit_Framework_TestCase {

  /**
   * @var BaseHttpResponse
   */
  protected $reponse;

  public function setUp() {
    $this->reponse = new BaseHttpResponse();
  }

  /**
   * @test
   */
  public function addOutputFilter() {
    $this->reponse->add_output_filter('foo', function(){});
    $this->assertTrue($this->reponse->has_output_filter('foo'));
    $this->assertFalse($this->reponse->has_output_filter('bar'));
  }

  /**
   * @test
   */
  public function addOutputFunction() {
    $this->reponse->add_output_function('foo', function(){});
    $this->assertTrue($this->reponse->has_output_function('foo'));
    $this->assertFalse($this->reponse->has_output_function('bar'));
  }

}