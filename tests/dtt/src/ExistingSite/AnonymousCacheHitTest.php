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
    if ($bill === NULL) {
      $this->markTestSkipped('No bill with valid print number and session found.');
    }
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
    if ($petition === NULL) {
      $this->markTestSkipped("No published 'petition' node found.");
    }
    foreach (self::TOP_LEVEL_PAGES as $path) {
      $this->warmCache($path);
    }
    $this->saveViaWebRequest($petition);
    foreach (self::TOP_LEVEL_PAGES as $path) {
      $this->assertAnonymousCacheHit($path);
    }
  }

}

