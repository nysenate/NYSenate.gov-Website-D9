<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\user\Entity\User;

/**
 * Verifies dynamic page cache behaviour for authenticated users.
 *
 * The dynamic page cache caches full page responses keyed by user context.
 * These tests confirm the properties that are unique to authenticated sessions:
 *
 *  1. A second authenticated visit returns x-drupal-dynamic-cache: HIT.
 *     Verified on two representative top-level pages (/ and /legislation).
 *  2. The dynamic cache skeleton is shared across different authenticated users.
 *  3. Any account change busts that user's warmed entries via the user:{uid} cache tag.
 *  4. Content type display pages cache correctly for authenticated users.
 *     Verified for bill (distinct lazy builders) and article (standard path).
 *  5. Senator-microsite content type pages (article, event, in_the_news with a
 *     senator ref) cache correctly. These pages render the senator microsite menu
 *     block; the block's search form must be wrapped in a lazy builder to prevent
 *     the CSRF form token from setting max-age: 0 on the full page response.
 *
 * Sampling rationale: all top-level pages and five of the seven content types
 * share identical dynamic cache mechanisms. bill is tested individually because
 * it adds BillVoteWidgetLazyBuilder and BillFormLazyBuilder on top of the
 * site-wide set (UserMenuLazyBuilder, SearchFormLazyBuilder, WantToLazyBuilder);
 * article represents the standard render path for all other types.
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
   * An authenticated user's second visit to a representative top-level page is a dynamic cache HIT.
   *
   * Verified on / and /legislation. All six top-level pages share the same
   * dynamic page cache stack; a regression in the mechanism would affect all
   * pages simultaneously, so two representative pages are sufficient.
   *
   * @dataProvider representativeTopLevelPageProvider
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

  // ---------------------------------------------------------------------------
  // Content type display pages — per-user dynamic cache HIT
  // ---------------------------------------------------------------------------

  /**
   * Representative content type display pages cache correctly per authenticated user.
   *
   * Drupal's dynamic page cache stores the page SKELETON (containing render
   * placeholders for lazy builders) keyed by cache contexts that exclude the
   * individual user. On the first authenticated visit, the skeleton is stored
   * (MISS). On the second visit by the SAME user the skeleton is served from
   * cache (HIT), and the lazy builders are resolved afresh per request.
   *
   * bill and article are tested. bill is architecturally distinct: it embeds
   * BillVoteWidgetLazyBuilder and BillFormLazyBuilder on top of the site-wide
   * set (UserMenuLazyBuilder, SearchFormLazyBuilder, WantToLazyBuilder). article
   * represents the standard render path shared by all other content types.
   *
   * @dataProvider representativeContentTypePageProvider
   */
  public function testContentTypeDisplayPageDynamicCacheHit(string $type): void {
    $path = $this->requireNodeUrlByType($type);

    $this->drupalLogin($this->userA);

    // First visit — warms the dynamic page cache skeleton.
    $this->assertDynamicCacheMiss($path);

    // Second visit by the same user — skeleton served from cache.
    $this->assertDynamicCacheHit($path);
  }

  /**
   * The dynamic page cache skeleton is shared across authenticated users for representative content types.
   *
   * Because lazy builders prevent their per-user cache contexts from bubbling
   * to the page skeleton, the dynamic page cache stores a single entry that is
   * reused regardless of which authenticated user visits next. The lazy
   * builders resolve the user-specific fragment (vote widget, bill form, user
   * menu) outside the cache for every request.
   *
   * bill and article are tested. A missing #create_placeholder => TRUE on any
   * lazy builder causes the user context to bubble, breaking skeleton sharing.
   * bill has the most lazy builders; article covers the standard path. If
   * sharing works for both, it works for all seven content types.
   *
   * @dataProvider representativeContentTypePageProvider
   */
  public function testContentTypeDisplayPageDynamicCacheSharedAcrossUsers(string $type): void {
    $path = $this->requireNodeUrlByType($type);

    // userA: first visit must be a MISS (cold cache — no skeleton stored yet).
    $this->drupalLogin($this->userA);
    $this->assertDynamicCacheMiss($path);
    $this->drupalLogout();

    // userB: must hit the skeleton stored by userA's visit.
    $this->drupalLogin($this->userB);
    $this->assertDynamicCacheHit($path);
    $this->drupalLogout();
  }

  // ---------------------------------------------------------------------------
  // Data providers
  // ---------------------------------------------------------------------------

  /**
   * Data provider: two representative top-level pages.
   *
   * / and /legislation cover the home page and a content-heavy top-level page.
   * All six top-level pages share the same dynamic page cache mechanism, so
   * two representatives are sufficient to detect a regression.
   */
  public static function representativeTopLevelPageProvider(): array {
    return [
      '/'            => ['/'],
      '/legislation' => ['/legislation'],
    ];
  }

  /**
   * Data provider: bill and one representative content type.
   *
   * bill is architecturally distinct — it embeds BillVoteWidgetLazyBuilder
   * and BillFormLazyBuilder on top of the site-wide lazy builder set
   * (UserMenuLazyBuilder, SearchFormLazyBuilder, WantToLazyBuilder). article
   * represents the standard render path shared by the other six types. Together
   * they verify both lazy builder configurations present across all primary
   * content type display pages.
   */
  public static function representativeContentTypePageProvider(): array {
    return [
      'bill'    => ['bill'],
      'article' => ['article'],
    ];
  }

  // ---------------------------------------------------------------------------
  // Senator-microsite content type pages — dynamic cache HIT
  // ---------------------------------------------------------------------------

  /**
   * Senator-tagged content type pages cache correctly for authenticated users.
   *
   * article, event and in_the_news nodes that reference a senator term render
   * the senator microsite menu block and senator microsite hero block in
   * addition to the standard page layout. The microsite menu block renders the
   * GlobalSearchForm via a lazy builder (SearchFormLazyBuilder) so that the
   * CSRF token-bearing form_token element is deferred past the page cache
   * layer. Without the lazy builder the form_token's max-age: 0 bubbles to the
   * response level, causing UNCACHEABLE (poor cacheability) for all users.
   *
   * This test acts as a regression guard for that fix.
   *
   * @dataProvider senatorMicrositeContentTypeProvider
   */
  public function testSenatorMicrositeContentTypeDynamicCacheHit(string $type): void {
    $path = $this->requireSenatorTaggedNodeUrlByType($type);

    $this->drupalLogin($this->userA);

    // First visit — warms the dynamic page cache skeleton.
    $this->assertDynamicCacheMiss($path);

    // Second visit — skeleton served from dynamic cache.
    $this->assertDynamicCacheHit($path);
  }

  /**
   * Data provider: senator-microsite content types.
   *
   * article, event and in_the_news are the three content types that receive
   * the page__node__microsite_page template suggestion when field_senator_multiref
   * is populated, triggering the senator microsite menu block and hero block.
   */
  public static function senatorMicrositeContentTypeProvider(): array {
    return [
      'article'     => ['article'],
      'event'       => ['event'],
      'in_the_news' => ['in_the_news'],
    ];
  }

}
