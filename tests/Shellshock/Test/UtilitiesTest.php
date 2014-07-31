<?php
namespace Shellshock\Test;

use Shellshock\Utilities;

/**
 * @coversDefaultClass \Shellshock\Utilities
 */
class UtilitiesTest extends \PHPUnit_Framework_TestCase {
	const TARGET_CLASSNAME	= '\\Shellshock\\Utilities';

	/**
	 * @covers ::normalizePath
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
			['~/path/to/file',	$_SERVER['HOME'].'/path/to/file',	'Home-relative paths should be expanded'],
			['~',				$_SERVER['HOME'],					'Home-relative paths should be expanded'],
		];
	}

	/**
	 * @covers ::normalizePath
	 * @covers ::<!public>
	 * @expectedException \RuntimeException
	 * @backupGlobals enabled
	 */
	public function testNormalizePathWithoutHome(){
		unset($_SERVER['HOME']);
		$this->testNormalizePath('~/path/to/file', null, null);
	}
}
