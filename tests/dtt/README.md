# Cache Regression Tests (DTT)

Automated cache regression tests for NYSenate.gov, built on [Drupal Test Traits](https://gitlab.com/weitzman/drupal-test-traits) (DTT) `ExistingSiteBase`. Unlike `BrowserTestBase` — which installs a fresh Drupal database and uses an internal test client — DTT's `ExistingSiteBase` makes real HTTP requests to an already-running site. This means responses pass through the actual cache infrastructure (Redis, Fastly) and carry real cache headers (`x-drupal-cache`, `x-drupal-dynamic-cache`, `cache-control`, `x-cache`) that reflect production behavior.

The suite is designed to exercise the full cache stack on both local and Pantheon environments:
- **DDEV / VM:** Redis is the page cache backend. `x-drupal-cache` is the authoritative cache status header.
- **Pantheon:** Fastly sits in front of PHP-FPM. `x-cache` (Fastly) is the authoritative header — `x-drupal-cache` reflects only what PHP-FPM returned and is stale on subsequent Fastly hits. Cache invalidations must also reach Fastly via BAN dispatch, which happens in `kernel.terminate` after a real web request. `saveViaWebRequest()` exists for this reason: it submits entity edit forms as real HTTP POSTs so `pantheon_advanced_page_cache` dispatches BAN requests for the invalidated cache tags.

`CacheTestBase::getCacheStatus()` normalises across both environments automatically.

## What these tests ensure

**Anonymous page cache** (`AnonymousCacheHitTest`)
- All 6 top-level navigation pages return `x-drupal-cache: HIT` after the first request.
- All 7 primary content type display pages (article, bill, event, in_the_news, meeting, public_hearing, resolution) return `x-drupal-cache: HIT` after the first request.
- All 13 of those pages declare a 24-hour `cache-control: max-age=86400, public` lifetime.
- Editing article, bill, or event nodes does not invalidate top-level pages that those content types do not feed (cross-invalidation negative cases).
- Editing a petition does not invalidate any top-level page.
- Saving a node of one primary content type does not invalidate the display page of any other primary content type.
- All content-type edits in these negative cases are submitted via the entity edit form (same mechanism as `CacheMissInvalidationTest`) to ensure Fastly BAN dispatch is deterministic and avoid race conditions from synchronous CLI saves.

**Cache invalidation** (`CacheMissInvalidationTest`)
- All invalidation tests follow the canonical `assertCacheMissOnSave($path, $entity)` sequence: warm cache → assert HIT → save entity via HTTP POST → assert MISS → assert HIT (re-cached).
- Top-level page invalidation:
  - `/` is invalidated by article edits, event edits, and homepage hero queue changes.
  - `/news-and-issues` is invalidated by article edits.
  - `/senators-committees` is invalidated by senator or committee term edits.
  - `/legislation` is invalidated by bill edits.
  - `/events` is invalidated by event edits.
  - `/about` is invalidated by landing page node edits and by edits to block_content entities embedded via `field_landing_blocks`.
- Content type display page invalidation — direct node edit: all 7 primary content type display pages are invalidated when their node is saved.
- Content type display page invalidation — related entity edit:
  - Article and in_the_news pages are invalidated by senator term edits (via `field_senator_multiref`).
  - Event, meeting, and public_hearing pages are invalidated by committee term edits (via `field_committee`).
  - Resolution pages are invalidated by senator term edits (via `field_ol_sponsor`).
- The homepage hero test exercises the real production code path: it fills the entity subqueue autocomplete and presses "Add item", triggering `HomepageHeroController::homepageHeroAddItem()` which calls `invalidateTags(['views:homepage_hero'])`. The "Add item" button does not invoke the main entity save handler, so the queue contents are not permanently modified.

**Authenticated dynamic page cache** (`AuthenticatedDynamicCacheTest`)
- A second authenticated visit to each of the 6 top-level pages returns `x-drupal-dynamic-cache: HIT`. The dynamic page cache bin is cleared in `setUp()` to guarantee a cold-cache starting state for every test run.
- The dynamic cache skeleton is correctly shared across different authenticated users — User A's first visit produces a MISS; User B's first visit to the same page produces a HIT from User A's warmed entry.
- Any direct entity save on a user account (even with no field changes) busts that user's warmed entries via the `user:{uid}` cache tag.

**Cache poisoning prevention** (`NoCachePoisoningTest`)
- Per-user lazy builder output — issue follow/unfollow state, committee follow/unfollow state, and the header user menu welcome message — is personalized correctly per user and never leaked across users via the shared dynamic cache skeleton.
- An authenticated visit does not cause authenticated content to be served to anonymous visitors.

## Running in CI (Pantheon multidev)

Tests run automatically on every pull request from a `feature/*` branch via the `run_cache_tests` job in `.github/workflows/pantheon-deploy-multidev.yml`. The job:

1. Deploys code to the Pantheon multidev for that PR (done by the preceding `deploy_multidev` job).
2. Wakes the multidev (Pantheon environments sleep when idle).
3. Runs PHPUnit **on the Pantheon container itself** via `terminus remote:drush -- ev`.

The CI step invokes `tests/dtt/run-on-container.sh` on the container via `terminus remote:drush -- ev`, passing the multidev URL as the first argument. The script accepts additional PHPUnit arguments after the URL, which is useful when debugging a failing CI run — e.g. to re-run a single test:

```bash
terminus remote:drush nysenate-2022.pr-NNN -- \
  ev "error_reporting(E_ERROR); passthru('bash /code/tests/dtt/run-on-container.sh https://pr-NNN-nysenate-2022.pantheonsite.io --filter testHomepageMissOnArticleEdit 2>&1', \$c); if (\$c !== 0) { throw new \Exception('PHPUnit failed with exit code ' . \$c); }"
```

Pass/fail status for each test class is visible directly on the PR via the `run_cache_tests` job.

### Why tests run on the container

This is an architectural constraint driven by specific operations in the test suite, not a general configuration choice. Some context first: `saveViaWebRequest()` and all of the `$anonClient->get()` assertions (warmCache, assertAnonymousCacheHit, assertAnonymousCacheMiss) are pure HTTP — they would work fine from an external GitHub Actions VM. The constraint comes from the other side: **DTT bootstraps a full Drupal instance inside the test process** for entity creates, entity queries, cache bin operations, and teardown. On Pantheon, Drupal's configured cache backend is the Pantheon-managed Redis. That Redis is only reachable from within the container network — there is no supported SSH tunnel path to it from outside.

The specific test operations that require co-location with the web server's Redis are:

1. **`\Drupal::cache('dynamic_page_cache')->deleteAll()` in `AuthenticatedDynamicCacheTest::setUp()`** — This must operate on the same Redis the web server uses. It establishes the cold-cache precondition that makes the MISS → HIT sequence in `testConstituentSecondVisitIsDynamicCacheHit` and `testDynamicCacheSharedAcrossUsers` reliable. If the test process connects to a different cache backend (e.g., a local database cache), this call is a no-op against the real cache — Pantheon's Redis still has warm entries from prior traffic, so the cold-cache MISS assertions fail spuriously on every run.

2. **`createUser()` and DTT's automatic teardown entity deletions (all four test classes)** — Entity creates and deletes in the test process write cache tag checksums to Redis (e.g., `user_list`, `user:{uid}`). The web server reads those same checksums to validate page cache entries. If the test process's Redis differs from the web server's, those invalidations are invisible to the web server — leading to stale pages or teardown pollution leaking into the next test run.

3. **`$this->userA->save()` in `testDynamicCacheMissAfterAccountChange()`** — This is a direct entity API save in the test process (intentionally not via `saveViaWebRequest()`, because the point of the test is that a CLI-style save of a user entity still busts the dynamic cache via the `user:{uid}` tag). That invalidation is written to Redis by the test process and must be visible to the web server for the subsequent `assertDynamicCacheMiss()` to observe a MISS.

4. **`$flagService->flag()` and `$flagService->unflag()` in `NoCachePoisoningTest`** — These Drupal service calls in the test process write flag state to the database and trigger cache tag invalidations in Redis. The web server uses those same tag states when rendering the flag UI. The flag state the assertions check is read from the database (reachable via tunnel), but the cache invalidations that make the rendered page reflect fresh flag state are written to Redis from the test process.

The alternative — rewriting as pure black-box HTTP tests with no DTT bootstrap — would sidestep the Redis constraint entirely, but would lose entity creation, controlled test users, database queries for test specimen discovery, service calls for flag operations, and DTT's automatic teardown. The tests would become brittle against whatever live content and session state happen to exist in the environment.

Running on the container is the minimal viable approach. `vendor/bin/phpunit` is available there because `vendor/` is included in the deployed artifact, and `terminus remote:drush -- ev` is the mechanism Pantheon exposes for executing arbitrary code on the container.

## Running locally

### With DDEV

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

### Without DDEV (VM or any local webserver)

1. Copy `tests/dtt/.env.example` to `tests/dtt/.env` and set `DTT_BASE_URL` to your local site URL:

   ```
   DTT_BASE_URL=http://nysenate.local
   ```

2. Run PHPUnit directly from the project root:

   ```bash
   php -d memory_limit=-1 vendor/bin/phpunit \
     -c tests/dtt/phpunit.xml \
     --testsuite existing-site \
     --group cache_regression \
     --testdox
   ```

   Or pass `DTT_BASE_URL` inline without editing `.env`:

   ```bash
   DTT_BASE_URL=http://nysenate.local \
     php -d memory_limit=-1 vendor/bin/phpunit \
     -c tests/dtt/phpunit.xml \
     --testsuite existing-site \
     --group cache_regression \
     --testdox
   ```

### Against a remote environment (Pantheon multidev or staging)

Set `DTT_BASE_URL` to the remote URL in `tests/dtt/.env` or inline, then run the same `vendor/bin/phpunit` command above. See **Why tests run on the container** above for why the full CI run executes on the Pantheon container itself rather than from an external machine.

## Key considerations

- **Production database required.** Tests query real content (nodes, taxonomy terms, entityqueues). Running against a fresh install with no content will cause tests to fail. A clone of the production database is required for full coverage.
- **Redis must be running.** The site uses Redis as the page cache backend. If Redis is not running, `x-drupal-cache` headers will be absent and all cache header assertions will fail. In CI, this is guaranteed because tests run inside the Pantheon container. Locally, ensure DDEV's Redis service is running (`ddev redis-cli ping`).
- **Tests are non-destructive.** Entity saves change no field values. The homepage hero test presses "Add item" (not the main Save button), so no queue changes are persisted. Flag operations are cleaned up in `finally` blocks. Synthetic users created by all test classes are deleted after each test run by DTT's built-in teardown.
- **Cron noise is suppressed.** The `automated_cron` module emits PHP warnings on every request via `kernel.terminate`. These are a pre-existing issue unrelated to cache behavior and are suppressed via `$failOnPhpWatchdogMessages = FALSE` in `CacheTestBase`.
- **Docker memory.** PHPUnit runs each test class in a separate PHP process (same as CI) so peak RSS stays well under 2 GB per class. Exit 137 (OOM/SIGKILL) should not occur under normal conditions; if it does, check for other resource-hungry containers sharing the same Docker VM.
- **Pantheon container memory.** The test suite is split into 7 filter-based chunks defined in `tests/dtt/test-chunks.yml`, which is the single source of truth read by both `run-on-container.sh` and the local DDEV command. `AnonymousCacheHitTest` is split into positive assertions (no saves) and negative cases (multiple warm + save + assert cycles per test); `CacheMissInvalidationTest` is split into top-level pages, content type node edits, and related entity edits. Each chunk runs as a separate PHP process, keeping peak RSS well under 1 GB; a single-process run exhausts the Pantheon container's 2 GB PHP limit due to Mink session state and Redis object accumulation. Each process uses `php -d memory_limit=2048M` to override the default 1 GB web limit.
