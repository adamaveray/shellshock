<?php
namespace Shellshock;

use Commando\Command	as Command;

class Input extends Settings {
	const DEFAULT_CONFIG_FILE	= 'shellshock.json';
	protected static $requiredDirs	= ['files', 'scripts', 'settings'];

	/** @var Command $cli */
	protected $cli;
	/** @var array $settings */
	protected $settings;

	/**
	 * @param array $args	The raw arguments passed to the script
	 */
	public function __construct(array $args){
		$this->cli	= new Command($args);
		$this->cli->doNotTrapErrors();

		$this->prepareOptions();
	}

	/**
	 * @return array	An array with the following keys:
	 *	 - string 'path'		The path to a Shellshock directory
	 *	 - string 'configFile'	The path to a Shellshock configuration file
	 *	 - array 'groups'		An array of group names
	 *	 - array 'scripts'		An array of relative paths to scripts
	 *	 - string 'command'		A command to execute on the remote host
	 *
	 * Additionally, the following debug boolean flags will be set:
	 *	 - bool 'ping'
	 *	 - bool 'safety'
	 *	 - bool 'verbose'
	 */
	public function getSettings(){
		if(!isset($this->settings)){
			$cli	= $this->cli;

			$path	= $this->processPath($cli[0]);
			$this->settings	= [
				'path'			=> $path,
				'configFile'	=> $this->processConfigFile($cli['config'], $path),
				'groups'		=> $this->processList($cli['groups']),
				'scripts'		=> $this->processList($cli['scripts']),
				'command'		=> $cli['command'],

				// Debug
				'ping'			=> $cli['ping'],
				'safety'		=> $cli['safety'],
				'verbose'		=> $cli['verbose'],
			];
		}
		return $this->settings;
	}

	/**
	 * Sets up the input parser to handle the allowed arguments to the script
	 */
	protected function prepareOptions(){
		$cli	= $this->cli;

		// Main argument
		$cli->option()
			->required()
			->describedAs('The path to the Shellshock directory');

		// Normal flags
		$cli->option('config')
			->aka('c')
			->describedAs('The path to the Shellshock config file');

		$cli->option('groups')
			->aka('g')
			->describedAs('Which host groups to use');

		$cli->option('scripts')
			->describedAs('A list of scripts to run instead of group-determined scripts');

		$cli->option('command')
			->describedAs('A command to run on the matched hosts. If provided, no provisioning will take place.');


		// Debug flags
		$cli->flag('ping')
			->boolean()
			->describedAs('Attempt connecting to hosts. No provisioning will take place.');

		$cli->flag('safety')
			->boolean()
			->describedAs('Output commands to be run, without running any');

		$cli->flag('verbose')
			->aka('v')
			->boolean()
			->describedAs('Output information from scripts');
	}

	/**
	 * @param string $path	A relative path to a Shellshock directory passed as an argument
	 * @return string		The absolute path on the local machine
	 *
	 * @throws \InvalidArgumentException	The directory does not exist, or is not a valid Shellshock directory
	 */
	protected function processPath($path){
		$path	= rtrim($path, '/');
		
		$path	= Utilities::normalizePath($path);
		if(!$this->pathIsDir($path)){
			throw new \InvalidArgumentException('Cannot read Shellshock directory');
		}

		if(!$this->pathHasDirs($path, static::$requiredDirs)){
			throw new \InvalidArgumentException('Directory "'.$path.'" does not contain Shellshock directories');
		}
		
		return $path;
	}

	/**
	 * @param string $file			The path to a Shellshock configuration file passed as an argument
	 * @param string $fallbackDir	A directory to look for the config file in if $file is not given
	 * @return string				The absolute path to the config file
	 */
	protected function processConfigFile($file, $fallbackDir){
		if(isset($file)){
			$file	= Utilities::normalizePath($file);
		} else {
			// Use default
			$file	= $fallbackDir.'/'.static::DEFAULT_CONFIG_FILE;
		}
		
		if(!$this->pathIsConfigFile($file)){
			throw new \RuntimeException('Cannot read Shellshock config file ('.$file.')');
		}
		
		return $file;
	}

	/**
	 * @param string $list	A comma-separated list of values
	 * @return array		The list separated into an array of strings
	 */
	protected function processList($list){
		$items	= array_map('trim', explode(',', $list));
		if(count($items) === 1 && $items[0] === ''){
			$items	= [];
		}

		return $items;
	}


	/**
	 * @param string $path	The path to a directory to check
	 * @param array $dirs	The directories to check for within the given directory
	 * @return bool	Whether the directory contains all the required directories
	 */
	protected function pathHasDirs($path, array $dirs){
		foreach($dirs as $dir){
			if(!$this->pathIsDir($path.'/'.$dir)){
				return false;
			}
		}

		return true;
	}

	/**
	 * @param string $path	The path to check if is a directory
	 * @return bool			Whether the given path is a directory
	 *
	 * @codeCoverageIgnore
	 */
	protected function pathIsDir($path){
		return is_dir($path);
	}

	/**
	 * @param string $path	The path to check if is a valid configuration file
	 * @return bool			Wether the given path is a valid configuration file
	 *
	 * @codeCoverageIgnore
	 */
	protected function pathIsConfigFile($path){
		return file_exists($path);
	}
}
