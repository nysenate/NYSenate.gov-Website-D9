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
  // saveViaWebRequest() is used rather than $entity->save() so that the Fastly
  // BAN is dispatched via a real kernel.terminate event (identical to a real
  // editorial save). This eliminates the cross-test contamination race that
  // occurs when CLI saves interact with the full test suite's warmCache state.
  // ---------------------------------------------------------------------------

  /**
   * An article edit must not invalidate pages that don't display articles.
   *
   * Articles feed / and /news-and-issues only.
   *
   * @dataProvider articleNonInvalidatedPagesProvider
   */
  public function testArticleEditDoesNotInvalidate(string $path): void {
    $article = $this->findNodeByType('article');
    $this->assertNotNull($article, "No published 'article' node found.");
    $this->warmCache($path);
    $this->saveViaWebRequest($article);
    $this->assertAnonymousCacheHit($path);
  }

  public function articleNonInvalidatedPagesProvider(): array {
    return $this->asProvider(['/senators-committees', '/legislation', '/events', '/about']);
  }

  /**
   * A bill edit must not invalidate pages that don't display bills.
   *
   * Bills appear on /legislation only.
   *
   * @dataProvider billNonInvalidatedPagesProvider
   */
  public function testBillEditDoesNotInvalidate(string $path): void {
    $bill = $this->findSaveableBillNode();
    $this->assertNotNull($bill, 'No bill with valid print number and session found.');
    $this->warmCache($path);
    $this->saveViaWebRequest($bill);
    $this->assertAnonymousCacheHit($path);
  }

  public function billNonInvalidatedPagesProvider(): array {
    return $this->asProvider(['/', '/news-and-issues', '/senators-committees', '/events', '/about']);
  }

  /**
   * An event edit must not invalidate pages that don't display events.
   *
   * Events appear on / and /events only.
   *
   * @dataProvider eventNonInvalidatedPagesProvider
   */
  public function testEventEditDoesNotInvalidate(string $path): void {
    $event = $this->findNodeByType('event');
    $this->assertNotNull($event, "No published 'event' node found.");
    $this->warmCache($path);
    $this->saveViaWebRequest($event);
    $this->assertAnonymousCacheHit($path);
  }

  public function eventNonInvalidatedPagesProvider(): array {
    return $this->asProvider(['/news-and-issues', '/senators-committees', '/legislation', '/about']);
  }

  /**
   * A petition edit must not invalidate any top-level page.
   *
   * Petitions appear on no top-level navigation pages.
   *
   * @dataProvider topLevelPageProvider
   */
  public function testPetitionEditDoesNotInvalidate(string $path): void {
    $petition = $this->findNodeByType('petition');
    $this->assertNotNull($petition, "No published 'petition' node found.");
    $this->warmCache($path);
    $this->saveViaWebRequest($petition);
    $this->assertAnonymousCacheHit($path);
  }

}

