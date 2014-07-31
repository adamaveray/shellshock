<?php
namespace Shellshock\Test;

use Shellshock\HostManager;
use Shellshock\Host;
use Shellshock\Group;
use PHPUnit_Framework_MockObject_MockObject	as MockObject;

/**
 * @coversDefaultClass \Shellshock\HostManager
 */
class HostManagerTest extends \PHPUnit_Framework_TestCase {
	const TARGET_CLASSNAME	= '\\Shellshock\\HostManager';

	/**
	 * @param array $hostnames	An array of hostname strings
	 * @return Host[]
	 */
	protected function hostnamesToHosts(array $hostnames){
		$hosts	= [];
		foreach($hostnames as $hostname){
			$host	= new Host($hostname);
			$hosts[$hostname]	= $host;
		}

		return $hosts;
	}

	/**
	 * @param Host[] $hosts
	 * @return array	An array of hostname strings
	 */
	protected function hostsToHostnames($hosts){
		$hostnames	= [];
		foreach($hosts as $key => $host){
			$hostnames[]	= $host->getHostname();
		}

		return $hostnames;
	}

	/**
	 * @param Host[] $hosts
	 * @return Group|MockObject
	 */
	protected function buildGroup(array $hosts){
		$group	= $this->getMockBuilder('\\Shellshock\\Group')
					   ->disableOriginalConstructor()
					   ->setMethods(null)
					   ->getMock();

		$group->addHosts($hosts);

		return $group;
	}


	/**
	 * @covers ::loadHosts
	 * @covers ::<!public>
	 * @dataProvider loadHostsDataProvider
	 *
	 * @param array $expected	The expected hostname strings for the created Host objects
	 * @param array $hostData	An array of raw host data
	 * @param bool $addDefault	Whether to add the default group
	 */
	public function testLoadHosts(array $allHosts, array $hostData, $addDefault = false){
		$instance	= new HostManager();
		if($addDefault){
			xdebug_break();
		}
		$groups		= $instance->loadHosts($hostData, $addDefault);

		// Check grouped
		if($addDefault){
			$hostData['_default']	= $allHosts;
		}
		foreach($hostData as $group => $hostnames){
			$this->assertTrue(isset($groups[$group]), 'All groups should be found');

			$this->assertEquals($hostData[$group], $this->hostsToHostnames($groups[$group]->getHosts()), 'All grouped hosts should be loaded correctly');
		}

		// Check ungrouped
		$loadedHostnames	= [];
		foreach($instance as $hostname => $host){
			$loadedHostnames[]	= $hostname;
			$this->assertEquals($hostname, $host->getHostname(), 'Hosts should be generated with the correct data');
		}
		$this->assertEquals($allHosts, $loadedHostnames, 'All hosts should be merged and stored inside Host Manager');
	}

	public function loadHostsDataProvider(){
		return [
			// Basic - single host, single group
			'Basic'	=> [
				['host.example.com'],
				[
					'group'	=> ['host.example.com'],
				],
				false,
			],

			// Complex - muliple hosts, multiple groups, some crossover
			'Complex'	=> [
				['one.example.com', 'two.example.com', 'three.example.com'],
				[
					'test'	=> ['one.example.com', 'two.example.com'],
					'other'	=> ['three.example.com', 'one.example.com'],
				],
				false,
			],

			// Add default when not set
			'Create Default'	=> [
				['one.example.com', 'two.example.com', 'three.example.com'],
				[
					'test'		=> ['one.example.com', 'two.example.com'],
					'other'		=> ['three.example.com', 'one.example.com'],
				],
				true,
			],

			// Replace default when set
			'Replace Default'	=> [
				['one.example.com', 'two.example.com', 'three.example.com'],
				[
					'_default'	=> ['one.example.com'],
					'test'		=> ['one.example.com', 'two.example.com'],
					'other'		=> ['three.example.com', 'one.example.com'],
				],
				true,
			],
		];
	}


	/**
	 * @covers ::containsHost
	 * @covers ::<!public>
	 */
	public function testContainsHost(){
		$hostname	= 'one.example.com';
		$host		= new Host($hostname);

		$instance	= new HostManager();
		$this->assertFalse($instance->containsHost($host), 'Manager should not contain host before adding');

		$instance->exchangeArray([$host->getHostname() => $host]);

		$this->assertTrue($instance->containsHost($host), 'Manager should contain host after adding');

		$hostname	= 'two.example.com';
		$otherHost	= new Host($hostname);
		$this->assertFalse($instance->containsHost($otherHost), 'Manager should not contain unaddedd hosts');
	}


	/**
	 * @covers ::matchHosts
	 * @covers ::<!public>
	 * @dataProvider matchHostsDataProvider
	 *
	 * @param array $expected		The expected hostname strings for the matched hosts
	 * @param string $searchString	The search string to match hosts against
	 * @param Host[] $hosts			The host objects to match
	 */
	public function testMatchHosts(array $expected, $searchString, array $hosts){
		$instance	= new HostManager();
		$method		= new \ReflectionMethod($instance, 'matchHosts');
		$method->setAccessible(true);

		$instance->exchangeArray($hosts);
		$matched	= $method->invoke($instance, $searchString);

		$this->assertEquals($expected, $this->hostsToHostnames($matched), 'The correct hosts should be matched');
	}

	public function matchHostsDataProvider(){
		return [
			// Basic match
			'IP Match'	=> [
				['127.0.0.1'],
				'127.0.0.1',
				$this->hostnamesToHosts(['127.0.0.0', '127.0.0.1', '127.0.0.3', 'example.com']),
			],

			// Wildcard
			'IP Wildcard'	=> [
				['127.0.0.0', '127.0.0.1', '127.0.0.3'],
				'127.0.0.*',
				$this->hostnamesToHosts(['127.0.0.0', '127.0.0.1', '127.0.0.3', 'example.com']),
			],

			// Domain basic match
			'Domain Match'	=> [
				['db.example.com'],
				'db.example.com',
				$this->hostnamesToHosts(['web.example.com', 'db.example.com', 'other.com', '127.0.0.1']),
			],

			// Domain wildcard
			'Domain Wildcard'	=> [
				['web.example.com', 'db.example.com'],
				'*.example.com',
				$this->hostnamesToHosts(['web.example.com', 'db.example.com', 'other.com', '127.0.0.1']),
			],
		];
	}


	/**
	 * @param Host[] $hosts
	 * @param array $connectionSettings
	 * @param array $hostMatches
	 */
	protected function doApplyConnectionSettings(array $hosts, array $connectionSettings, array $hostMatches = null){
		/** @var HostManager|MockObject $mock */
		$mock	= $this->getMockBuilder(static::TARGET_CLASSNAME)
					   ->setMethods(['matchHosts', 'processConnectionSetting'])
					   ->getMock();

		if(isset($hostMatches)){
			// Return specific hosts
			foreach($hostMatches as $matchKey => $matchValue){
				$mock->expects($this->any())
					 ->method('matchHosts')
					 ->will($this->returnCallback(function($key) use($hostMatches){
						 return (isset($hostMatches[$key]) ? $hostMatches[$key] : []);
					 }));
			}
		} else {
			// Return all hosts
			$mock->expects($this->any())
				 ->method('matchHosts')
				 ->will($this->returnValue(current($connectionSettings)));
		}

		$mock->expects($this->any())
			 ->method('processConnectionSetting')
			 ->with($connectionSettings, $this->anything(), $this->anything())
			 ->will($this->returnCallback(function($connectionSettings, $settings){
				// Loop it back
				return $settings;
			 }));

		$mock->exchangeArray($hosts);
		$mock->applyConnectionSettings($connectionSettings);
	}

	/**
	 * @covers ::applyConnectionSettings
	 * @covers ::<!public>
	 */
	public function testApplyConnectionSettings(){
		$verifyHost	= function($host, $properties){
			foreach($properties as $name => $value){
				$property	= new \ReflectionProperty($host, $name);
				$property->setAccessible(true);

				$this->assertEquals($value, $property->getValue($host), 'Host property "'.$name.'" should have correct value applied');
			}
		};

		$hostA	= new Host('a.example.com');
		$hostB	= new Host('b.example.com');
		$hostC	= new Host('c.example.com');

		$hostMatches	= [
			'one'	=> [$hostA, $hostB],
			'two'	=> [$hostB],
		];

		$settings	= [
			'_default'	=> [
				'user'	=> 'defaultUser',
			],
			'one'	=> [
				'user'			=> 'userOne',
				'private-key'	=> '/path/to/key/one',
				'verify-host'	=> false,
			],
			'two'	=> [
				'private-key'	=> '/path/to/key/two',
				'verify-host'	=> true,
			],
			'nomatches'	=> [
				'private-key'	=> '/path/to/key/unused',
			],
		];

		$this->doApplyConnectionSettings([$hostA, $hostB, $hostC], $settings, $hostMatches);

		$verifyHost($hostA, [
			'username'		=> 'userOne',
			'identityFile'	=> '/path/to/key/one',
			'verifyHost'	=> false,
		]);
		$verifyHost($hostB, [
			'username'		=> 'userOne',
			'identityFile'	=> '/path/to/key/two',
			'verifyHost'	=> true,
		]);
		$verifyHost($hostC, [
			'username'		=> 'defaultUser',
			'identityFile'	=> null,
			'verifyHost'	=> true,
		]);
	}


	/**
	 * @covers ::processConnectionSetting
	 * @covers ::<!public>
	 * @dataProvider processConnectionSettingsDataProvider
	 *
	 * @param array|null $expected
	 * @param array $connectionSettings
	 * @param string $searchString
	 * @param array|null $stack
	 * @param string|null $message	A message to use if the test fails
	 */
	public function testProcessConnectionSetting($expected, array $connectionSettings, $searchString, $stack = null, $message = null){
		$instance	= new HostManager();
		$method		= new \ReflectionMethod($instance, 'processConnectionSetting');
		$method->setAccessible(true);

		$result	= $method->invoke($instance, $connectionSettings, $searchString, $stack);

		$this->assertEquals($expected, $result, $message);
	}

	public function processConnectionSettingsDataProvider(){
		$items	= [];
		$value	= [
			'item'	=> 'value',
			'other'	=> 'more',
		];

		// Basic
		$items['Basic']	= [
			$value,
			[
				'single'	=> $value
			],
			'single',
			null,
			'Direct matches should be found',
		];

		// Referenced
		$items['Referenced']	= [
			$value,
			[
				'referenced'	=> 'other',
				'other'			=> $value,
			],
			'referenced',
			null,
			'Referenced matches should be followed',
		];

		// Deep referenced
		$items['Deep Referenced']	= [
			$value,
			[
				'first'		=> 'second',
				'second'	=> 'third',
				'third'		=> 'fourth',
				'fourth'	=> $value,
			],
			'first',
			null,
			'Deeply-referenced matches should be followed',
		];

		return $items;
	}

	/**
	 * @covers ::processConnectionSetting
	 * @covers ::<!public>
	 * @expectedException \OverflowException
	 */
	public function testProcessRecursiveConnectionSetting(){
		$this->testProcessConnectionSetting(null, [
			'first'		=> 'second',
			'second'	=> 'third',
			'third'		=> 'second',
		], 'first', null);
	}

	/**
	 * @covers ::processConnectionSetting
	 * @covers ::<!public>
	 * @expectedException \OverflowException
	 */
	public function testProcessExcessiveReferencesConnectionSetting(){
		$settings	= [];
		for($i = 1; $i <= (HostManager::RECURSION_MAX+1); $i++){
			$settings['item-'.$i]	= 'item-'.($i+1);
		}

		$this->testProcessConnectionSetting(null, $settings, 'item-1', null);
	}

	/**
	 * @covers ::processConnectionSetting
	 * @covers ::<!public>
	 * @expectedException \OutOfBoundsException
	 */
	public function testProcessUnknownReferenceConnectionSetting(){
		$this->testProcessConnectionSetting(null, [
			'first'		=> 'unknown',
		], 'first', null);
	}


	/**
	 * @covers ::filterHosts
	 * @covers ::<!public>
	 * @dataProvider filterHostsDataProvider
	 * @param Host[] $expectedFiltered
	 * @param array $groups
	 * @param arary $groupNames
	 */
	public function testFilterHosts(array $expectedFiltered, array $groups, array $groupNames){
		$filtered	= HostManager::filterHosts($groups, $groupNames);

		$this->assertEquals($expectedFiltered, $this->hostsToHostnames($filtered->getArrayCopy()), 'The correct hosts should be returned after filtering');
	}

	public function filterHostsDataProvider(){
		$items	= [];

		// No filtering
		$items['No Filtering']	= [
			['one.example.com', 'two.example.com', 'three.example.com'],
			[
				'test'	=> $this->buildGroup($this->hostnamesToHosts(['one.example.com', 'two.example.com'])),
				'other'	=> $this->buildGroup($this->hostnamesToHosts(['three.example.com'])),
			],
			[],
		];

		// Single group
		$items['Single Group']	= [
			['one.example.com', 'two.example.com'],
			[
				'test'	=> $this->buildGroup($this->hostnamesToHosts(['one.example.com', 'two.example.com'])),
				'other'	=> $this->buildGroup($this->hostnamesToHosts(['three.example.com'])),
			],
			['test'],
		];

		// Multiple groups
		$items['Multiple Groups Multiple Search']	= [
			['one.example.com', 'two.example.com', 'three.example.com'],
			[
				'test'	=> $this->buildGroup($this->hostnamesToHosts(['one.example.com', 'two.example.com'])),
				'other'	=> $this->buildGroup($this->hostnamesToHosts(['three.example.com'])),
			],
			['test', 'other'],
		];

		// Multiple groups
		$items['Multiple Groups Single Search']	= [
			['one.example.com', 'three.example.com'],
			[
				'test'	=> $this->buildGroup($this->hostnamesToHosts(['one.example.com', 'two.example.com'])),
				'other'	=> $this->buildGroup($this->hostnamesToHosts(['three.example.com'])),
				'mix'	=> $this->buildGroup($this->hostnamesToHosts(['one.example.com', 'three.example.com'])),
			],
			['mix'],
		];

		// Multiple groups with duplicates
		$one	= $this->hostnamesToHosts(['one.example.com', 'two.example.com']);
		$two	= $this->hostnamesToHosts(['three.example.com']);
		$two['one.example.com']	= $one['one.example.com'];

		$items['Multiple Groups With Duplicates']	= [
			['one.example.com', 'two.example.com', 'three.example.com'],
			[
				'one'	=> $this->buildGroup($one),
				'two'	=> $this->buildGroup($two),
			],
			['one', 'two'],
		];

		return $items;
	}

	/**
	 * @covers ::filterHosts
	 * @covers ::<!public>
	 * @expectedException \OutOfBoundsException
	 */
	public function testFilterHostsUnknownGroup(){
		$this->testFilterHosts(
			[],
			[
				'groupname'	=> $this->buildGroup($this->hostnamesToHosts(['one.example.com', 'two.example.com'])),
			],
			['unknown']
		);
	}

	/**
	 * @covers ::filterHosts
	 * @covers ::<!public>
	 * @expectedException \InvalidArgumentException
	 */
	public function testFilterHostsInvalidGroup(){
		$this->testFilterHosts(
			[],
			[
				'one'	=> $this->buildGroup($this->hostnamesToHosts(['one.example.com', 'two.example.com'])),
				'two'	=> $this->hostnamesToHosts(['one.example.com', 'two.example.com']),	// Not a group
			],
			['one', 'two']
		);
	}
}
