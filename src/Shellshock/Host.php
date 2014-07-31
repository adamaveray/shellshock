<?php
namespace Shellshock;

class Host {
	// Connection
	/** @var string|null $hostname */
	protected $hostname;
	/** @var int|null $post */
	protected $port;
	/** @var string|null $username */
	protected $username;
	/** @var string|null $identityFile */
	protected $identityFile;
	/** @var bool $verifyHost */
	protected $verifyHost	= true;

	// Other
	/** @var bool $useSudo */
	protected $useSudo	= false;
	/** @var string|null $sudoPassword */
	protected $sudoPassword;

	// Details
	/** @var Group[] $groups */
	protected $groups	= [];
	/** @var array $scripts */
	protected $scripts	= [];
	/** @var string|null $remoteDir */
	protected $remoteDir;


	public function __construct($hostname){
		$this->setHostname($hostname);
	}

	/**
	 * @return string|null
	 */
	public function getHostname(){
		return $this->hostname;
	}

	/**
	 * @param string $hostname
	 */
	public function setHostname($hostname){
		$this->hostname	= $hostname;
	}

	/**
	 * @param int $port	An integer for the port number
	 * @throws \InvalidArgumentException	An invalid port number is given
	 */
	public function setPort($port){
		if(!is_numeric($port)){
			throw new \InvalidArgumentException('Port must be a number');
		}
		$this->port	= intval($port);
	}

	/**
	 * @param string $username
	 */
	public function setUsername($username){
		$this->username	= $username;
	}

	/**
	 * @return string|null
	 */
	public function getUsername(){
		return $this->username;
	}

	/**
	 * @param string $identityFile	The path to an identity file
	 */
	public function setIdentityFile($identityFile){
		$this->identityFile	= Utilities::normalizePath($identityFile);
	}

	/**
	 * @param bool $verifyHost	Whether to verify the remote host's authenticity
	 */
	public function setVerifyHost($verifyHost){
		$this->verifyHost	= (bool)$verifyHost;
	}

	/**
	 * @param bool $useSudo	Whether the host should use sudo for all commands
	 */
	public function setUseSudo($useSudo){
		$this->useSudo	= (bool)$useSudo;
	}

	/**
	 * @param string $password	A password to use when executing sudo commands on the remote host
	 */
	public function setSudoPassword($password){
		$this->sudoPassword	= $password;
	}

	/**
	 * @param Group $group
	 */
	public function addGroup(Group $group){
		$groups	= $this->getGroups();

		if(in_array($group, $groups, true)){
			// Already added
			return;
		}

		$groups[]		= $group;
		$this->groups	= $groups;
	}

	/**
	 * @return Group[]
	 */
	public function getGroups(){
		return $this->groups;
	}

	/**
	 * @return array	An array of relative paths to scripts
	 */
	public function getScripts(){
		return $this->scripts;
	}

	/**
	 * @param array|string $scripts	A path or array of paths to scripts to run on the remote server
	 */
	public function addScripts($scripts){
		if(!is_array($scripts)){
			$scripts	= [$scripts];
		}

		// Merge scripts, removing duplicates and resetting keys
		$this->scripts	= array_values(array_unique(
			array_merge(
				$this->scripts,
				array_values($scripts)
			)
		));
	}

	/**
	 * @return string	The path on the remote host temporary files will be stored in during connection
	 */
	public function getRemoteDir(){
		if(!isset($this->remoteDir)){
			$this->remoteDir	= '/tmp/shellshock-'.sha1(time()+mt_rand(0,1000));
		}

		return $this->remoteDir;
	}


	/**
	 * @param string $command	The command to execute on the remote host
	 * @param bool $needsSudo	Whether the given command needs to be run with sudo
	 * @return string			The full SSH command
	 */
	public function buildSSH($command, $needsSudo = false){
		if($needsSudo && $this->useSudo){
			$sudo	= 'sudo -s';
			if(isset($this->sudoPassword)){
				// Password provided
				$sudo	= ' echo '.escapeshellarg($this->sudoPassword).' | '.$sudo.' -p "" -k -S';
			}
			$command	= $sudo.' '.$command;
		}

		$common	= $this->buildCommon();

		return 'ssh '
			.$this->getBase().' '
			.($common ? $common.' ' : '')
			.escapeshellarg($command);
	}

	/**
	 * @param string|array $from	The path or paths to the files on the local machine
	 * @param string $to			The path to upload the file(s) to on this host
	 * @return string				The full SCP command
	 */
	public function buildSCP($from, $to){
		$common	= $this->buildCommon();

		if(!is_array($from)){
			$from	= [$from];
		}

		return 'scp '
			.($common ? $common.' ' : '')
			.'-r '
			.implode(' ', array_map('escapeshellarg', $from)).' '.$this->getBase().':'.escapeshellarg($to);
	}


	/**
	 * @return string	The base hostname (and optionally username) fragment of the final SSH/SCP commands to connect to this host
	 */
	protected function getBase(){
		$base	= escapeshellarg($this->hostname);

		if(isset($this->username)){
			$base	= escapeshellarg($this->username).'@'.$base;
		}

		return $base;
	}

	/**
	 * @return string	The common arguments to both SSH and SCP commands for connections to this host
	 */
	protected function buildCommon(){
		$args	= [];

		if(isset($this->identityFile)){
			$args[]	= '-i '.escapeshellarg($this->identityFile);
		}
		if(isset($this->port)){
			$args[]	= '-p '.$this->port;
		}
		if(!$this->verifyHost){
			$args[]	= '-o StrictHostKeyChecking=no';
			$args[]	= '-o UserKnownHostsFile=/dev/null';
		}

		return implode(' ', $args);
	}
}
