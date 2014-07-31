<?php
namespace Shellshock\Test;

use Shellshock\Settings;
use PHPUnit_Framework_MockObject_MockObject	as MockObject;

/**
 * @coversDefaultClass \Shellshock\Settings
 */
class SettingsTest extends \PHPUnit_Framework_TestCase {
	const TARGET_CLASSNAME	= '\\Shellshock\\Settings';

	/**
	 * @param array $values	The settings values for the object to hold
	 * @return Settings|MockObject
	 */
	protected function buildSettings(array $values){
		$mock	= $this->getMockBuilder(static::TARGET_CLASSNAME)
					   ->setMethods(['getSettings'])
					   ->getMockForAbstractClass();

		$mock->expects($this->any())
			 ->method('getSettings')
			 ->will($this->returnValue($values));

		return $mock;
	}


	/**
	 * @covers ::get
	 * @covers ::<!public>
	 */
	public function testGet(){
		$instance	= $this->buildSettings([
			'one'	=> 'something',
			'two'	=> 'other',
		]);

		$this->assertEquals('something', $instance->get('one'), 'Values should be retrieved from settings');
		$this->assertEquals('fallback', $instance->get('notset', 'fallback'), 'Fallbacks should be used for unset settings');
		$this->assertNull($instance->get('notset'), 'Fallbacks should default to NULL');
	}
}
