#!/bin/bash

# Print the script name.
echo $(basename "$0")

echo "Installing latest build of bh-wp-autologin-urls"
wp plugin install ./setup/bh-wp-autologin-urls.latest.zip --activate --force