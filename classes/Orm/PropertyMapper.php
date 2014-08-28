<?php
namespace AppZap\PHPFramework\Orm;

use AppZap\PHPFramework\Domain\Model\AbstractModel;
use AppZap\PHPFramework\Nomenclature;

class PropertyMapper {

  /**
   * @param $source
   * @param $target
   * @return mixed
   * @throws \Exception
   */
  public function map($source, $target) {
    if ($source instanceof $target) {
      return $source;
    }
    $original_target = $target;
    $value = NULL;
    while(TRUE) {
      switch ($target) {
        case AbstractModel::class:
          $value = $this->mapToModel($source, $original_target);
          break(2);
        case \DateTime::class:
          $value = $this->mapToDateTime($source);
          break(2);
        default:
          $target = get_parent_class($target);
          if ($target === FALSE) {
            throw new \Exception('No conversion found for type "' . $original_target . '"');
          }
      }
    }
    return $value;
  }

  /**
   * @param int $source
   * @return \DateTime
   */
  protected function mapToDateTime($source) {
    if (!($source instanceof \DateTime) && is_numeric($source)) {
      $timestamp = (int)$source;
      $dateTime = new \DateTime();
      $dateTime->setTimestamp($timestamp);
      return $dateTime;
    } else {
      return $source;
    }
  }

  /**
   * @param int $source
   * @param string $target_class
   * @return AbstractModel
   */
  protected function mapToModel($source, $target_class) {
    $repository_classname = Nomenclature::modelclassname_to_repositoryclassname($target_class);
    /** @var \AppZap\PHPFramework\Domain\Repository\AbstractDomainRepository $repository */
    $repository = $repository_classname::get_instance();
    return $repository->find_by_id((int) $source);
  }

}