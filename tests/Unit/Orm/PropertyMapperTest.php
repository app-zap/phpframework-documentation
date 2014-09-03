<?php
namespace AppZap\PHPFramework\Tests\Unit\Orm;

use AppZap\PHPFramework\Orm\PropertyMapper;

class PropertyMapperTest extends \PHPUnit_Framework_TestCase {

  /**
   * @var \AppZap\PHPFramework\Orm\PropertyMapper
   */
  protected $fixture;

  public function setUp() {
    $this->fixture = new PropertyMapper();
  }

  /**
   * @test
   */
  public function source_is_already_of_target_type() {
    $source = new \DateTime();
    $this->assertSame($source, $this->fixture->map($source, '\\DateTime'));
  }

  /**
   * @test
   */
  public function timestamp_to_datetime() {
    $source = 1409738029;
    /** @var \DateTime $datetime */
    $datetime = $this->fixture->map($source, '\\DateTime');
    $this->assertTrue($datetime instanceof \DateTime);
    $this->assertSame($source, $datetime->getTimestamp());
  }

  /**
   * @test
   */
  public function with_or_without_trailing_backslash() {
    $source = 1409738157;
    /** @var \DateTime $datetime */
    $datetime = $this->fixture->map($source, '\\DateTime');
    $this->assertTrue($datetime instanceof \DateTime);
    $datetime = $this->fixture->map($source, 'DateTime');
    $this->assertTrue($datetime instanceof \DateTime);
  }

}