#!/bin/bash

# Environment
export CLICOLOR=1

# Variables
SHOCK="$(dirname "$BASH_SOURCE[0]")"
SHOCK_FILES="${SHOCK}/files"
SHOCK_SCRIPTS="${SHOCK}/scripts"

SHOCK_USER="$1"
shift # Remove from args

SHOCK_GROUPS="$1"
shift # Remove from args

# Run scripts
for SUBSCRIPT in "$@"
do
	echo "→ Running '${SUBSCRIPT}'"

	# Check script exists
	if [ ! -f "${SHOCK_SCRIPTS}/${SUBSCRIPT}" ]; then
		echo "ERROR: Cannot find script"
		continue
	fi

	# Isolate each script
	cat <<EOF | bash
# Redefine variables
SHOCK="${SHOCK}"
SHOCK_FILES="${SHOCK_FILES}"
SHOCK_SCRIPTS="${SHOCK_SCRIPTS}"
SHOCK_GROUPS="${SHOCK_GROUPS}"
SHOCK_USER="${SHOCK_USER}"

# Utilities
isShockGroup () {
	IFS=\$'\\n' # Newline-separated
	for word in \${SHOCK_GROUPS}; do
    	if [[ "\$word" = "\$1" ]]; then
			# Match
			unset IFS
			return 0
		fi	
	done
	
	unset IFS
	return 1 # No match
}

# Run script
source "${SHOCK_SCRIPTS}/${SUBSCRIPT}"
EOF
done
