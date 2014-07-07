<?php
namespace AppZap\PHPFramework\Domain\Model;

abstract class AbstractModel {

  /**
   * @var int
   */
  protected $id;

  /**
   * @return int
   */
  public function get_id() {
    return $this->id;
  }

  /**
   * @param int $id
   */
  public function set_id($id) {
    $this->id = $id;
  }

}