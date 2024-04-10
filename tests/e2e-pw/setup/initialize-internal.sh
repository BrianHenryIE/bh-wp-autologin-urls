#!/bin/bash

# Print the script name.
echo $(basename "$0")

# Print out all environmental variables
echo "$ printenv"
printenv

echo "Maybe update WordPress core database"
wp core update-db

echo "Set up pretty permalinks for REST API."
wp rewrite structure /%year%/%monthnum%/%postname%/ --hard;

echo "Adding pages"
if [[ '[]' == $(wp post list --name="Blocks Checkout" --post_type="page" --format=json) ]]; then
  echo "Adding the WooCommerce Block Checkout page";
    # TODO: Check the file exists.
  wp post create --post_type=page --post_title="Blocks Checkout" --post_status=publish ./setup/blocks-checkout-post-content.txt;
fi

if [[ '[]' == $(wp post list --name="Shortcode Checkout" --post_type=page --format=json) ]]; then
  echo "Adding the WooCommerce Shortcode Checkout page";
  wp post create --post_type=page --post_title="Shortcode Checkout" --post_status=publish ./setup/shortcode-checkout-post-content.txt;
fi

wp plugin activate --all

# https://sarathlal.com/create-shipping-zone-and-add-shippig-method-in-to-shipping-zone-using-wp-cli-wordpress/
echo "Configuring WooCommerce shipping"
if [[ '[]' == $(wp wc shipping_zone_method list 0 --format=json) ]]; then
  echo "Adding free shipping";
  wp wc shipping_zone_method create 0 --method_id="free_shipping";
fi

if [[ '[]' == $(wp wc product list --format=json) ]]; then
  echo "Adding WooCommerce \"Test Product\""
  wp wc product create --name="Test Product" --type=simple --regular_price=100
fi

echo "Maybe updating WooCommerce database"
wp wc update

wp plugin deactivate wp-super-cache
