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
 *  2. Any account change (role, district, preferences) busts that user's
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
   * Synthetic authenticated user created in setUp and removed in tearDown.
   *
   * @var \Drupal\user\Entity\User
   */
  private User $testUser;

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

    // Create a minimal authenticated user. No roles beyond 'authenticated'.
    $this->testUser = $this->createUser();
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
    $this->drupalLogin($this->testUser);

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
    $this->drupalLogin($this->testUser);

    // Warm the dynamic cache on the homepage.
    $this->visit('/');
    $this->assertDynamicCacheHit('/');

    // Re-save the user without changing any fields — this is sufficient to
    // invalidate the user:{uid} cache tag and bust the dynamic cache entry.
    $this->testUser->save();

    // Next visit must be a MISS — the warmed entry has been invalidated.
    $this->assertDynamicCacheMiss('/');
  }

}
