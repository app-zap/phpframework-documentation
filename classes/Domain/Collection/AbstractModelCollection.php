<?php
namespace AppZap\PHPFramework\Domain\Collection;

use AppZap\PHPFramework\Domain\Model\AbstractModel;

abstract class AbstractModelCollection {

  /**
   * @var array
   */
  protected $items;

  /**
   * @param AbstractModel $model
   */
  public function set_item(AbstractModel $model) {
    $this->items[spl_object_hash($model)] = $model;
  }

  /**
   * @param AbstractModel $model
   */
  public function remove_item(AbstractModel $model) {
    unset($this->items[spl_object_hash($model)] );
  }

}