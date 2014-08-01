<?php
namespace Shellshock\Test;

use Shellshock\Utilities;

/**
 * @coversDefaultClass \Shellshock\Utilities
 */
class UtilitiesTest extends \PHPUnit_Framework_TestCase {
	const TARGET_CLASSNAME	= '\\Shellshock\\Utilities';
	const HOME_DIR	= '/path/to/dir';

	public function setUp(){
		Utilities::setHomeDir(static::HOME_DIR);
	}

	/**
	 * @covers ::normalizePath
	 * @covers ::setHomeDir
	 * @covers ::<!public>
	 * @dataProvider normalizePathDataProvider
	 */
	public function testNormalizePath($path, $expected, $message = null){
		$this->assertEquals($expected, Utilities::normalizePath($path), $message = null);
	}

	public function normalizePathDataProvider(){
		return [
			// Absolute
			['/path/to/file',	'/path/to/file',			'Absolute paths should not be changed'],

			// Relative
			['path/to/file',	getcwd().'/path/to/file',	'Relative paths should be made absolute'],
			['./path/to/file',	getcwd().'/./path/to/file',	'Relative paths should be made absolute'],
			['',				getcwd(),					'Relative paths should be made absolute'],

			// Home directory
			['~/path/to/file',	static::HOME_DIR.'/path/to/file',	'Home-relative paths should be expanded'],
			['~',				static::HOME_DIR,					'Home-relative paths should be expanded'],
		];
	}

	/**
	 * @covers ::normalizePath
	 * @covers ::setHomeDir
	 * @covers ::<!public>
	 * @expectedException \RuntimeException
	 */
	public function testNormalizePathWithoutHome(){
		Utilities::setHomeDir(null);
		$this->testNormalizePath('~/path/to/file', null, null);
	}
}
