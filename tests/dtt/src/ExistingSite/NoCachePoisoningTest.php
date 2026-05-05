<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\user\Entity\User;

/**
 * Verifies that no cache poisoning occurs between users or across sessions.
 *
 * Cache poisoning means one user's personalized content is wrongly served
 * from cache to a different user. These tests verify:
 *
 *  1. Follow/unfollow flag state (issues and committees) is not leaked across users.
 *  2. Each user sees their own name in the header user menu.
 *  3. Each user sees their own district senator in the "I Want To" block.
 *  4. The bill vote widget resolves per-user state on top of the shared dynamic cache skeleton.
 *  5. An anonymous visitor after an authenticated visit still receives the
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

    // Clear the dynamic page cache so tests that assert MISS/HIT sequences
    // start from a guaranteed cold-cache state, independent of prior test
    // runs or real editor traffic.
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
  // Follow/unfollow state isolation (issues and committees)
  // ---------------------------------------------------------------------------

  /**
   * Each user sees their own follow/unfollow flag state; User A's flagged
   * state is not served to User B from the shared cache skeleton.
   *
   * Verified for both follow_issue (IssueFlagLazyBuilder) and follow_committee
   * (CommitteeFlagLazyBuilder) — distinct implementations, same isolation mechanism.
   *
   * @dataProvider flagStateProvider
   */
  public function testFlagStateNotLeakedAcrossUsers(string $vocabulary, string $flagId, string $cssClass): void {
    $term = $this->requireTermByVocabulary($vocabulary);

    $this->assertTrue(\Drupal::hasService('flag'), 'Flag module not available.');

    /** @var \Drupal\flag\FlagServiceInterface $flagService */
    $flagService = \Drupal::service('flag');
    $flag = $flagService->getFlagById($flagId);
    $this->assertNotNull($flag, "{$flagId} flag not found.");

    $termPath = $term->toUrl()->toString();

    // User A follows the term via the service (simulates clicking Follow).
    $flagService->flag($flag, $term, $this->userA);

    try {
      // User A visits the page: must see the "unflag" link (already following).
      $this->drupalLogin($this->userA);
      $this->visit($termPath);
      $this->assertSession()->elementExists('css', ".{$cssClass}.action-unflag");
      $this->drupalLogout();

      // User B visits the same page: must see the "flag" link — not User A's
      // "unflag" state — proving the lazy builder personalizes per user.
      $this->drupalLogin($this->userB);
      $this->visit($termPath);
      $this->assertSession()->elementExists('css', ".{$cssClass}.action-flag");
      $this->drupalLogout();
    }
    finally {
      // Always remove the flag so data is unmodified even on failure.
      $flagService->unflag($flag, $term, $this->userA);
    }
  }

  /**
   * Data provider: issue and committee flag configurations.
   */
  public static function flagStateProvider(): array {
    return [
      'issue'     => ['issues', 'follow_issue', 'flag-follow-issue'],
      'committee' => ['committees', 'follow_committee', 'flag-follow-committee'],
    ];
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
  // Senator section personalization ("I Want To" block)
  // ---------------------------------------------------------------------------

  /**
   * Each user sees their own district's senator in the "I Want To" block.
   *
   * WantToLazyBuilder resolves the senator headshot and microsite link from
   * the current user's field_district assignment. User A's senator must not
   * appear in User B's rendered response.
   */
  public function testSenatorSectionNotLeakedAcrossUsers(): void {
    $this->assertNotNull($this->districtATid, 'Fewer than two district terms found — database may be corrupt.');

    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $districtA = $storage->load($this->districtATid);
    $districtB = $storage->load($this->districtBTid);
    $senatorA = $districtA?->field_senator->entity;
    $senatorB = $districtB?->field_senator->entity;

    $this->assertNotNull($senatorA, "District {$this->districtATid} has no senator assigned.");
    $this->assertNotNull($senatorB, "District {$this->districtBTid} has no senator assigned.");
    $this->assertNotEquals($senatorA->id(), $senatorB->id(), 'Districts A and B share the same senator — cannot test isolation.');

    // Resolve microsite URLs via the same service the lazy builder uses.
    // The template renders these as href attributes on the senator block link.
    /** @var \Drupal\nys_senators\Service\Microsites $microsites */
    $microsites = \Drupal::service('nys_senators.microsites');
    $urlA = $microsites->getMicrosite($senatorA);
    $urlB = $microsites->getMicrosite($senatorB);

    $this->assertNotEmpty($urlA, "Senator {$senatorA->label()} (district {$this->districtATid}) has no microsite URL.");
    $this->assertNotEmpty($urlB, "Senator {$senatorB->label()} (district {$this->districtBTid}) has no microsite URL.");

    // User A sees their own senator's microsite link in the block.
    $this->drupalLogin($this->userA);
    $this->visit('/');
    $this->assertSession()->responseContains($urlA);
    $this->drupalLogout();

    // User B sees their own senator's link — not User A's — proving the lazy
    // builder personalizes per user on top of the shared cache entry.
    $this->drupalLogin($this->userB);
    $this->visit('/');
    $this->assertSession()->responseNotContains($urlA);
    $this->assertSession()->responseContains($urlB);
    $this->drupalLogout();
  }

  // ---------------------------------------------------------------------------
  // Bill vote widget — per-user lazy builder isolation
  // ---------------------------------------------------------------------------

  /**
   * The bill vote widget resolves per-user state and does not leak across users.
   *
   * BillVoteWidgetLazyBuilder runs on every request (including dynamic cache
   * HITs), resolving each user's vote state from the database. User A's
   * voted-yes label must not appear in User B's response — User B sees the
   * neutral "Do you support this bill?" prompt. User B's visit returns
   * x-drupal-dynamic-cache: HIT, confirming skeleton sharing is intact while
   * per-user state is isolated.
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

