# Cache Regression Tests (DTT)

Automated cache regression tests for NYSenate.gov, built on [Drupal Test Traits](https://gitlab.com/weitzman/drupal-test-traits) (DTT) `ExistingSiteBase`. Unlike unit or kernel tests, these make real HTTP requests to a running Drupal site so that actual cache headers (`x-drupal-cache`, `x-drupal-dynamic-cache`, `cache-control`) are present in responses.

## What these tests ensure

**Anonymous page cache** (`AnonymousCacheHitTest`)
- All 6 top-level navigation pages return `x-drupal-cache: HIT` after the first request.
- All 6 pages declare a 24-hour `cache-control: max-age=86400, public` lifetime.
- Editing content that does not feed a given page leaves that page's cache intact (cross-invalidation negative cases). These edits are submitted via the entity edit form (same mechanism as `CacheMissInvalidationTest`) to ensure Fastly BAN dispatch is deterministic and avoids race conditions from synchronous CLI saves.

**Cache invalidation** (`CacheMissInvalidationTest`)
- Editing relevant content — articles, events, bills, senator/committee taxonomy terms, landing page nodes, embedded block content, and homepage hero queue changes — correctly invalidates the corresponding page cache entries.
- All entity edits are submitted via the Drupal UI (real HTTP POST) rather than the Drupal API, so `kernel.terminate` fires and `pantheon_advanced_page_cache` dispatches Fastly BAN requests for the invalidated cache tags.
- The homepage hero test exercises the real production code path: it fills the entity subqueue autocomplete and presses "Add item", triggering `HomepageHeroController::homepageHeroAddItem()` which invalidates the `views:homepage_hero` cache tag.

**Authenticated dynamic page cache** (`AuthenticatedDynamicCacheTest`)
- Authenticated users always get a `x-drupal-dynamic-cache: MISS` on first visit and `HIT` on second.
- Any account change (role, district, preferences) busts that user's warmed entries via the `user:{uid}` cache tag.

**Cache poisoning prevention** (`NoCachePoisoningTest`)
- The dynamic cache is correctly shared across authenticated users (not duplicated per user).
- Per-user lazy builder output — issue follow/unfollow state, committee follow/unfollow state, and the header user menu (name, senator link) — is personalized correctly per user and never leaked across users.
- An authenticated visit does not cause authenticated content to be served to anonymous visitors.

## Running in CI (Pantheon multidev)

Tests run automatically on every pull request from a `feature/*` branch via the `run_cache_tests` job in `.github/workflows/pantheon-deploy-multidev.yml`. The job:

1. Deploys code to the Pantheon multidev for that PR (done by the preceding `run_if` job).
2. Wakes the multidev (Pantheon environments sleep when idle).
3. Runs PHPUnit **on the Pantheon container itself** via `terminus remote:drush -- ev`.

**Why tests run on the container:** Drupal's cache tag invalidation works by incrementing integer checksums in Redis. When `$entity->save()` is called in the test process, it must increment those checksums in the *same* Redis instance the web server reads from — otherwise cache MISS assertions never trigger. Pantheon's Redis is not publicly reachable; running the test process inside the container ensures the test process, web server, database, and Redis are all co-located. `vendor/bin/phpunit` is available on the container because `vendor/` is included in the deployed artifact.

The CI step invokes `tests/dtt/run-on-container.sh` on the container via `terminus remote:drush -- ev`, passing the multidev URL as the first argument. The script accepts additional PHPUnit arguments after the URL, which is useful when debugging a failing CI run — e.g. to re-run a single test:

```bash
terminus remote:drush nysenate-2022.pr-NNN -- \
  ev "error_reporting(E_ERROR); passthru('bash /code/tests/dtt/run-on-container.sh https://pr-NNN-nysenate-2022.pantheonsite.io --filter testHomepageMissOnArticleEdit 2>&1', \$c); if (\$c !== 0) { throw new \Exception('PHPUnit failed with exit code ' . \$c); }"
```

The `functional_tests_passed` umbrella job depends on `run_cache_tests` and is the single status check to register in GitHub branch protection settings (Settings → Branches → branch protection rule for `main`). Adding `functional_tests_passed` as a required status check means all test jobs must pass before a PR can be merged, without needing to update branch protection rules as new test jobs are added.

## Running locally

The easiest way is via the DDEV custom command:

```bash
ddev run-cache-tests
```

This runs the full `cache_regression` group against `https://nysenate.ddev.site`. Additional options:

```bash
# Run a single test class or method
ddev run-cache-tests --filter AnonymousCacheHitTest
ddev run-cache-tests --filter testHomepageMissOnArticleEdit

# Run the full DTT suite (not just cache_regression group)
ddev run-cache-tests --all
```

To run against a remote environment (e.g. a Pantheon multidev), set `DTT_BASE_URL` in `tests/dtt/.env` (copy from `.env.example`) or as a shell environment variable:

```
DTT_BASE_URL=https://pr-123-nys-website.pantheonsite.io
```

Or pass it inline without DDEV:

```bash
DTT_BASE_URL=https://pr-123-nys-website.pantheonsite.io \
  php -d memory_limit=-1 vendor/bin/phpunit \
  -c tests/dtt/phpunit.xml \
  --testsuite existing-site \
  --group cache_regression \
  --testdox
```

## Key considerations

- **Production database required.** Tests query real content (nodes, taxonomy terms, entityqueues). Running against a fresh install with no content will cause tests to skip or fail. A clone of the production database is required for full coverage.
- **Redis must be running.** The site uses Redis as the page cache backend. If Redis is not running, `x-drupal-cache` headers will be absent and all cache header assertions will fail. In CI, this is guaranteed because tests run inside the Pantheon container. Locally, ensure DDEV's Redis service is running (`ddev redis-cli ping`).
- **Tests are non-destructive.** Entity saves change no field values. The homepage hero test presses "Add item" (not the main Save button), so no queue changes are persisted. Flag operations are cleaned up in `finally` blocks. Synthetic users created by all test classes are deleted after each test run by DTT's built-in teardown.
- **Cron noise is suppressed.** The `automated_cron` module emits PHP warnings on every request via `kernel.terminate`. These are a pre-existing issue unrelated to cache behavior and are suppressed via `$failOnPhpWatchdogMessages = FALSE` in `CacheTestBase`.
- **Docker memory.** If PHPUnit exits with code 137 (OOM/SIGKILL), increase Docker Desktop's memory allocation to at least 6–8 GB (Settings → Resources → Memory).
- **Pantheon container memory.** `run-on-container.sh` runs each of the four test classes as a separate PHP process (`--filter AnonymousCacheHitTest`, etc.). This keeps peak RSS well under 2 GB per process; a single-process run of all 64 tests exhausts the Pantheon container's 2 GB PHP limit partway through due to Mink session state and Redis object accumulation. Each process still uses `php -d memory_limit=2048M` to override the default 1 GB web limit.
