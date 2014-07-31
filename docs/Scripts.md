Writing Scripts
===============

While Shellshock provides no helpers for dealing with remote hosts [by design](../README.md), it does provide a handful of utilities for determing facts about the current Shellshock execution.

Variables
---------

- `SHOCK`: The absolute path to the Shellshock directory
- `SHOCK_FILES`: The absolute path to the Shellshock files directory
- `SHOCK_SCRIPTS`: The absolute path to the Shellshock scripts directory
- `SHOCK_GROUPS`: A newline-separated list of which groups the current host is a member of _(see below for a simpler wrapper)_
- `SHOCK_USER`: The username scripts are being run under


Functions
---------

### `isShockGroup`

The function `isShockGroup` lets you determine whether the script is executing on a host that is a member of the specified host. For example:

~~~sh
if isShockGroup "web"; then
	# In "web" group
	export HOSTNAME="web.example.com"
else
	# Not in "web" group
	export HOSTNAME="other.example.com"
fi
~~~
