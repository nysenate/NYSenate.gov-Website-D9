<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\user\Entity\User;

/**
 * Verifies dynamic page cache behaviour for authenticated users.
 *
 * The dynamic page cache caches full page responses keyed by user context.
 * These tests confirm the properties that are unique to authenticated sessions:
 *  1. An authenticated user's first visit is always a MISS (cold cache).
 *  2. A second identical visit is a HIT (dynamic cache warmed).
 *  3. Any account change (role, district, preferences) busts that user's
 *     warmed entries via the user:{uid} cache tag.
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
   * Synthetic test user created in setUp and removed in tearDown.
   *
   * @var \Drupal\user\Entity\User
   */
  private User $constituentUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a minimal authenticated user. No roles beyond 'authenticated'.
    $this->constituentUser = $this->createUser();
  }

  // ---------------------------------------------------------------------------
  // Dynamic cache MISS on first visit
  // ---------------------------------------------------------------------------

  /**
   * An authenticated user's first visit to each top-level page is a dynamic cache MISS.
   *
   * @dataProvider topLevelPageProvider
   */
  public function testConstituentFirstVisitIsDynamicCacheMiss(string $path): void {
    $this->drupalLogin($this->constituentUser);
    $this->assertDynamicCacheMiss($path);
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
    $this->drupalLogin($this->constituentUser);

    // First visit — warms the dynamic cache entry for this user/path.
    $this->visit($path);

    // Second visit — must be served from dynamic cache.
    $this->assertDynamicCacheHit($path);
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
    $this->drupalLogin($this->constituentUser);

    // Warm the dynamic cache on the homepage.
    $this->visit('/');
    $this->assertDynamicCacheHit('/');

    // Re-save the user without changing any fields — this is sufficient to
    // invalidate the user:{uid} cache tag and bust the dynamic cache entry.
    $this->constituentUser->save();

    // Next visit must be a MISS — the warmed entry has been invalidated.
    $this->assertDynamicCacheMiss('/');
  }

}
