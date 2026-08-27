# Cache Regression Tests (DTT)

Automated cache regression tests for NYSenate.gov, built on [Drupal Test Traits](https://gitlab.com/weitzman/drupal-test-traits) (DTT) `ExistingSiteBase`. Unlike `BrowserTestBase` — which installs a fresh Drupal database and uses an internal test client — DTT's `ExistingSiteBase` makes real HTTP requests to an already-running site. This means responses pass through the actual cache infrastructure (Redis, Cloudflare) and carry real cache headers (`x-drupal-cache`, `x-drupal-dynamic-cache`, `cache-control`, `cf-cache-status`) that reflect production behavior.

The suite is designed to exercise the full cache stack on both local and Pantheon environments:
- **DDEV / VM:** Redis is the page cache backend. `x-drupal-cache` is the authoritative cache status header.
- **Pantheon (production and multidev):** Cloudflare sits in front of PHP-FPM. `cf-cache-status` (Cloudflare) is the authoritative header — `x-drupal-cache` reflects only what PHP-FPM returned and is stale on subsequent Cloudflare hits. Cache invalidations reach Cloudflare via BAN dispatch: `pantheon_advanced_page_cache` calls `pantheon_clear_edge_keys()` inside `CacheTagsInvalidator::invalidateTags()`.

`CacheTestBase::getCacheStatus()` normalises across all environments automatically.

## What these tests ensure

**Anonymous page cache** (`AnonymousCacheHitTest`)
- All 6 top-level navigation pages return a CDN cache HIT (`cf-cache-status: HIT` on Cloudflare environments, `x-drupal-cache: HIT` on local) after the first request, and each declares `cache-control: max-age=86400, public`.
- All 7 primary content type display pages (article, bill, event, in_the_news, meeting, public_hearing, resolution) return a cache HIT and declare a 24-hour `cache-control: max-age=86400, public` lifetime.

**Anonymous cache non-invalidation** (`AnonymousCacheNonInvalidationTest`)
- All saves use `saveEntity()` so Cloudflare BANs are genuinely dispatched before the HIT assertions run — the tests verify that the BAN was correctly scoped, not merely that no BAN occurred.
- Editing an article node does not invalidate top-level pages that articles do not feed (e.g. /legislation, /events, /senators-committees, /about).
- Editing a bill node does not invalidate top-level pages that bills do not feed (e.g. /, /news-and-issues, /events, /senators-committees, /about).
- Editing an event node does not invalidate top-level pages that events do not feed (e.g. /news-and-issues, /legislation, /senators-committees, /about).
- Editing a petition does not invalidate any top-level page.

**Cache invalidation** (`CacheMissInvalidationTest`)
- All invalidation tests follow the canonical `assertCacheMissOnSave($path, $entity)` sequence: warm cache → assert HIT → `saveEntity($entity)` (save + flush Pantheon BAN buffer) → assert MISS → assert HIT (re-cached).
- Top-level page invalidation:
  - `/` is invalidated by article edits, event edits, and homepage hero queue changes.
  - `/news-and-issues` is invalidated by article edits.
  - `/senators-committees` is invalidated by senator or committee term edits.
  - `/legislation` is invalidated by bill edits.
  - `/events` is invalidated by event edits.
  - `/about` is invalidated by landing page node edits and by edits to block_content entities embedded via `field_landing_blocks`.
- Content type display page invalidation — direct node edit: bill and article display pages are invalidated when their node is saved. bill is tested because BillsHelper runs complex save-time logic; article represents the standard `node:{nid}` tag path shared by all other types.
- Content type display page invalidation — related entity edit:
  - Article and in_the_news pages are invalidated by senator term edits (via `field_senator_multiref`).
  - Event, meeting, and public_hearing pages are invalidated by committee term edits (via `field_committee`).
  - Resolution pages are invalidated by senator term edits (via `field_ol_sponsor`).
- The homepage hero test exercises the real production code path: it fills the entity subqueue autocomplete and presses "Add item", triggering `HomepageHeroController::homepageHeroAddItem()` which calls `invalidateTags(['views:homepage_hero'])`. The "Add item" button does not invoke the main entity save handler, so the queue contents are not permanently modified.

**Authenticated dynamic page cache** (`AuthenticatedDynamicCacheTest`)
- All assertions use the cross-user pattern: User A warms the dynamic cache skeleton; User B's first visit must return `x-drupal-dynamic-cache: HIT`. This is the correct regression pattern — a per-user cache bug (missing `#create_placeholder => TRUE` on a lazy builder) would cause same-user repeat-visit tests to pass while cross-user tests fail.
- Verified on two representative top-level pages (/ and /legislation), two representative content type display pages (bill and article), and one senator-microsite content type page (article with `field_senator_multiref`).
- Any direct entity save on a user account (even with no field changes) busts that user's warmed entries via the `user:{uid}` cache tag.
- The dynamic page cache bin is cleared in `setUp()` to guarantee a cold-cache starting state for every test run.

**Cache poisoning prevention** (`NoCachePoisoningTest`)
- Per-user lazy builder output — issue follow/unfollow state, committee follow/unfollow state, the header user menu welcome message, and the district senator link in the "I Want To" block — is personalized correctly per user and never leaked across users via the shared dynamic cache skeleton.
- An authenticated visit does not cause authenticated content to be served to anonymous visitors.

## Running in CI (Pantheon multidev)

Tests run automatically on every pull request from a `feature/*` branch via the `run_cache_tests` job in `.github/workflows/pantheon-deploy-multidev.yml`. The job:

1. Deploys code to the Pantheon multidev for that PR (done by the preceding `deploy_multidev` job).
2. Wakes the multidev (Pantheon environments sleep when idle).
3. Runs PHPUnit **on the Pantheon container itself** via `terminus remote:drush -- ev`.

The CI step reads `tests/dtt/test-chunks.yml` (using `python3`, available on `ubuntu-latest`) and invokes `run-on-container.sh` via `terminus remote:drush -- ev` **once per chunk**, passing each chunk's filter via `--filter`. One terminus call per chunk bounds each SSH session to a single chunk's runtime, preventing Pantheon's SSH connection timeout from aborting mid-suite. `run-on-container.sh` passes all extra arguments straight through to PHPUnit, so `--filter` flows through naturally.

```bash
terminus remote:drush nysenate-2022.pr-NNN -- \
  ev "error_reporting(E_ERROR); passthru('bash /code/tests/dtt/run-on-container.sh https://pr-NNN-nysenate-2022.pantheonsite.io --filter testHomepageMissOnArticleEdit 2>&1', \$c); if (\$c !== 0) { throw new \Exception('PHPUnit failed with exit code ' . \$c); }"
```

Pass/fail status for each test class is visible directly on the PR via the `run_cache_tests` job.

### Why tests run on the container

The `$anonClient->get()` assertions are pure HTTP and would work from any external machine. The constraint is on the other side: **DTT bootstraps a full Drupal instance inside the test process**, and that Drupal instance connects to the same Pantheon-managed Redis the web server uses — for cache bin operations (`deleteAll()`), entity saves that write cache-tag checksums, flag state writes, and DTT's teardown deletions. Pantheon's Redis is only reachable from within the container network; there is no supported SSH tunnel from an external CI VM.

Two alternatives exist, each with significant trade-offs:

- **Run DTT on a CI VM with a DB snapshot** — solves the Redis constraint, but eliminates Cloudflare from the test path entirely. The invalidation tests degrade to exercising only the Drupal/Redis layer with no CDN to BAN. DB restore time (~2 hours) is a secondary concern.
- **Rewrite as pure black-box HTTP tests** — sidesteps Redis by eliminating all in-process Drupal operations, but loses entity creation, controlled test users, DB queries for test specimen discovery, flag operations, and DTT teardown. Tests become brittle against live content state.

Running on the Pantheon container is the only approach that exercises the full stack — Drupal, Redis, and Cloudflare — without those trade-offs.

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

`tests/dtt/run-all-chunks.sh` (the same script the CI workflow delegates to) runs the full suite in the same chunked, on-container manner as CI. `PANTHEON_TEST_UA` must be exported before calling it:

```bash
# Full suite — all chunks, same as CI
PANTHEON_TEST_UA=<token> bash tests/dtt/run-all-chunks.sh \
  nysenate-2022.pr-NNN \
  https://pr-NNN-nysenate-2022.pantheonsite.io

# Single test class or method — call run-on-container.sh directly via Terminus
terminus remote:drush nysenate-2022.pr-NNN -- \
  ev "error_reporting(E_ERROR); putenv('PANTHEON_TEST_UA=<token>'); passthru('/code/tests/dtt/run-on-container.sh https://pr-NNN-nysenate-2022.pantheonsite.io --filter AnonymousCacheHitTest', \$c); if (\$c !== 0) exit(\$c);"
```

**Prefer PR multidevs over `dev`.** The `dev` environment serves production-volume traffic and may have cold caches, making pages significantly slower to respond. `warmCache()` allows up to 20 × 60-second attempts per page — on a cold or busy environment this can push individual chunks past Pantheon's 10-minute SSH idle timeout, killing the connection mid-test. PR multidevs are dedicated and consistently faster.

See **Why tests run on the container** above for why this is the only approach that works reliably for all test classes.

## Key considerations

- **Production database required.** Tests query real content (nodes, taxonomy terms, entityqueues). Running against a fresh install with no content will cause tests to fail. A clone of the production database is required for full coverage.
- **Redis must be running.** The site uses Redis as the page cache backend. If Redis is not running, `x-drupal-cache` headers will be absent and all cache header assertions will fail. In CI, this is guaranteed because tests run inside the Pantheon container. Locally, ensure DDEV's Redis service is running (`ddev redis-cli ping`).
- **Tests are non-destructive.** Entity saves change no field values. The homepage hero test presses "Add item" (not the main Save button), so no queue changes are persisted. Flag operations are cleaned up in `finally` blocks. Synthetic users created by all test classes are deleted after each test run by DTT's built-in teardown.
- **All HTTP requests use a Cloudflare-allowlisted User-Agent.** Cloudflare Bot Management on Pantheon challenges automated HTTP clients by default. Pantheon has allowlisted a custom UA string so that both the anonymous Guzzle client (`$anonClient`) and the Mink/BrowserKit client used for authenticated page visits pass through to PHP-FPM without being challenged. The UA value is read at runtime from the `PANTHEON_TEST_UA` environment variable (a GitHub Actions repository secret in CI; set in `tests/dtt/.env` for local Pantheon-targeted runs). It is **not** stored in source control — it is a Bot Management allowlist bypass token and must be treated as a secret. Any change to the token must be coordinated with Pantheon support.
- **Login uses a programmatic session, not the OTL flow.** `CacheTestBase::drupalLogin()` bypasses Drupal's one-time login (OTL) HTTP flow. On Pantheon, Cloudflare Bot Management challenges BrowserKit's OTL request (BrowserKit cannot execute JavaScript to satisfy the challenge) so the session cookie is never delivered to BrowserKit's cookie jar. The fix writes the session directly to the `sessions` table (using Symfony's `_sf2_attributes` serialisation, the same format Drupal uses) and injects the session cookie into BrowserKit's jar. The cookie name is computed from `DTT_BASE_URL` rather than `\Drupal::request()` to guarantee the correct `SSESS` (HTTPS) prefix in CLI context. Login is test infrastructure — the assertions cover cache header behaviour, not authentication.
- **Entity saves use `CacheTestBase::saveEntity()` to flush Pantheon's BAN buffer.** `pantheon_clear_edge_keys()` does not dispatch BANs immediately — it buffers tags in a PHP static variable and defers the actual HTTP call to a shutdown function (`pantheon_clear_edge_keys_shutdown()`). In a web request (PHP-FPM) the process exits per-response so the BAN fires promptly, but in the long-running PHPUnit process the shutdown would only fire after all tests complete. `saveEntity()` calls `$entity->save()` then immediately calls `pantheon_clear_edge_keys_shutdown()` to flush the buffer synchronously. On local/DDEV environments the call is skipped via a `function_exists()` guard.
- **Cron noise is suppressed.** The `automated_cron` module emits PHP warnings on every request via `kernel.terminate`. These are a pre-existing issue unrelated to cache behavior and are suppressed via `$failOnPhpWatchdogMessages = FALSE` in `CacheTestBase`.
- **Local `services.yml` must not contain deprecated Symfony 7 session parameters.** If all tests fail locally with `warmCache() did not reach HIT`, check whether `web/sites/default/services.yml` still contains `sid_length` and `sid_bits_per_character`. These parameters were removed from `default.services.yml` in the Drupal 11 upgrade because Symfony 7.2 deprecated them. If they remain in your local `services.yml`, `NativeSessionStorage::setOptions()` calls `trigger_deprecation()` for each, the deprecation notice cascades through Drupal's error handler → `Messenger::addMessage()` → `KillSwitch::trigger()`, making every anonymous page uncacheable. Fix: remove those two parameters (and their comment blocks) from `web/sites/default/services.yml` and run `drush cr`.
- **Docker memory.** PHPUnit runs each test class in a separate PHP process (same as CI) so peak RSS stays manageable per process. Exit 137 (OOM/SIGKILL) should not occur under normal conditions; if it does, check for other resource-hungry containers sharing the same Docker VM.
- **Pantheon container memory and New Relic.** The test suite is split into 8 filter-based chunks defined in `tests/dtt/test-chunks.yml`, which is the single source of truth read by the CI workflow and the local DDEV command. `AnonymousCacheHitTest` is split into two chunks: top-level pages and content type + OpenLeg browse pages. `AnonymousCacheNonInvalidationTest` is its own chunk; `CacheMissInvalidationTest` is split into three chunks: top-level pages, content type node edits, and related entity edits. Each chunk runs as a separate PHP process via a dedicated `terminus remote:drush` SSH session. In practice each chunk uses ~40 MB of PHP memory; `run-on-container.sh` caps allocation at 512 MB with `-d memory_limit=512M`. **Critical:** `run-on-container.sh` also passes `-d newrelic.enabled=0`. Without this flag, Pantheon's New Relic PHP extension instruments every function call across the entire Drupal 11 codebase at runtime — the transaction trace buffer grows without bound and fills the Pantheon container's 9 GB cgroup memory limit, triggering SIGKILL (exit 137) on every chunk. The exit code looked like an OOM from large PHP heap allocations, but the actual cause was New Relic instrumentation overhead, not Drupal or test memory usage.
