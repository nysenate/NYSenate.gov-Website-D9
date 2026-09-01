<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Base class for NYSenate.gov cache regression tests.
 *
 * Makes real HTTP requests to DTT_BASE_URL so that actual Drupal cache headers
 * (x-drupal-cache, x-drupal-dynamic-cache, cache-control) are present — which
 * does not happen with Drupal's internal test client.
 *
 * The suite is designed to exercise the full cache stack on both local and
 * Pantheon environments:
 *  - On local environments (DDEV, VM, etc.), Redis is the page cache backend
 *    and x-drupal-cache is the authoritative header.
 *  - On Pantheon (production and multidev), Cloudflare sits in front of
 *    PHP-FPM. cf-cache-status (Cloudflare) is the authoritative header;
 *    x-drupal-cache reflects only what PHP-FPM returned and is stale on
 *    subsequent Cloudflare hits. Cache invalidations reach Cloudflare via BAN
 *    dispatch: pantheon_advanced_page_cache calls pantheon_clear_edge_keys()
 *    synchronously inside CacheTagsInvalidator::invalidateTags(). That function
 *    buffers keys in a static array and flushes them to the Pantheon cache proxy
 *    via pantheon_clear_edge_keys_shutdown() at PHP process shutdown. In a web
 *    request (PHP-FPM) the process exits per-response, so BANs fire promptly. In
 *    this long-running PHPUnit process, saveEntity() explicitly calls
 *    pantheon_clear_edge_keys_shutdown() after each $entity->save() to dispatch
 *    the BAN immediately instead of waiting for process exit.
 *
 * getCacheStatus() normalises across all environments automatically.
 * assertCacheMissOnSave() encapsulates the canonical warm → HIT → save →
 * MISS → HIT test sequence used throughout CacheMissInvalidationTest.
 *
 * DTT_BASE_URL resolution order:
 *  1. Shell / CI environment variable (highest priority).
 *  2. tests/dtt/.env file (copy tests/dtt/.env.example to configure locally).
 *  3. Falls back to https://nysenate.ddev.site (DDEV default).
 *
 * All entity mutations are non-destructive (re-saves with no field changes);
 * all synthetic users are cleaned up in tearDown().
 *
 * @group cache_regression
 */
abstract class CacheTestBase extends ExistingSiteBase {

  /**
   * The 6 top-level navigation paths present on every NYSenate.gov environment.
   */
  protected const TOP_LEVEL_PAGES = [
    '/',
    '/news-and-issues',
    '/senators-committees',
    '/legislation',
    '/events',
    '/about',
  ];

  /**
   * The 7 primary content types covered by the cache regression suite.
   */
  public const PRIMARY_CONTENT_TYPES = [
    'article',
    'bill',
    'event',
    'in_the_news',
    'meeting',
    'public_hearing',
    'resolution',
  ];

  /**
   * A Guzzle HTTP client configured for anonymous (cookie-free) requests.
   *
   * A fresh instance is created per test to guarantee no session state leaks
   * between anonymous assertions.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $anonClient;

  /**
   * Suppress DTT's PHP watchdog failure checks.
   *
   * The automated_cron module emits Cron::processQueue() warnings on every
   * HTTP request via kernel.terminate (a pre-existing ultimate_cron issue).
   * These are unrelated to cache behavior and would cause false test failures,
   * so the watchdog check is disabled for this test suite.
   *
   * @var bool
   */
  protected $failOnPhpWatchdogMessages = FALSE;

  /**
   * Data provider supplying the 6 top-level paths as named PHPUnit datasets.
   */
  public static function topLevelPageProvider(): array {
    return self::asProvider(self::TOP_LEVEL_PAGES);
  }

  /**
   * Data provider supplying all 7 primary content type names as named datasets.
   */
  public static function contentTypeProvider(): array {
    return self::asProvider(self::PRIMARY_CONTENT_TYPES);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Ensure DTT_BASE_URL is set before parent::setUp() consumes it.
    $this->ensureDttBaseUrl();

    parent::setUp();

    // Set the allowlisted UA on the Mink/BrowserKit client so that all
    // authenticated requests (drupalLogin, saveViaWebRequest, etc.) pass
    // Cloudflare Bot Management on Pantheon. DrupalTestBrowser::doRequest()
    // translates HTTP_USER_AGENT → User-Agent header automatically.
    $this->getSession()->getDriver()->getClient()
      ->setServerParameter('HTTP_USER_AGENT', $this->pantheonTestUA());

    $this->anonClient = new Client([
      'base_uri' => getenv('DTT_BASE_URL'),
      // Never follow redirects — a redirect itself is already a miss signal
      // worth catching explicitly.
      'allow_redirects' => FALSE,
      // Do not share a cookie jar across requests so no session bleeds through.
      'cookies' => FALSE,
      // Fail fast rather than hanging indefinitely if the server stalls.
      'connect_timeout' => 15,
      'timeout' => 60,
      // Pantheon has allowlisted this UA in Cloudflare Bot Management so that
      // anonymous cache-header assertions are not challenged at the CDN edge.
      'headers' => ['User-Agent' => $this->pantheonTestUA()],
    ]);
  }

  /**
   * Ensures DTT_BASE_URL is present in the process environment.
   *
   * Resolution order:
   *  1. Already set in the environment (CI or `ddev run-cache-tests`) — no-op.
   *  2. Sourced from tests/dtt/.env if the file exists and is readable.
   *  3. Falls back to https://nysenate.ddev.site (DDEV default).
   */
  private function ensureDttBaseUrl(): void {
    if (getenv('DTT_BASE_URL') !== FALSE && getenv('DTT_BASE_URL') !== '') {
      return;
    }

    $envFile = dirname(__DIR__, 3) . '/.env';
    if (!is_readable($envFile)) {
      return;
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
      // Skip comments.
      if (str_starts_with(ltrim($line), '#')) {
        continue;
      }
      if (str_contains($line, '=')) {
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Only set if the variable is not already in the environment.
        if ($key !== '' && getenv($key) === FALSE) {
          putenv("{$key}={$value}");
        }
      }
    }

    // Step 3: fall back to the DDEV default if still unset.
    if (getenv('DTT_BASE_URL') === FALSE || getenv('DTT_BASE_URL') === '') {
      putenv('DTT_BASE_URL=https://nysenate.ddev.site');
    }
  }

  /**
   * Returns the Cloudflare-allowlisted User-Agent for automated test requests.
   *
   * The value is read from the PANTHEON_TEST_UA environment variable, which
   * must be set as a GitHub Actions repository secret in CI, or in
   * tests/dtt/.env for local Pantheon-targeted runs. It is intentionally not
   * stored in source control — the string is a Bot Management allowlist token
   * and must be treated as a secret. See tests/dtt/.env.example.
   *
   * On local DDEV environments where PANTHEON_TEST_UA is not set, an empty
   * string is returned; BrowserKit and Guzzle will send their default UA,
   * which is fine because there is no Cloudflare layer locally.
   */
  private function pantheonTestUA(): string {
    return (string) (getenv('PANTHEON_TEST_UA') ?: '');
  }

  /**
   * Returns the session cookie name for the current DTT_BASE_URL.
   *
   * Mirrors SessionConfiguration::getName(): SSESS/SESS prefix + first 32 hex
   * chars of SHA-256 of the hostname (getHost() with empty basepath for a
   * root-installed site). Uses DTT_BASE_URL rather than \Drupal::request()
   * because in CLI context the request host is 'localhost', not the public
   * Pantheon/DDEV hostname.
   */
  private function sessionCookieName(): string {
    $dttUrl = (string) (getenv('DTT_BASE_URL') ?: 'https://nysenate.ddev.site');
    $host   = parse_url($dttUrl, PHP_URL_HOST) ?: $dttUrl;
    $prefix = str_starts_with($dttUrl, 'https://') ? 'SSESS' : 'SESS';
    return $prefix . substr(hash('sha256', $host), 0, 32);
  }

  /**
   * Wraps a single anonymous GET with retry logic for transient errors.
   *
   * - 429 Too Many Requests: backs off using the Retry-After header (default
   *   5 s) then retries once. After 2 total 429 responses the exception is
   *   re-thrown so the caller's outer loop (warmCache, assertAnonymousCacheMiss,
   *   assertAnonymousCacheHit) can honour the rate-limit at a higher level
   *   rather than spending up to 4 minutes spinning here.
   * - 5xx Server Error: retries immediately up to 8 attempts.
   * - All other exceptions are re-thrown immediately.
   */
  private function anonGet(string $path): \Psr\Http\Message\ResponseInterface {
    $maxAttempts = 8;
    $max429 = 2;
    $count429 = 0;
    $lastException = NULL;
    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
      try {
        return $this->anonClient->get($path);
      }
      catch (ClientException $e) {
        if ($e->getResponse()->getStatusCode() === 429) {
          $lastException = $e;
          if (++$count429 >= $max429) {
            break; // Caller handles repeated rate-limiting.
          }
          $retryAfter = max(1, (int) ($e->getResponse()->getHeaderLine('Retry-After') ?: 5));
          sleep($retryAfter);
          continue;
        }
        throw $e;
      }
      catch (ServerException $e) {
        $lastException = $e;
        // Brief pause before retrying on 5xx.
        usleep(500000);
        continue;
      }
    }
    throw $lastException ?? new \RuntimeException("anonGet({$path}): failed after {$maxAttempts} attempts");
  }

  /**
   * {@inheritdoc}
   *
   * On Pantheon, Cloudflare Bot Management intercepts BrowserKit's OTL request
   * (BrowserKit cannot execute JavaScript to satisfy the Bot Management
   * challenge) so the SSESS session cookie is never delivered to BrowserKit's
   * cookie jar and every subsequent drupalUserIsLoggedIn() check fails.
   *
   * To work around this we bypass the HTTP OTL flow entirely: write the session
   * directly to the database (using Symfony's _sf2_attributes serialisation,
   * the same format Drupal's SessionHandler uses) and inject the session cookie
   * into BrowserKit's jar. Login is test infrastructure — the assertions cover
   * cache header behaviour, not authentication.
   *
   * The cookie name is computed from DTT_BASE_URL rather than
   * \Drupal::request() because in CLI context (drush ev) the request is not
   * HTTPS, so session_configuration would return the wrong SESS prefix instead
   * of the SSESS prefix the HTTPS web server uses.
   */
  protected function drupalLogin(AccountInterface $account): void {
    $rawSessionId = \Drupal\Component\Utility\Crypt::randomBytesBase64(32);

    \Drupal::database()->merge('sessions')
      ->key('sid', \Drupal\Component\Utility\Crypt::hashBase64($rawSessionId))
      ->fields([
        'uid'       => $account->id(),
        'hostname'  => '127.0.0.1',
        'timestamp' => \Drupal::time()->getRequestTime(),
        'session'   => '_sf2_attributes|' . serialize(['uid' => (string) $account->id()]),
      ])
      ->execute();

    $account->sessionId = $rawSessionId;
    $this->getSession()->setCookie($this->sessionCookieName(), $rawSessionId);
    // Required so drupalLogout() and drupalUserIsLoggedIn() work correctly.
    // The parent's drupalLogin() normally sets this; since we bypass it, we
    // must set it ourselves.
    $this->loggedInUser = $account;

    $this->assertTrue(
      $this->drupalUserIsLoggedIn($account),
      "User {$account->getAccountName()} successfully logged in."
    );
  }

  /**
   * {@inheritdoc}
   *
   * Mirrors drupalLogin(): bypasses the HTTP /user/logout flow entirely to
   * avoid Cloudflare Bot Management challenges on the logout endpoint. Deletes
   * the session row from the database and clears the session cookie from
   * BrowserKit's jar directly.
   */
  protected function drupalLogout(): void {
    if ($this->loggedInUser === FALSE) {
      return;
    }
    if (isset($this->loggedInUser->sessionId)) {
      \Drupal::database()->delete('sessions')
        ->condition('sid', \Drupal\Component\Utility\Crypt::hashBase64($this->loggedInUser->sessionId))
        ->execute();
    }
    // Clear all cookies from BrowserKit's jar so no authenticated state bleeds
    // into subsequent anonymous requests.
    $this->getSession()->getDriver()->getClient()->getCookieJar()->clear();
    $this->loggedInUser = FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Retries up to 3 times on HTTP 429 Too Many Requests responses from
   * Cloudflare. Mink/BrowserKit requests share the same CF rate-limit pool as
   * the Guzzle anonymous client. Without this guard, tests running after heavy
   * anonymous traffic (AnonymousCacheHitTest, CacheMissInvalidationTest) can
   * receive CF 429 error pages instead of Drupal pages, causing all session
   * and element assertions to fail.
   *
   * Sleeps 90 s between retries — long enough for CF’s sliding-window rate
   * limit to expire before the next attempt.
   */
  protected function visit(string $url): void {
    for ($attempt = 0; $attempt < 3; $attempt++) {
      parent::visit($url);
      if ($this->getSession()->getStatusCode() !== 429) {
        return;
      }
      if ($attempt < 2) {
        sleep(90);
      }
    }
    // All 3 attempts returned 429. Proceed so the next assertion produces a
    // clear diagnostic failure message rather than a silent retry loop.
  }

  // ---------------------------------------------------------------------------
  // Anonymous cache helpers
  // ---------------------------------------------------------------------------

  /**
   * Warms the page cache for a path and waits until a HIT is confirmed.
   *
   * The first request triggers rendering and initiates page cache storage.
   * On PHP-FPM environments (e.g. Pantheon), cache storage happens in
   * kernel.terminate AFTER the response is sent to the client, creating a
   * race window where a second request can arrive before the cache entry is
   * written. This method polls until a HIT is returned (or exhausts retries),
   * so subsequent assertions always start from a genuinely warm-cache state.
   *
   * On local environments the second request is usually an immediate HIT with no sleep.
   */
  protected function warmCache(string $path): void {
    // Each iteration serves as both the priming request (triggering rendering
    // and initiating page cache storage via kernel.terminate) and the HIT poll.
    // On PHP-FPM environments the cache entry is written after the response is
    // sent, so a 500 ms sleep between iterations lets kernel.terminate complete
    // before the next check. For already-warm pages the first iteration returns
    // HIT immediately, keeping the request count to 1 per call — important for
    // staying under CF per-URL rate limits when consecutive test classes warm
    // the same pages.
    $maxAttempts = 20;
    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
      try {
        $response = $this->anonGet($path);
        if ($this->getCacheStatus($response) === 'HIT') {
          return;
        }
      }
      catch (ClientException $e) {
        if ($e->getResponse()->getStatusCode() === 429) {
          // CF rate-limited this poll. Sleep long enough for the CF sliding
          // window to fully expire before the next attempt. Short sleeps (< the
          // window length) just reset the window with each retry and never
          // escape the rate limit. Default to 90 s if Retry-After is absent.
          $retryAfter = max(90, (int) ($e->getResponse()->getHeaderLine('Retry-After') ?: 90));
          sleep($retryAfter);
          continue;
        }
        // Non-429 4xx (e.g. 404, 403) — keep polling; the page may be in flux.
      }
      catch (\Exception $e) {
        // Any other persistent error after anonGet's own retries — keep polling.
      }
      usleep(500000);
    }
    $this->fail("warmCache({$path}): cache did not reach HIT after {$maxAttempts} attempts. Check that the page cache backend is running and that the page is actually cacheable.");
  }

  /**
   * Asserts that the next anonymous request returns x-drupal-cache: HIT.
   *
   * Does NOT warm the cache first — callers must call warmCache() before
   * any operation whose effect they want to test, then call this method.
   * Internally re-warming would mask cache invalidations and produce
   * false positives in negative test cases.
   *
   * Retries up to 5 times on 429 Too Many Requests, sleeping at least 90 s
   * each time. Fewer retries with a longer sleep lets CF's sliding-window rate
   * limit expire; many short retries reset the window and never escape it.
   */
  protected function assertAnonymousCacheHit(string $path): void {
    for ($attempt = 0; $attempt < 5; $attempt++) {
      try {
        $response = $this->anonGet($path);
        $status   = $this->getCacheStatus($response);
        $this->assertSame('HIT', $status,
          "Expected cache HIT on anonymous request to {$path}, got: {$status}");
        return;
      }
      catch (ClientException $e) {
        if ($e->getResponse()->getStatusCode() === 429 && $attempt < 4) {
          $retryAfter = max(90, (int) ($e->getResponse()->getHeaderLine('Retry-After') ?: 90));
          sleep($retryAfter);
          continue;
        }
        throw $e;
      }
    }
  }

  /**
   * Blocks until a set of pages have no BAN still propagating across the CDN.
   *
   * Used only by AnonymousCacheNonInvalidationTest. That suite runs several
   * tests back-to-back in the same PHP process, and some of those tests'
   * saves *correctly* invalidate a page (e.g. an event edit invalidates
   * /events) that a *later* test then asserts is still a HIT (because that
   * later test's own save doesn't touch it). Cloudflare's BAN dispatch for
   * the earlier, correct invalidation is asynchronous —
   * pantheon_clear_edge_keys_shutdown() returns as soon as the BAN request is
   * accepted, not once every edge node has processed it — so it can still be
   * propagating when the next test starts, producing a MISS that has nothing
   * to do with the entity that next test saves.
   *
   * Rather than guessing a fixed sleep duration, this actively detects
   * whether a page's cache state is still in flux: warm it to a HIT, wait a
   * gap, then re-check with a bare request. If it's still HIT after the gap,
   * no BAN landed during that window and the page is considered settled. If
   * it flipped to MISS, a pending BAN just arrived — re-warm and repeat.
   * Bounded by $maxRounds so a genuinely broken page still fails fast via the
   * caller's own warmCache()/assertAnonymousCacheHit() rather than hanging
   * here indefinitely.
   *
   * Call this before a test's own warm → save → assert sequence, not after,
   * so that the final assertion can stay strict (assertAnonymousCacheHit())
   * and keep its ability to catch a genuine over-invalidation bug introduced
   * by *this* test's own save.
   *
   * On local/DDEV there is no CDN layer and no asynchronous BAN to wait out —
   * x-drupal-cache reflects Redis state immediately — so the wait/re-check
   * loop is skipped entirely and this just warms each page once.
   */
  protected function settleCachePages(array $paths, int $recheckAfterSeconds = 5, int $maxRounds = 6): void {
    $isPantheon = function_exists('pantheon_clear_edge_keys_shutdown');
    foreach ($paths as $path) {
      if (!$isPantheon) {
        $this->warmCache($path);
        continue;
      }
      for ($round = 0; $round < $maxRounds; $round++) {
        $this->warmCache($path);
        sleep($recheckAfterSeconds);
        try {
          $response = $this->anonGet($path);
          if ($this->getCacheStatus($response) === 'HIT') {
            break;
          }
        }
        catch (\Exception $e) {
          // Treat as unsettled and let the next round's warmCache() recover.
        }
      }
    }
  }

  /**
   * Asserts that an anonymous request returns a cache MISS.
   *
   * Cache invalidations in this suite are triggered by saveEntity(), which
   * calls $entity->save() and then immediately flushes Pantheon's BAN buffer
   * via pantheon_clear_edge_keys_shutdown(). There is still a window between
   * the BAN dispatch and Cloudflare processing it on the specific edge this
   * request happens to land on — the same asynchronous, unbounded propagation
   * gap documented at length on settleCachePages() and in
   * tests/dtt/README.md. This method polls until cf-cache-status: MISS is
   * confirmed, the same race warmCache() handles in reverse. The polling
   * window (~45 s) is deliberately generous: an earlier, much shorter window
   * (~5 s) was observed to intermittently fail in CI on pages that were
   * genuinely invalidated correctly — the BAN had simply not finished
   * propagating yet.
   *
   * On local environments there is no CDN; getCacheStatus() falls back to
   * x-drupal-cache which reflects the Redis state and returns MISS immediately
   * after a save.
   */
  protected function assertAnonymousCacheMiss(string $path): void {
    $status = '';
    $maxAttempts = 45;
    for ($attempt = 0; $attempt <= $maxAttempts; $attempt++) {
      try {
        $response = $this->anonGet($path);
        $status = $this->getCacheStatus($response);
        if ($status === 'MISS') {
          return;
        }
      }
      catch (ServerException $e) {
        // 5xx — origin processed the request; effectively a MISS.
        return;
      }
      catch (ClientException $e) {
        if ($e->getResponse()->getStatusCode() === 429) {
          // CF rate-limited this request. Sleep long enough for the sliding
          // window to expire before retrying — short sleeps just extend it.
          $retryAfter = max(90, (int) ($e->getResponse()->getHeaderLine('Retry-After') ?: 90));
          sleep($retryAfter);
          continue;
        }
        throw $e;
      }
      if ($attempt < $maxAttempts) {
        sleep(1);
      }
    }
    $this->assertSame('MISS', $status,
      "Expected cache MISS on anonymous request to {$path}, got: {$status}");
  }

  /**
   * Normalises the page cache status from whichever CDN header is present.
   *
   * Priority order:
   *  1. cf-cache-status (Cloudflare) — present on production (www.nysenate.gov)
   *     and Pantheon multidev environments (all environments are migrating to
   *     Cloudflare as the CDN layer). This is the authoritative signal.
   *     Common values: HIT, MISS, DYNAMIC (content not eligible for caching),
   *     BYPASS (cache skipped due to cookies or Cache-Control: private).
   *     warmCache() will poll and eventually fail with a descriptive message if
   *     Cloudflare returns DYNAMIC or BYPASS, indicating the page is not
   *     publicly cacheable.
   *  2. x-cache (Fastly) — legacy fallback for Pantheon multidev environments
   *     not yet migrated to Cloudflare. x-cache may be a comma-separated list
   *     (e.g. "MISS, HIT"); the last token is the most recent CDN result.
   *  3. x-drupal-cache — present on local environments (DDEV, VM, etc.) where
   *     there is no CDN layer.
   *
   * Returns 'HIT', 'MISS', or the raw uppercase value if neither maps to those
   * (e.g. 'DYNAMIC', 'BYPASS'). Returns an empty string if no header is present.
   */
  private function getCacheStatus(ResponseInterface $response): string {
    $cfCacheStatus = $response->getHeaderLine('cf-cache-status');
    if ($cfCacheStatus !== '') {
      return strtoupper(trim($cfCacheStatus));
    }
    $xCache = $response->getHeaderLine('x-cache');
    if ($xCache !== '') {
      $parts = array_map('trim', explode(',', $xCache));
      return strtoupper((string) end($parts));
    }
    return strtoupper(trim($response->getHeaderLine('x-drupal-cache')));
  }

  /**
   * Returns TRUE if an anonymous GET to $path yields a 2xx response with a
   * public Cache-Control header, indicating the page is eligible for CDN caching.
   *
   * Used by findNodeWithReferencedTerm() to skip candidate nodes whose canonical
   * pages are not publicly accessible or are configured as non-cacheable
   * (e.g. senator-microsite pages returned as CF BYPASS, 404s from broken path
   * aliases, or pages with max-age: 0 due to un-lazy-built form elements).
   */
  private function isPageAnonymouslyCacheable(string $path): bool {
    try {
      $response = $this->anonClient->get($path);
      return $response->getStatusCode() >= 200
        && $response->getStatusCode() < 300
        && str_contains($response->getHeaderLine('cache-control'), 'public');
    }
    catch (ClientException $e) {
      // 429: CF is rate-limiting this URL. The page exists and is publicly
      // accessible — treat it as cacheable rather than discarding the candidate
      // and firing more requests to other URLs. Other 4xx (404, 403) indicate
      // the page is missing or access-denied and are correctly filtered out.
      return $e->getResponse()->getStatusCode() === 429;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Asserts cache-control max-age header on an anonymous request.
   *
   * @deprecated Use assertAnonymousCacheHitWithMaxAge() to combine the HIT and
   *   max-age assertions in a single request and avoid unnecessary round-trips.
   */
  protected function assertCacheControlMaxAge(string $path, int $expectedMaxAge = 86400): void {
    $response = $this->anonClient->get($path);
    $cacheControl = $response->getHeaderLine('cache-control');
    $this->assertStringContainsString(
      "max-age={$expectedMaxAge}",
      $cacheControl,
      "Expected cache-control: max-age={$expectedMaxAge} for {$path}, got: {$cacheControl}"
    );
    $this->assertStringContainsString(
      'public',
      $cacheControl,
      "Expected cache-control to include 'public' for {$path}, got: {$cacheControl}"
    );
  }

  /**
   * Asserts cache HIT and public max-age in a single anonymous request.
   *
   * Combines assertAnonymousCacheHit() and assertCacheControlMaxAge() into one
   * round-trip. Use this instead of calling both methods separately to avoid
   * doubling the per-page request count, which can trigger CF rate limits when
   * multiple test classes warm the same URLs back-to-back.
   *
   * Retries up to 5 times on 429 Too Many Requests, sleeping at least 90 s
   * each time — see assertAnonymousCacheHit() for the sliding-window rationale.
   */
  protected function assertAnonymousCacheHitWithMaxAge(string $path, int $expectedMaxAge = 86400): void {
    for ($attempt = 0; $attempt < 5; $attempt++) {
      try {
        $response = $this->anonGet($path);
        $status   = $this->getCacheStatus($response);
        $this->assertSame('HIT', $status,
          "Expected cache HIT on anonymous request to {$path}, got: {$status}");
        $cacheControl = $response->getHeaderLine('cache-control');
        $this->assertStringContainsString(
          "max-age={$expectedMaxAge}",
          $cacheControl,
          "Expected cache-control: max-age={$expectedMaxAge} for {$path}, got: {$cacheControl}"
        );
        $this->assertStringContainsString(
          'public',
          $cacheControl,
          "Expected cache-control to include 'public' for {$path}, got: {$cacheControl}"
        );
        return;
      }
      catch (ClientException $e) {
        if ($e->getResponse()->getStatusCode() === 429 && $attempt < 4) {
          $retryAfter = max(90, (int) ($e->getResponse()->getHeaderLine('Retry-After') ?: 90));
          sleep($retryAfter);
          continue;
        }
        throw $e;
      }
    }
  }

  /**
   * Saves an entity and immediately flushes the Pantheon edge-key BAN buffer.
   *
   * On Pantheon, pantheon_advanced_page_cache's CacheTagsInvalidator calls
   * pantheon_clear_edge_keys() during the save, but that function only buffers
   * the tags in a PHP static variable and defers the actual HTTP BAN dispatch
   * to a shutdown function (pantheon_clear_edge_keys_shutdown()). In a web
   * request (PHP-FPM) the process exits after each response, so the BAN fires
   * promptly. In this long-running PHPUnit process the shutdown function would
   * only run after all tests have completed — far too late for per-test
   * cache-header polls to see a MISS or verify that BANs are correctly scoped.
   *
   * This wrapper calls pantheon_clear_edge_keys_shutdown() immediately after
   * the save, dispatching the accumulated keys synchronously via
   * pantheon_clear_edge_keys_batch() (a direct HTTP POST to the Pantheon cache
   * proxy API). On local/DDEV environments the function guard is a no-op and
   * the save behaves identically to a bare $entity->save().
   *
   * Use this method for ALL entity saves inside test methods that exercise the
   * anonymous CDN cache layer (CacheMissInvalidationTest,
   * AnonymousCacheNonInvalidationTest, etc.) so that post-save cache-header
   * assertions reflect actual BAN dispatch rather than an absence of dispatch.
   */
  protected function saveEntity(EntityInterface $entity): void {
    $entity->save();
    if (function_exists('pantheon_clear_edge_keys_shutdown')) {
      pantheon_clear_edge_keys_shutdown();
    }
  }

  /**
   * Performs the standard warm → HIT → save → MISS → HIT assertion sequence.
   *
   * Warms the cache for $path, confirms a HIT, saves $entity via saveEntity()
   * (which also flushes the Pantheon edge-key BAN buffer), then confirms the
   * page transitions to MISS and back to HIT once re-cached.
   */
  protected function assertCacheMissOnSave(string $path, EntityInterface $entity): void {
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
    $this->saveEntity($entity);
    $this->assertAnonymousCacheMiss($path);
    $this->assertAnonymousCacheHit($path);
  }

  // ---------------------------------------------------------------------------
  // Authenticated / dynamic cache helpers
  // ---------------------------------------------------------------------------

  /**
   * Asserts that a logged-in request returns x-drupal-dynamic-cache: HIT.
   *
   * Uses the DTT session-based browser so that the user's session cookie is
   * automatically included.
   *
   * Retries up to 5 times with a 500 ms pause between attempts to handle the
   * narrow window on Pantheon where the dynamic page cache entry for the first
   * request may not yet be visible to a back-to-back second request (high-load
   * PHP-FPM processes can delay kernel.terminate cache writes slightly).
   */
  protected function assertDynamicCacheHit(string $path): void {
    $header = '';
    for ($attempt = 0; $attempt < 5; $attempt++) {
      $this->visit($path);
      $header = strtoupper(trim($this->getSession()->getResponseHeader('x-drupal-dynamic-cache') ?? ''));
      if ($header === 'HIT') {
        return;
      }
      if ($attempt < 4) {
        usleep(500000);
      }
    }
    $this->assertSame('HIT', $header,
      "Expected x-drupal-dynamic-cache: HIT on {$path}, got: {$header}");
  }

  /**
   * Asserts that a logged-in request returns x-drupal-dynamic-cache: MISS.
   */
  protected function assertDynamicCacheMiss(string $path): void {
    $this->visit($path);
    $header = strtoupper(trim($this->getSession()->getResponseHeader('x-drupal-dynamic-cache') ?? ''));
    $this->assertSame('MISS', $header,
      "Expected x-drupal-dynamic-cache: MISS on {$path}, got: {$header}");
  }

  // ---------------------------------------------------------------------------
  // Content / entity helpers
  // ---------------------------------------------------------------------------

  /**
   * Returns the first published node of a given content type, or NULL.
   *
   * @return \Drupal\node\NodeInterface|null
   */
  protected function findNodeByType(string $type): ?NodeInterface {
    $ids = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $type)
      ->condition('status', 1)
      ->sort('changed', 'DESC')
      ->range(0, 1)
      ->execute();
    if (empty($ids)) {
      return NULL;
    }
    return \Drupal::entityTypeManager()->getStorage('node')->load(reset($ids));
  }

  /**
   * Returns the first term of a given vocabulary, or NULL.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   */
  protected function findTermByVocabulary(string $vocabulary): ?TermInterface {
    $ids = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('vid', $vocabulary)
      ->range(0, 1)
      ->execute();
    if (empty($ids)) {
      return NULL;
    }
    return \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load(reset($ids));
  }

  /**
   * Returns the most recently changed published node of a given type that has
   * at least one value in the given field, or NULL if no such node exists.
   *
   * Use this instead of findNodeByType() when the test requires the node to
   * have a specific field populated (e.g. field_senator_multiref on articles).
   * findNodeByType() returns the most recently changed published node of that
   * type regardless of whether the field has data, so it can produce un-usable
   * specimens even on a full production DB clone.
   *
   * @return \Drupal\node\NodeInterface|null
   */
  protected function findNodeByTypeWithField(string $type, string $fieldName): ?NodeInterface {
    $ids = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $type)
      ->condition('status', 1)
      ->exists($fieldName)
      ->sort('changed', 'DESC')
      ->range(0, 1)
      ->execute();
    if (empty($ids)) {
      return NULL;
    }
    return \Drupal::entityTypeManager()->getStorage('node')->load(reset($ids));
  }

  /**
   * Returns the first published bill node that can be non-destructively saved.
   *
   * Bills with empty field_ol_base_print_no or field_ol_session fail on save
   * with an EntityStorageException from BillsHelper::generateBillVersionCacheKey().
   * This helper filters to only bills that have both fields populated.
   *
   * @return \Drupal\node\NodeInterface|null
   */
  protected function findSaveableBillNode(): ?NodeInterface {
    $ids = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'bill')
      ->condition('status', 1)
      ->condition('field_ol_base_print_no', '', '<>')
      ->condition('field_ol_session', '', '<>')
      ->sort('changed', 'DESC')
      ->range(0, 1)
      ->execute();
    if (empty($ids)) {
      return NULL;
    }
    return \Drupal::entityTypeManager()->getStorage('node')->load(reset($ids));
  }

  /**
   * Converts a plain string array into a named PHPUnit data provider array.
   *
   * PHPUnit requires each dataset to be an array (unpacked as method args).
   * Using the path as both key and value gives readable output in test results
   * ("data set '/about'" instead of "data set #3").
   */
  protected static function asProvider(array $paths): array {
    return array_combine($paths, array_chunk($paths, 1));
  }

  /**
   * Returns the canonical root-relative URL path for the most recently changed
   * published node of a given content type, or NULL if none is found.
   *
   * For bill nodes, delegates to findSaveableBillNode() so that only bills
   * with populated field_ol_base_print_no and field_ol_session are returned —
   * bills without those fields have no pathauto alias and Drupal throws a 404
   * in nys_bills_node_view_alter().
   *
   * For article, event and in_the_news nodes, delegates to
   * findNonSenatorNodeByType() to scope the test to nodes that do NOT have a
   * senator microsite association. Senator-associated nodes render additional
   * senator microsite blocks and are covered by dedicated senator-microsite
   * cache tests (e.g. testSenatorMicrositeContentTypeDynamicCacheHit).
   *
   * @return string|null
   */
  protected function findNodeUrlByType(string $type): ?string {
    $senator_microsite_types = ['article', 'event', 'in_the_news'];
    if ($type === 'bill') {
      $node = $this->findSaveableBillNode();
    }
    elseif (in_array($type, $senator_microsite_types)) {
      $node = $this->findNonSenatorNodeByType($type);
    }
    else {
      $node = $this->findNodeByType($type);
    }
    if ($node === NULL) {
      return NULL;
    }
    return $node->toUrl('canonical')->setAbsolute(FALSE)->toString();
  }

  /**
   * Returns the most recently changed published node of $type that does NOT
   * have field_senator_multiref populated, or NULL if none exists.
   *
   * Used by findNodeUrlByType() to scope generic cache tests to nodes that
   * are not senator-microsite-associated. Senator-associated nodes render the
   * senator microsite menu block and hero block in addition to the standard
   * page layout. Senator-microsite caching behaviour is covered by dedicated
   * tests in AuthenticatedDynamicCacheTest.
   *
   * @return \Drupal\node\NodeInterface|null
   */
  protected function findNonSenatorNodeByType(string $type): ?NodeInterface {
    $ids = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $type)
      ->condition('status', 1)
      ->notExists('field_senator_multiref')
      ->sort('changed', 'DESC')
      ->range(0, 1)
      ->execute();
    if (empty($ids)) {
      return NULL;
    }
    return \Drupal::entityTypeManager()->getStorage('node')->load(reset($ids));
  }

  /**
   * Returns the first taxonomy term referenced via an entity-reference field
   * on a node, or NULL if the field is absent or empty.
   *
   * Used by content-type display-page invalidation tests to find the specific
   * term whose save should bust a given node's cached page (e.g. the senator
   * tagged on an article, or the committee tagged on a meeting).
   *
   * @return \Drupal\taxonomy\TermInterface|null
   */
  protected function findReferencedTerm(NodeInterface $node, string $fieldName): ?TermInterface {
    if (!$node->hasField($fieldName)) {
      return NULL;
    }
    foreach ($node->get($fieldName) as $item) {
      if ($item->entity instanceof TermInterface) {
        return $item->entity;
      }
    }
    return NULL;
  }

  /**
   * Returns a [node, term] pair for the most recently changed published node of
   * $type that has $fieldName populated with a taxonomy term reference, or NULL
   * if no such node exists.
   *
   * @param string[] $notExistsFields
   *   Optional list of field names that must NOT exist on the node. Use this to
   *   exclude senator-microsite nodes from committee-based queries: e.g. passing
   *   ['field_senator_multiref'] limits results to nodes that are not associated
   *   with a senator microsite, whose pages are more reliably publicly cacheable.
   *
   * @return array{0: \Drupal\node\NodeInterface, 1: \Drupal\taxonomy\TermInterface}|null
   */
  protected function findNodeWithReferencedTerm(string $type, string $fieldName, array $notExistsFields = []): ?array {
    $query = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $type)
      ->condition('status', 1)
      ->exists($fieldName)
      ->sort('changed', 'DESC')
      ->range(0, 20);
    foreach ($notExistsFields as $excludeField) {
      $query->notExists($excludeField);
    }
    $ids = $query->execute();

    foreach ($ids as $nid) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      if (!$node) {
        continue;
      }
      $term = $this->findReferencedTerm($node, $fieldName);
      if (!$term) {
        continue;
      }
      // Verify the canonical page is publicly cacheable. Some nodes have broken
      // path aliases (404), senator-microsite pages that CF returns as BYPASS,
      // or embedded forms that suppress public caching — warmCache() would
      // never reach HIT for any of these.
      $canonicalPath = $node->toUrl('canonical')->setAbsolute(FALSE)->toString();
      if (!$this->isPageAnonymouslyCacheable($canonicalPath)) {
        continue;
      }
      return [$node, $term];
    }
    return NULL;
  }

  /**
   * Returns a [node, term] pair or fails the test if none is found.
   *
   * @return array{0: \Drupal\node\NodeInterface, 1: \Drupal\taxonomy\TermInterface}
   */
  protected function requireNodeWithReferencedTerm(string $type, string $fieldName, array $notExistsFields = []): array {
    return $this->findNodeWithReferencedTerm($type, $fieldName, $notExistsFields)
      ?? $this->fail("No published '{$type}' node with a '{$fieldName}' taxonomy term found.");
  }

  // ---------------------------------------------------------------------------
  // Require helpers — assert-and-return variants of the find helpers above.
  //
  // These methods call the corresponding find* helper and fail the test
  // immediately if the result is NULL. Use these in test methods to eliminate
  // the find + assertNotNull boilerplate at every call site.
  // ---------------------------------------------------------------------------

  /**
   * Returns the node whose URL alias matches $alias, or NULL if not found.
   */
  protected function findNodeByAlias(string $alias): ?NodeInterface {
    $path = \Drupal::service('path_alias.manager')->getPathByAlias($alias);
    if (preg_match('/node\/(\d+)/', $path, $matches)) {
      return \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load((int) $matches[1]);
    }
    return NULL;
  }

  /**
   * Returns the node whose URL alias matches $alias, or fails the test.
   */
  protected function requireNodeByAlias(string $alias): NodeInterface {
    return $this->findNodeByAlias($alias)
      ?? $this->fail("No node found with alias '{$alias}'.");
  }

  /**
   * Returns the most recently changed published node of a given type, or fails.
   */
  protected function requireNodeByType(string $type): NodeInterface {
    return $this->findNodeByType($type)
      ?? $this->fail("No published '{$type}' node found.");
  }

  /**
   * Returns the first term of a given vocabulary, or fails.
   */
  protected function requireTermByVocabulary(string $vocabulary): TermInterface {
    return $this->findTermByVocabulary($vocabulary)
      ?? $this->fail("No '{$vocabulary}' taxonomy term found.");
  }

  /**
   * Returns the most recently changed published node with a
   * populated field, or fails.
   */
  protected function requireNodeByTypeWithField(string $type, string $fieldName): NodeInterface {
    return $this->findNodeByTypeWithField($type, $fieldName)
      ?? $this->fail("No published '{$type}' node with field '{$fieldName}' populated found.");
  }



  /**
   * Returns the first published bill node that can be non-destructively saved, or fails.
   */
  protected function requireSaveableBillNode(): NodeInterface {
    return $this->findSaveableBillNode()
      ?? $this->fail('No published bill node with field_ol_base_print_no and field_ol_session populated found.');
  }

  /**
   * Returns the canonical root-relative URL path for the most recently changed
   * published node of a given content type, or fails.
   */
  protected function requireNodeUrlByType(string $type): string {
    return $this->findNodeUrlByType($type)
      ?? $this->fail("No published '{$type}' node found.");
  }

  /**
   * Returns the most recently changed published node of $type that HAS
   * field_senator_multiref populated (i.e. is associated with a senator
   * microsite), or NULL if none exists.
   *
   * Used by senator-microsite cache tests to verify that senator-tagged
   * article, event and in_the_news nodes cache correctly for authenticated
   * users. The senator microsite menu block and hero block are only rendered
   * for these nodes, so they must be targeted explicitly.
   *
   * @return \Drupal\node\NodeInterface|null
   */
  protected function findSenatorTaggedNodeByType(string $type): ?NodeInterface {
    $ids = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $type)
      ->condition('status', 1)
      ->exists('field_senator_multiref')
      ->sort('changed', 'DESC')
      ->range(0, 1)
      ->execute();
    if (empty($ids)) {
      return NULL;
    }
    return \Drupal::entityTypeManager()->getStorage('node')->load(reset($ids));
  }

  /**
   * Returns the canonical URL for the most recently changed published node of
   * $type that has field_senator_multiref set, or NULL if none is found.
   *
   * @return string|null
   */
  protected function findSenatorTaggedNodeUrlByType(string $type): ?string {
    $node = $this->findSenatorTaggedNodeByType($type);
    if ($node === NULL) {
      return NULL;
    }
    return $node->toUrl('canonical')->setAbsolute(FALSE)->toString();
  }

  /**
   * Returns the canonical URL for the most recently changed senator-tagged
   * published node of $type, or fails the test if none is found.
   */
  protected function requireSenatorTaggedNodeUrlByType(string $type): string {
    return $this->findSenatorTaggedNodeUrlByType($type)
      ?? $this->fail("No published senator-tagged '{$type}' node found.");
  }

  /**
   * Returns the first taxonomy term referenced via an entity-reference field
   * on a node, or fails.
   */
  protected function requireReferencedTerm(NodeInterface $node, string $fieldName): TermInterface {
    return $this->findReferencedTerm($node, $fieldName)
      ?? $this->fail("No term found for field '{$fieldName}'.");
  }

}
