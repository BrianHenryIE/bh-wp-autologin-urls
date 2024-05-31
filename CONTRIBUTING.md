
#### Rough notes.

List scripts:

`composer run -l`




### PHPUnit Tests with Codeception/WP-Browser

Requires local Apache and MySQL.

```bash
composer install
composer create-databases
composer setup-wordpress
XDEBUG_MODE=coverage composer coverage-tests; 
$ composer delete-databases
```

### E2E testing with wp-env and Playwright

Requires Docker

```bash
# nvm use
npm install
npx playwright install
npx wp-env start --xdebug
npx playwright test --config ./playwright.config.ts
npx wp-env destroy
```
```
npx playwright test --config ./wordpresscore.playwright.config.js

# Error: Cannot find module '@wordpress/scripts/config/playwright.config'
npm install @wordpress/scripts --save-dev

# Error: Cannot find module 'vendor/wordpress/wordpress/tests/e2e/config/global-setup.js'

# Error: Requiring @playwright/test second time, 

# ReferenceError: defineConfig is not defined


```
```
cd vendor/wordpress/wordpress
npm install
npx playwright test --config  tests/e2e/playwright.config.js
```


```

    "woocommerce/woocommerce": {
      "type": "package",
      "package": {
        "name": "woocommerce/woocommerce",
        "version": "dev-trunk",
        "source": {
          "url": "https://github.com/woocommerce/woocommerce/",
          "type": "git",
          "reference": "trunk"
        },
        "installation-source": "dist"
      }
    },
    
    
composer config allow-plugins.automattic/jetpack-autoloader true
composer require --dev woocommerce/woocommerce --with-all-dependencies

npx wp-env run tests-cli wp rewrite structure /%year%/%monthnum%/%postname%/ --hard    

 npx wp-env run tests-cli wp user create customer customer@woocommercecoree2etestsuite.com \
	--user_pass=password \
	--role=customer \
	--first_name='Jane' \
	--last_name='Smith' \
	--user_registered='2022-01-01 12:23:45'
 
 
cd vendor/woocommerce/woocommerce
nvm install
nvm use

# (curl -fsSL https://get.pnpm.io/install.sh | sh -)
# (brew install corepack)
# corepack use pnpm@8
 
npm install -g pnpm@8

pnpm install
pnpm build
npx playwright install --with-deps chromium
BASE_URL=http://localhost:8889 npx playwright test --config  plugins/woocommerce/tests/e2e-pw/playwright.config.js

COREPACK_ENABLE_STRICT=0 BASE_URL=http://localhost:8889 USE_WP_ENV=1 pnpm playwright test --config=tests/e2e-pw/woocommerce/playwright.config.js 
```


cd vendor/wordpress/wordpress
npm install
npx playwright test --config tests/e2e/playwright.config.js
npx playwright test --config vendor/wordpress/wordpress/tests/e2e/playwright.config.js

Notes:

```
npx wp-env start --xdebug

# Destroy the environment and restart
echo Y | npx wp-env destroy; npx wp-env start

# for development work
open http://localhost:8888

# is used for automated tests.
open http://localhost:8889

# Start the playwright test runner UI and return to the Terminal (otherwise Terminal is unavailable until the application is exited).
npx playwright test --ui &;

# Start browser and record Playwright steps
npx playwright codegen -o tests/e2e-pw/example.spec.js


# Run WP CLI commands on the tests instance
npx wp-env run tests-cli wp option get rewrite_rules
```

Tests not working? It's possibly due to rate limiting:

```
npx wp-env run tests-cli wp transient delete --all;
npx wp-env run cli wp transient delete --all;
```


Reset everything between tests:

```
wp db query "DELETE FROM wp_wpml_mails;";
wp db query "DELETE FROM wp_newsletter_emails;";
wp db query "DELETE FROM wp_newsletter;";
wp db query "DELETE FROM wp_newsletter_meta;";
wp db query "DELETE FROM wp_newsletter_stats;";
wp db query "DELETE FROM wp_newsletter_sent;";
wp db query "DELETE FROM wp_autologin_urls;";
rm -rf wp-content/uploads/logs;
rm wp-content/debug.log;
wp user delete $(wp user list --role=subscriber --format=ids) --yes;
wp transient delete --all;
```
```bash
wp config set WP_DEBUG true --raw;
wp config set WP_DEBUG_LOG true --raw; 
wp config set SCRIPT_DEBUG true --raw
```
```
wp config set WP_DEBUG_LOG "/var/www/html/wp-content/debug.log"
```

Don't have xdebug enabled in IDE when starting wp-env.


TEST_DB_HOST="127.0.0.1"
TEST_DB_USER="bh-wp-autologin-urls"
TEST_DB_PASSWORD="bh-wp-autologin-urls"
TEST_DB_HOST="127.0.0.1:62561"
TEST_DB_USER="root"
TEST_DB_PASSWORD="password"


### More Information

See [github.com/BrianHenryIE/WordPress-Plugin-Boilerplate](https://github.com/BrianHenryIE/WordPress-Plugin-Boilerplate) for initial setup rationale. 
