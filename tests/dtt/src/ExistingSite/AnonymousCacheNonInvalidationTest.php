<?php

namespace Drupal\Tests\nys\ExistingSite;

/**
 * Verifies that edits to certain content types do NOT invalidate unrelated
 * top-level navigation pages.
 *
 * These are negative cases: the assertion is that a cache HIT is still
 * observed on pages that have no dependency on the edited content.
 *
 * The positive counterpart (pages that DO get a cache MISS after a relevant
 * edit) lives in CacheMissInvalidationTest.
 * The anonymous cache HIT and max-age assertions live in AnonymousCacheHitTest.
 *
 * Tests in this class run back-to-back in the same process, and one test's
 * save can *correctly* invalidate a page (e.g. an event edit invalidates
 * /events) that a later, unrelated test then checks — the asynchronous
 * Cloudflare BAN for that earlier, correct invalidation can still be
 * propagating when the later test starts. setUp() calls settleCachePages()
 * to actively wait out any such leftover propagation before each test's own
 * warm → save → assert sequence, so the final assertion can stay a strict
 * assertAnonymousCacheHit() and keep its ability to catch a genuine
 * over-invalidation bug introduced by that test's own save.
 *
 * @group cache_regression
 */
class AnonymousCacheNonInvalidationTest extends CacheTestBase {

  protected function setUp(): void {
    parent::setUp();
    $this->settleCachePages(self::TOP_LEVEL_PAGES);
  }

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
    $this->saveEntity($article);
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
    $this->saveEntity($bill);
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
    $this->saveEntity($event);
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
    $this->saveEntity($petition);
    foreach (self::TOP_LEVEL_PAGES as $path) {
      $this->assertAnonymousCacheHit($path);
    }
  }

}
