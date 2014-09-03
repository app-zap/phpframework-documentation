<?php
namespace AppZap\PHPFramework\Tests\Unit\Domain;

use AppZap\PHPFramework\Domain\Model\AbstractModel;

class MyModel extends AbstractModel {

  /**
   * @var string
   */
  protected $title;

  /**
   * @return string
   */
  public function get_title() {
    return $this->title;
  }

  /**
   * @param string $title
   */
  public function set_title($title) {
    $this->title = $title;
  }



}

class ModelTest extends \PHPUnit_Framework_TestCase {

  /**
   * @test
   */
  public function roundtrip_properties() {
    $model = new MyModel();
    $model->set_id(42);
    $model->set_title('My Model');
    $this->assertSame(42, $model->get_id());
    $this->assertSame('My Model', $model->get_title());
  }

}