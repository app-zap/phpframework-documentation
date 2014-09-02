<?php
namespace AppZap\PHPFramework\Tests;

use AppZap\PHPFramework\Nomenclature;

class NomenclatureTest extends \PHPUnit_Framework_TestCase {

  /**
   * @test
   */
  public function collectionclassname_to_repositoryclassname() {
    $collection_classname = '\\Vendor\\Project\\Domain\\Collection\\ItemCollection';
    $repository_classname = '\\Vendor\\Project\\Domain\\Repository\\ItemRepository';
    $this->assertSame($repository_classname, Nomenclature::collectionclassname_to_repositoryclassname($collection_classname));
  }

}