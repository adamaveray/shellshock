<?php
namespace Shellshock;

class Config extends Settings {
	/** @var array $config */
	protected $config	= [];

	/**
	 * @param array $config	Config settings
	 */
	public function import(array $config){
		$this->config	= array_merge($this->config, $config);
	}

	/**
	 * @param string $path			The path to a config file
	 * @param array $requiredKeys	Keys the config data must have to be valid
	 * @throws \RuntimeException 	The config file cannot be read or is invalid
	 */
	public function importFile($path, array $requiredKeys = null){
		// Parse file
		$config	= @json_decode($this->getFileContents($path), true);
		if(!isset($config)){
			throw new \RuntimeException('Cannot read config');
		}

		// Verify contents
		foreach((array)$requiredKeys as $key){
			if(!isset($config[$key])){
				throw new \RuntimeException('Config file does not contain "'.$key.'"');
			}
		}

		$this->import($config);
	}

	/**
	 * @return array	The stored settings data
	 */
	public function getSettings(){
		return $this->config;
	}

	/**
	 * @param string $name	The name to store the value under
	 * @param mixed $value	The value to store under the given name
	 */
	public function set($name, $value){
		$this->config[$name]	= $value;
	}


	/**
	 * @param string $path
	 * @return string|false
	 * @codeCoverageIgnore
	 */
	protected function getFileContents($path){
		return file_get_contents($path);
	}
};
