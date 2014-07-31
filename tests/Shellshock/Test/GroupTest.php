<?php
namespace Shellshock\Test;

use Shellshock\Group;
use Shellshock\Host;
use Shellshock\HostManager;
use PHPUnit_Framework_MockObject_MockObject	as MockObject;

/**
 * @coversDefaultClass \Shellshock\Group
 */
class GroupTest extends \PHPUnit_Framework_TestCase {
	const TARGET_CLASSNAME	= '\\Shellshock\\Group';

	public function setUp(){
		// Reset settings dir
		$property	= new \ReflectionProperty(static::TARGET_CLASSNAME, 'settingsDir');
		$property->setAccessible(true);
		$property->setValue(null, null);
	}

	/**
	 * @param array|null $methods
	 * @return Group|MockObject
	 */
	protected function buildGroup(array $methods = null){
		// Build mock
		$mock	= $this->getMockBuilder(static::TARGET_CLASSNAME)
					   ->setMethods($methods)
					   ->disableOriginalConstructor()
					   ->getMock();

		return $mock;
	}


	/**
	 * @covers ::__construct
	 * @covers ::getName
	 * @covers ::getSettings
	 * @covers ::getScripts
	 * @covers ::setSettingsDir
	 * @covers ::<!public>
	 * @dataProvider constructionDataProvider
	 *
	 * @param array|null $expected
	 * @param string $path
	 * @param string $dataDir
	 * @param string $gorupname
	 * @param string $content
	 * @param string|null $message	A message to use if the test fails
	 */
	public function testConstruction(array $expected = null, $path, $dataDir, $groupName, $fileExists, $content, $message = null){
		// Build mock
		$mock	= $this->buildGroup(['fileExists', 'getFileContents']);

		$mock->expects($this->any())
			 ->method('fileExists')
			 ->with($path)
			 ->will($this->returnValue($fileExists));

		$mock->expects($this->any())
			 ->method('getFileContents')
			 ->with($path)
			 ->will($this->returnValue($content));

		// Run constructor
		Group::setSettingsDir($dataDir);
		$mock->__construct($groupName);

		$this->assertEquals($expected, $mock->getSettings(), $message);
		$this->assertEquals((isset($expected['scripts']) ? $expected['scripts'] : []), $mock->getScripts(), 'The setting scripts should be handled correctly');
		$this->assertEquals($groupName, $mock->getName(), 'The group name should be stored correctly');
	}

	public function constructionDataProvider(){
		return [
			// Has file
			'Has File'	=> [
				[
					'scripts' => [
						'script1.sh',
						'script2.sh',
					],
				],
				'/path/to/settings/groupname.json',
				'/path/to/settings',
				'groupname',
				true,
				'{"scripts": ["script1.sh", "script2.sh"]}',
				'Decoded contents for the group\'s script file should be returned'
			],

			// No file
			'No File'	=> [
				[],
				'/path/to/settings/groupname.json',
				'/path/to/settings',
				'groupname',
				false,
				null,
				'An empty array should be returned for not found settings files',
			],
		];
	}

	/**
	 * @covers ::__construct
	 * @covers ::<!public>
	 * @expectedException \RuntimeException
	 */
	public function testConstructionInvalidSettings(){
		$this->testConstruction(null, '/path/to/settings/groupname.json', '/path/to/settings', 'groupname', true, 'notjson');
	}

	/**
	 * @covers ::__construct
	 * @covers ::<!public>
	 * @expectedException \UnexpectedValueException
	 */
	public function testConstructionScriptlessSettings(){
		$this->testConstruction(null, '/path/to/settings/groupname.json', '/path/to/settings', 'groupname', true, '{"somethingelse": "butnotscripts"}');
	}


	/**
	 * @covers ::getHosts
	 * @covers ::addHost
	 * @covers ::addHosts
	 * @covers ::<!public>
	 * @dataProvider hostsDataProvider
	 *
	 * @param array $scripts
	 * @param Host[]|HostManager $hosts
	 * @param string|null $message			A message to use if the test fails
	 */
	public function testHosts($scripts, $hosts, $message = null){
		$validateHosts	= function($expected, $actual, $message = null){
			$expectedArray	= [];
			foreach($expected as $host){
				$expectedArray[]	= $host;
			}

			$actualArray	= [];
			foreach($actual as $host){
				$actualArray[]	= $host;
			}

			$this->assertEquals($expectedArray, $actualArray, $message);
		};

		$mock	= $this->buildGroup(['getScripts']);
		$mock->expects($this->any())
			 ->method('getScripts')
			 ->will($this->returnValue($scripts));

		$mock->addHosts($hosts);
		$validateHosts($hosts, $mock->getHosts(), $message);

		$extraHost	= new Host('example.com');
		$mock->addHost($extraHost);
		$hosts[]	= $extraHost;
		$validateHosts($hosts, $mock->getHosts(), 'Added hosts should be merged with existing hosts');

		$mock->addHost($extraHost);
		$validateHosts($hosts, $mock->getHosts(), 'Existing hosts should not be added');
	}

	public function hostsDataProvider(){
		$buildHost	= function($scripts){
			$mock	= $this->getMockBuilder('\\Shellshock\\Host')
						   ->setMethods(['addScripts'])
						   ->disableOriginalConstructor()
						   ->getMock();

			$mock->expects($this->once())
				 ->method('addScripts')
				 ->with($scripts);

			return $mock;
		};

		$items	= [];


		// Host manager
		$scripts	= ['script1.sh', 'script2.sh'];
		$hostManager	= new HostManager();
		$hostManager[]	= $buildHost($scripts);
		$items['Host Manager']	= [
			$scripts,
			$hostManager,
			'Host managers should be stored and retrieved correctly',
		];

		// Array of hosts
		$scripts	= ['script3.sh', 'script4.sh'];
		$items['Array of Hosts']	= [
			$scripts,
			[$buildHost($scripts), $buildHost($scripts), $buildHost($scripts)],
			'Arrays of hosts should be stored and retrieved correctly',
		];

		return $items;
	}

	/**
	 * @covers ::getHosts
	 * @covers ::addHost
	 * @covers ::addHosts
	 * @covers ::<!public>
	 * @expectedException \InvalidArgumentException
	 */
	public function testInvalidHosts(){
		$this->testHosts(null, 'not hosts', null);
	}
}
