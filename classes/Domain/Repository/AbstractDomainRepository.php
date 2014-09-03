<?php
namespace AppZap\PHPFramework\Domain\Repository;

use AppZap\PHPFramework\Domain\Collection\AbstractModelCollection;
use AppZap\PHPFramework\Domain\Model\AbstractModel;
use AppZap\PHPFramework\Utility\Nomenclature;
use AppZap\PHPFramework\Orm\EntityMapper;
use AppZap\PHPFramework\Persistence\MySQL;
use AppZap\PHPFramework\Persistence\StaticMySQL;

abstract class AbstractDomainRepository {

  /**
   * @var EntityMapper
   */
  protected $entity_mapper;

  /**
   * @var AbstractModelCollection
   */
  protected $known_items;

  /**
   * @var string
   */
  protected $tablename;

  /**
   * @var MySQL
   */
  protected $db;

  public function __construct() {
    $this->db = StaticMySQL::getInstance();
    $collection_classname = Nomenclature::repositoryclassname_to_collectionclassname(get_called_class());
    $this->known_items = new $collection_classname();
    $this->entity_mapper = EntityMapper::get_instance();
    $this->tablename = Nomenclature::repositoryclassname_to_tablename(get_called_class());
  }

  /**
   * @return AbstractDomainRepository
   */
  public static function get_instance() {
    static $_instance = NULL;
    $class = get_called_class();
    return $_instance ?: $_instance = new $class;
  }

  public function __clone() {
    trigger_error('Cloning ' . __CLASS__ . ' is not allowed.', E_USER_ERROR);
  }

  public function __wakeup() {
    trigger_error('Unserializing ' . __CLASS__ . ' is not allowed.', E_USER_ERROR);
  }

  /**
   * @param int $id
   * @return AbstractModel
   */
  public function find_by_id($id) {
    $item = $this->known_items->get_by_id($id);
    if (is_null($item)) {
      $model = $this->create_identity_model($id);
      $item = $this->entity_mapper->map_record_to_object($this->db->row($this->tablename, '*', ['id' => (int)$id]), $model);
      $this->known_items->set_item($item);
    }
    return $item;
  }

  /**
   * @param AbstractModel $object
   */
  public function save(AbstractModel $object) {
    $record = $this->entity_mapper->object_to_record($object);
    if ($record['id']) {
      $where = ['id' => (int)$record['id']];
      $this->db->update($this->tablename, $record, $where);
    } else {
      $insert_id = $this->db->insert($this->tablename, $record);
      $object->set_id($insert_id);
    }
  }

  /**
   * @param $where
   * @return AbstractModel
   */
  protected function query_one($where) {
    foreach ($where as $property => $value) {
      $where[$property] = $this->entity_mapper->scalarize_value($value);
    }
    return $this->record_to_object($this->db->row($this->tablename, '*', $where));
  }

  /**
   * @param $where
   * @return AbstractModelCollection
   */
  protected function query_many($where) {
    foreach ($where as $property => $value) {
      $where[$property] = $this->entity_mapper->scalarize_value($value);
    }
    $collection_classname = Nomenclature::repositoryclassname_to_collectionclassname(get_called_class());
    /** @var AbstractModelCollection $collection */
    $collection = new $collection_classname();
    $records = $this->db->select($this->tablename, '*', $where);
    foreach ($records as $record) {
      $collection->set_item($this->record_to_object($record));
    }
    return $collection;
  }

  /**
   * @param $record
   * @return AbstractModel
   */
  protected function record_to_object($record) {
    return $this->entity_mapper->map_record_to_object($record, $this->create_empty_model());
  }

  /**
   * @return AbstractModel
   */
  protected function create_empty_model() {
    $model_classname = Nomenclature::repositoryclassname_to_modelclassname(get_called_class());
    /** @var AbstractModel $model */
    $model = new $model_classname();
    return $model;
  }

  /**
   * @param int $id
   * @return AbstractModel
   */
  protected function create_identity_model($id) {
    $model = $this->create_empty_model();
    $model->set_id($id);
    $this->known_items->set_item($model);
    return $model;
  }

}