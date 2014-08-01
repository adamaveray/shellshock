<?php
namespace Shellshock;

class Utilities {
	/**
	 * @codeCoverageIgnore
	 */
	private function __construct(){}

	/**
	 * @param string $path	A path to make absolute
	 * @return string		The normalised path
	 */
	public static function normalizePath($path){
		if($path === '~' || substr($path, 0, 2) === '~/'){
			// Expand tilde
			if(!isset($_SERVER['HOME'])){
				throw new \RuntimeException('Cannot expand tilde');
			}

			$path	= $_SERVER['HOME'].substr($path, 1);

		} else if(substr($path, 0, 1) !== '/'){
			// Make relative path absolute
			$path	= getcwd().((strlen($path) > 0) ? '/'.$path : '');
		}

		return $path;
	}
}
