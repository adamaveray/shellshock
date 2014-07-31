<?php
namespace Shellshock\Test;

use Shellshock\Config;
use PHPUnit_Framework_MockObject_MockObject	as MockObject;

/**
 * @coversDefaultClass \Shellshock\Config
 */
class ConfigTest extends \PHPUnit_Framework_TestCase {
	const TARGET_CLASSNAME	= '\\Shellshock\\Config';

	/**
	 * @param string $path
	 * @param string $content
	 * @return Config|MockObject
	 */
	protected function buildConfig($path, $content){
		$mock	= $this->getMockBuilder(static::TARGET_CLASSNAME)
					   ->setMethods(['getFileContents'])
					   ->getMock();

		$mock->expects($this->once())
			 ->method('getFileContents')
			 ->with($path)
			 ->will($this->returnValue($content));

		return $mock;
	}


	/**
	 * @covers ::import
	 * @covers ::getSettings
	 * @covers ::get
	 * @covers ::<!public>
	 */
	public function testImport(){
		$instance	= new Config();

		$values	= [
			'one'	=> 'something',
			'two'	=> 'other',
		];

		$instance->import($values);
		$this->assertEquals($values, $instance->getSettings(), 'Imported values should be stored in settings');

		$otherValues	= [
			'one'	=> 'modified',
			'extra'	=> 'hello',
		];

		$instance->import($otherValues);
		$this->assertEquals('modified', $instance->get('one'), 'Existing imported values should overwrite previous values');
		$this->assertEquals('hello', $instance->get('extra'), 'New imported values should be merged with existing values');
	}

	/**
	 * @covers ::importFile
	 * @covers ::<!public>
	 */
	public function testImportFile(){
		// Basic
		$path		= '/path/to/config.json';
		$instance	= $this->buildConfig($path, '{"example": "value"}');
		$instance->importFile($path);
		$this->assertEquals([
			'example'	=> 'value',
		], $instance->getSettings(), 'Settings should be imported from file');

		// Required keys
		$path		= '/path/to/config.json';
		$instance	= $this->buildConfig($path, '{"something": "value", "other": "extra", "final": "one more"}');
		$instance->importFile($path, ['something', 'other']);
		$this->assertEquals([
			'something'	=> 'value',
			'other'		=> 'extra',
			'final'		=> 'one more',
		], $instance->getSettings(), 'Settings should be imported from file if all required keys are present');
	}

	/**
	 * @covers ::importFile
	 * @covers ::<!public>
	 * @expectedException \RuntimeException
	 */
	public function testImportFileWithoutRequiredKeys(){
		$path		= '/path/to/config.json';
		$instance	= $this->buildConfig($path, '{"something": "value"}');
		$instance->importFile($path, ['something', 'other']);
	}

	/**
	 * @covers ::importFile
	 * @covers ::<!public>
	 * @expectedException \RuntimeException
	 */
	public function testImportInvalidFile(){
		$path		= '/path/to/config.json';
		$instance	= $this->buildConfig($path, 'not a json file');
		$instance->importFile($path);
	}

	/**
	 * @covers ::get
	 * @covers ::set
	 * @covers ::getSettings
	 * @covers ::<!public>
	 */
	public function testSet(){
		$instance	= new Config();

		$value	= 'some value';
		$key	= 'akey';

		$this->assertNull($instance->get($key), 'Value should not exist yet');
		$instance->set($key, $value);
		$this->assertEquals($value, $instance->get($key), 'Value should be retrieved correctly');
		$this->assertArrayHasKey($key, $instance->getSettings(), 'Value should be stored in settings');
	}
}
