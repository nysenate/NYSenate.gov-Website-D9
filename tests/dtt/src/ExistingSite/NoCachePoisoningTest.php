<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\user\Entity\User;

/**
 * Verifies that no cache poisoning occurs between users or across sessions.
 *
 * Cache poisoning means one user's personalized content is wrongly served
 * from cache to a different user. These tests verify:
 *
 *  1. User A's follow state (issues, committees) is not visible to User B.
 *  2. Each user sees their own name in the header user menu.
 *  3. An anonymous visitor after an authenticated visit still receives the
 *     anonymous rendering — not a leaked authenticated response.
 *
 * That the dynamic page cache skeleton IS correctly shared across users is
 * verified in AuthenticatedDynamicCacheTest::testDynamicCacheSharedAcrossUsers.
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

    // Clear the dynamic page cache so the User A MISS / User B HIT assertion
    // in testDynamicCacheSharedAcrossUsers() is reliable regardless of prior
    // test runs or real editor traffic warming the same role-keyed entries.
    \Drupal::cache('dynamic_page_cache')->deleteAll();

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
    $issue = $this->requireTermByVocabulary('issues');

    $this->assertTrue(\Drupal::hasService('flag'), 'Flag module not available.');

    /** @var \Drupal\flag\FlagServiceInterface $flagService */
    $flagService = \Drupal::service('flag');
    $flag = $flagService->getFlagById('follow_issue');
    $this->assertNotNull($flag, 'follow_issue flag not found.');

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
    $committee = $this->requireTermByVocabulary('committees');

    $this->assertTrue(\Drupal::hasService('flag'), 'Flag module not available.');

    /** @var \Drupal\flag\FlagServiceInterface $flagService */
    $flagService = \Drupal::service('flag');
    $flag = $flagService->getFlagById('follow_committee');
    $this->assertNotNull($flag, 'follow_committee flag not found.');

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

  // ---------------------------------------------------------------------------
  // Bill vote widget — per-user lazy builder isolation
  // ---------------------------------------------------------------------------

  /**
   * The bill vote widget resolves per-user state and does not leak across users.
   *
   * BillVoteWidgetLazyBuilder is invoked for every request (even dynamic page
   * cache HITs), resolving each user's personal vote state from the database.
   * This test confirms that User A's voted-yes state is visible only to User A,
   * and that User B (who has no vote) sees the neutral "Do you support this
   * bill?" prompt rather than User A's voted label.
   *
   * Test strategy:
   *  1. Create a vote entity for User A (value = 1 = 'yes') directly via the
   *     entity API, avoiding any form submission side-effects.
   *  2. User A visits the bill page → the BillVoteWidgetForm::getVotedLabel()
   *     method, called inside the lazy builder, detects the existing vote and
   *     renders the "You are in favor of this bill" label.
   *  3. User B visits the same page (which returns x-drupal-dynamic-cache: HIT
   *     for the skeleton, confirming sharing) → the lazy builder resolves User
   *     B's empty vote state and renders "Do you support this bill?".
   *  4. Confirm User B's rendered output does NOT contain User A's voted label.
   */
  public function testBillVoteWidgetIsolatedPerUser(): void {
    $node = $this->findSaveableBillNode();
    if ($node === NULL) {
      $this->markTestSkipped('No suitable published bill node found.');
    }

    $path = $node->toUrl('canonical')->setAbsolute(FALSE)->toString();

    // Create a 'yes' vote entity for User A directly — value 1 = 'yes' in
    // BillVoteHelper::getVal().
    /** @var \Drupal\Core\Entity\EntityStorageInterface $voteStorage */
    $voteStorage = \Drupal::entityTypeManager()->getStorage('vote');
    $vote = $voteStorage->create([
      'type'        => 'nys_bill_vote',
      'entity_type' => 'node',
      'entity_id'   => $node->id(),
      'value'       => 1,
      'value_type'  => 'option',
      'user_id'     => $this->userA->id(),
    ]);
    $vote->save();

    // Invalidate all dynamic cache entries so both users start cold.
    \Drupal::cache('dynamic_page_cache')->deleteAll();

    try {
      // User A (voted 'yes'): the lazy builder must render the voted label.
      $this->drupalLogin($this->userA);
      $this->visit($path);
      // Dynamic cache: MISS (skeleton stored now).
      $dynamicCache = strtoupper(trim($this->getSession()->getResponseHeader('x-drupal-dynamic-cache') ?? ''));
      $this->assertSame('MISS', $dynamicCache, "User A's first visit to bill page must be a dynamic cache MISS.");
      // Use a CSS selector rather than pageTextContains so we only inspect the
      // vote widget heading, not the full page text (which always includes
      // drupalSettings JSON containing all vote-option strings regardless of the
      // current user's vote state).
      $this->assertSession()->elementTextContains('css', '.c-bill-polling--cta', 'You are in favor of this bill');
      $this->assertSession()->elementTextNotContains('css', '.c-bill-polling--cta', 'Do you support this bill?');
      $this->drupalLogout();

      // User B (no vote): the dynamic cache skeleton is HIT (skeleton shared),
      // but the lazy builder resolves User B's own empty vote state.
      $this->drupalLogin($this->userB);
      $this->visit($path);
      $dynamicCache = strtoupper(trim($this->getSession()->getResponseHeader('x-drupal-dynamic-cache') ?? ''));
      $this->assertSame('HIT', $dynamicCache, "User B's first visit to bill page must be a dynamic cache HIT (shared skeleton).");
      // User B has not voted — must see the neutral prompt, not User A's label.
      // The lazy builder resolves the vote widget per-user on top of the shared
      // dynamic-cache skeleton, so User A's "in favor" label must not appear
      // in the vote widget heading.
      $this->assertSession()->elementTextContains('css', '.c-bill-polling--cta', 'Do you support this bill?');
      $this->assertSession()->elementTextNotContains('css', '.c-bill-polling--cta', 'You are in favor of this bill');
      $this->drupalLogout();
    }
    finally {
      $vote->delete();
    }
  }

}

