<?php
namespace Shellshock\Test;

use Shellshock\Input;

/**
 * @coversDefaultClass \Shellshock\Input
 */
class InputTest extends \PHPUnit_Framework_TestCase {
	const TARGET_CLASSNAME	= '\\Shellshock\\Input';
	const SETTINGS_BASE_DIR	= '/path/to/somewhere';

	/**
	 * @covers ::__construct
	 * @covers ::getSettings
	 * @covers ::<!public>
	 * @dataProvider getSettingsDataProvider
	 *
	 * @param array $expected		The expected settings values
	 * @param array $args			The command-line arguments to process
	 * @param bool $isDir			Whether the given path in $args should be considered a directory
	 * @param array $existingDirs	Paths to consider directories
	 * @param bool $isConfigFile	Whether the given config file path is $args should be considered a valid config file
	 * @param string|null $message	A message to use if the test fails
	 */
	public function testGetSettings(array $expected, array $args, $isDir, array $existingDirs, $isConfigFile, $message = null){
		$expected	= array_merge([
			'path'			=> static::SETTINGS_BASE_DIR,
			'configFile'	=> static::SETTINGS_BASE_DIR.'/'.Input::DEFAULT_CONFIG_FILE,
			'groups'		=> [],
			'scripts'		=> [],
			'command'		=> null,
			'ping'			=> false,
			'safety'		=> false,
			'verbose'		=> false,
		], $expected);


		// Build mock
		array_unshift($args, '__placeholder__'); // First argument would be path for current script...

		$input	= $this->getMockBuilder(static::TARGET_CLASSNAME)
					   ->setMethods(['pathIsDir', 'pathIsConfigFile'])
					   ->setConstructorArgs([$args])
					   ->getMock();

		$baseDir	= $expected['path'];
		$input->expects($this->any())
			  ->method('pathIsDir')
			  ->will($this->returnCallback(function($path) use($baseDir, $isDir, $existingDirs){
				  $path	= str_replace($baseDir, '', $path);
				  if($path === ''){
					  return $isDir;
				  }

				  $path	= ltrim($path, '/');
				  return in_array($path, $existingDirs);
			  }));

		$input->expects($this->any())
			  ->method('pathIsConfigFile')
			  ->will($this->returnValue($isConfigFile));

		$this->assertEquals($expected, $input->getSettings(), $message);
	}

	public function getSettingsDataProvider(){
		$items	= [];

		$subdirs	= ['files', 'scripts', 'settings'];

		// All defaults
		$items['Defaults']	= [
			[], [ static::SETTINGS_BASE_DIR ], true, $subdirs, true, 'Default values should be used if no arguments set'
		];

		// Config path
		$configPath	= '/path/to/config.json';
		$items['Config Path']	= [
			[
				'configFile'	=> $configPath,
			], [ static::SETTINGS_BASE_DIR, '-c', $configPath ], true, $subdirs, true, 'Custom config path should be used'
		];

		// Command
		$command	= 'ls /';
		$items['Command']	= [
			[
				'command'	=> $command,
			], [ static::SETTINGS_BASE_DIR, '--command', $command ], true, $subdirs, true, 'Command should be recognised'
		];

		// One group
		$items['Single Group']	= [
			[
				'groups'	=> ['one'],
			], [ static::SETTINGS_BASE_DIR, '--groups', 'one' ], true, $subdirs, true, 'A group should be recognised'
		];

		// Multiple groups
		$items['Multiple Groups']	= [
			[
				'groups'	=> ['one', 'two', 'three'],
			], [ static::SETTINGS_BASE_DIR, '--groups', 'one,two,three' ], true, $subdirs, true, 'Multiple groups should be accepted'
		];

		// One script
		$items['Single Script']	= [
			[
				'scripts'	=> ['item/one.sh'],
			], [ static::SETTINGS_BASE_DIR, '--scripts', 'item/one.sh' ], true, $subdirs, true, 'A script should be recognised'
		];

		// Multiple scripts
		$items['Multiple Scripts']	= [
			[
				'scripts'	=> ['item/one.sh', 'item/two.sh', 'item/three.sh'],
			], [ static::SETTINGS_BASE_DIR, '--scripts', 'item/one.sh,item/two.sh,item/three.sh' ], true, $subdirs, true, 'Multiple scripts should be accepted'
		];


		// Debug flags
		$items['Debug Ping Flag']	= [
			[
				'ping'	=> true,
			], [ static::SETTINGS_BASE_DIR, '--ping' ], true, $subdirs, true, 'Ping flag should be recognised'
		];
		$items['Debug Safety Flag']	= [
			[
				'safety'	=> true,
			], [ static::SETTINGS_BASE_DIR, '--safety' ], true, $subdirs, true, 'Safety flag should be recognised'
		];
		$items['Debug Verbose Flag']	= [
			[
				'verbose'	=> true,
			], [ static::SETTINGS_BASE_DIR, '--verbose' ], true, $subdirs, true, 'Verbose flag should be recognised'
		];

		return $items;
	}

	/**
	 * @covers ::__construct
	 * @covers ::getSettings
	 * @covers ::<!public>
	 *
	 * @expectedException \InvalidArgumentException
	 */
	public function testGetSettingsNotFoundDir(){
		$this->testGetSettings([], [ static::SETTINGS_BASE_DIR ], false, ['files', 'scripts', 'settings'], true, null);
	}

	/**
	 * @covers ::__construct
	 * @covers ::getSettings
	 * @covers ::<!public>
	 *
	 * @expectedException \InvalidArgumentException
	 */
	public function testGetSettingsInvalidDir(){
		$this->testGetSettings([], [ static::SETTINGS_BASE_DIR ], true, ['files', 'settings'], true, null);
	}

	/**
	 * @covers ::__construct
	 * @covers ::getSettings
	 * @covers ::<!public>
	 *
	 * @expectedException \RuntimeException
	 */
	public function testGetSettingsNotFoundConfig(){
		$this->testGetSettings([], [ static::SETTINGS_BASE_DIR ], true, ['files', 'scripts', 'settings'], false, null);
	}
}
