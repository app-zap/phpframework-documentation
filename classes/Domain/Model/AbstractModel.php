<?php
namespace AppZap\PHPFramework\Domain\Model;

use AppZap\PHPFramework\Property\PropertyMapper;

abstract class AbstractModel {

  /**
   * @var int
   */
  protected $id;

  /**
   * @var PropertyMapper
   */
  protected $propertyMapper;

  /**
   * @var array
   */
  protected $_relations = [];

  public function __construct() {
    $this->propertyMapper = new PropertyMapper();
  }

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
    $this->id = (int) $id;
  }

  /**
   * @return array
   */
  public function _get_mapping_relations() {
    return $this->_relations;
  }

}