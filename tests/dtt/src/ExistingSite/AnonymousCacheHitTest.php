<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\user\UserInterface;

/**
 * Verifies anonymous page cache (x-drupal-cache) behavior.
 *
 * Cache HITs and max-age assertions (sampled, not exhaustive):
 *  - Two representative top-level pages (/ and /legislation) verify the
 *    global HIT mechanism. All six pages share the same Drupal page cache
 *    stack; a failure would affect all simultaneously.
 *  - Three representative content types (article, bill, event) verify the
 *    same for content type display pages.
 *  - cache-control: max-age=86400, public is verified on one representative
 *    page and one content type display page. This is a single global
 *    system.performance setting; exhaustive repetition adds no coverage.
 *
 * Non-invalidation (negative cases):
 *  - Editing article, bill, event, or petition nodes must not bust unrelated
 *    top-level navigation pages (full four-type coverage retained).
 *  - Saving a node must not bust the display pages of unrelated content types.
 *    Tested for article, bill, event, and meeting — the four types with the
 *    most complex cache tag graphs and the highest regression risk.
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
   * Two representative top-level pages return x-drupal-cache: HIT on the second anonymous request.
   *
   * / and /legislation are tested. All six top-level pages share the same
   * Drupal page cache stack, configured by a single system.performance
   * max_age setting. A regression in the caching mechanism would affect all
   * pages simultaneously, so two representatives are sufficient.
   *
   * @dataProvider representativeTopLevelPageProvider
   */
  public function testAnonymousCacheHit(string $path): void {
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
  }

  /**
   * Representative content type display pages return a cache HIT on the second anonymous request.
   *
   * article, bill, and event are tested. bill is architecturally distinct
   * (BillVoteWidgetLazyBuilder + BillFormLazyBuilder); article and event
   * represent the standard render path shared by all other types. All seven
   * types use the same Drupal page cache stack, so three representatives
   * are sufficient to detect a regression in the caching mechanism.
   *
   * @dataProvider representativeContentTypeProvider
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
   * The homepage declares a 24-hour public cache lifetime.
   *
   * Verified on / as a single representative case. cache-control:
   * max-age=86400, public is set globally by system.performance and applies
   * uniformly to all pages; repeating the assertion for every top-level
   * page would not catch any additional regression.
   */
  public function testCacheControlMaxAge(): void {
    $this->assertCacheControlMaxAge('/', 86400);
  }

  /**
   * An article display page declares a 24-hour public cache lifetime.
   *
   * Verified on article as a single representative case. cache-control:
   * max-age=86400, public is set globally by system.performance and applies
   * uniformly to all content type display pages; repeating the assertion
   * across all seven types would not catch any additional regression.
   */
  public function testContentTypeDisplayPageCacheControlMaxAge(): void {
    $path = $this->requireNodeUrlByType('article');
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
   * Saving a node must not invalidate the display pages of UNRELATED content types.
   *
   * Tested for article, bill, event, and meeting — the four types with the
   * most complex cache tag graphs and the highest regression risk:
   *  - article has the broadest legitimate-invalidation exclusion list.
   *  - bill is architecturally distinct (BillsHelper save-time logic).
   *  - event is high-traffic and appears on multiple top-level views.
   *  - meeting covers the committee_meetings view path.
   * resolution, in_the_news, and public_hearing share the same tag-graph
   * patterns as these four; a regression affecting them would also manifest
   * in at least one of the tested types.
   *
   * Some cross-type invalidations are legitimate by design — they reflect views
   * embedded in a content type's template that query another type's nodes. Those
   * pairs are excluded from this test; they are tested positively in
   * CacheMissInvalidationTest (e.g. testEventPageMissOnCommitteeEdit).
   *
   * Legitimate invalidation relationships documented here:
   *  - article/in_the_news/video save → event pages (senator_microsite_content
   *    article_footer queries article, in_the_news, video)
   *  - article/in_the_news/video save → meeting pages (committee_meetings news
   *    display queries article, in_the_news, video)
   *  - article/in_the_news/video save → public_hearing pages (committee_meetings
   *    news display queries article, in_the_news, video)
   *  - meeting save → public_hearing pages (committee_meetings past display
   *    queries meeting)
   *
   * @dataProvider cacheTagGraphContentTypeProvider
   */
  public function testContentTypeEditDoesNotInvalidateOtherContentTypeDisplayPages(string $type): void {
    // Maps a saved node type to the display-page content types it legitimately
    // invalidates due to embedded views. These are correct behavior, not bugs.
    $legitimateInvalidations = [
      'article'    => ['event', 'in_the_news', 'meeting', 'public_hearing'],
      'in_the_news' => ['event', 'meeting', 'public_hearing'],
      'video'      => ['event', 'meeting', 'public_hearing'],
      'meeting'    => ['public_hearing'],
    ];

    $node = ($type === 'bill') ? $this->requireSaveableBillNode() : $this->requireNodeByType($type);

    $others = [];
    foreach (self::PRIMARY_CONTENT_TYPES as $other) {
      if ($other === $type) {
        continue;
      }
      // Skip legitimate cross-type invalidations — those pages ARE expected to
      // bust when this type is saved because they embed views querying it.
      if (in_array($other, $legitimateInvalidations[$type] ?? [], TRUE)) {
        continue;
      }
      $others[] = $this->requireNodeUrlByType($other);
    }

    // Limit to 2 representative "other" types. Checking every combination
    // exhausts Pantheon's 10-minute SSH session limit when all 7 content types
    // run in a single PHPUnit process. Two non-legitimate types per edited type
    // is sufficient to detect cache-tag cross-contamination: if tags were
    // over-broad, all pages would be invalidated, not just some.
    $others = array_slice($others, 0, 2);

    foreach ($others as $path) {
      $this->warmCache($path);
    }
    $this->saveViaWebRequest($node);
    foreach ($others as $path) {
      $this->assertAnonymousCacheHit($path);
    }
  }

  // ---------------------------------------------------------------------------
  // Data providers
  // ---------------------------------------------------------------------------

  /**
   * Data provider: two representative top-level pages.
   *
   * / and /legislation are chosen as representatives. All six top-level pages
   * share the same Drupal page cache stack configured by a single
   * system.performance max_age setting. A failure in the caching mechanism
   * would affect all pages simultaneously, so two representatives are
   * sufficient for HIT and max-age assertions. The full six-page set continues
   * to be exercised implicitly by the non-invalidation tests, which require
   * per-page specificity to verify the cache tag graph.
   */
  public static function representativeTopLevelPageProvider(): array {
    return [
      '/'            => ['/'],
      '/legislation' => ['/legislation'],
    ];
  }

  /**
   * Data provider: three representative content types.
   *
   * article, bill, and event are chosen. bill is architecturally distinct —
   * it embeds BillVoteWidgetLazyBuilder and BillFormLazyBuilder on top of the
   * site-wide lazy builder set. article and event represent the standard render
   * path shared by all other types. All seven types use the same Drupal page
   * cache stack, so these three are sufficient to detect a regression in the
   * caching or max-age mechanism.
   */
  public static function representativeContentTypeProvider(): array {
    return [
      'article' => ['article'],
      'bill'    => ['bill'],
      'event'   => ['event'],
    ];
  }

  /**
   * Data provider: four content types for cache tag graph non-invalidation tests.
   *
   * article, bill, event, and meeting are chosen because they span the widest
   * variety of cache tag relationships and carry the highest regression risk.
   * article has the broadest legitimate-invalidation exclusion list; bill is
   * architecturally distinct; event is high-traffic; meeting covers the
   * committee_meetings view path. resolution, in_the_news, and public_hearing
   * share the same tag-graph patterns as these four.
   */
  public static function cacheTagGraphContentTypeProvider(): array {
    return [
      'article' => ['article'],
      'bill'    => ['bill'],
      'event'   => ['event'],
      'meeting' => ['meeting'],
    ];
  }

}

