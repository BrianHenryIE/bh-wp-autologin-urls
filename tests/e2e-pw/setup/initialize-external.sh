#!/bin/bash

# Script which runs outside Docker

# Print the script name.
echo $(basename "$0")

# This presumes the current working directory is the project root and the directory name matches the plugin slug.
PLUGIN_SLUG=$(basename $PWD)
echo "Building $PLUGIN_SLUG"

# Build the plugin
vendor/bin/wp i18n make-pot src languages/$PLUGIN_SLUG.pot --domain=$PLUGIN_SLUG
vendor/bin/wp dist-archive . ./tests/e2e-pw/setup --plugin-dirname=$PLUGIN_SLUG --filename-format="{name}.latest"

# Configure the environment
wp-env run cli ./setup/initialize-internal.sh;
wp-env run tests-cli ./setup/initialize-internal.sh;
wp-env run cli ./setup/initialize-internal-dev.sh;
wp-env run tests-cli ./setup/initialize-internal-tests.sh;