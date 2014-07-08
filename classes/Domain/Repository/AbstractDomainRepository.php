<?php
namespace AppZap\PHPFramework\Domain\Repository;

use AppZap\PHPFramework\Domain\Model\AbstractModel;
use AppZap\PHPFramework\Persistence\MySQL;
use AppZap\PHPFramework\Persistence\StaticMySQL;
use AppZap\PHPFramework\Singleton;

abstract class AbstractDomainRepository {
use Singleton;

  /**
   * @var MySQL
   */
  protected $db;

  public function __construct() {
    $this->db = StaticMySQL::getInstance();
  }

  /**
   * @param int $id
   * @return AbstractModel
   */
  public function find_by_id($id) {
    $table = $this->determine_tablename();
    return $this->record_to_object($this->db->row($table, '*', ['id' => (int) $id]));
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
   * @param array $record
   * @return AbstractModel
   */
  protected function record_to_object($record) {
    if (!is_array($record)) {
      return NULL;
    }
    $model_classname = $this->determine_model_classname();
    $object = new $model_classname();
    foreach ($record as $key => $value) {
      $setter = 'set_' . $key;
      if (method_exists($object, $setter)) {
        call_user_func(array($object, $setter), $value);
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
    foreach (get_class_methods($object) as $method_name) {
      if (substr($method_name, 0, 4) == 'get_') {
        $field_name = substr($method_name, 4);
        $value = call_user_func(array($object, $method_name));
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
    $called_repository_classname = get_called_class();
    $called_repository_classname_parts = explode('\\', $called_repository_classname);
    $classname_without_namespace = array_pop($called_repository_classname_parts);
    $tablename = strtolower(substr($classname_without_namespace, 0, -strlen('Repository')));
    return $tablename;
  }

  /**
   * return string
   */
  protected function determine_model_classname() {
    $called_repository_classname = get_called_class();
    $model_classname = str_replace('Repository', 'Model', $called_repository_classname);
    $model_classname = substr($model_classname, 0, -strlen('Model'));
    return $model_classname;
  }

}