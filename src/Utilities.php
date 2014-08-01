<?php
namespace Shellshock;

class Utilities {
	/** @var string $homeDir */
	protected static $homeDir;

	/**
	 * @codeCoverageIgnore
	 */
	private function __construct(){}

	/**
	 * @param string $homeDir	The user's home directory for tilde expansion
	 */
	public static function setHomeDir($homeDir){
		static::$homeDir	= $homeDir;
	}

	/**
	 * @param string $path	A path to make absolute
	 * @return string		The normalised path
	 */
	public static function normalizePath($path){
		if($path === '~' || substr($path, 0, 2) === '~/'){
			// Expand tilde
			if(!isset(static::$homeDir)){
				throw new \RuntimeException('Cannot expand tilde - home dir not set');
			}

			$path	= static::$homeDir.substr($path, 1);

		} else if(substr($path, 0, 1) !== '/'){
			// Make relative path absolute
			$path	= getcwd().((strlen($path) > 0) ? '/'.$path : '');
		}

		return $path;
	}
}
