Structure
=========

_(See [the sample Shellshock provisioning directory](https://github.com/adamaveray/shellshock-example) for examples)_

A Shellshock provisioning directory should be structured like so:

~~~
files/
scripts/
settings/
shellshock.json
~~~

The `shellshock.json` file is the [Shellshock configuration file](Configuration.md). Each directory is explained below.


Files
-----

Any files to be copied to the remote machine should be stored in the `files` directory. These files will be available to the provisioning scripts, but will be deleted after provisioning so any files needed after provisioning should be copied by the scripts to persistent locations.


Scripts
-------

The `scripts` directory contains one or more shell scripts. These will be referenced in the `settings` directory, explained below. [See the full docs on writing scripts for more.](Scripts.md)


Settings
--------

The `settings` directory contains a number of JSON files, matching the names of groups in the [main config file](Configuration.md). Each file should contain the following format:

~~~json
{
	"files":	[
		...
	],
	"scripts":	[
		...
	]
}
~~~

`files` is an array of paths _within the `files` directory_ to be copied with this group, and the `scripts` is an array of paths to scripts _within the `scripts` directory_ to be run for this group.
