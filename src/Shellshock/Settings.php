<?php
namespace Shellshock;

abstract class Settings {
	/**
	 * @return array	All settings values
	 * @codeCoverageIgnore
	 */
	abstract public function getSettings();

	/**
	 * @param string $name			The name of the setting value to retrieve
	 * @param mixed|null $default	A value to return if the setting does not exist
	 * @return mixed				The value for the given name, or $default
	 */
	public function get($name, $default = null){
		$settings	= $this->getSettings();
		
		return (isset($settings[$name]) ? $settings[$name] : $default);
	}
}
