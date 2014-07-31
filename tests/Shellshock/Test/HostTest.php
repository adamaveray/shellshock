<?php
namespace Shellshock\Test;

use Shellshock\Group;
use Shellshock\Host;
use PHPUnit_Framework_MockObject_MockObject	as MockObject;

/**
 * @coversDefaultClass \Shellshock\Host
 */
class HostTest extends \PHPUnit_Framework_TestCase {
	const TARGET_CLASSNAME	= '\\Shellshock\\Host';

	/**
	 * @return Host|MockObject
	 */
	protected function buildHost(array $methods = null){
		$mock	= $this->getMockBuilder(static::TARGET_CLASSNAME)
					   ->disableOriginalConstructor()
					   ->setMethods($methods)
					   ->getMock();

		return $mock;
	}

	protected function getProtectedProperty(Host $host, $name){
		$mirror		= new \ReflectionClass($host);
		$property	= $mirror->getProperty($name);
		$property->setAccessible(true);

		return $property->getValue($host);
	}

	protected function setProtectedProperties(Host $host, array $properties){
		$mirror		= new \ReflectionClass($host);

		foreach($properties as $name => $value){
			$property	= $mirror->getProperty($name);
			$property->setAccessible(true);
			$property->setValue($host, $value);
		}
	}

	protected function setProperties(Host $host, array $properties){
		foreach($properties as $name => $value){
			$host->{'set'.$name}($value);
		}
	}


// Properties
	/**
	 * @covers ::__construct
	 * @covers ::getHostname
	 * @covers ::setHostname
	 * @covers ::<!public>
	 */
	public function testHostname(){
		$value	= 'example.com';

		$host	= new Host($value);
		$this->assertEquals($value, $host->getHostname(), 'Hostname should be stored correctly');

		$value	= 'other.example.com';
		$host->setHostname($value);
		$this->assertEquals($value, $host->getHostname(), 'Hostname should be updated correctly');
	}

	/**
	 * @covers ::setPort
	 * @covers ::<!public>
	 */
	public function testPort(){
		$value	= 1234;

		$host	= $this->buildHost();
		$host->setPort($value);

		$this->assertEquals($value, $this->getProtectedProperty($host, 'port'), 'Port should be stored correctly');
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testInvalidPort(){
		$value	= 'not a port number';

		$host	= $this->buildHost();
		$host->setPort($value);
	}

	/**
	 * @covers ::getUsername
	 * @covers ::setUsername
	 * @covers ::<!public>
	 */
	public function testUsername(){
		$value	= 'somebody';

		$host	= $this->buildHost();
		$host->setUsername($value);

		$this->assertEquals($value, $host->getUsername(), 'Username should be stored correctly');
	}

	/**
	 * @covers ::setIdentityFile
	 * @covers ::<!public>
	 */
	public function testIdentityFile(){
		$value	= '/path/to/identity';

		$host	= $this->buildHost();
		$host->setIdentityFile($value);

		$this->assertEquals($value, $this->getProtectedProperty($host, 'identityFile'), 'Identity File should be stored correctly');
	}

	/**
	 * @covers ::setVerifyHost
	 * @covers ::<!public>
	 */
	public function testVerifyHost(){
		$host	= $this->buildHost();

		$value	= true;
		$host->setVerifyHost($value);
		$this->assertEquals($value, $this->getProtectedProperty($host, 'verifyHost'), 'Verify host flag should be stored correctly');

		$value	= 'not a boolean';
		$host->setVerifyHost($value);
		$this->assertInternalType('bool', $this->getProtectedProperty($host, 'verifyHost'), 'Verify host flag should be stored as a boolean');
	}

	/**
	 * @covers ::setUseSudo
	 * @covers ::<!public>
	 */
	public function testUseSudo(){
		$host	= $this->buildHost();

		$value	= true;
		$host->setUseSudo($value);
		$this->assertEquals($value, $this->getProtectedProperty($host, 'useSudo'), 'Sudo status should be stored correctly');

		$value	= 'not a boolean';
		$host->setUseSudo($value);
		$this->assertInternalType('boolean', $this->getProtectedProperty($host, 'useSudo'), 'Sudo status should be stored as a boolean');
	}

	/**
	 * @covers ::setSudoPassword
	 * @covers ::<!public>
	 */
	public function testSudoPassword(){
		$host	= $this->buildHost();

		$value	= 'secret password';
		$host->setSudoPassword($value);
		$this->assertEquals($value, $this->getProtectedProperty($host, 'sudoPassword'), 'Sudo password should be stored correctly');
	}

	/**
	 * @covers ::getScripts
	 * @covers ::addScripts
	 * @covers ::<!public>
	 */
	public function testScripts(){
		$host	= $this->buildHost();

		$this->assertEquals([], $host->getScripts(), 'An empty array should be returned if no scripts have been set');

		$scripts	= ['script1.sh', 'script2.sh'];
		$host->addScripts($scripts);
		$this->assertEquals($scripts, $host->getScripts(), 'Scripts should be stored correctly');

		$script	= 'script3.sh';
		$host->addScripts([$script]);
		$scripts[]	= $script;
		$this->assertEquals($scripts, $host->getScripts(), 'Additional scripts should be merged correctly');

		$scripts[]	= 'script4.sh';
		$scripts[]	= 'script5.sh';
		$host->addScripts($scripts); // Re-adds the originals plus these new ones
		$this->assertEquals($scripts, $host->getScripts(), 'Duplicate scripts should be ignored');

		$script	= 'script6.sh';
		$host->addScripts($script);	// Not wrapped in an array
		$scripts[]	= $script;
		$this->assertEquals($scripts, $host->getScripts(), 'Single scripts should be added to others');
	}

	/**
	 * @covers ::getGroups
	 * @covers ::addGroup
	 * @covers ::<!public>
	 */
	public function testGroups(){
		$host = $this->buildHost();

		$this->assertEquals([], $host->getGroups(), 'An empty array should be returned if no groups have been set');

		$groups = [
			new Group('group-one'),
			new Group('group-two'),
		];
		foreach($groups as $group){
			$host->addGroup($group);
		}
		$this->assertEquals($groups, $host->getGroups(), 'Groups should be stored correctly');

		$host->addGroup($groups[0]);
		$this->assertEquals($groups, $host->getGroups(), 'Duplicate groups should be ignored');

		$group	= new Group('group-one');
		$host->addGroup($group);
		$groups[]	= $group;
		$this->assertEquals($groups, $host->getGroups(), 'Different instances of same-named groups should be added');
	}


// Actual Complex Methods
	/**
	 * @covers ::getRemoteDir
	 * @covers ::<!public>
	 */
	public function testGetRemoteDir(){
		$host	= $this->buildHost();
		$result	= $host->getRemoteDir();
		$this->assertRegExp('~^/tmp/shellshock-[^/\.]+$~', $result, 'Remote directory should be in temporary directory');
		$this->assertEquals($result, $host->getRemoteDir(), 'The same directory should be returned for subsequent calls');

		$host	= $this->buildHost();
		$otherResult	= $host->getRemoteDir();
		$this->assertNotEquals($result, $otherResult, 'Different hosts should use different temporary directories');
	}


	/**
	 * @covers ::getBase
	 * @covers ::<!public>
	 * @dataProvider getBaseDataProvider
	 *
	 * @param string $expected		The expected base string
	 * @param array $properties		Properties to set on the Host object
	 * @param string|null $message	A message to use if the test fails
	 */
	public function testGetBase($expected, array $properties, $message = null){
		$host	= $this->buildHost();
		$this->setProperties($host, $properties);

		// Access protected method
		$reflectionMethod	= new \ReflectionMethod(static::TARGET_CLASSNAME, 'getBase');
		$reflectionMethod->setAccessible(true);
		$result	= $reflectionMethod->invoke($host);

		$this->assertEquals($expected, $result, $message);
	}

	public function getBaseDataProvider(){
		return [
			'Plain hostname'	=> [
				"'example.com'",
				[
					'hostname'	=> 'example.com',
				],
				'Plain hostnames should be handled',
			],

			'Plain IP'	=> [
				"'127.0.0.1'",
				[
					'hostname'	=> '127.0.0.1',
				],
				'Plain IP addresses should be handled',
			],

			'Usernames'	=> [
				"'somebody'@'example.com'",
				[
					'hostname'	=> 'example.com',
					'username'	=> 'somebody',
				],
				'Usernames should be added',
			],
		];
	}


	/**
	 * @covers ::buildCommon
	 * @covers ::<!public>
	 * @dataProvider buildCommonDataProvider
	 *
	 * @param string $expected		The expected common string
	 * @param array $properties		Properties to set on the Host object
	 * @param string|null $message	A message to use if the test fails
	 */
	public function testBuildCommon($expected, array $properties, $message = null){
		$host	= $this->buildHost();
		$this->setProperties($host, $properties);

		// Access protected method
		$reflectionMethod	= new \ReflectionMethod(static::TARGET_CLASSNAME, 'buildCommon');
		$reflectionMethod->setAccessible(true);
		$result	= $reflectionMethod->invoke($host);

		$this->assertEquals($expected, $result, $message);
	}

	public function buildCommonDataProvider(){
		return [
			'Basic'	=> [
				"",
				[
					'hostname'	=> 'example.com',
				],
				'Basic configurations should have no common arguments',
			],
			'Identity File'	=> [
				"-i '/path/to/identity'",
				[
					'hostname'		=> 'example.com',
					'identityFile'	=> '/path/to/identity',
				],
				'Identity file arguments should be added',
			],
			'Port'	=> [
				"-p 1234",
				[
					'hostname'		=> 'example.com',
					'port'			=> 1234,
				],
				'Port arguments should be added',
			],
			[
				"-o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null",
				[
					'hostname'		=> 'example.com',
					'verifyHost'	=> false,
				],
				'Host verification arguments should be added',
			],
			[
				"-i '/path/to/identity' -p 1234",
				[
					'hostname'		=> 'example.com',
					'identityFile'	=> '/path/to/identity',
					'port'			=> 1234,
				],
				'Multiple arguments should be concatenated correctly',
			],
		];
	}


	/**
	 * @covers ::buildSSH
	 * @covers ::<!public>
	 * @dataProvider buildSSHDataProvider
	 *
	 * @param string $expected		The expected SSH command to be generated
	 * @param array $properties		Properties to set on the Host object
	 * @param string $command		The command to generate the SSH command to execute
	 * @param bool $needsSudo		Whether the command needs sudo to execute
	 * @param string|null $message	A message to use if the test fails
	 */
	public function testBuildSSH($expected, array $properties, $command, $needsSudo = false, $message = null){
		$host	= $this->buildHost();
		$this->setProperties($host, $properties);
		$this->assertEquals($expected, $host->buildSSH($command, $needsSudo), $message);
	}

	public function buildSSHDataProvider(){
		return [
			[
				"ssh 'example.com' 'ls /'",
				[
					'hostname'	=> 'example.com',
				],
				'ls /',
				false,
				'SSH commands should be build correctly',
			],
			[
				"ssh 'example.com' 'ls /'",
				[
					'hostname'	=> 'example.com',
				],
				'ls /',
				true,
				'Sudoable commands should not use sudo if the host does not support it',
			],
			[
				"ssh 'example.com' 'ls /'",
				[
					'hostname'	=> 'example.com',
					'useSudo'	=> true,
				],
				'ls /',
				false,
				'Commands should not use sudo unless specified',
			],
			[
				"ssh 'example.com' 'sudo -s ls /'",
				[
					'hostname'	=> 'example.com',
					'useSudo'	=> true,
				],
				'ls /',
				true,
				'Sudoable commands should use sudo if the host supports it',
			],
			[
				"ssh 'someuser'@'example.com' -i '/path/to/file' -p 1234 -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null ' echo '\\''secret password'\\'' | sudo -s -p \"\" -k -S ls /'",
				[
					'hostname'		=> 'example.com',
					'useSudo'		=> true,
					'sudoPassword'	=> 'secret password',
					'username'		=> 'someuser',
					'port'			=> 1234,
					'identityFile'	=> '/path/to/file',
					'verifyHost'	=> false,
				],
				'ls /',
				true,
				'SSH commands should use all applicable options',
			],
		];
	}


	/**
	 * @covers ::buildSCP
	 * @covers ::<!public>
	 * @dataProvider buildSCPDataProvider
	 *
	 * @param string $expected
	 * @param array $properties
	 * @param string $from
	 * @param string $to
	 * @param string|null $message
	 */
	public function testBuildSCP($expected, array $properties, $from, $to, $message = null){
		$host	= $this->buildHost();
		$this->setProperties($host, $properties);
		$this->assertEquals($expected, $host->buildSCP($from, $to), $message);
	}

	public function buildSCPDataProvider(){
		return [
			'Single File'		=> [
				"scp -r '/path/on/local' 'example.com':'/path/on/remote'",
				[
					'hostname'	=> 'example.com',
				],
				'/path/on/local',
				'/path/on/remote',
				'SCP commands should be build correctly',
			],
			'Multiple Files'	=> [
				"scp -r '/path/on/local-1' '/path/on/local-2' 'example.com':'/path/on/remote'",
				[
					'hostname'	=> 'example.com',
				],
				['/path/on/local-1', '/path/on/local-2'],
				'/path/on/remote',
				'SCP commands should be build correctly',
			],
		];
	}
}
