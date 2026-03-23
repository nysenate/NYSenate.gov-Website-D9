<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\block_content\BlockContentInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use GuzzleHttp\Client;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Base class for NYSenate.gov cache regression tests.
 *
 * Makes real HTTP requests to DTT_BASE_URL so that actual Drupal cache headers
 * (x-drupal-cache, x-drupal-dynamic-cache, cache-control) are present — which
 * does not happen with Drupal's internal test client. All entity mutations are
 * non-destructive (re-saves with no field changes); all synthetic users are
 * cleaned up in tearDown().
 *
 * DTT_BASE_URL resolution order:
 *  1. Shell / CI environment variable (highest priority).
 *  2. tests/dtt/.env file (copy tests/dtt/.env.example to configure locally).
 *  3. Falls back to https://nysenate.ddev.site (DDEV default).
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
  public function topLevelPageProvider(): array {
    return $this->asProvider(self::TOP_LEVEL_PAGES);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Ensure DTT_BASE_URL is set before parent::setUp() consumes it.
    $this->ensureDttBaseUrl();

    parent::setUp();

    $this->anonClient = new Client([
      'base_uri' => getenv('DTT_BASE_URL') ?: 'https://nysenate.ddev.site',
      // Never follow redirects — a redirect itself is already a miss signal
      // worth catching explicitly.
      'allow_redirects' => FALSE,
      // Do not share a cookie jar across requests so no session bleeds through.
      'cookies' => FALSE,
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

  // ---------------------------------------------------------------------------
  // Anonymous cache helpers
  // ---------------------------------------------------------------------------

  /**
   * Warms the page cache for a path by issuing one anonymous request.
   *
   * The first request will always be a MISS; this helper discards the response
   * so subsequent assertions start from a warm-cache state.
   */
  protected function warmCache(string $path): void {
    $this->anonClient->get($path);
  }

  /**
   * Asserts that the next anonymous request returns x-drupal-cache: HIT.
   *
   * Does NOT warm the cache first — callers must call warmCache() before
   * any operation whose effect they want to test, then call this method.
   * Internally re-warming would mask cache invalidations and produce
   * false positives in negative test cases.
   */
  protected function assertAnonymousCacheHit(string $path): void {
    $response = $this->anonClient->get($path);
    $header = strtoupper(trim($response->getHeaderLine('x-drupal-cache')));
    $this->assertSame('HIT', $header,
      "Expected x-drupal-cache: HIT on anonymous request to {$path}, got: {$header}");
  }

  /**
   * Asserts that the next anonymous request returns x-drupal-cache: MISS.
   *
   * Does NOT warm the cache first — callers control when to issue this.
   */
  protected function assertAnonymousCacheMiss(string $path): void {
    $response = $this->anonClient->get($path);
    $header = strtoupper(trim($response->getHeaderLine('x-drupal-cache')));
    $this->assertSame('MISS', $header,
      "Expected x-drupal-cache: MISS on anonymous request to {$path}, got: {$header}");
  }

  /**
   * Asserts cache-control max-age header on an anonymous request.
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

  // ---------------------------------------------------------------------------
  // Authenticated / dynamic cache helpers
  // ---------------------------------------------------------------------------

  /**
   * Asserts that a logged-in request returns x-drupal-dynamic-cache: HIT.
   *
   * Uses the DTT session-based browser so that the user's session cookie is
   * automatically included.
   */
  protected function assertDynamicCacheHit(string $path): void {
    $this->visit($path);
    $header = strtoupper(trim($this->getSession()->getResponseHeader('x-drupal-dynamic-cache') ?? ''));
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
  protected function asProvider(array $paths): array {
    return array_combine($paths, array_chunk($paths, 1));
  }

}
