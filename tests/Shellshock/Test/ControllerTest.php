<?php
namespace Shellshock\Test;

use Shellshock\Controller;
use PHPUnit_Framework_MockObject_MockObject	as MockObject;

/**
 * These tests are not ideal, but provide some level of comfort that certain inputs lead to the correct commands.
 *
 * @coversDefaultClass \Shellshock\Controller
 */
class ControllerTest extends \PHPUnit_Framework_TestCase {
	const TARGET_CLASSNAME	= '\\Shellshock\\Controller';
	const REPLACE_DIR		= '%%REPLACE_DIR%%';
	const REPLACE_SOURCE	= '%%REPLACE_SOURCE%%';
	const REPLACE_RUNNER	= '%%REPLACE_RUNNER%%';

	protected static $testFiles;

	private static function getFilePath(){
		if(!isset(static::$testFiles)){
			static::$testFiles	= __DIR__.'/../test-files';
		}

		return static::$testFiles;
	}


	/**
	 * @param array $values	The settings values for the object to hold
	 * @return Controller|MockObject
	 */
	protected function buildController(array $methods = null){
		$methods	= array_merge((array)$methods, ['executeCommand']); // No chance of executing a command!

		$mock	= $this->getMockBuilder(static::TARGET_CLASSNAME)
					   ->setMethods($methods)
					   ->getMock();

		$mock->setUseColor(false);

		return $mock;
	}

	/**
	 * @param array $commands
	 * @param array|null $methods
	 * @return MockObject|Controller
	 */
	protected function buildControllerWithExpectedCommands($commands, &$executedCommands = null, array $methods = null){
		$controller	= $this->buildController($methods);

		$executedCommands	= [];
		$i	= -1;
		$controller->expects($this->exactly(count($commands)))
				   ->method('executeCommand')
				   ->will($this->returnCallback(function($command) use($commands, &$executedCommands, &$i){
					   $return	= null;

					   $i++;
					   $executedCommands[]	= $command;

					   if(isset($commands[$i])){
						   $currentCommand = $commands[$i];
						   if(isset($currentCommand['callback'])){
							   $currentCommand['callback']();
						   }
						   if(isset($currentCommand['return'])){
							   $return	= $currentCommand['return'];
						   }
					   }

					   return $return;
				   }));

		return $controller;
	}


	/**
	 * @covers ::run
	 * @covers ::<!public>
	 */
	public function testRunWithMissingArgument(){
		$args		= [];
		$commands	= [];

		$controller	= $this->buildController();

		array_unshift($args, '__placeholder__');

		try {
			$controller->run($args);
		} catch(\Exception $e){
			$isCorrectException	= (bool)preg_match('~Required argument .+? must be specified~', $e->getMessage());
			$this->assertTrue($isCorrectException, 'Missing inputs should trigger an exception');
		}
	}


	/**
	 * @covers ::run
	 * @covers ::<!public>
	 * @dataProvider runDataProvider
	 */
	public function testRun(array $commands, $stdout, array $args, array $stdoutFilters = null){
		$controller	= $this->buildControllerWithExpectedCommands($commands, $executedCommands);

		array_unshift($args, '__placeholder__');

		ob_start();
		$controller->run($args);

		// Verify stdout
		$result	= ob_get_clean();
		foreach((array)$stdoutFilters as $find => $replace){
			// Apply custom replacement filters
			$result	= preg_replace('~'.$find.'~', $replace, $result);
		}
		$this->assertEquals($stdout, $result, 'The stdout should be correct');

		// Verify commands
		$replace	= '[/\w\d_ \-\.]+';
		$find		= [
			static::REPLACE_DIR		=> $replace,
			static::REPLACE_SOURCE	=> $replace,
			static::REPLACE_RUNNER	=> $replace,
		];
		$count	= 0;
		foreach($commands as $expectedCommand){
			$count++;

			if(is_array($expectedCommand)){
				$expectedCommand	= $expectedCommand['command'];
			}

			$pattern	= preg_quote($expectedCommand, '~');
			$pattern	= str_replace(array_keys($find), array_values($find), $pattern);
			$pattern	= '~^'.$pattern.'$~';

			$found	= false;
			foreach($executedCommands as $command){
				if(preg_match($pattern, $command)){
					$found	= true;
				}
			}

			$this->assertTrue($found, 'Each command should have been executed'."\n".$expectedCommand."\n\n".implode("\n", $executedCommands));
		}
		$this->assertEquals(count($commands), count($executedCommands), 'Only the specified commands should have been executed'."\n".implode("\n", $executedCommands));
	}

	public function runDataProvider(){
		$die	= function(){
			throw new \Exception('Simulated error');
		};
		$REPLACE_DIR	= static::REPLACE_DIR;
		$REPLACE_RUNNER	= static::REPLACE_RUNNER;
		$REPLACE_SOURCE	= static::REPLACE_SOURCE;

		$items	= [];
		// Normal operation
		$items['Normal']	= [
			[
				"ssh 'root'@'127.0.0.2' 'mkdir -p '\\''".static::REPLACE_DIR."'\\'''",
				"scp -r '".static::REPLACE_RUNNER."' '".static::REPLACE_SOURCE."/files' '".static::REPLACE_SOURCE."/scripts' 'root'@'127.0.0.2':'".static::REPLACE_DIR."'",
				"ssh 'root'@'127.0.0.2' 'sudo -s bash '\\''".static::REPLACE_RUNNER."'\\'' '\\''root'\\'' '\\''_default"."\n"
					."web'\\'' '\\''script1.sh'\\'' '\\''script2.sh'\\'' '\\''script-web.sh'\\'''",
				"ssh 'root'@'127.0.0.2' 'rm -rf '\\''".static::REPLACE_DIR."'\\'''",

				"ssh 'root'@'127.0.0.3' 'mkdir -p '\\''".static::REPLACE_DIR."'\\'''",
				"scp -r '".static::REPLACE_RUNNER."' '".static::REPLACE_SOURCE."/files' '".static::REPLACE_SOURCE."/scripts' 'root'@'127.0.0.3':'".static::REPLACE_DIR."'",
				"ssh 'root'@'127.0.0.3' 'sudo -s bash '\\''".static::REPLACE_RUNNER."'\\'' '\\''root'\\'' '\\''_default"."\n"
					."db'\\'' '\\''script1.sh'\\'' '\\''script2.sh'\\'''",
				"ssh 'root'@'127.0.0.3' 'rm -rf '\\''".static::REPLACE_DIR."'\\'''",

				"ssh 'root'@'127.0.0.4' 'mkdir -p '\\''".static::REPLACE_DIR."'\\'''",
				"scp -r '".static::REPLACE_RUNNER."' '".static::REPLACE_SOURCE."/files' '".static::REPLACE_SOURCE."/scripts' 'root'@'127.0.0.4':'".static::REPLACE_DIR."'",
				"ssh 'root'@'127.0.0.4' 'sudo -s bash '\\''".static::REPLACE_RUNNER."'\\'' '\\''root'\\'' '\\''_default"."\n"
					."db'\\'' '\\''script1.sh'\\'' '\\''script2.sh'\\'''",
				"ssh 'root'@'127.0.0.4' 'rm -rf '\\''".static::REPLACE_DIR."'\\'''",
			],
			<<<EOD
127.0.0.2
✔ Success
127.0.0.3
✔ Success
127.0.0.4
✔ Success

EOD
			,
			[static::getFilePath()],
		];

		// Failure during upload - no cleanup should happen
		$items['Upload Failure']	= [
			[
				[
					'command'	=> "ssh 'root'@'127.0.0.2' 'mkdir -p '\\''".static::REPLACE_DIR."'\\'''",
					'callback'	=> $die,
				],

				"ssh 'root'@'127.0.0.3' 'mkdir -p '\\''".static::REPLACE_DIR."'\\'''",
				"scp -r '".static::REPLACE_RUNNER."' '".static::REPLACE_SOURCE."/files' '".static::REPLACE_SOURCE."/scripts' 'root'@'127.0.0.3':'".static::REPLACE_DIR."'",
				"ssh 'root'@'127.0.0.3' 'sudo -s bash '\\''".static::REPLACE_RUNNER."'\\'' '\\''root'\\'' '\\''_default"."\n"
					."db'\\'' '\\''script1.sh'\\'' '\\''script2.sh'\\'''",
				"ssh 'root'@'127.0.0.3' 'rm -rf '\\''".static::REPLACE_DIR."'\\'''",

				"ssh 'root'@'127.0.0.4' 'mkdir -p '\\''".static::REPLACE_DIR."'\\'''",
				"scp -r '".static::REPLACE_RUNNER."' '".static::REPLACE_SOURCE."/files' '".static::REPLACE_SOURCE."/scripts' 'root'@'127.0.0.4':'".static::REPLACE_DIR."'",
				"ssh 'root'@'127.0.0.4' 'sudo -s bash '\\''".static::REPLACE_RUNNER."'\\'' '\\''root'\\'' '\\''_default"."\n"
					."db'\\'' '\\''script1.sh'\\'' '\\''script2.sh'\\'''",
				"ssh 'root'@'127.0.0.4' 'rm -rf '\\''".static::REPLACE_DIR."'\\'''",
			],
			<<<EOD
127.0.0.2
127.0.0.3
✔ Success
127.0.0.4
✔ Success

EOD
			,
			[static::getFilePath()],
		];

		// Failure during operation - must cleanup
		$items['Normal Failure']	= [
			[
				"ssh 'root'@'127.0.0.2' 'mkdir -p '\\''".static::REPLACE_DIR."'\\'''",
				"scp -r '".static::REPLACE_RUNNER."' '".static::REPLACE_SOURCE."/files' '".static::REPLACE_SOURCE."/scripts' 'root'@'127.0.0.2':'".static::REPLACE_DIR."'",
				[
					'command'	=> "ssh 'root'@'127.0.0.2' 'sudo -s bash '\\''".static::REPLACE_RUNNER."'\\'' '\\''root'\\'' '\\''_default"."\n"
										."web'\\'' '\\''script1.sh'\\'' '\\''script2.sh'\\'' '\\''script-web.sh'\\'''",
					'callback'	=> $die,
				],
				"ssh 'root'@'127.0.0.2' 'rm -rf '\\''".static::REPLACE_DIR."'\\'''",

				"ssh 'root'@'127.0.0.3' 'mkdir -p '\\''".static::REPLACE_DIR."'\\'''",
				"scp -r '".static::REPLACE_RUNNER."' '".static::REPLACE_SOURCE."/files' '".static::REPLACE_SOURCE."/scripts' 'root'@'127.0.0.3':'".static::REPLACE_DIR."'",
				"ssh 'root'@'127.0.0.3' 'sudo -s bash '\\''".static::REPLACE_RUNNER."'\\'' '\\''root'\\'' '\\''_default"."\n"
					."db'\\'' '\\''script1.sh'\\'' '\\''script2.sh'\\'''",
				"ssh 'root'@'127.0.0.3' 'rm -rf '\\''".static::REPLACE_DIR."'\\'''",

				"ssh 'root'@'127.0.0.4' 'mkdir -p '\\''".static::REPLACE_DIR."'\\'''",
				"scp -r '".static::REPLACE_RUNNER."' '".static::REPLACE_SOURCE."/files' '".static::REPLACE_SOURCE."/scripts' 'root'@'127.0.0.4':'".static::REPLACE_DIR."'",
				"ssh 'root'@'127.0.0.4' 'sudo -s bash '\\''".static::REPLACE_RUNNER."'\\'' '\\''root'\\'' '\\''_default"."\n"
					."db'\\'' '\\''script1.sh'\\'' '\\''script2.sh'\\'''",
				"ssh 'root'@'127.0.0.4' 'rm -rf '\\''".static::REPLACE_DIR."'\\'''",
			],
			<<<EOD
127.0.0.2
ERROR: Simulated error
127.0.0.3
✔ Success
127.0.0.4
✔ Success

EOD
			,
			[static::getFilePath()],
		];

		// Failure during cleanup... :|
		$items['Cleanup Failure']	= [
			[
				"ssh 'root'@'127.0.0.2' 'mkdir -p '\\''".static::REPLACE_DIR."'\\'''",
				"scp -r '".static::REPLACE_RUNNER."' '".static::REPLACE_SOURCE."/files' '".static::REPLACE_SOURCE."/scripts' 'root'@'127.0.0.2':'".static::REPLACE_DIR."'",
				"ssh 'root'@'127.0.0.2' 'sudo -s bash '\\''".static::REPLACE_RUNNER."'\\'' '\\''root'\\'' '\\''_default"."\n"
					."web'\\'' '\\''script1.sh'\\'' '\\''script2.sh'\\'' '\\''script-web.sh'\\'''",
				[
					'command'	=> "ssh 'root'@'127.0.0.2' 'rm -rf '\\''".static::REPLACE_DIR."'\\'''",
					'callback'	=> $die,
				],

				"ssh 'root'@'127.0.0.3' 'mkdir -p '\\''".static::REPLACE_DIR."'\\'''",
				"scp -r '".static::REPLACE_RUNNER."' '".static::REPLACE_SOURCE."/files' '".static::REPLACE_SOURCE."/scripts' 'root'@'127.0.0.3':'".static::REPLACE_DIR."'",
				"ssh 'root'@'127.0.0.3' 'sudo -s bash '\\''".static::REPLACE_RUNNER."'\\'' '\\''root'\\'' '\\''_default"."\n"
					."db'\\'' '\\''script1.sh'\\'' '\\''script2.sh'\\'''",
				"ssh 'root'@'127.0.0.3' 'rm -rf '\\''".static::REPLACE_DIR."'\\'''",

				"ssh 'root'@'127.0.0.4' 'mkdir -p '\\''".static::REPLACE_DIR."'\\'''",
				"scp -r '".static::REPLACE_RUNNER."' '".static::REPLACE_SOURCE."/files' '".static::REPLACE_SOURCE."/scripts' 'root'@'127.0.0.4':'".static::REPLACE_DIR."'",
				"ssh 'root'@'127.0.0.4' 'sudo -s bash '\\''".static::REPLACE_RUNNER."'\\'' '\\''root'\\'' '\\''_default"."\n"
					."db'\\'' '\\''script1.sh'\\'' '\\''script2.sh'\\'''",
				"ssh 'root'@'127.0.0.4' 'rm -rf '\\''".static::REPLACE_DIR."'\\'''",
			],
			<<<EOD
127.0.0.2
ERROR: Could not remove files (${REPLACE_DIR})
127.0.0.3
✔ Success
127.0.0.4
✔ Success

EOD
			,
			[static::getFilePath()],
			['(Could not remove files \().+?(\))'	=> '$1'.static::REPLACE_DIR.'$2'], // Clear directory from stdout
		];

		return $items;
	}

	/**
	 * @covers ::run
	 * @covers ::<!public>
	 */
	public function testRunPing(){
		$die	= function(){
			throw new \Exception('Simulated error');
		};
		$REPLACE_DIR	= static::REPLACE_DIR;
		$REPLACE_RUNNER	= static::REPLACE_RUNNER;
		$REPLACE_SOURCE	= static::REPLACE_SOURCE;

		$this->testRun(
			[
				[
					'command'	=> "ssh 'root'@'127.0.0.2' 'echo \"success\"'",
					'return'	=> 'success',
				],
				[
					'command'	=> "ssh 'root'@'127.0.0.3' 'echo \"success\"'",
					'callback'	=> $die,
				],
				[
					'command'	=> "ssh 'root'@'127.0.0.4' 'echo \"success\"'",
					'return'	=> null,
				],
			],
			<<<EOD
127.0.0.2
✔ Connected
127.0.0.3
ERROR: Could not connect
127.0.0.4
ERROR: Unexpected response

EOD
			,
			[static::getFilePath(), '--ping']
		);
	}

	/**
	 * @covers ::run
	 * @covers ::<!public>
	 */
	public function testRunCustomCommand(){
		$die	= function(){
			throw new \Exception('Simulated error');
		};
		$REPLACE_DIR	= static::REPLACE_DIR;
		$REPLACE_RUNNER	= static::REPLACE_RUNNER;
		$REPLACE_SOURCE	= static::REPLACE_SOURCE;

		$this->testRun(
			[
				[
					'command'	=> "ssh 'root'@'127.0.0.2' 'ls -l'",
					'return'	=> 'output one',
				],
				[
					'command'	=> "ssh 'root'@'127.0.0.3' 'ls -l'",
					'return'	=> 'output two',
				],
				[
					'command'	=> "ssh 'root'@'127.0.0.4' 'ls -l'",
					'return'	=> 'output three',
				],
			],
			<<<EOD
127.0.0.2
output one
127.0.0.3
output two
127.0.0.4
output three

EOD
			,
			[static::getFilePath(), '--command', 'ls -l', '-v']
		);
	}

	/**
	 * @covers ::run
	 * @covers ::<!public>
	 */
	public function testRunGroups(){
		$die	= function(){
			throw new \Exception('Simulated error');
		};
		$REPLACE_DIR	= static::REPLACE_DIR;
		$REPLACE_RUNNER	= static::REPLACE_RUNNER;
		$REPLACE_SOURCE	= static::REPLACE_SOURCE;

		$this->testRun(
			[
				"ssh 'root'@'127.0.0.2' 'mkdir -p '\\''".static::REPLACE_DIR."'\\'''",
				"scp -r '".static::REPLACE_RUNNER."' '".static::REPLACE_SOURCE."/files' '".static::REPLACE_SOURCE."/scripts' 'root'@'127.0.0.2':'".static::REPLACE_DIR."'",
				"ssh 'root'@'127.0.0.2' 'sudo -s bash '\\''".static::REPLACE_RUNNER."'\\'' '\\''root'\\'' '\\''_default"."\n"
					."web'\\'' '\\''script1.sh'\\'' '\\''script2.sh'\\'' '\\''script-web.sh'\\'''",
				"ssh 'root'@'127.0.0.2' 'rm -rf '\\''".static::REPLACE_DIR."'\\'''",
			],
			<<<EOD
127.0.0.2
✔ Success

EOD
			,
			[static::getFilePath(), '--groups', 'web']
		);
	}

	/**
	 * @covers ::run
	 * @covers ::<!public>
	 */
	public function testRunScripts(){
		$die	= function(){
			throw new \Exception('Simulated error');
		};
		$REPLACE_DIR	= static::REPLACE_DIR;
		$REPLACE_RUNNER	= static::REPLACE_RUNNER;
		$REPLACE_SOURCE	= static::REPLACE_SOURCE;

		$this->testRun(
			[
				"ssh 'root'@'127.0.0.2' 'mkdir -p '\\''".static::REPLACE_DIR."'\\'''",
				"scp -r '".static::REPLACE_RUNNER."' '".static::REPLACE_SOURCE."/files' '".static::REPLACE_SOURCE."/scripts' 'root'@'127.0.0.2':'".static::REPLACE_DIR."'",
				"ssh 'root'@'127.0.0.2' 'sudo -s bash '\\''".static::REPLACE_RUNNER."'\\'' '\\''root'\\'' '\\''_default"."\n"
					."web'\\'' '\\''script1.sh'\\'''",
				"ssh 'root'@'127.0.0.2' 'rm -rf '\\''".static::REPLACE_DIR."'\\'''",
			],
			<<<EOD
127.0.0.2
✔ Success

EOD
			,
			[static::getFilePath(), '--scripts', 'script1.sh', '--groups', 'web']
		);
	}
}
