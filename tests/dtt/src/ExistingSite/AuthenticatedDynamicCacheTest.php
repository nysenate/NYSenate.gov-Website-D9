<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\user\Entity\User;

/**
 * Verifies dynamic page cache behaviour for authenticated users.
 *
 * The dynamic page cache caches full page responses keyed by user context.
 * These tests confirm the properties that are unique to authenticated sessions:
 *
 *  1. A second authenticated visit to the same page returns x-drupal-dynamic-cache: HIT.
 *  2. The dynamic cache skeleton is shared across different authenticated users.
 *  3. Any account change busts that user's warmed entries via the user:{uid} cache tag.
 *
 * Content-level isolation (follow/unfollow state, user menu) is verified in
 * NoCachePoisoningTest.
 *
 * Content-edit invalidation (e.g. article resave busting a page) is not
 * duplicated here. Both the page cache and the dynamic page cache share the
 * same Drupal cache tag system, so those tags are already proven by
 * CacheMissInvalidationTest — if they were missing, the anonymous MISS tests
 * would fail first.
 *
 * Synthetic users are created by the Drupal entity API and deleted in
 * tearDown() — no database state persists after the suite runs.
 *
 * @group cache_regression
 */
class AuthenticatedDynamicCacheTest extends CacheTestBase {

  /**
   * First synthetic authenticated user.
   *
   * @var \Drupal\user\Entity\User
   */
  private User $userA;

  /**
   * Second synthetic user for shared-cache assertions.
   *
   * @var \Drupal\user\Entity\User
   */
  private User $userB;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Clear the dynamic page cache so every test in this class starts from a
    // guaranteed cold-cache state. The dynamic page cache is keyed by cache
    // contexts (e.g. user.roles), not by individual user ID, so a warm entry
    // left by a prior test run or real editor traffic would make the
    // cold-cache MISS assertions unreliable without this.
    \Drupal::cache('dynamic_page_cache')->deleteAll();

    // Create two minimal authenticated users. No roles beyond 'authenticated'.
    $this->userA = $this->createUser();
    $this->userB = $this->createUser();
  }

  // ---------------------------------------------------------------------------
  // Dynamic cache HIT on second visit
  // ---------------------------------------------------------------------------

  /**
   * An authenticated user's second visit to each top-level page is a dynamic cache HIT.
   *
   * @dataProvider topLevelPageProvider
   */
  public function testConstituentSecondVisitIsDynamicCacheHit(string $path): void {
    $this->drupalLogin($this->userA);

    // First visit — warms the dynamic cache entry for this user/path.
    $this->visit($path);

    // Second visit — must be served from dynamic cache.
    $this->assertDynamicCacheHit($path);
  }

  // ---------------------------------------------------------------------------
  // Shared dynamic cache skeleton across users
  // ---------------------------------------------------------------------------

  /**
   * The dynamic page cache skeleton is correctly shared across authenticated users.
   *
   * Drupal's dynamic page cache stores the full rendered response — including
   * lazy builder placeholders — after the first visit. All subsequent users
   * receive x-drupal-dynamic-cache: HIT; their lazy builder elements are then
   * resolved per-user outside the cache.
   *
   * Therefore:
   *  - userA's first visit (cold cache) returns MISS.
   *  - A second user's first visit hits the now-warm entry and returns HIT.
   *
   * Content-level isolation is verified separately in NoCachePoisoningTest.
   */
  public function testDynamicCacheSharedAcrossUsers(): void {
    foreach (['/', '/senators-committees'] as $path) {
      // userA: first visit must be a MISS (cold cache — no entry exists yet).
      $this->drupalLogin($this->userA);
      $this->assertDynamicCacheMiss($path);
      $this->drupalLogout();

      // userB: HIT is expected. The dynamic page cache entry is shared across
      // users; only lazy builder placeholders are resolved per-user outside
      // the cache.
      $this->drupalLogin($this->userB);
      $this->assertDynamicCacheHit($path);
      $this->drupalLogout();
    }
  }

  // ---------------------------------------------------------------------------
  // Dynamic cache MISS after account change (cache context invalidation)
  // ---------------------------------------------------------------------------

  /**
   * Any change to a user account busts their dynamic cache entry.
   *
   * Dynamic cache entries include the user:{uid} cache tag. Saving the user
   * entity — regardless of which field changed — invalidates that tag and
   * discards all of that user's warmed entries. This ensures stale personalised
   * content (e.g. district, role, or preference changes) is never served from
   * cache.
   */
  public function testDynamicCacheMissAfterAccountChange(): void {
    $this->drupalLogin($this->userA);

    // Warm the dynamic cache on the homepage.
    $this->visit('/');
    $this->assertDynamicCacheHit('/');

    // Re-save the user without changing any fields — this is sufficient to
    // invalidate the user:{uid} cache tag and bust the dynamic cache entry.
    $this->userA->save();

    // Next visit must be a MISS — the warmed entry has been invalidated.
    $this->assertDynamicCacheMiss('/');
  }

}
