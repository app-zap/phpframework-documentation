<?php
namespace AppZap\PHPFramework\Tests\Unit\Configuration\Parser;

use AppZap\PHPFramework\Configuration\Configuration;
use AppZap\PHPFramework\Configuration\Parser\IniParser;

class IniParserTest extends \PHPUnit_Framework_TestCase {

  public function setUp() {
    $_ENV['AppZap\PHPFramework\ProjectRoot'] = dirname(__FILE__);
  }

  /**
   * @test
   * @expectedException \AppZap\PHPFramework\Mvc\ApplicationPartMissingException
   * @expectedExceptionCode 1410538265
   */
  public function notExistingApplicationFolder() {
    IniParser::init('not_existing');
  }

  /**
   * @test
   */
  public function applicationFolderWithoutIniFiles() {
    IniParser::init('_nofiles');
  }

  /**
   * @test
   */
  public function applicationFolderOnlyGlobalFile() {
    IniParser::init('_onlyglobal');
    $this->assertSame('bar', Configuration::get('unittest', 'foo'));
  }

  /**
   * @test
   */
  public function applicationFolderOnlyLocalFile() {
    IniParser::init('_onlylocal');
    $this->assertSame('bar', Configuration::get('unittest', 'foo'));
  }

  /**
   * @test
   */
  public function applicationFolderBothFiles() {
    IniParser::init('_both');
    $this->assertSame('b', Configuration::get('unittest', 'foo'));
    $this->assertSame('42', Configuration::get('unittest', 'bar'));
    $this->assertNull(Configuration::get('unittest', 'baz'));
  }


}