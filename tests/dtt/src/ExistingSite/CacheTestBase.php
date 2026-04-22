<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use GuzzleHttp\Client;
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
 *  - On Pantheon, Fastly sits in front of PHP-FPM. x-cache (Fastly) is the
 *    authoritative header; x-drupal-cache reflects only what PHP-FPM returned
 *    and is stale on subsequent Fastly hits. Cache invalidations must also reach
 *    Fastly via BAN dispatch, which happens in kernel.terminate after a real web
 *    request — not during a CLI entity save. saveViaWebRequest() exists for this
 *    reason: it submits the entity edit form as a real HTTP POST so that
 *    kernel.terminate fires and pantheon_advanced_page_cache dispatches BAN
 *    requests for the invalidated cache tags.
 *
 * getCacheStatus() normalises across both environments automatically.
 * assertCacheMissOnSave() encapsulates the canonical warm → HIT → save →
 * MISS → HIT test sequence used throughout CacheMissInvalidationTest.
 *
 * All entity mutations are non-destructive (re-saves with no field changes);
 * all synthetic users are cleaned up in tearDown().
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
  public function topLevelPageProvider(): array {
    return $this->asProvider(self::TOP_LEVEL_PAGES);
  }

  /**
   * Data provider supplying all 7 primary content type names as named datasets.
   */
  public function contentTypeProvider(): array {
    return $this->asProvider(self::PRIMARY_CONTENT_TYPES);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Ensure DTT_BASE_URL is set before parent::setUp() consumes it.
    $this->ensureDttBaseUrl();

    parent::setUp();

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
    $this->anonClient->get($path);
    for ($attempt = 0; $attempt < 10; $attempt++) {
      $response = $this->anonClient->get($path);
      if ($this->getCacheStatus($response) === 'HIT') {
        return;
      }
      usleep(250000);
    }
    $this->fail("warmCache({$path}): cache did not reach HIT after 10 attempts. Check that the page cache backend is running and that the page is actually cacheable.");
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
    $status = $this->getCacheStatus($response);
    $this->assertSame('HIT', $status,
      "Expected cache HIT on anonymous request to {$path}, got: {$status}");
  }

  /**
   * Asserts that an anonymous request returns a cache MISS.
   *
   * All cache invalidations in this suite are triggered by saveViaWebRequest(),
   * which submits the entity edit form as a real HTTP POST through the full
   * stack. On Pantheon, PHP-FPM fires kernel.terminate after sending the
   * response, which causes pantheon_advanced_page_cache to dispatch a Fastly
   * BAN for the invalidated cache tags. There is a short window between the
   * save completing and Fastly processing the BAN, so this method polls until
   * x-cache: MISS is confirmed — the same race warmCache() handles in reverse.
   *
   * On local environments there is no Fastly; getCacheStatus() falls back to
   * x-drupal-cache which reflects the Redis state and returns MISS immediately
   * after a save.
   */
  protected function assertAnonymousCacheMiss(string $path): void {
    $status = '';
    for ($attempt = 0; $attempt <= 10; $attempt++) {
      $response = $this->anonClient->get($path);
      $status = $this->getCacheStatus($response);
      if ($status === 'MISS') {
        return;
      }
      if ($attempt < 10) {
        usleep(500000);
      }
    }
    $this->assertSame('MISS', $status,
      "Expected cache MISS on anonymous request to {$path}, got: {$status}");
  }

  /**
   * Normalises the page cache status from whichever header is present.
   *
   * Priority order:
   *  1. x-cache (Fastly/CDN) — present on Pantheon. This is the authoritative
   *     signal because Fastly faithfully replays the original x-drupal-cache
   *     header from the PHP-FPM response, making x-drupal-cache stale on
   *     subsequent Fastly hits. x-cache may be a comma-separated list
   *     (e.g. "MISS, HIT"); the last token is the most recent CDN result.
   *  2. x-drupal-cache — present on local environments (DDEV, VM, etc.) where there is no CDN layer.
   *
   * Returns 'HIT', 'MISS', or an empty string if neither header is present.
   */
  private function getCacheStatus(ResponseInterface $response): string {
    $xCache = $response->getHeaderLine('x-cache');
    if ($xCache !== '') {
      $parts = array_map('trim', explode(',', $xCache));
      return strtoupper((string) end($parts));
    }
    return strtoupper(trim($response->getHeaderLine('x-drupal-cache')));
  }

  /**
   * Saves an entity by submitting its edit form through the DTT browser.
   *
   * Submitting via real HTTP POST fires kernel.terminate on the web server,
   * which causes pantheon_advanced_page_cache to dispatch Fastly BAN requests
   * for any cache tags invalidated by the save. This is required for
   * assertAnonymousCacheMiss() to observe x-cache: MISS on Pantheon; CLI saves
   * ($entity->save()) correctly invalidate Redis but never reach Fastly because
   * kernel.terminate is never fired outside a web request.
   *
   * The caller must be logged in (e.g. via drupalLogin()) before calling this.
   */
  protected function saveViaWebRequest(EntityInterface $entity): void {
    $path = $entity->toUrl('edit-form')->setAbsolute(FALSE)->toString();
    $this->visit($path);
    $this->getSession()->getPage()->pressButton('Save');
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

  /**
   * Performs the standard warm → HIT → save → MISS → HIT assertion sequence.
   *
   * This is the canonical test pattern for cache-miss invalidation: warm the
   * cache for $path, confirm a HIT, save $entity via a real HTTP POST so that
   * kernel.terminate fires and Fastly BANs are dispatched, then confirm the
   * page transitions to MISS and back to HIT once re-cached.
   */
  protected function assertCacheMissOnSave(string $path, EntityInterface $entity): void {
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
    $this->saveViaWebRequest($entity);
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
  protected function asProvider(array $paths): array {
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
   * For event and in_the_news nodes, delegates to
   * findNonSenatorNodeByType() to exclude senator-microsite-associated nodes
   * (those with field_senator_multiref populated). Senator-associated nodes
   * render the senator microsite hero block, which sets a per-user variable
   * in its preprocess hook without a corresponding cache context, causing
   * max-age: 0 to bubble up for authenticated users. This is a pre-existing
   * issue in senator microsite rendering unrelated to the bill cache fix.
   *
   * @return string|null
   */
  protected function findNodeUrlByType(string $type): ?string {
    $senator_microsite_types = ['event', 'in_the_news'];
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
   * Senator-associated nodes (those with field_senator_multiref set) render the
   * senator microsite hero block, which sets per-user template variables
   * without declaring a 'user' cache context. This causes max-age: 0 to bubble
   * up for authenticated users, making the page UNCACHEABLE (poor cacheability).
   * That is a pre-existing issue in senator microsite rendering and is out of
   * scope for the bill cache tests.
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
   * Returns the first node (of $type) whose referenced term in $fieldName
   * passes entity validation, or NULL if no such pair exists.
   *
   * Some senator terms have stale/broken entity-reference field values that
   * cause the edit form to reject the submission with a validation error,
   * preventing the save from firing. This helper skips those broken terms so
   * that tests can reliably trigger a real web-form save.
   *
   * Iterates through published nodes of the given type (most recently changed
   * first) until it finds one whose referenced term has zero validation errors.
   *
   * @return array{0: \Drupal\node\NodeInterface, 1: \Drupal\taxonomy\TermInterface}|null
   */
  protected function findNodeAndValidTermByField(string $nodeType, string $fieldName): ?array {
    $ids = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $nodeType)
      ->condition('status', 1)
      ->exists($fieldName)
      ->sort('changed', 'DESC')
      ->range(0, 50)
      ->execute();

    foreach ($ids as $nid) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      if (!$node) {
        continue;
      }
      $term = $this->findReferencedTerm($node, $fieldName);
      if (!$term) {
        continue;
      }
      // Only use terms whose edit form will pass validation.
      if ($term->validate()->count() === 0) {
        return [$node, $term];
      }
    }
    return NULL;
  }

  // ---------------------------------------------------------------------------
  // Require helpers — assert-and-return variants of the find helpers above.
  //
  // These methods call the corresponding find* helper and fail the test
  // immediately if the result is NULL. Use these in test methods to eliminate
  // the find + assertNotNull boilerplate at every call site.
  // ---------------------------------------------------------------------------

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
   * Returns the most recently changed published node of a given type with a
   * populated field, or fails.
   */
  protected function requireNodeByTypeWithField(string $type, string $fieldName): NodeInterface {
    return $this->findNodeByTypeWithField($type, $fieldName)
      ?? $this->fail("No published '{$type}' node with field '{$fieldName}' populated found.");
  }

  /**
   * Returns the first [node, term] pair where the term passes entity
   * validation (i.e. its edit form will submit successfully), or fails.
   *
   * @return array{0: \Drupal\node\NodeInterface, 1: \Drupal\taxonomy\TermInterface}
   */
  protected function requireNodeAndValidTermByField(string $nodeType, string $fieldName): array {
    return $this->findNodeAndValidTermByField($nodeType, $fieldName)
      ?? $this->fail("No published '{$nodeType}' node with a valid (saveable) '{$fieldName}' term found.");
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
   * Returns the first taxonomy term referenced via an entity-reference field
   * on a node, or fails.
   */
  protected function requireReferencedTerm(NodeInterface $node, string $fieldName): TermInterface {
    return $this->findReferencedTerm($node, $fieldName)
      ?? $this->fail("No term found for field '{$fieldName}'.");
  }

}
