<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\user\Entity\User;

/**
 * Verifies dynamic page cache behaviour for authenticated users.
 *
 *  1. The dynamic page cache skeleton is shared across authenticated users on two
 *     representative top-level pages (/ and /legislation).
 *  2. Content type display pages share their dynamic cache skeleton across users.
 *     Verified for bill (distinct lazy builders) and article (standard path).
 *  3. Senator-microsite content type pages (article with a senator ref, as a
 *     representative of article/event/in_the_news) share their skeleton across users.
 *  4. Any account change busts that user's warmed entries via the user:{uid} cache tag.
 *
 * All assertions use the cross-user pattern (userA warms, userB must hit) because
 * a per-user cache bug — caused by a missing #create_placeholder => TRUE on a lazy
 * builder — would make same-user repeat-visit tests pass while cross-user tests fail.
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
  // Cross-user cache sharing (top-level pages)
  // ---------------------------------------------------------------------------

  /**
   * A different authenticated user's first visit to a warmed entry is a dynamic cache HIT.
   *
   * userA warms the skeleton; userB's first visit must hit it. Content-level
   * isolation (personalised fragments such as the user menu) is verified in
   * NoCachePoisoningTest.
   *
   * @dataProvider representativeTopLevelPageProvider
   */
  public function testDynamicCacheSharedAcrossUsers(string $path): void {
    // userA: first visit must be a MISS (cold cache — no skeleton stored yet).
    $this->drupalLogin($this->userA);
    $this->assertDynamicCacheMiss($path);
    $this->drupalLogout();

    // userB: must hit the skeleton stored by userA's visit.
    $this->drupalLogin($this->userB);
    $this->assertDynamicCacheHit($path);
    $this->drupalLogout();
  }

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

  // ---------------------------------------------------------------------------
  // Cross-user cache sharing (content type display pages)
  // ---------------------------------------------------------------------------

  /**
   * Content type display page skeletons are shared across authenticated users.
   *
   * A missing #create_placeholder => TRUE on any lazy builder causes user cache
   * contexts to bubble into the skeleton key, producing per-user entries. bill
   * is tested because it has the most lazy builders (BillVoteWidgetLazyBuilder,
   * BillFormLazyBuilder, plus the site-wide set); article covers the standard
   * path shared by all other content types.
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
  // Cross-user cache sharing (senator-microsite pages)
  // ---------------------------------------------------------------------------

  /**
   * Senator-microsite page skeletons are shared across authenticated users.
   *
   * Regression guard for the GlobalSearchForm lazy builder fix in
   * nysenate_theme_preprocess_senator_microsite_menu_block(). Without the lazy
   * builder, the CSRF form token's max-age: 0 bubbles to the response level,
   * making the page UNCACHEABLE — causing both same-user and cross-user
   * assertions to fail.
   *
   * @dataProvider senatorMicrositeContentTypeProvider
   */
  public function testSenatorMicrositeContentTypeDynamicCacheSharedAcrossUsers(string $type): void {
    $path = $this->requireSenatorTaggedNodeUrlByType($type);

    // userA: first visit must be a MISS (cold cache — no skeleton stored yet).
    $this->drupalLogin($this->userA);
    $this->assertDynamicCacheMiss($path);
    $this->drupalLogout();

    // userB: must hit the skeleton stored by userA's visit.
    $this->drupalLogin($this->userB);
    $this->assertDynamicCacheHit($path);
    $this->drupalLogout();
  }

  /**
   * Data provider: one representative senator-microsite content type.
   *
   * article, event and in_the_news all receive the page__node__microsite_page
   * template suggestion when field_senator_multiref is populated, triggering
   * the same senator microsite menu block and hero block. The fix being guarded
   * (GlobalSearchForm lazy builder in nysenate_theme_preprocess_senator_microsite_menu_block)
   * contains no per-type branching, so a regression would affect all three
   * simultaneously. article is tested as the representative type.
   */
  public static function senatorMicrositeContentTypeProvider(): array {
    return [
      'article' => ['article'],
    ];
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
