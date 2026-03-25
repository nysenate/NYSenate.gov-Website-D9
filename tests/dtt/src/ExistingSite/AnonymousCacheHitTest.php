<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\user\UserInterface;

/**
 * Verifies anonymous page cache (x-drupal-cache) behavior.
 *
 * Covers:
 *  - Cache HITs on the 6 top-level navigation pages after a warm request.
 *  - cache-control: max-age=86400, public on those pages.
 *  - Negative cases: irrelevant content edits must not bust unrelated pages.
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
   * for the negative-case tests. This mirrors CacheMissInvalidationTest and
   * makes Fastly BAN dispatch deterministic: saveViaWebRequest() submits a
   * real HTTP POST, kernel.terminate fires, and pantheon_advanced_page_cache
   * dispatches Fastly BANs for the saved entity's cache tags via
   * pantheon_clear_edge_keys(). CLI saves ($entity->save()) also call
   * pantheon_clear_edge_keys() synchronously, but the BAN arrives at
   * Fastly before the page has fully re-cached from the warmCache() call,
   * creating a race that generates spurious failures in the full test suite.
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
  // Content edit non-invalidation (negative cases)
  //
  // For each content type, assert that pages it does NOT feed remain cached
  // after a re-save. The complement (MISS on pages that DO display the
  // content) is covered in CacheMissInvalidationTest.
  //
  // Each method warms all unrelated pages, calls saveViaWebRequest() once,
  // then asserts every page is still a HIT. One HTTP round trip per content
  // type instead of one per (type × page) pair keeps growth O(content_types)
  // rather than O(content_types × pages) as new types and pages are added.
  //
  // saveViaWebRequest() is used rather than $entity->save() so that the Fastly
  // BAN is dispatched via a real kernel.terminate event (identical to a real
  // editorial save). This eliminates the cross-test contamination race that
  // occurs when CLI saves interact with the full test suite's warmCache state.
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
    $this->assertNotNull($bill, 'No bill with valid print number and session found.');
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

}

