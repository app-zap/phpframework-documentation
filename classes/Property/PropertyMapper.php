<?php
namespace AppZap\PHPFramework\Property;

class PropertyMapper {

  /**
   * @param $source
   * @param $target
   * @return mixed
   * @throws \Exception
   */
  public function map($source, $target) {

    switch ($target) {
      case \DateTime::class:
        $value = $this->mapToDateTime($source);
        break;
      default:
        throw new \Exception('No conversion found for type "' . $target . '"');
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

}