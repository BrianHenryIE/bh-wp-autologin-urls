# PLAN: Modernize bh-wp-autologin-urls dev environment

Goal (unchanged): update the dev environment to match `bh-wp-venmo-gateway` / `bh-wp-logger` /
`bh-wp-mailboxes`, update dependencies, fix deprecation warnings with current WordPress/plugins,
no new features, all tests passing. `bh-wp-venmo-gateway` is the most recently modernized
reference — when in doubt, copy its conventions.

## How to pick up this work

Work so far is on a branch named `modernize-dev-environment`, delivered as a git bundle at the
repo root (the cloud session could not push). To import it:

```bash
git fetch modernize-dev-environment.bundle modernize-dev-environment:modernize-dev-environment
git stash        # your working tree's uncommitted WIP is ALREADY PRESERVED as the branch's first commit
git checkout modernize-dev-environment
```

The branch is based on `dev` (58c98b9). Commits:

1. `WIP: in-progress dev environment modernization` — your previously-uncommitted working-tree
   changes (composer.json PHP 8.1, .wp-env.json rewrite targeting WP 7.0.2, bin/ sync scripts,
   vendor-prefixed autoloading, `Settings::get_cli_base()`, E2E script edits). Nothing was lost.
2. `Modernize composer.json...` — venmo-style require-dev + scripts + resolved composer.lock
   (wp-browser 4.7 / codeception 5.3 / phpunit 9.6 / wp_mock 1.1.1 / WP core 7.0.2 / WC 10.9.4 /
   rector 2.x added).
3. `Migrate test bootstraps and integration suite to wp-browser 4 / codeception 5`.
4. `Fix deprecations/compat with PHP 8.4, WordPress 7.0, MailPoet 5.34` (src/ changes).
5. `Update tests... [INCOMPLETE]` — one test still failing, see below.

After checkout: `composer install` (bin scripts + strauss run via composer scripts), then delete
`_claude_tmp/` at the repo root (leftover transfer tarballs from the cloud session).

## Test status when the session ended

- `codecept run unit` — ✅ 76 tests passing (1 skipped, 2 incomplete — pre-existing).
- `codecept run wpunit` — ✅ 45 tests passing, idempotent across repeated runs without dropping
  the DB.
- `codecept run integration` — ❌ ONE failing test (`test_repeated_logins_rate_limited`, new);
  all others pass. See "Immediate next step" below.
- phpcs / phpstan / rector — NOT RUN YET.
- Playwright E2E (`tests/e2e-pw`) — NOT RUN (needs wp-env).

## Immediate next step: the failing rate-limit test

`tests/integration/wp-includes/class-login-integration-Test.php::test_repeated_logins_rate_limited`

Background: the old tests `test_bad_attempt_records_ip`, `test_bad_user_records`,
`test_bad_code_records` tested failure-transient bookkeeping
(`bh-wp-autologin-urls-failure-{ip}` transients with count/malformed/users keys) that NO LONGER
EXISTS in src/ — it was replaced by `brianhenryie/bh-wp-rate-limiter`. I replaced them with one
behavioral test: log in `Login::MAX_BAD_LOGIN_ATTEMPTS` (5) times, assert the 6th attempt is
refused. The 5 logins succeed but the 6th ALSO succeeds (returns the user id, expected 0).

Things already ruled out / discovered:
- `API::generate_code()` caches codes per `"$user_id~$seconds_valid"` per request, and codes are
  single-use — the test varies `expires_in` per attempt to get fresh codes.
- `Login::process()` calls `API::should_allow_login_attempt()` for `ip:{$ip}` and
  `wp_user:{$id}` — each calls `WordPress_Rate_Limiter::limitSilently()` (nikolaposa/rate-limit
  3.3 + wp-oop/transient-cache, via the LOCAL path repo `../bh-wp-rate-limiter`).
- The rate limiter instance is a `static` inside `should_allow_login_attempt()` so it persists
  across tests in a suite run; its transient-backed storage is inside the per-test DB
  transaction, so counts written during a test roll back afterwards (that part is fine — the
  whole test runs inside one transaction).

Hypotheses to check (in order):
1. Does `limitSilently()` on the 6th call actually report `limitExceeded()`? Check semantics of
   `Rate::custom(5, DAY_IN_SECONDS)` in nikolaposa/rate-limit 3.3 — off-by-one, or "5 per day"
   might mean the 6th is the first blocked... the test does expect exactly that, so instrument
   `should_allow_login_attempt()` (or unit-test `WordPress_Rate_Limiter` directly) to see the
   counts actually stored.
2. wp-oop/transient-cache uses `set_transient()`; inside the test, `pre_option_siteurl` filters
   and object-cache behavior may make `get_transient` miss so every call sees count 0/1. Check
   whether the stored transient count increments across the 5 attempts within one test.
3. `../bh-wp-rate-limiter` (local path repo) — note its GitHub copy still requires the
   `dev-psr-16` fork of nikolaposa/rate-limit while the local working tree requires `^3.3.0`
   (uncommitted local change, visible in composer.lock). Verify the local copy actually works
   against upstream 3.3 semantics; there may be a real bug here rather than a test bug
   (`limitSilently` vs `limit`, or status semantics changed between the fork and upstream 3.3).
   **If the rate limiter genuinely never blocks, that's a real plugin bug worth fixing in
   bh-wp-rate-limiter, not papering over in the test.**

If the behavior turns out to be "rate limiter counts only successful logins and the limit
semantics differ", adjust the test to the actual intended behavior — but confirm intent first.

Note the test also does `wp_set_current_user( 0 )` between attempts because `process()` returns
early when a user is already determined.

## Remaining tasks (in suggested order)

1. **Fix the failing integration test** (above), then a full clean `codecept run unit && codecept
   run wpunit && codecept run integration`.
2. **phpcs**: `composer cs` (new script: phpcs + phpstan). Expect churn — phpcs.xml is close to
   venmo's but venmo's has a few extra excludes (`/bin/`, `/tests/e2e-pw/`, `/tests/_wp-env/`,
   `Universal.Operators.DisallowShortTernary`, `Squiz.Commenting.FunctionCommentThrowTag.WrongNumber`,
   `WordPress.Security.EscapeOutput.ExceptionNotEscaped`, unused-parameter allowances,
   `Squiz.Commenting.VariableComment.MissingVar`). Consider copying venmo's phpcs.xml wholesale
   and re-adding autologin-specific excludes (it currently excludes
   `WordPress.Files.FileName.InvalidClassFileName` for `abstract-*.php` files).
   NOTE: `woocommerce/woocommerce-sniffs` and `phpcompatibility/phpcompatibility-wp` were REMOVED
   from require-dev (neither referenced in phpcs.xml; matches venmo). The `php-compatibility`
   composer script was removed accordingly.
3. **phpstan**: `phpstan analyse --memory-limit 1G`. phpstan.neon still analyses only
   `src` + main plugin file; venmo also covers `tests`, `uninstall.php`, `autoload.php` — decide
   whether to expand paths (venmo does; recommended) and fix findings.
4. **rector.php**: copy from `../bh-wp-venmo-gateway/rector.php`, change paths `includes` → `src`
   and package name. Run `vendor/bin/rector --dry-run`, review, apply.
5. **Root config files** venmo has that autologin lacks: `.editorconfig`, `.gitattributes`,
   `.nvmrc` (venmo pins `v24`), `patchwork.json` (`{"redefinable-internals": ["constant"]}`).
   Copy each from venmo, adapt if needed.
6. **package.json**: update devDependencies toward venmo's
   (`@wordpress/env: "*"`, `@playwright/test ^1.58`, `@wordpress/e2e-test-utils-playwright ^1.40`,
   `dotenv ^17`, `@wordpress/scripts "*"`); keep autologin extras
   (`@woocommerce/woocommerce-rest-api`, `allure-playwright`) unless unused. Add venmo's npm
   scripts block (`wp-env`, `test:e2e`, `test:e2e:ui`, `test:e2e:report`). `npm install`, commit
   lockfile.
7. **GitHub Actions**: replace `codecoverage.yml` + `test-matrix.yml` with venmo's
   `unit-coverage.yml` (checkout@v6, matrix PHP 8.1–8.4 for this project since min is 8.1,
   coverage on one version, gh-pages coverage report, coverage PR comment, `.nvmrc` node setup,
   "Remove local repository references" step for the `../bh-wp-rate-limiter` path repo — CI can't
   see it, venmo's workflow shows the pattern). Also diff/update `phpcbf.yml`, `phpstan.yml`,
   `release.yml` against venmo's, and consider adding venmo's `e2e.yml`. Adapt slug and
   `src` vs `includes` paths everywhere. NOTE: current CI only ran unit+wpunit; the integration
   suite had been rotting since 2023 — consider adding it to CI now that it passes (needs the
   `wordpress/wordpress` git dependency and MySQL service; see venmo's workflow).
8. **Plugin header** (`bh-wp-autologin-urls.php`): `Tested up to: 6.6` → `7.0` (wp-env targets
   WordPress/WordPress#7.0.2). Also update `Tested up to` in README.txt.
9. **wp-env / E2E**: `npx wp-env start` (already running on your machine), run
   `tests/e2e-pw` Playwright tests, fix breakage. The `afterStart` script
   (`tests/e2e-pw/setup/initialize-external.sh`) has WIP edits (first commit) — verify they work.
   Compare against venmo's `tests/_wp-env/` layout (venmo moved e2e setup scripts there and its
   `.wp-env.json` maps `"../setup": "./tests/_wp-env"`).
10. **Final sweep for deprecation warnings**: run wpunit/integration with a PHP error log and
    grep for `Deprecated`; check `wp-content/uploads/logs/bh-wp-autologin-urls-*.log` after
    interacting with the wp-env site. Known remaining noise: `vendor/wp-cli/process/Process.php`
    emits PHP 8.4 implicit-nullable deprecations when WPLoader boots wp-cli (dev-dep only;
    check if newer wp-cli fixes it, else ignore). `klaviyo/sdk` is flagged abandoned by composer —
    runtime dep, left as-is deliberately (conservative; replacing it is a feature-level change).

## Decisions already made (keep consistent)

- PHP floor stays **8.1** (your WIP's choice; venmo uses 8.4 but autologin is a distributed
  WP.org plugin). Composer platform.php = 8.1.
- Runtime deps conservative: only compat-level src changes; `klaviyo/sdk` untouched;
  `brianhenryie/bh-wp-logger` pinned `^0.3.4` (0.3.4 = what venmo uses and what was locked).
- Delivery = multiple logical commits on `modernize-dev-environment`, never touching `dev`.

## Gotchas discovered (do not re-litigate)

- **MailPoet 5.34 breaks WP test-suite transaction isolation**: its Doctrine ORM issues
  `START TRANSACTION`/`COMMIT` on `user_register` (subscriber sync), implicitly committing the
  test framework's wrapping transaction → "username already exists" cascades, cross-run
  pollution. Fixed by unhooking `MailPoet\Segments\WP::synchronizeUser` in
  `tests/wpunit/_bootstrap.php`. If integration suite ever activates MailPoet, do the same there.
- **`MailPoet\Models\Subscriber` no longer exists** — src and tests now use
  `SubscribersRepository`/`LinkTokens`/`SubscriberEntity` via `ContainerWrapper`.
- **wp-browser 4 `remove_action('query', array($this, '_create_temporary_tables'))` no-ops**
  (filter registered on an internal core test case instance) — use `remove_all_filters('query')`;
  hooks are restored from backup in tear_down.
- **wp-browser 4 can't activate the plugin via the `wp-content/plugins` symlink** in its
  activation subprocess (`get_plugins()` there doesn't see the symlinked dir; root cause not
  fully diagnosed) — integration.suite.yml now passes the plugin as external path
  `'bh-wp-autologin-urls.php'`, which wp-browser resolves and registers itself.
- **`Login::process()` hooks `determine_current_user` (since daffd16, 2023) and removes itself
  after first run** — integration tests must call it directly, not `do_action('plugins_loaded')`.
- **Default From `wordpress@localhost` is rejected by PHPMailer** in the test env → mail tests
  need a `wp_mail_from` filter.
- `tests/bootstrap.php` now creates the `wp-content/plugins/bh-wp-autologin-urls` symlink for
  template resolution (the removed `brianhenryie/composer-phpstorm` config used to make it);
  your existing local checkout probably still has the old symlink, fresh clones won't.
- `.env.secret` can no longer be passed to `Codeception\Configuration::config()` (codeception 5
  requires yml) — Dotenv load alone is kept.

## Test coverage gaps noticed (worth addressing sometime)

- CI (test-matrix.yml) only ran unit + wpunit; the integration suite silently rotted for ~2 years.
- Malformed-autologin-querystring attempts are no longer rate-limit-recorded at all
  (`Login::process()` returns before `should_allow_login_attempt()` when no user is found) —
  the old tests asserted this protection existed. If that protection is intended, it's currently
  missing in src, and only the replaced tests would have caught it.
- The legacy codeception `acceptance` suite (WPBrowser/WPDb, `tests/acceptance`) is stale — venmo
  dropped it in favor of Playwright e2e. Its suite yml still uses wp-browser v3 module names
  (`WPDb`, `WPBrowser`) and will fail under wp-browser 4 if run (`codecept build` tolerates it).
  Either migrate the module names (`lucatume\WPBrowser\Module\*`) or delete the suite like venmo.
- `Klaviyo_WPUnit_Test` and others create users with no email; several tests rely on fixed
  usernames — fine now that transactions roll back, but brittle if isolation ever breaks again.
