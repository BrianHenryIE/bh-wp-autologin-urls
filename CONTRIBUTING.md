

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

```php
npm install
npx playwright install
npx wp-env start --xdebug
npx playwright test --config ./playwright.config.ts
npx wp-env destroy
```

Notes:

```
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

### More Information

See [github.com/BrianHenryIE/WordPress-Plugin-Boilerplate](https://github.com/BrianHenryIE/WordPress-Plugin-Boilerplate) for initial setup rationale. 
