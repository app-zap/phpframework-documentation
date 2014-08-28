<?php
namespace AppZap\PHPFramework\Domain\Repository;

use AppZap\PHPFramework\Domain\Collection\AbstractModelCollection;
use AppZap\PHPFramework\Domain\Model\AbstractModel;
use AppZap\PHPFramework\Nomenclature;
use AppZap\PHPFramework\Persistence\MySQL;
use AppZap\PHPFramework\Persistence\StaticMySQL;
use AppZap\PHPFramework\Singleton;

abstract class AbstractDomainRepository {
use Singleton;

  /**
   * @var AbstractModelCollection
   */
  protected $known_items;

  /**
   * @var MySQL
   */
  protected $db;

  public function __construct() {
    $this->db = StaticMySQL::getInstance();
    $collection_classname = Nomenclature::repositoryclassname_to_collectionclassname(get_called_class());
    $this->known_items = new $collection_classname();
  }

  /**
   * @param int $id
   * @return AbstractModel
   */
  public function find_by_id($id) {
    $item = $this->known_items->get_by_id($id);
    if (is_null($item)) {
      $table = $this->determine_tablename();

      $model = $this->create_identity_model($id);
      $item = $this->map_record_to_object($this->db->row($table, '*', ['id' => (int)$id]), $model);
      $this->known_items->set_item($item);
    }
    return $item;
  }

  protected function create_identity_model($id) {
    $model_classname = $this->determine_model_classname();
    /** @var AbstractModel $model */
    $model = new $model_classname();
    $model->set_id($id);
    $this->known_items->set_item($model);
    return $model;
  }

  /**
   * @param AbstractModel $object
   */
  public function save(AbstractModel $object) {
    $table = $this->determine_tablename();
    $record = $this->object_to_record($object);
    if ($record['id']) {
      $this->db->update($table, $record, 'id = ' . (int) $record['id']);
    } else {
      $insert_id = $this->db->insert($table, $record);
      $object->set_id($insert_id);
    }
  }

  /**
   * @param AbstractModel $parent_object
   * @return AbstractModelCollection
   */
  public function find_by_parent_object(AbstractModel $parent_object) {
    $model_classname = get_class($parent_object);
    $property = Nomenclature::modelclassname_to_propertyname($model_classname);
    $table = $this->determine_tablename();
    $collection_classname = Nomenclature::repositoryclassname_to_collectionclassname(get_called_class());
    /** @var AbstractModelCollection $collection */
    $collection = new $collection_classname();
    $records = $this->db->select($table, '*', [$property => (int) $parent_object->get_id()]);
    foreach ($records as $record) {
      /** @var AbstractModel $model */
      $model = $this->create_identity_model($record['id']);
      $collection->set_item($this->map_record_to_object($record, $model));
    }
    return $collection;
  }

  /**
   * @param array $record
   * @param $object
   * @return AbstractModel
   */
  protected function map_record_to_object($record, $object = NULL) {
    if (!is_array($record)) {
      return NULL;
    }
    if (is_null($object)) {
      $model_classname = $this->determine_model_classname();
      $object = new $model_classname();
    }
    /** @var AbstractModel $object */
    foreach ($record as $key => $value) {
      $setter = 'set_' . $key;
      if (method_exists($object, $setter)) {
        call_user_func([$object, $setter], $value);
      }
    }
    foreach ($object->_get_mapping_relations() as $property => $collection_classname) {
      $setter = 'set_' . $property;
      if (method_exists($object, $setter)) {
        $repository_classname = Nomenclature::collectionclassname_to_repositoryclassname($collection_classname);
        /** @var AbstractDomainRepository $repository */
        $repository = $repository_classname::get_instance();
        $related_objects = $repository->find_by_parent_object($object);
        call_user_func([$object, $setter], $related_objects);
      }
    }
    return $object;
  }

  /**
   * @param AbstractModel $object
   * @return array
   */
  protected function object_to_record(AbstractModel $object) {
    $record = [];
    $mapping_relations = $object->_get_mapping_relations();
    foreach (get_class_methods($object) as $method_name) {
      if (substr($method_name, 0, 4) == 'get_') {
        $field_name = substr($method_name, 4);
        if (array_key_exists($field_name, $mapping_relations)) {
          continue;
        }
        $value = call_user_func([$object, $method_name]);
        if ($value instanceof AbstractModel) {
          $value = $value->get_id();
        } elseif ($value instanceof \DateTime) {
          $value = $value->getTimestamp();
        }
        $record[$field_name] = $value;
      }
    }
    return $record;
  }

  /**
   * @return string
   */
  protected function determine_tablename() {
    return Nomenclature::repositoryclassname_to_tablename(get_called_class());
  }

  /**
   * return string
   */
  protected function determine_model_classname() {
    return Nomenclature::repositoryclassname_to_modelclassname(get_called_class());
  }

}