Practices
=========

_(This documentation refers to best practices when building your provisioning scripts. See [the sample Shellshock provisioning directory](https://github.com/adamaveray/shellshock-example) for an example of a complete Shellshock setup, using these practices.)_

At a high level, you should aim to make your scripts [idempotent](http://stackoverflow.com/a/1077421/626682) – the scripts should be able to be run multiple times without any additional effects.

In general, here are some common practices in the scripts:

- **Check if files already exist before creating them.**
	
	~~~sh
	if [ ! -f "path/to/file" ]; then
		# File does not exist - create it, etc
	fi
	~~~
	
	Note this might not matter for some files – if they will just be replaced with the same content, it might not be worth checking.
	
- **Store entire configuration files in the `files` directory.** Rather than trying to modify a config file using `sed`, etc, you should just store the entire configuration file and make your changes there, ensuring each server has the _exact_ same file, and the scripts will not break due to unexpected changes to the file.

- **Group related functionality into separate scripts.** Rather than creating a single, giant file that provisions the entire server, you should break related parts into separate files. For example, in a classic LAMP-stack, installing and configuring Apache could be in a separate file to installing PHP, which would itself be in a different file from installing MySQL.

- **Create a 'common' file `source`-d by other files.** Since your scripts will be in multiple files, you should create a common file that is included by other scripts, where you can define functions or variables used across many of them.

These are all _suggestions_, of course – the whole point of Shellshock is that everything is just shell scripts, so you can have as little or as much structure as you would like!
