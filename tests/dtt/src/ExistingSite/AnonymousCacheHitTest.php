<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\user\UserInterface;

/**
 * Verifies anonymous page cache (x-drupal-cache) behavior.
 *
 * Cache HITs:
 *  - Top-level navigation pages return x-drupal-cache: HIT after a warm request.
 *  - Primary content type display pages return x-drupal-cache: HIT after a warm request.
 *
 * cache-control: max-age=86400, public:
 *  - All top-level navigation pages.
 *  - All primary content type display pages.
 *
 * Non-invalidation (negative cases):
 *  - Editing article, bill, event, or petition nodes must not bust unrelated
 *    top-level navigation pages.
 *  - Editing a node of one content type must not bust the display page of an
 *    unrelated content type.
 *
 * The complement (cache MISS when the relevant content changes) lives in
 * CacheMissInvalidationTest.
 *
 * @group cache_regression
 */
class AnonymousCacheHitTest extends CacheTestBase {

  /**
   * Administrator user used by the negative-case "does not invalidate" tests.
   *
   * @var \Drupal\user\UserInterface|null
   */
  protected ?UserInterface $adminUser = NULL;

  /**
   * {@inheritdoc}
   *
   * Creates an admin user and logs in so that saveViaWebRequest() is available
   * for the negative-case tests. Web-based saves are used (not $entity->save())
   * to ensure kernel.terminate fires and Fastly BANs are dispatched before the
   * next warmCache() poll, eliminating the race that causes spurious failures
   * when CLI saves interact with the full test suite's warm-cache state.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->drupalLogout();
    parent::tearDown();
  }

  // ---------------------------------------------------------------------------
  // Cache HITs
  // ---------------------------------------------------------------------------

  /**
   * Each top-level page returns x-drupal-cache: HIT on second anonymous request.
   *
   * @dataProvider topLevelPageProvider
   */
  public function testAnonymousCacheHit(string $path): void {
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
  }

  /**
   * A primary content type display page returns a cache HIT on second anonymous request.
   *
   * @dataProvider contentTypeProvider
   */
  public function testContentTypeDisplayPageCacheHit(string $type): void {
    $path = $this->requireNodeUrlByType($type);
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
  }

  // ---------------------------------------------------------------------------
  // 24-hour public cache lifetime
  // ---------------------------------------------------------------------------

  /**
   * All top-level pages declare a 24-hour public cache lifetime.
   *
   * @dataProvider topLevelPageProvider
   */
  public function testCacheControlMaxAge(string $path): void {
    $this->assertCacheControlMaxAge($path, 86400);
  }

  /**
   * A primary content type display pages declare 24-hour public cache lifetime.
   *
   * @dataProvider contentTypeProvider
   */
  public function testContentTypeDisplayPageCacheControlMaxAge(string $type): void {
    $path = $this->requireNodeUrlByType($type);
    $this->assertCacheControlMaxAge($path, 86400);
  }

  // ---------------------------------------------------------------------------
  // Non-invalidation
  // ---------------------------------------------------------------------------

  /**
   * An article edit must not invalidate top-level pages that don't display articles.
   *
   * Articles feed / and /news-and-issues only.
   */
  public function testArticleEditDoesNotInvalidateUnrelatedPages(): void {
    $article = $this->requireNodeByType('article');
    $unrelated = ['/senators-committees', '/legislation', '/events', '/about'];
    foreach ($unrelated as $path) {
      $this->warmCache($path);
    }
    $this->saveViaWebRequest($article);
    foreach ($unrelated as $path) {
      $this->assertAnonymousCacheHit($path);
    }
  }

  /**
   * A bill edit must not invalidate top-level pages that don't display bills.
   *
   * Bills appear on /legislation only.
   */
  public function testBillEditDoesNotInvalidateUnrelatedPages(): void {
    $bill = $this->requireSaveableBillNode();
    $unrelated = ['/', '/news-and-issues', '/senators-committees', '/events', '/about'];
    foreach ($unrelated as $path) {
      $this->warmCache($path);
    }
    $this->saveViaWebRequest($bill);
    foreach ($unrelated as $path) {
      $this->assertAnonymousCacheHit($path);
    }
  }

  /**
   * An event edit must not invalidate top-level pages that don't display events.
   *
   * Events appear on / and /events only.
   */
  public function testEventEditDoesNotInvalidateUnrelatedPages(): void {
    $event = $this->requireNodeByType('event');
    $unrelated = ['/news-and-issues', '/senators-committees', '/legislation', '/about'];
    foreach ($unrelated as $path) {
      $this->warmCache($path);
    }
    $this->saveViaWebRequest($event);
    foreach ($unrelated as $path) {
      $this->assertAnonymousCacheHit($path);
    }
  }

  /**
   * A petition edit must not invalidate any top-level page.
   *
   * Petitions do not appear on any top-level navigation page.
   */
  public function testPetitionEditDoesNotInvalidateAnyTopLevelPage(): void {
    $petition = $this->requireNodeByType('petition');
    foreach (self::TOP_LEVEL_PAGES as $path) {
      $this->warmCache($path);
    }
    $this->saveViaWebRequest($petition);
    foreach (self::TOP_LEVEL_PAGES as $path) {
      $this->assertAnonymousCacheHit($path);
    }
  }

  /**
   * Saving a node must not invalidate the display pages of other content types.
   *
   * @dataProvider contentTypeProvider
   */
  public function testContentTypeEditDoesNotInvalidateOtherContentTypeDisplayPages(string $type): void {
    $node = ($type === 'bill') ? $this->requireSaveableBillNode() : $this->requireNodeByType($type);

    $others = [];
    foreach (self::PRIMARY_CONTENT_TYPES as $other) {
      if ($other !== $type) {
        $others[] = $this->requireNodeUrlByType($other);
      }
    }

    foreach ($others as $path) {
      $this->warmCache($path);
    }
    $this->saveViaWebRequest($node);
    foreach ($others as $path) {
      $this->assertAnonymousCacheHit($path);
    }
  }

}

