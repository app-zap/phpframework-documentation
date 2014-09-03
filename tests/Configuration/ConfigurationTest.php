<?php
namespace AppZap\PHPFramework\Tests\Configuration;

use AppZap\PHPFramework\Configuration\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase {

  /**
   * @test
   */
  public function roundtrip_single_value() {
    Configuration::set('test', 'foo', 'bar');
    $this->assertSame('bar', Configuration::get('test', 'foo'));
  }

  /**
   * @test
   */
  public function get_default_value() {
    Configuration::reset();
    $this->assertSame('bar', Configuration::get('test', 'foo', 'bar'));
  }

  /**
   * @test
   */
  public function reset_works() {
    Configuration::set('test', 'reset_works', TRUE);
    $this->assertTrue(Configuration::get('test', 'reset_works'));
    Configuration::reset();
    $this->assertNull(Configuration::get('test', 'reset_works'));
  }

  /**
   * @test
   */
  public function remove_key_works() {
    Configuration::set('test', 'key1', TRUE);
    Configuration::set('test', 'key2', TRUE);
    $this->assertTrue(Configuration::get('test', 'key1'));
    $this->assertTrue(Configuration::get('test', 'key2'));
    Configuration::remove_key('test', 'key1');
    $this->assertNull(Configuration::get('test', 'key1'));
    $this->assertTrue(Configuration::get('test', 'key2'));
  }

  /**
   * @test
   */
  public function get_section() {
    Configuration::reset();
    Configuration::set('test', 'key1', TRUE);
    Configuration::set('test', 'key2', TRUE);
    Configuration::set('othersection', 'key3', TRUE);
    $test_section = Configuration::getSection('test');
    $this->assertSame(2, count($test_section));
    $this->assertArrayNotHasKey('key3', $test_section);
  }

  /**
   * @test
   */
  public function get_non_existing_section() {
    Configuration::reset();
    $this->assertNull(Configuration::getSection('not_existing'));
  }

  /**
   * @test
   */
  public function remove_section() {
    Configuration::reset();
    Configuration::set('test', 'key1', TRUE);
    Configuration::set('test', 'key2', TRUE);
    Configuration::set('othersection', 'key3', TRUE);
    $test_section = Configuration::getSection('test');
    $this->assertSame(2, count($test_section));
    Configuration::remove_section('test');
    $this->assertNull(Configuration::getSection('test'));
    $this->assertSame(1, count(Configuration::getSection('othersection')));
  }

}