<?php
namespace AppZap\PHPFramework\Domain\Repository;

use AppZap\PHPFramework\Domain\Collection\AbstractModelCollection;
use AppZap\PHPFramework\Domain\Model\AbstractModel;
use AppZap\PHPFramework\Nomenclature;
use AppZap\PHPFramework\Orm\EntityMapper;
use AppZap\PHPFramework\Persistence\MySQL;
use AppZap\PHPFramework\Persistence\StaticMySQL;

abstract class AbstractDomainRepository {

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
   * @var EntityMapper
   */
  protected $entity_mapper;

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
    $this->entity_mapper = EntityMapper::get_instance();
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
      $item = $this->entity_mapper->map_record_to_object($this->db->row($table, '*', ['id' => (int)$id]), $model);
      $this->known_items->set_item($item);
    }
    return $item;
  }

  /**
   * @return AbstractModel
   */
  protected function create_empty_model() {
    $model_classname = $this->determine_model_classname();
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

  /**
   * @param AbstractModel $object
   */
  public function save(AbstractModel $object) {
    $table = $this->determine_tablename();
    $record = $this->entity_mapper->object_to_record($object);
    if ($record['id']) {
      $this->db->update($table, $record, 'id = ' . (int)$record['id']);
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
    $records = $this->db->select($table, '*', [$property => (int)$parent_object->get_id()]);
    foreach ($records as $record) {
      /** @var AbstractModel $model */
      $model = $this->create_identity_model($record['id']);
      $collection->set_item($this->entity_mapper->map_record_to_object($record, $model));
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