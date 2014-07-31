<?php
namespace Shellshock;

use Colors\Color;
use Commando\Util\Terminal;

class Controller {
	const FILE_RUNNER	= 'runner.sh';
	const TYPE_ERROR	= 'error';
	const TYPE_SUCCESS	= 'success';
	const TYPE_INFO		= 'info';
	const DIRECTORY_FILES		= 'files';
	const DIRECTORY_SCRIPTS		= 'scripts';
	const DIRECTORY_SETTINGS	= 'settings';

	/** @var bool $safety */
	protected $safety;
	/** @var bool $verbose */
	protected $verbose;
	/** @var array|null $sshDetails */
	protected $sshDetails;

	/**
	 * Runs the application.
	 *
	 * @param array $argv	The arguments passed into the script
	 */
	public function run(array $argv){
		// Load CLI config
		$input	= new Input($argv);
		$this->safety	= $input->get('safety');
		$this->verbose	= $input->get('verbose');

		// Load config file
		$config	= new Config();
		$config->importFile($input->get('configFile'), ['hosts']);
		Group::setSettingsDir($input->get('path').'/'.static::DIRECTORY_SETTINGS);

		// Load hosts
		$hosts	= new HostManager();
		$groups	= $hosts->loadHosts($config->get('hosts'), true);

		// Apply connection settings
		$hosts->applyConnectionSettings($config->get('connections'));

		// Filter hosts by group
		/** @var Host[] $filteredHosts */
		$filteredHosts	= HostManager::filterHosts($groups, $input->get('groups'));

		$this->runCommands($input, $config, $groups, $filteredHosts);
	}

	/**
	 * Determines which commands should be run on the correct hosts, and executes them.
	 *
	 * @param Input $input
	 * @param Config $config
	 * @param array $groups
	 * @param HostManager $allHosts
	 *
	 * @throws \ErrorException
	 */
	protected function runCommands(Input $input, Config $config, array $groups, HostManager $allHosts){
		if($input->get('ping')){
			// Test connections
			foreach($allHosts as $host){
				try {
					$this->lineInfo($host->getHostname(), true);
					$result	= $this->executeCommand($host->buildSSH('echo "success"'), false);
					if($this->safety){
						continue;
					}
					if($result !== 'success'){
						$this->lineError('Unexpected response', true);
						continue;
					}

					$this->lineSuccess('Connected', true);

				} catch(\Exception $e){
					$this->lineError('Could not connect', true);
				}
			}
			return;
		}

		// Handle custom command
		$customCommand	= $input->get('command');
		if(isset($customCommand)){
			foreach($allHosts as $host){
				$this->lineInfo($host->getHostname(), true);
				$result	= $this->executeCommand($host->buildSSH($customCommand), true);
				$this->lineout($result);
			}

			// Do not perform any extra actions
			return;
		}

		$baseDir	= $input->get('path');

		// Reference runner outside phar
		$runner = tempnam(sys_get_temp_dir(), 'shk');
		file_put_contents($runner, file_get_contents(__DIR__.'/../'.static::FILE_RUNNER));

		// Build local paths
		$uploadFiles	= [
			$runner,
			$baseDir.'/'.static::DIRECTORY_FILES,
			$baseDir.'/'.static::DIRECTORY_SCRIPTS,
		];

		foreach($allHosts as $host){
			/** @var Host $host */
			$this->lineInfo($host->getHostname(), true);
			$output	= [];

			// Transfer files
			try {
				// Create directory
				$this->executeCommand($host->buildSSH('mkdir -p '.escapeshellarg($host->getRemoteDir())));
				// Copy files
				$this->executeCommand($host->buildSCP($uploadFiles, $host->getRemoteDir()));
				$this->lineSuccess('Uploaded files');

			} catch(\Exception $e){
				$this->lineError($e);
				continue; // Nothing to cleanup - skip
			}

			// Run scripts
			try {
				$remoteRunner	= escapeshellarg($host->getRemoteDir().'/'.basename($runner));
				$scripts	= $host->getScripts();

				// Filter scripts
				$whitelistedScripts = $input->get('scripts');
				if($whitelistedScripts){
					$scripts	= $whitelistedScripts;
				}

				$args		= array_map('escapeshellarg', $scripts);
				$groups	= [];
				foreach($host->getGroups() as $group){
					$groups[]	= $group->getName();
				}
				array_unshift($args, escapeshellarg(implode("\n", $groups)));
				
				array_unshift($args, escapeshellarg($host->getUsername()));
				
				$this->executeCommand($host->buildSSH('bash '.$remoteRunner.' '.implode(' ', $args), true)); // Output is streamed

				$success	= true;

			} catch(\Exception $e){
				$this->lineError($e, true);
				$success	= false;
				// Still have to cleanup - continue
			}

			// Cleanup
			try {
				$output[]	= $this->executeCommand($host->buildSSH('rm -rf '.escapeshellarg($host->getRemoteDir())));
			} catch(\Exception $e){
				$this->lineError('Could not remove files ('.$host->getRemoteDir().')', true);
				$success	= false;
			}

			$output	= array_filter($output);
			if(!$this->safety && $output){
				// Show detailed output
				$this->lineout(implode("\n\n", $output));
			}

			if($success){
				$this->lineSuccess('Success', true);
			}
		}

		// Cleanup on local machine
		unlink($runner);
	}


	/**
	 * Runs the given command on the local machine.
	 *
	 * @param string $command	The full local command to execute
	 * @return string			The stdout from the command
	 * @throws \ErrorException	The command failed to execute
	 */
	protected function executeCommand($command, $printout = null){
		if($this->safety){
			// Display command only
			$this->lineout($command, true);
			return;
		}

		$environment	= [];
		
		if(!isset($this->sshDetails)){
			// Start SSH session
			ob_start();
			$rawDetails	= shell_exec('ssh-agent');
			ob_end_clean();
			
			$this->sshDetails	= [];
			foreach(explode("\n", $rawDetails) as $detail){
				if(!preg_match('~^([\w_]+)=(.+?);~', $detail, $matches)){
					continue;
				}
	
				$this->sshDetails[$matches[1]]	= $matches[2];
			}
		}
		$environment	= array_merge($environment, $this->sshDetails);

		if(!isset($printout)){
			$printout	= $this->verbose;
		}

		// Actually execute command
		$descriptorSpec = [
			0	=> ['pipe', 'r'],	// stdin
			1	=> ['pipe', 'w'],	// stdout
			2	=> ['pipe', 'w'],	// stderr
		];
		
		$environment['CLICOLOR'] = 1;
		$process = proc_open($command, $descriptorSpec, $pipes, null, $environment);

		// Load contents
		$stdout	= '';
		while($s = fgets($pipes[1])){
			if(preg_match('~^(→ )(Running \'.*?\')(\n?)$~', $s, $matches)){
				// Shellshock separator - do not add to stdout
				if($printout){
					echo $matches[1];
					$this->lineInfo($matches[2], true, false);
					echo $matches[3];
				}
			} else if(preg_match('~^(ERROR: )(.*?)(\n?)$~', $s, $matches)){
				// Shellshock error
				if($printout){
					$this->lineError($matches[2], true, false);
					echo $matches[3];
				}
			} else {
				// Normal output
				$stdout	.= $s;
				if($printout){
					echo $s;
				}
			}
		}
		fclose($pipes[1]);

		$stderr	= stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		// Close process
		$status	= proc_get_status($process);
		proc_close($process);

		// Tidy stderr
		$errorLines	= array_filter(array_map('trim', explode("\n", trim($stderr))));

		$ignoreError	= false;
		if(isset($errorLines[0]) && preg_match("~^Warning: Permanently added '[^']+' \\(\\w+\\) to the list of known hosts\\.$~", $errorLines[0])){
			// Suppress SSH hosts warning
			array_shift($errorLines);
			$ignoreError	= (count($errorLines) === 0);
		}

		$errorOut	= implode(PHP_EOL, $errorLines);
		if($status['exitcode'] !== 0 && !$ignoreError){
			$this->lineError($errorOut);
			throw new \ErrorException('Command failed');

		} else if($errorOut !== ''){
			// Append error output to stdout
			$stdout	.= PHP_EOL.$errorOut;
		}

		return trim($stdout);
	}


	/**
	 * Outputs a string to stdout.
	 *
	 * @param string $line		The string to output
	 * @param bool $always		If true, the line will be output regardless of the verbose settings. If false, only if verbose is enabled.
	 * @param string|null $type	One of static::TYPE_*
	 * @param bool $newline		Whether to output a newline after the line
	 */
	protected function lineout($line, $always = false, $type = null, $newline = true){
		if($line === '' && $type !== static::TYPE_ERROR){
			// Nothing to display
			return;
		}

		if(!$always && !$this->verbose){
			// Suppress detailed message
			return;
		}

		if($line instanceof \Exception){
			$line	= $line->getMessage();
		}

		$colorInstance	= new Color();
		$colorInstance($line);
		switch($type){
			case static::TYPE_SUCCESS:
				$colorInstance("✔︎ ".$line)->bg('green')->bold()->white();
				break;

			case static::TYPE_ERROR:
				Terminal::beep();
				$colorInstance("ERROR: ".$line)->bg('red')->bold()->white();
				break;

			case static::TYPE_INFO:
				$colorInstance($line)->bg('cyan')->white();
				break;
		}

		echo $colorInstance;
		if($newline){
			echo PHP_EOL;
		}
	}

	protected function lineError($message, $always = false, $newline = true){
		$this->lineout($message, $always, static::TYPE_ERROR, $newline);
	}

	protected function lineInfo($message, $always = false, $newline = true){
		$this->lineout($message, $always, static::TYPE_INFO, $newline);
	}

	protected function lineSuccess($message, $always = false, $newline = true){
		$this->lineout($message, $always, static::TYPE_SUCCESS, $newline);
	}
}
