#!/bin/bash

PLUGIN_SLUG=$1;
# Print the script name.
echo "Running " $(basename "$0") " for " $PLUGIN_SLUG;

# The scripts are mapped one level above the webroot so they are not web-servable.
SCRIPT_DIR="$(dirname "$0")"

mkdir /var/www/html/wp-content/uploads || true;
chmod a+w /var/www/html/wp-content/uploads;

echo "Maybe update WordPress core database"
wp core update-db

echo "wp plugin activate --all"
wp plugin activate --all

# wp-super-cache serves stale pages, which breaks the autologin E2E assertions.
wp plugin deactivate wp-super-cache

echo "Set up pretty permalinks for REST API."
wp rewrite structure /%year%/%monthnum%/%postname%/ --hard;

echo "Adding pages"
if [[ '[]' == $(wp post list --name="Blocks Checkout" --post_type="page" --format=json) ]]; then
  echo "Adding the WooCommerce Block Checkout page";
  wp post create --post_type=page --post_title="Blocks Checkout" --post_status=publish \
    --post_content="$(cat "$SCRIPT_DIR/blocks-checkout-post-content.txt")";
fi

if [[ '[]' == $(wp post list --name="Shortcode Checkout" --post_type=page --format=json) ]]; then
  echo "Adding the WooCommerce Shortcode Checkout page";
  wp post create --post_type=page --post_title="Shortcode Checkout" --post_status=publish \
    --post_content="$(cat "$SCRIPT_DIR/shortcode-checkout-post-content.txt")";
fi

# https://sarathlal.com/create-shipping-zone-and-add-shippig-method-in-to-shipping-zone-using-wp-cli-wordpress/
echo "Configuring WooCommerce shipping"
# `wp wc` requires an authenticated user.
if [[ '[]' == $(wp wc shipping_zone_method list 0 --user=1 --format=json) ]]; then
  echo "Adding free shipping";
  wp wc shipping_zone_method create 0 --user=1 --method_id="free_shipping";
fi

if [[ '[]' == $(wp wc product list --user=1 --format=json) ]]; then
  echo "Adding WooCommerce \"Test Product\""
  wp wc product create --user=1 --name="Test Product" --type=simple --regular_price=100 --status=publish
fi

echo "Maybe updating WooCommerce database"
wp wc update

# Otherwise the storefront returns the "coming soon" page to every request.
wp option set woocommerce_coming_soon no

# Don't let the block editor's welcome guide cover the UI the E2E tests click on.
wp user meta update 1 wp_persisted_preferences '{"core/edit-post":{"welcomeGuide":false}}' --format=json

# Create customer for WooCommerce e2e tests
if [[ '[]' == $(wp user list --login=customer.username --format=json) ]]; then
  wp user create customer.username customer.username@example.org --user_pass=customer.password
fi
