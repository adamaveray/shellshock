<?php
namespace Shellshock;

class Group extends Settings {
	/** @var string|null $settingsDir */
	protected static $settingsDir;

	/** @var string $name */
	protected $name;
	/** @var HostManager $hosts */
	protected $hosts;
	/** @var array $settings */
	protected $settings;

	/**
	 * @param string $name	The name of the group
	 */
	public function __construct($name){
		$this->name	= $name;

		$this->settings	= $this->loadSettings(static::$settingsDir);
	}

	/**
	 * @return string
	 */
	public function getName(){
		return $this->name;
	}

	/**
	 * @param Host[]|HostManager $hosts	An array of Host objects or a HostManager instance
	 */
	public function addHosts($hosts){
		if(!is_array($hosts) && !($hosts instanceof HostManager)){
			throw new \InvalidArgumentException('Hosts must be collection of hosts');
		}

		foreach($hosts as $host){
			$this->addHost($host);
		}
	}

	/**
	 * @param Host $host
	 */
	public function addHost(Host $host){
		$hosts	= $this->getHosts();

		if($hosts->containsHost($host)){
			// Already added
			return;
		}

		$hosts[]	= $host;

		// Assign properties
		$host->addScripts($this->getScripts());
	}

	/**
	 * @return HostManager	A HostManager instance containing all the Host objects within this group
	 */
	public function getHosts(){
		if(!isset($this->hosts)){
			$this->hosts	= new HostManager();
		}

		return $this->hosts;
	}

	/**
	 * @return array	The settings data for this group
	 */
	public function getSettings(){
		return $this->settings;
	}

	/**
	 * @return array	Relative paths to the scripts to be run for this group
	 */
	public function getScripts(){
		return (isset($this->settings['scripts']) ? $this->settings['scripts'] : []);
	}


	/**
	 * @param string $dataDir	The directory to look for a group-specific settings file in
	 * @return array			The settings data from the file if found
	 */
	protected function loadSettings($dataDir){
		$path	= $dataDir.'/'.$this->getName().'.json';
		if(!$this->fileExists($path)){
			// No config for group
			return [];
		}

		$settings	= @json_decode($this->getFileContents($path), true);
		if(!isset($settings)){
			throw new \RuntimeException('Cannot read settings for group "'.$this->getName().'"');
		}

		// Validate scripts
		if(!isset($settings['scripts']) || !is_array($settings['scripts'])){
			throw new \UnexpectedValueException('Cannot read scripts in group "'.$this->getName().'"');
		}

		return $settings;
	}


	/**
	 * @param string $path
	 * @return bool
	 * @codeCoverageIgnore
	 */
	protected function fileExists($path){
		return file_exists($path);
	}

	/**
	 * @param string $path
	 * @return string|false
	 * @codeCoverageIgnore
	 */
	protected function getFileContents($path){
		return file_get_contents($path);
	}


	/**
	 * Sets the global directory where groups should look for group-specific settings files in, in the format `{groupname}.json`
	 *
	 * @param string $settingsDir
	 */
	public static function setSettingsDir($settingsDir){
		static::$settingsDir	= $settingsDir;
	}
}
