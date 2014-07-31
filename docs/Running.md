Running
=======

Call Shellshock in your provisioning directory (see below). All arguments are optional.

~~~sh
$ shellshock [configpath] [options]
~~~

The main argument is the path to a Shellshock directory.

The following flags are available:

- `--config`/`-c`: Shellshock will automatically look for a `shellshock.js` file in the main directory directory, but the path to a specific config file can be provided.

- `--groups`/`-g`: The group or groups of hosts to run, comma-separated. If not provided, all groups will be run.

- `--command`/`-c`: A command to run on the matched hosts. If this is provided, the command will be run _instead of provisioning._ Note you will need to use the verbose flag to see output from the command.

The following debug flags are also available:

- `--safety`: Outputs the commands that will be run, without running them.

- `--verbose`/`-v`: Outputs script responses.

- `--ping`: Tests the connection to each host without running any scripts.

The `--help` flag is always available, giving the full list of possible flags.


Vagrant
-------

There is no native support for Vagrant. In order to use Shellshock with Vagrant, add the following to your `shellshock.json` file:
 
~~~json
{
	"hosts":	{
		...
		
		"vagrant": [
			"127.0.0.*"			
		]
		
		...
	},
	
	"connections": {
		...
		
		"vagrant":	{
			"user":			"vagrant",
			"private-key":	"~/.vagrant.d/insecure_private_key",
			"verify-host":	false,
			"sudo":			true
		},
		"127.0.0.*":		"vagrant"
		
		...
	}
}
~~~

_Replace `127.0.0.` with the IP range for your Vagrant boxes. The IPs for Vagrant in the `hosts` section can also appear in other groups, grouping them under `vagrant` is simply for convenience._
  
Now you can manually provision your Vagrant boxes:

~~~sh
$ shellshock path/to/shellshock --groups vagrant
~~~
