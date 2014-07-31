<?php
namespace Shellshock;

use Shellshock\Host;
use Shellshock\Group;

class HostManager extends \ArrayObject {
	const RECURSION_MAX	= 10;
	const SETTINGS_KEY_DEFAULT	= '_default';

	/**
	 * Processes the raw host data into Host objects within Group objects, while storing references to all hosts within the HostManager
	 *
	 * @param array $hostData	An array of arrays containing raw host data
	 * @param bool $addDefault	Whether to add the default group to the collection, which will contain all hosts
	 * @return Group[]			An array with group names as keys, and Group objects an values
	 */
	public function loadHosts(array $hostData, $addDefault = false){
		$groups	= [];

		if($addDefault){
			$defaultGroup	= new Group(static::SETTINGS_KEY_DEFAULT);
		}

		foreach($hostData as $group => $rawHosts){
			if(!isset($groups[$group])){
				$groups[$group]	= new Group($group);
			}

			foreach($rawHosts as $hostname){
				if(!isset($this[$hostname])){
					// Build host
					$host	= new Host($hostname);

					if(isset($defaultGroup)){
						// Add to default, all-encompassing group
						$defaultGroup->addHost($host);
						$host->addGroup($defaultGroup);
					}

					// Store in collection
					$this[$hostname]	= $host;
				}

				// Add to group
				$groups[$group]->addHost($this[$hostname]);
				$this[$hostname]->addGroup($groups[$group]);
			}
		}

		if(isset($defaultGroup)){
			$groups[static::SETTINGS_KEY_DEFAULT]	= $defaultGroup;
		}
		return $groups;
	}

	/**
	 * @param Host $host	The host to search for
	 * @return bool			Whether the collection contains the given host
	 */
	public function containsHost(Host $host){
		return in_array($host, $this->getArrayCopy(), true);
	}

	/**
	 * @param string $searchString	A hostname or wildcard hostname pattern to match hosts against
	 * @return Host[]				All hosts that match the given key
	 */
	protected function matchHosts($searchString){
		$matchedHosts	= [];

		if(isset($this[$searchString])){
			// Direct match - single host
			$matchedHosts[]	= $this[$searchString];

		} else if(strpos($searchString, '*') !== false){
			// Wildcard - search for matches
			$pattern	= '/'.str_replace('\\*', '(.*?)', preg_quote($searchString, '/')).'/';
			foreach($this as $subkey => $host){
				if(!preg_match($pattern, $subkey)){
					// No match
					continue;
				}

				// Matched
				$matchedHosts[]	= $host;
			}
		}

		return $matchedHosts;
	}


	/**
	 * Applies the given connection settings to all hosts
	 *
	 * @param array $connectionSettings	An array containing arrays of connection settings for each host, with host search strings for keys
	 */
	public function applyConnectionSettings(array $connectionSettings){
		if(isset($connectionSettings[static::SETTINGS_KEY_DEFAULT])){
			// Apply default settings
			$this->applyConnectionSettingsToHosts($connectionSettings[static::SETTINGS_KEY_DEFAULT], $this);
		}

		foreach($connectionSettings as $key => $settings){
			if($key === static::SETTINGS_KEY_DEFAULT){
				// Already handled
				continue;
			}

			$matchedHosts	= $this->matchHosts($key);
			if(!$matchedHosts){
				// Unknown host - probably only for referencing - ignore
				continue;
			}
		
			// Load settings
			$item	= $this->processConnectionSetting($connectionSettings, $settings, [$key]);
		
			// Apply settings
			$this->applyConnectionSettingsToHosts($item, $matchedHosts);
		}
	}

	/**
	 * Applies the given connection settings to the given hosts
	 *
	 * @param array $settings	Settings for the hosts. The following keys are valid:
	 *	- string 'user'
	 *	- bool 'sudo'
	 *	- string 'sudo-password'
	 *	- string 'private-key'
	 *	- bool 'verify-host'
	 *
	 * @param array $hosts		An array (or array-compatible object) of Host objects
	 */
	protected function applyConnectionSettingsToHosts(array $settings, $hosts){
		$map	= [
			'user'			=> 'username',
			'sudo'			=> 'useSudo',
			'sudo-password'	=> 'sudoPassword',
			'private-key'	=> 'identityFile',
			'verify-host'	=> 'verifyHost',
		];

		foreach($hosts as $host){
			foreach($map as $setting => $method){
				if(isset($settings[$setting])){
					$host->{'set'.$method}($settings[$setting]);
				}
			}
		}
	}

	/**
	 * Processes a given connection settings item, which may be a reference to another one, so steps through references to find the final settings values.
	 *
	 * @param array $connectionSettings		The entire array of connection settings arrays
	 * @param array|string $keyOrSettings	A settings array, or a search string if referencing other settings
	 * @param array|null $stack				An array of search strings used so far
	 * @return array						A connection settings array
	 * @throws \OverflowException		Recursive referencing is detected, or referencing too deep
	 * @throws \OutOfBoundsException	A nonexistent reference is used
	 */
	protected function processConnectionSetting(array $connectionSettings, $keyOrSettings, array $stack = null){
		if(is_array($keyOrSettings)){
			// Match found
			return $keyOrSettings;
		}

		// Using a key
		$key	= $keyOrSettings;

		if(!isset($stack)){
			$stack	= [];
		}

		if(in_array($key, $stack)){
			throw new \OverflowException('Recursive connection importing detected');
		}

		if(count($stack) > static::RECURSION_MAX){
			throw new \OverflowException('Too many reference levels');
		}

		if(!isset($connectionSettings[$key])){
			throw new \OutOfBoundsException('Unknown connection settings "'.$key.'"');
		}

		$settings	= $connectionSettings[$key];

		// Follow next reference
		$stack[]	= $key;
		return $this->processConnectionSetting($connectionSettings, $settings, $stack);
	}


	/**
	 * Reduces the given set of groups to only those that match the given group names.
	 *
	 * @param array &$groups		The groups to filter. Will be set to a filtered version of itself.
	 * @param array $groupNames		An array of group name strings to filter by
	 * @return array|HostManager	A HostManager instance containing the Host objects within the filtered Groups
	 */
	public static function filterHosts(array &$groups, array $groupNames){
		$hosts	= new HostManager();

		$filteredGroups	= [];
		if(!count($groupNames)){
			// No filtering
			$filteredGroups	= $groups;

		} else {
			// Filter groups
			foreach($groupNames as $name){
				if(!isset($groups[$name])){
					// Unknown group
					throw new \OutOfBoundsException('Unknown group "'.$name.'"');
				}

				$filteredGroups[$name]	= $groups[$name];
			}
		}

		// Pull hosts out of groups
		foreach($filteredGroups as $group){
			if(!($group instanceof Group)){
				throw new \InvalidArgumentException('Groups must be instances of Group');
			}

			foreach($group->getHosts() as $host){
				if($hosts->containsHost($host)){
					// Already added - ignore
					continue;
				}

				$hosts[]	= $host;
			}
		}

		$groups	= $filteredGroups;
		return $hosts;
	}
}
