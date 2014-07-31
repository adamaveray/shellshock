Configuration
=============

Shellshock is configured using the `shellshock.js` configuration file in each provisioning directory.

_(If the config file seems confusing, see examples below for better visualisation)_

- `hosts`: An object containing arrays of hostnames or IP addresses, with keys as group names.

- `connection`: An object with settings for connecting to the remote hosts. Each key can be either a single hostname/IP, or a hostname/IP with an asterisk for wildcard matching of many.

	If the value is a string, it will use the settings from the host with that name. Otherwise, it should be an object with any of the following optional keys:
	
	- `user`: The username to use for SSH
	- `sudo`: A boolean for whether to run all commands with `sudo`
	- `private-key`: A path to the private key to use when connecting
	- `verify-host`: A boolean for whether to verify the remote host connection (defaults to `true`)
	- `sudo-password`: A password to use for `sudo` commands on the remote machine under the given `user`
	
	The connection name "_default" will be used for all hosts, and further connection matches will be overridden _in order._


Examples
--------

### Basic

This is an example of a basic `shellshock.json` file:

~~~json
{
	"hosts":	{
		"web":	[
			"127.0.0.2"
		],
		"db":	[
			"127.0.0.3"
		]
	},
	
	"connections":	{
		"_default":	{
			"user":	"root",
			"sudo":	true
		}
	}
}
~~~


### Advanced

This is a more complex `shellshock.json` file (comments for illustration – not valid JSON):

~~~json
{
	"hosts":	{
		// Development hosts, by IP address
		"dev":	[
			"127.0.0.2",
			"127.0.0.3"
		],
		
		// Production hosts
		"production":	[
			"web1.example.com",
			"web2.example.com",
			"web3.example.com",
			"db1.example.com",
			"db2.example.com"
		]
		
		// Hosts can be in multiple groups
		"web":	[
			"web1.example.com",
			"web2.example.com",
			"web3.example.com",
			"127.0.0.2",
		],
		"db":	[
			"db1.example.com",
			"db2.example.com",
			"127.0.0.3"
		]
	},
	
	"connections":	{
		"_default":	{
			"user":	"root",
			"sudo":	true
		},
		
		// An alias for use with all dev machines. Since it does not match any hosts, it is purely for aliasing
		"local":	{
			// Will take precedence over any conflicting "_default" values
			"private-key":	"~/.vagrant.d/insecure_private_key",
			"user":			"vagrant"
		},
		
		// Another alias
		"production-db":	{
			"private-key":	"~/.ssh/alternate_key"
		},
		
		// Use "local" alias settings for local hosts (wildcard IP match)
		"127.0.0.*":	"local",
		
		// Use "production-db" alias settings for production DB hosts (wildcard hostname match)
		"db*.example.com":	"production-db",
		
		// Special settings for a single host
		"web3.example.com":	{
			"user":	"~/.ssh/another_key"
		}
	}
}
~~~

You could then provision only development servers with the following command:

~~~sh
# Will provision "127.0.0.2" and "127.0.0.3"
$ shellshock --groups dev
~~~
