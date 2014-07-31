Shellshock
==========

[![Build Status](https://travis-ci.org/adamaveray/shellshock.svg?branch=master)](https://travis-ci.org/adamaveray/shellshock)
[![Code Coverage](https://scrutinizer-ci.com/g/adamaveray/shellshock/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/adamaveray/shellshock/?branch=master)


Inspired by [FSS](http://fuckingshellscripts.org), a glorified wrapper around provisioning servers with shell scripts. Shellshock lets you group the scripts into "roles", manages putting the scripts and related files on the remote servers and running them, and not a whole lot else. You probably don't want to use this.

Shellshock's goals:

- **Everything runs on the remote server.** This ensures consistent, repeatable performance.

- **Raw shell scripts.** No abstractions or utilities for dealing with the remote host are provided within the scripts. That's up to you.

- **Simple grouping.** Shellshock puts managing scripts grouped into "roles", "types", whatever as a major priority. What each group _means_ is up to you, but should be managed and handled clearly by Shellshock.

- **Maps to basic `ssh` and `scp` commands.** All Shellshock does is manage transferring and running files, using only those two underlying commands. You can even access those commands and run them yourself instead at any time.

**Docs:** [Installation](docs/Installation.md) → [Structure](docs/Structure.md) → [Configuration](docs/Configuration.md) → [Running](docs/Running.md) → [Practices](docs/Practices.md)


Development
-----------

After cloning this repository, use [Composer](https://getcomposer.org) to install the dependencies, then run the `build` script to create the executable file. Please be sure to run [the tests](tests/Shellshock/Test) before sending a pull request. Shellshock requires PHP 5.4+.


Licence
-------

[MIT](LICENSE).
