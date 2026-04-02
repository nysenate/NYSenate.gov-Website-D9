<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\user\UserInterface;

/**
 * Verifies anonymous page cache (x-drupal-cache) behavior.
 *
 * Covers:
 *
 *  1. Cache HITs on the 6 top-level navigation pages after a warm request.
 *  2. cache-control: max-age=86400, public on those pages.
 *  3. Negative cases: irrelevant content edits must not bust unrelated pages.
 *
 * Add new test methods here for any additional anonymous caching assertions.
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
  // Cache HIT per page
  // ---------------------------------------------------------------------------

  /**
   * Each top-level page must return x-drupal-cache: HIT on second request.
   *
   * @dataProvider topLevelPageProvider
   */
  public function testAnonymousCacheHit(string $path): void {
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
  }

  // ---------------------------------------------------------------------------
  // cache-control: max-age=86400, public
  // ---------------------------------------------------------------------------

  /**
   * All 6 top-level pages must declare a 24-hour public cache lifetime.
   *
   * @dataProvider topLevelPageProvider
   */
  public function testCacheControlMaxAge(string $path): void {
    $this->assertCacheControlMaxAge($path, 86400);
  }

  // ---------------------------------------------------------------------------
  // Content edit non-invalidation
  //
  // For each content type, assert that pages it does NOT feed remain cached
  // after a re-save. The complement (MISS on pages that DO display the
  // content) is covered in CacheMissInvalidationTest.
  //
  // Each method warms all unrelated pages, calls saveViaWebRequest() once,
  // then asserts every page is still a HIT. saveViaWebRequest() is used
  // (not $entity->save()) to ensure Fastly BAN dispatch is deterministic —
  // see CacheTestBase::saveViaWebRequest() for the full rationale.
  // ---------------------------------------------------------------------------

  /**
   * An article edit must not invalidate pages that don't display articles.
   *
   * Articles feed / and /news-and-issues only.
   */
  public function testArticleEditDoesNotInvalidateUnrelatedPages(): void {
    $article = $this->findNodeByType('article');
    $this->assertNotNull($article, "No published 'article' node found.");
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
   * A bill edit must not invalidate pages that don't display bills.
   *
   * Bills appear on /legislation only.
   */
  public function testBillEditDoesNotInvalidateUnrelatedPages(): void {
    $bill = $this->findSaveableBillNode();
    $this->assertNotNull($bill, 'No published bill node with field_ol_base_print_no and field_ol_session populated found.');
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
   * An event edit must not invalidate pages that don't display events.
   *
   * Events appear on / and /events only.
   */
  public function testEventEditDoesNotInvalidateUnrelatedPages(): void {
    $event = $this->findNodeByType('event');
    $this->assertNotNull($event, "No published 'event' node found.");
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
   * Petitions appear on no top-level navigation pages.
   */
  public function testPetitionEditDoesNotInvalidateAnyTopLevelPage(): void {
    $petition = $this->findNodeByType('petition');
    $this->assertNotNull($petition, "No published 'petition' node found.");
    foreach (self::TOP_LEVEL_PAGES as $path) {
      $this->warmCache($path);
    }
    $this->saveViaWebRequest($petition);
    foreach (self::TOP_LEVEL_PAGES as $path) {
      $this->assertAnonymousCacheHit($path);
    }
  }

  // ---------------------------------------------------------------------------
  // Content type display page — cache HITs (Part 2)
  //
  // Each test resolves the most-recently-changed published node of the given
  // type and asserts that a second anonymous visit to that node's canonical
  // display page is served from cache.
  //
  // Bill is the known exception: BillVoteWidgetForm::buildForm() sets
  // $form['#cache'] = ['max-age' => 0], which bubbles into the page render
  // and prevents anonymous page caching entirely. The bill test documents the
  // current (broken) state and is expected to fail until the Phase 2
  // lazy-builder fix in nys_bill_vote is complete.
  // ---------------------------------------------------------------------------

  /**
   * An article display page returns a cache HIT on second anonymous request.
   */
  public function testArticleDisplayPageCacheHit(): void {
    $path = $this->findNodeUrlByType('article');
    $this->assertNotNull($path, "No published 'article' node found.");
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
  }

  /**
   * A bill display page returns a cache HIT on second anonymous request.
   *
   * @todo Phase 2 fix required: BillVoteWidgetForm sets max-age:0 inline via
   *   $form['#cache'] = ['max-age' => 0], which bubbles into the page render
   *   and kills anonymous page caching for all bill nodes. This test documents
   *   the current broken state. It is expected to fail at warmCache() until the
   *   lazy-builder refactor in nys_bill_vote ships.
   */
  public function testBillDisplayPageCacheHit(): void {
    $path = $this->findNodeUrlByType('bill');
    $this->assertNotNull($path, 'No published bill node with a valid path alias found.');
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
  }

  /**
   * An event display page returns a cache HIT on second anonymous request.
   */
  public function testEventDisplayPageCacheHit(): void {
    $path = $this->findNodeUrlByType('event');
    $this->assertNotNull($path, "No published 'event' node found.");
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
  }

  /**
   * An in_the_news display page returns a cache HIT on second anonymous request.
   */
  public function testInTheNewsDisplayPageCacheHit(): void {
    $path = $this->findNodeUrlByType('in_the_news');
    $this->assertNotNull($path, "No published 'in_the_news' node found.");
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
  }

  /**
   * A meeting display page returns a cache HIT on second anonymous request.
   */
  public function testMeetingDisplayPageCacheHit(): void {
    $path = $this->findNodeUrlByType('meeting');
    $this->assertNotNull($path, "No published 'meeting' node found.");
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
  }

  /**
   * A public_hearing display page returns a cache HIT on second anonymous request.
   */
  public function testPublicHearingDisplayPageCacheHit(): void {
    $path = $this->findNodeUrlByType('public_hearing');
    $this->assertNotNull($path, "No published 'public_hearing' node found.");
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
  }

  /**
   * A resolution display page returns a cache HIT on second anonymous request.
   */
  public function testResolutionDisplayPageCacheHit(): void {
    $path = $this->findNodeUrlByType('resolution');
    $this->assertNotNull($path, "No published 'resolution' node found.");
    $this->warmCache($path);
    $this->assertAnonymousCacheHit($path);
  }

  // ---------------------------------------------------------------------------
  // Content type display page — cache-control max-age (Part 2)
  //
  // All cacheable content type display pages must declare a 24-hour public
  // cache lifetime so Fastly and anonymous page cache can serve them for up
  // to 24 hours before requiring a fresh render.
  //
  // Bill is again the known exception: max-age:0 from BillVoteWidgetForm will
  // cause both bill tests here to fail until Phase 2.
  // ---------------------------------------------------------------------------

  /**
   * An article display page declares cache-control: max-age=86400, public.
   */
  public function testArticleDisplayPageCacheControlMaxAge(): void {
    $path = $this->findNodeUrlByType('article');
    $this->assertNotNull($path, "No published 'article' node found.");
    $this->assertCacheControlMaxAge($path, 86400);
  }

  /**
   * A bill display page declares cache-control: max-age=86400, public.
   *
   * @todo Phase 2 fix required: BillVoteWidgetForm sets max-age:0, making the
   *   cache-control header read max-age=0, private instead of the expected
   *   max-age=86400, public. This test documents the current broken state and
   *   is expected to fail until the lazy-builder refactor ships.
   */
  public function testBillDisplayPageCacheControlMaxAge(): void {
    $path = $this->findNodeUrlByType('bill');
    $this->assertNotNull($path, 'No published bill node with a valid path alias found.');
    $this->assertCacheControlMaxAge($path, 86400);
  }

  /**
   * An event display page declares cache-control: max-age=86400, public.
   */
  public function testEventDisplayPageCacheControlMaxAge(): void {
    $path = $this->findNodeUrlByType('event');
    $this->assertNotNull($path, "No published 'event' node found.");
    $this->assertCacheControlMaxAge($path, 86400);
  }

  /**
   * An in_the_news display page declares cache-control: max-age=86400, public.
   */
  public function testInTheNewsDisplayPageCacheControlMaxAge(): void {
    $path = $this->findNodeUrlByType('in_the_news');
    $this->assertNotNull($path, "No published 'in_the_news' node found.");
    $this->assertCacheControlMaxAge($path, 86400);
  }

  /**
   * A meeting display page declares cache-control: max-age=86400, public.
   */
  public function testMeetingDisplayPageCacheControlMaxAge(): void {
    $path = $this->findNodeUrlByType('meeting');
    $this->assertNotNull($path, "No published 'meeting' node found.");
    $this->assertCacheControlMaxAge($path, 86400);
  }

  /**
   * A public_hearing display page declares cache-control: max-age=86400, public.
   */
  public function testPublicHearingDisplayPageCacheControlMaxAge(): void {
    $path = $this->findNodeUrlByType('public_hearing');
    $this->assertNotNull($path, "No published 'public_hearing' node found.");
    $this->assertCacheControlMaxAge($path, 86400);
  }

  /**
   * A resolution display page declares cache-control: max-age=86400, public.
   */
  public function testResolutionDisplayPageCacheControlMaxAge(): void {
    $path = $this->findNodeUrlByType('resolution');
    $this->assertNotNull($path, "No published 'resolution' node found.");
    $this->assertCacheControlMaxAge($path, 86400);
  }

  // ---------------------------------------------------------------------------
  // Content type display page non-invalidation (Part 2)
  //
  // Verifies that editing a node of type X does not bust the display page of
  // an unrelated type Y. The complement (MISS when the correct content changes)
  // is covered in CacheMissInvalidationTest.
  //
  // Bill display pages are excluded from the warm set in both tests because
  // bill pages are currently uncacheable (max-age:0). That cross-type isolation
  // will be verified once Phase 2 lands.
  // ---------------------------------------------------------------------------

  /**
   * A bill edit must not invalidate display pages of other content types.
   *
   * Bill cache tags (node:{nid}) are scoped to the individual bill node. Saving
   * a bill must not bust the display page of any unrelated content type.
   */
  public function testBillEditDoesNotInvalidateContentTypeDisplayPages(): void {
    $bill = $this->findSaveableBillNode();
    $this->assertNotNull($bill, 'No published bill node with field_ol_base_print_no and field_ol_session populated found.');

    // Collect display page paths for non-bill types in scope for Part 2.
    $unrelated = [];
    foreach (['article', 'event', 'in_the_news', 'meeting', 'public_hearing', 'resolution'] as $type) {
      $path = $this->findNodeUrlByType($type);
      if ($path !== NULL) {
        $unrelated[] = $path;
      }
    }
    $this->assertNotEmpty($unrelated, 'No published nodes found for any non-bill content type.');

    foreach ($unrelated as $path) {
      $this->warmCache($path);
    }
    $this->saveViaWebRequest($bill);
    foreach ($unrelated as $path) {
      $this->assertAnonymousCacheHit($path);
    }
  }

  /**
   * An article edit must not invalidate unrelated content type display pages.
   *
   * Articles feed the homepage (/) and /news-and-issues landing pages, and
   * their own display page. Saving an article must not bust the display page
   * of a meeting, public_hearing, or resolution node. Bill display pages are
   * excluded from the warm set because they are currently uncacheable (Phase 2
   * fixes this).
   */
  public function testArticleEditDoesNotInvalidateUnrelatedContentTypeDisplayPages(): void {
    $article = $this->findNodeByType('article');
    $this->assertNotNull($article, "No published 'article' node found.");

    $unrelated = [];
    foreach (['meeting', 'public_hearing', 'resolution'] as $type) {
      $path = $this->findNodeUrlByType($type);
      if ($path !== NULL) {
        $unrelated[] = $path;
      }
    }
    $this->assertNotEmpty($unrelated, 'No published nodes found for meeting, public_hearing, or resolution content types.');

    foreach ($unrelated as $path) {
      $this->warmCache($path);
    }
    $this->saveViaWebRequest($article);
    foreach ($unrelated as $path) {
      $this->assertAnonymousCacheHit($path);
    }
  }

}

