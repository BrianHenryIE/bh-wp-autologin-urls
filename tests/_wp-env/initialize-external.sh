#!/bin/bash

# Script which runs outside Docker (on the host machine).
# Called by wp-env's afterStart lifecycle hook.

# Print the script name.
echo $(basename "$0")

# This presumes the current working directory is the project root and the directory name matches the plugin slug.
PLUGIN_SLUG=$(basename $PWD)
#echo "Building $PLUGIN_SLUG"

# Build the plugin's translation template.
echo "Creating .pot language file"
vendor/bin/wp i18n make-pot src languages/$PLUGIN_SLUG.pot --domain=$PLUGIN_SLUG

# Detect the operating system.
OS_TYPE=$(uname)
echo "Detected OS: $OS_TYPE"

# Run the internal script which configures the environment inside Docker.
configure_environment() {
  echo "run npx wp-env run cli ../setup/initialize-internal.sh $PLUGIN_SLUG;"
  npx wp-env run cli ../setup/initialize-internal.sh $PLUGIN_SLUG;
}

case "$OS_TYPE" in
  Linux)
    echo "Running on Linux"
    configure_environment
    ;;
  Darwin)
    echo "Running on macOS"
    configure_environment
    ;;
  MINGW*|CYGWIN*)
    echo "Running on Windows (Git Bash or Cygwin)"
    configure_environment
    ;;
  *)
    echo "Unsupported OS: $OS_TYPE"
    exit 1
    ;;
esac
