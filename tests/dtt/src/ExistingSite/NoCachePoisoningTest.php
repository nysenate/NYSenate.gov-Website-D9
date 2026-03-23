<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\user\Entity\User;

/**
 * Verifies that no cache poisoning occurs between users or across sessions.
 *
 * Cache poisoning means one user's personalized content is wrongly served
 * from cache to a different user. These tests verify:
 *
 *  1. The dynamic page cache is shared across authenticated users — a second
 *     user's first visit returns HIT from the first user's warmed entry.
 *  2. User A's follow state (issues, committees) is not visible to User B.
 *  3. Each user sees their own name in the header user menu.
 *  4. An anonymous visitor after an authenticated visit still receives the
 *     anonymous rendering — not a leaked authenticated response.
 *
 * All synthetic users are created via the Drupal entity API and deleted in
 * tearDown(). Flag state and personalised content are asserted on the rendered
 * HTML response.
 *
 * @group cache_regression
 */
class NoCachePoisoningTest extends CacheTestBase {

  /**
   * District A taxonomy term ID (resolved in setUp if available).
   *
   * @var int|null
   */
  private ?int $districtATid = NULL;

  /**
   * District B taxonomy term ID (resolved in setUp if available).
   *
   * @var int|null
   */
  private ?int $districtBTid = NULL;

  /**
   * Test user assigned to district A.
   *
   * @var \Drupal\user\Entity\User
   */
  private User $userA;

  /**
   * Test user assigned to district B.
   *
   * @var \Drupal\user\Entity\User
   */
  private User $userB;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Resolve two distinct district terms if they exist in the DB.
    $districts = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'districts']);
    if (count($districts) >= 2) {
      $terms = array_values($districts);
      $this->districtATid = (int) $terms[0]->id();
      $this->districtBTid = (int) $terms[1]->id();
    }

    // Create two synthetic authenticated users with distinct first names.
    // The names are used by testUserMenuNotLeakedAcrossUsers.
    $this->userA = $this->createUser();
    $this->userB = $this->createUser();
    $this->userA->set('field_first_name', 'NysCacheTestAlpha');
    $this->userB->set('field_first_name', 'NysCacheTestBeta');

    // Assign distinct districts when available.
    if ($this->districtATid !== NULL) {
      $this->userA->set('field_district', $this->districtATid);
      $this->userB->set('field_district', $this->districtBTid);
    }

    $this->userA->save();
    $this->userB->save();
  }

  // ---------------------------------------------------------------------------
  // Shared dynamic cache entries across users
  // ---------------------------------------------------------------------------

  /**
   * The dynamic page cache is correctly shared across authenticated users.
   *
   * Drupal's dynamic page cache stores the full rendered response — including
   * lazy builder placeholders — after the first visit. All subsequent users
   * receive x-drupal-dynamic-cache: HIT; their lazy builder elements
   * (e.g. follow/unfollow buttons from IssueFlagLazyBuilder) are then rendered
   * server-side per-user on top of that cached skeleton, outside the cache.
   *
   * Therefore:
   *  - User A's first visit (cold cache) returns MISS.
   *  - User B's first visit hits the now-warm entry and returns HIT.
   *
   * Content-level isolation is verified separately in
   * testFollowUnfollowNotLeakedAcrossUsers.
   *
   * @dataProvider topLevelPageProvider
   */
  public function testDynamicCacheSharedAcrossUsers(string $path): void {
    // User A: first visit must be a MISS (cold cache — no entry exists yet).
    $this->drupalLogin($this->userA);
    $this->assertDynamicCacheMiss($path);
    $this->drupalLogout();

    // User B: HIT is expected. The dynamic page cache entry is shared across
    // users; only lazy builder placeholders are resolved per-user outside
    // the cache.
    $this->drupalLogin($this->userB);
    $this->assertDynamicCacheHit($path);
    $this->drupalLogout();
  }

  // ---------------------------------------------------------------------------
  // Follow/unfollow issue state isolation on /news-and-issues
  // ---------------------------------------------------------------------------

  /**
   * Each user sees their own follow/unfollow state on the issue page.
   *
   * The dynamic page cache skeleton is shared across users, but the
   * IssueFlagLazyBuilder renders the flag link server-side per user on top of
   * that skeleton. This test confirms that User A's flagged state does not
   * bleed through to User B's rendered response — User A sees "Unfollow" and
   * User B sees "Follow" for the same issue.
   */
  public function testFollowUnfollowNotLeakedAcrossUsers(): void {
    $issue = $this->findTermByVocabulary('issues');
    if ($issue === NULL) {
      $this->markTestSkipped('No issues taxonomy term found.');
    }

    if (!\Drupal::hasService('flag')) {
      $this->markTestSkipped('Flag module not available.');
    }

    /** @var \Drupal\flag\FlagServiceInterface $flagService */
    $flagService = \Drupal::service('flag');
    $flag = $flagService->getFlagById('follow_issue');
    if ($flag === NULL) {
      $this->markTestSkipped('follow_issue flag not found.');
    }

    $issuePath = $issue->toUrl()->toString();

    // User A follows the issue via the service (simulates clicking Follow).
    $flagService->flag($flag, $issue, $this->userA);

    try {
      // User A visits the issue page: the lazy builder must render the
      // "unflag" link because User A has already followed.
      $this->drupalLogin($this->userA);
      $this->visit($issuePath);
      $this->assertSession()->elementExists('css', '.flag-follow-issue.action-unflag');
      $this->drupalLogout();

      // User B visits the same page: the lazy builder must render the "flag"
      // link — not User A's "unflag" state — proving the shared cache skeleton
      // is personalized correctly per user.
      $this->drupalLogin($this->userB);
      $this->visit($issuePath);
      $this->assertSession()->elementExists('css', '.flag-follow-issue.action-flag');
      $this->drupalLogout();
    }
    finally {
      // Always remove the flag so data is unmodified even on failure.
      $flagService->unflag($flag, $issue, $this->userA);
    }
  }

  // ---------------------------------------------------------------------------
  // Committee follow/unfollow state isolation
  // ---------------------------------------------------------------------------

  /**
   * Each user sees their own follow/unfollow state on a committee page.
   *
   * CommitteeFlagLazyBuilder uses the same per-user lazy builder mechanism as
   * IssueFlagLazyBuilder. This confirms committee flag state is personalized
   * correctly and not shared across users via the cached page skeleton.
   */
  public function testCommitteeFollowNotLeakedAcrossUsers(): void {
    $committee = $this->findTermByVocabulary('committees');
    if ($committee === NULL) {
      $this->markTestSkipped('No committee taxonomy term found.');
    }

    if (!\Drupal::hasService('flag')) {
      $this->markTestSkipped('Flag module not available.');
    }

    /** @var \Drupal\flag\FlagServiceInterface $flagService */
    $flagService = \Drupal::service('flag');
    $flag = $flagService->getFlagById('follow_committee');
    if ($flag === NULL) {
      $this->markTestSkipped('follow_committee flag not found.');
    }

    $committeePath = $committee->toUrl()->toString();

    $flagService->flag($flag, $committee, $this->userA);

    try {
      // User A visits the committee page: must see the "unflag" link.
      $this->drupalLogin($this->userA);
      $this->visit($committeePath);
      $this->assertSession()->elementExists('css', '.flag-follow-committee.action-unflag');
      $this->drupalLogout();

      // User B visits the same page: must see the "flag" link, not User A's
      // "unflag" state.
      $this->drupalLogin($this->userB);
      $this->visit($committeePath);
      $this->assertSession()->elementExists('css', '.flag-follow-committee.action-flag');
      $this->drupalLogout();
    }
    finally {
      $flagService->unflag($flag, $committee, $this->userA);
    }
  }

  // ---------------------------------------------------------------------------
  // User menu personalization
  // ---------------------------------------------------------------------------

  /**
   * Each user sees their own name in the header user menu.
   *
   * UserMenuLazyBuilder renders a per-user welcome message server-side.
   * This confirms that User A's name is not served to User B from the shared
   * dynamic cache skeleton.
   */
  public function testUserMenuNotLeakedAcrossUsers(): void {
    // User A sees their own name in the header.
    $this->drupalLogin($this->userA);
    $this->visit('/');
    $this->assertSession()->pageTextContains('Welcome, NysCacheTestAlpha!');
    $this->drupalLogout();

    // User B sees their own name — not User A's — proving the lazy builder
    // personalizes correctly per user on top of the shared cache entry.
    $this->drupalLogin($this->userB);
    $this->visit('/');
    $this->assertSession()->pageTextContains('Welcome, NysCacheTestBeta!');
    $this->assertSession()->pageTextNotContains('Welcome, NysCacheTestAlpha!');
    $this->drupalLogout();
  }

  // ---------------------------------------------------------------------------
  // Anonymous cache not contaminated by authenticated visits
  // ---------------------------------------------------------------------------

  /**
   * An authenticated response must not be served to anonymous users.
   *
   * If a page's cache contexts incorrectly omit user.roles, an authenticated
   * response could be stored and returned to anonymous visitors. This test
   * confirms that after an authenticated user warms the dynamic cache, an
   * anonymous visitor still receives the correct anonymous response — the
   * login link, not the authenticated user menu.
   */
  public function testAuthenticatedContentNotLeakedToAnonymous(): void {
    // Authenticated user visits first — warms the dynamic cache entry.
    $this->drupalLogin($this->userA);
    $this->visit('/');
    $this->drupalLogout();

    // Anonymous visitor must receive the anonymous rendering: login link
    // present, personalised user menu absent. Use the href rather than link
    // text so the assertion is immune to theme label changes.
    $this->visit('/');
    $this->assertSession()->elementExists('css', 'a[href*="/user/login"]');
    $this->assertSession()->pageTextNotContains('Welcome, NysCacheTestAlpha!');
  }

}
