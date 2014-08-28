<?php
namespace AppZap\PHPFramework\Orm;

use AppZap\PHPFramework\Domain\Model\AbstractModel;
use AppZap\PHPFramework\Domain\Repository\AbstractDomainRepository;
use AppZap\PHPFramework\Nomenclature;
use AppZap\PHPFramework\Singleton;

class EntityMapper {
  use Singleton;

  /**
   * @param array $record
   * @param $object
   * @return AbstractModel
   */
  public function map_record_to_object($record, $object) {
    if (!is_array($record)) {
      return NULL;
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
  public function object_to_record(AbstractModel $object) {
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

}