<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\user\UserInterface;

/**
 * Verifies anonymous page cache behavior.
 *
 * Cache HITs and max-age assertions (exhaustive):
 *  - All six top-level pages verify the global HIT mechanism and that each
 *    declares cache-control: max-age=86400, public.
 *  - All seven primary content types verify the same for content type display
 *    pages.
 *
 * Non-invalidation (negative cases):
 *  - Editing article, bill, event, or petition nodes must not bust unrelated
 *    top-level navigation pages (full four-type coverage retained).
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
   * to ensure kernel.terminate fires and CDN BANs are dispatched before the
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
   * All six top-level pages return a cache HIT and declare a 24-hour public
   * cache lifetime on the second anonymous request.
   *
   * The max-age assertion is folded in here rather than standing
   * alone because both properties are observable in the same warm GET request.
   *
   * @dataProvider representativeTopLevelPageProvider
   */
  public function testAnonymousCacheHit(string $path): void {
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
    $this->assertCacheControlMaxAge($path, 86400);
  }

  /**
   * All seven content type display pages return a cache HIT and declare a
   * 24-hour public cache lifetime on the second anonymous request.
   *
   * Full coverage is warranted because individual blocks or lazy builders on a
   * single content type's template can break caching for that type only without
   * affecting others. The max-age assertion is folded in here because both
   * properties are observable in the same warm GET request.
   *
   * @dataProvider representativeContentTypeProvider
   */
  public function testContentTypeDisplayPageCacheHit(string $type): void {
    $path = $this->requireNodeUrlByType($type);
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
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

  // ---------------------------------------------------------------------------
  // Data providers
  // ---------------------------------------------------------------------------

  /**
   * Data provider: all six top-level pages.
   */
  public static function representativeTopLevelPageProvider(): array {
    return [
      '/'                    => ['/'],
      '/news-and-issues'     => ['/news-and-issues'],
      '/senators-committees' => ['/senators-committees'],
      '/legislation'         => ['/legislation'],
      '/events'              => ['/events'],
      '/about'               => ['/about'],
    ];
  }

  /**
   * Data provider: all seven primary content types.
   */
  public static function representativeContentTypeProvider(): array {
    return [
      'article'       => ['article'],
      'bill'          => ['bill'],
      'event'         => ['event'],
      'in_the_news'   => ['in_the_news'],
      'meeting'       => ['meeting'],
      'public_hearing' => ['public_hearing'],
      'resolution'    => ['resolution'],
    ];
  }


}

