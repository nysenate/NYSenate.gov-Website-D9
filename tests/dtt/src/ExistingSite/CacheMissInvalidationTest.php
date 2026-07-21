<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\block_content\BlockContentInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Verifies that cache is invalidated (MISS) when relevant content changes.
 *
 * Top-level pages (sampled by trigger type):
 *  - Article edits invalidate / and /news-and-issues.
 *  - Event edits invalidate / and /events.
 *  - Senator/committee term edits invalidate /senators-committees.
 *  - Bill edits invalidate /legislation.
 *  - Landing page node and embedded block_content edits invalidate /about.
 *  - The homepage_hero entity subqueue invalidates / on queue change via the
 *    node entityqueue tab (/node/{nid}/entityqueue).
 *
 * Content type display pages — node edit (sampled):
 *  - bill and article display pages are invalidated when the node is saved.
 *    bill is tested because BillsHelper runs complex save-time logic; article
 *    represents the standard Drupal node:{nid} tag invalidation path.
 *
 * Content type display pages — related entity edit:
 *  - Article and in_the_news pages are invalidated by senator term edits.
 *  - Event, meeting, and public_hearing pages are invalidated by committee term edits.
 *  - Resolution pages are invalidated by senator term edits via field_ol_sponsor.
 *
 * Test pattern: warm → HIT → `saveEntity($entity)` → MISS → HIT, encapsulated by
 * assertCacheMissOnSave(). saveEntity() calls $entity->save() then immediately
 * flushes Pantheon's BAN buffer via pantheon_clear_edge_keys_shutdown() so CF
 * processes the invalidation before the per-test MISS poll begins.
 *
 * @group cache_regression
 */
class CacheMissInvalidationTest extends CacheTestBase {

  // ---------------------------------------------------------------------------
  // Top-level pages
  // ---------------------------------------------------------------------------

  /**
   * Editing an article invalidates the homepage (articles appear in homepage views).
   */
  public function testHomepageMissOnArticleEdit(): void {
    $article = $this->requireNodeByType('article');

    $this->assertCacheMissOnSave('/', $article);
  }

  /**
   * Editing an event node invalidates the homepage (events appear in homepage views).
   */
  public function testHomepageMissOnEventNodeEdit(): void {
    $event = $this->requireNodeByType('event');

    $this->assertCacheMissOnSave('/', $event);
  }

  /**
   * Changing the homepage_hero queue via the node entityqueue tab invalidates the homepage.
   *
   * The production path is /node/{nid}/entityqueue, where the "Add to queue"
   * AJAX link calls EntityQueueUIController::subqueueAjaxOperation(), which
   * saves the subqueue entity directly. nys_homepage_hero_entity_subqueue_update()
   * then invalidates the views:homepage_hero cache tag (block/page cache layer),
   * while the entity save automatically invalidates entity_subqueue:homepage_hero
   * (declared in the view's custom_tag cache plugin), busting the views result cache.
   *
   * The queue is cleared before the test via CLI (acceptable for test setup)
   * and restored afterwards, so this test is idempotent regardless of the
   * current queue contents.
   */
  public function testHomepageMissOnHomepageHeroQueueChange(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('entity_subqueue');
    $subqueue = $storage->load('homepage_hero');
    $this->assertNotNull($subqueue, 'homepage_hero entity_subqueue not found.');

    // Save current queue state so we can restore it after the test.
    $originalItems = $subqueue->get('items')->getValue();

    // Clear the queue so the node tab shows "Add to queue". CLI saves are
    // acceptable for test setup; the action under test is the web request below.
    $subqueue->set('items', [])->save();

    $node = $this->requireHomepageHeroQueueItem();

    $this->warmCache('/');
    $this->assertAnonymousCacheHit('/');

    // This test exercises the production UI path — clicking the "Add to queue"
    // link on the node entityqueue tab. A real authenticated web request is
    // required here because the link contains a CSRF token and the action is
    // specific to the form-based entityqueue UI, not a generic entity save.
    $adminUser = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($adminUser);

    // Add the node via the node entityqueue tab — the production UI path used
    // by site admins. The link href contains the CSRF token added by Drupal's
    // URL generator; Mink follows it as a plain GET, triggering the same entity
    // save and hook invocation as the AJAX path.
    $this->visit('/node/' . $node->id() . '/entityqueue');
    $addLink = $this->getSession()->getPage()->find(
      'css',
      'a[href*="/homepage_hero/homepage_hero/' . $node->id() . '/add-item"]'
    );
    $this->assertNotNull($addLink, 'Add to queue link not found on the node entityqueue tab.');
    $addLink->click();

    $this->drupalLogout();

    $this->assertAnonymousCacheMiss('/');
    $this->assertAnonymousCacheHit('/');

    // Restore the original queue state.
    $storage->resetCache(['homepage_hero']);
    $storage->load('homepage_hero')->set('items', $originalItems)->save();
  }

  /**
   * Editing an article invalidates /news-and-issues (articles feed the page views).
   */
  public function testNewsAndIssuesMissOnArticleEdit(): void {
    $article = $this->requireNodeByType('article');

    $this->assertCacheMissOnSave('/news-and-issues', $article);
  }

  /**
   * Editing a senator term invalidates /senators-committees.
   */
  public function testSenatorsCommitteesMissOnSenatorEdit(): void {
    $senator = $this->requireTermByVocabulary('senator');

    $this->assertCacheMissOnSave('/senators-committees', $senator);
  }

  /**
   * Editing a committee term invalidates /senators-committees.
   */
  public function testSenatorsCommitteesMissOnCommitteeEdit(): void {
    $committee = $this->requireTermByVocabulary('committees');

    $this->assertCacheMissOnSave('/senators-committees', $committee);
  }

  /**
   * Editing a bill node invalidates /legislation.
   */
  public function testLegislationMissOnBillEdit(): void {
    $bill = $this->requireSaveableBillNode();

    $this->assertCacheMissOnSave('/legislation', $bill);
  }

  /**
   * Editing an event node invalidates /events.
   */
  public function testEventsMissOnEventNodeEdit(): void {
    $event = $this->requireNodeByType('event');

    $this->assertCacheMissOnSave('/events', $event);
  }

  /**
   * Editing a landing page node invalidates that page.
   *
   * All landing pages are structurally identical (/about is used as the
   * specimen); the Drupal node cache tag mechanism is the same for all.
   */
  public function testAboutMissOnLandingPageEdit(): void {
    $node = $this->requireNodeByAlias('/about');

    $this->assertCacheMissOnSave('/about', $node);
  }

  /**
   * Editing an embedded block_content entity invalidates its landing page.
   *
   * block_content entities referenced via field_landing_blocks carry their own
   * cache tags; a save must bubble up and bust the full page. Tested once on
   * /about — the tag-bubbling mechanism is identical for all landing pages.
   */
  public function testAboutMissOnContentBlockEdit(): void {
    $aboutNode = $this->requireNodeByAlias('/about');
    $blockContent = $this->requireBlockContentOnNode($aboutNode);

    $this->assertCacheMissOnSave('/about', $blockContent);
  }

  // ---------------------------------------------------------------------------
  // Content type display pages — node edit
  // ---------------------------------------------------------------------------

  /**
   * A content type display page is invalidated when the node is saved.
   *
   * Verified for bill and article. The node:{nid} cache tag invalidation
   * mechanism is provided automatically by Drupal core and is identical for
   * all content types. bill is included specifically because BillsHelper runs
   * complex save-time logic; a silent exception there could prevent the save
   * completing cleanly and leave the cache un-invalidated. article represents
   * the standard save path for all other types.
   *
   * @dataProvider representativeContentTypeProvider
   */
  public function testContentTypeDisplayPageMissOnNodeEdit(string $type): void {
    $node = ($type === 'bill') ? $this->requireSaveableBillNode() : $this->requireNodeByType($type);
    $path = $node->toUrl('canonical')->setAbsolute(FALSE)->toString();

    $this->assertCacheMissOnSave($path, $node);
  }

  // ---------------------------------------------------------------------------
  // Content type display pages — related entity edit
  // ---------------------------------------------------------------------------

  /**
   * Editing a senator term referenced by an article invalidates its display page.
   */
  public function testArticlePageMissOnSenatorEdit(): void {
    [$article, $senator] = $this->requireNodeWithReferencedTerm('article', 'field_senator_multiref');
    $path = $article->toUrl('canonical')->setAbsolute(FALSE)->toString();

    $this->assertCacheMissOnSave($path, $senator);
  }

  /**
   * Editing a committee term referenced by an event invalidates its display page.
   */
  public function testEventPageMissOnCommitteeEdit(): void {
    [$event, $committee] = $this->requireNodeWithReferencedTerm('event', 'field_committee');
    $path = $event->toUrl('canonical')->setAbsolute(FALSE)->toString();

    $this->assertCacheMissOnSave($path, $committee);
  }

  /**
   * Editing a senator term referenced by an in_the_news node invalidates its display page.
   */
  public function testInTheNewsPageMissOnSenatorEdit(): void {
    [$node, $senator] = $this->requireNodeWithReferencedTerm('in_the_news', 'field_senator_multiref');
    $path = $node->toUrl('canonical')->setAbsolute(FALSE)->toString();

    $this->assertCacheMissOnSave($path, $senator);
  }

  /**
   * Editing a committee term referenced by a meeting invalidates its display page.
   */
  public function testMeetingPageMissOnCommitteeEdit(): void {
    [$meeting, $committee] = $this->requireNodeWithReferencedTerm('meeting', 'field_committee');
    $path = $meeting->toUrl('canonical')->setAbsolute(FALSE)->toString();

    $this->assertCacheMissOnSave($path, $committee);
  }

  /**
   * Editing a committee term referenced by a public hearing invalidates its display page.
   */
  public function testPublicHearingPageMissOnCommitteeEdit(): void {
    [$node, $committee] = $this->requireNodeWithReferencedTerm('public_hearing', 'field_committee');
    $path = $node->toUrl('canonical')->setAbsolute(FALSE)->toString();

    $this->assertCacheMissOnSave($path, $committee);
  }

  /**
   * Editing a senator term referenced by a resolution (via field_ol_sponsor) invalidates its display page.
   */
  public function testResolutionPageMissOnSenatorEdit(): void {
    [$resolution, $senator] = $this->requireNodeWithReferencedTerm('resolution', 'field_ol_sponsor');
    $path = $resolution->toUrl('canonical')->setAbsolute(FALSE)->toString();

    $this->assertCacheMissOnSave($path, $senator);
  }

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  /**
   * Returns a published node suitable for adding to the homepage_hero queue, or NULL.
   *
   * Valid bundles are those configured on the homepage_hero entityqueue handler:
   * article, event, meeting, public_hearing, session. The first available type
   * is returned so the test is not skipped even on sparse database clones.
   *
   * @return \Drupal\node\NodeInterface|null
   */
  private function findHomepageHeroQueueItem(): ?NodeInterface {
    foreach (['session', 'event', 'article', 'meeting', 'public_hearing'] as $type) {
      $node = $this->findNodeByType($type);
      if ($node !== NULL) {
        return $node;
      }
    }
    return NULL;
  }

  /**
   * Returns a published node suitable for adding to the homepage_hero queue, or fails.
   */
  private function requireHomepageHeroQueueItem(): NodeInterface {
    return $this->findHomepageHeroQueueItem()
      ?? $this->fail('No published node found with a valid homepage_hero queue bundle (article, event, meeting, public_hearing, session).');
  }

  /**
   * Returns the first block_content entity directly referenced on a node, or NULL.
   *
   * field_landing_blocks is an entity_reference field pointing directly at
   * block_content entities (not paragraph items). The first non-NULL reference
   * is returned.
   *
   * @return \Drupal\block_content\BlockContentInterface|null
   */
  protected function findBlockContentOnNode(NodeInterface $node): ?BlockContentInterface {
    if ($node->hasField('field_landing_blocks')) {
      foreach ($node->get('field_landing_blocks') as $item) {
        $entity = $item->entity;
        if ($entity instanceof BlockContentInterface) {
          return $entity;
        }
      }
    }

    return NULL;
  }

  /**
   * Returns the first block_content entity directly referenced on a node, or fails the test.
   */
  protected function requireBlockContentOnNode(NodeInterface $node): BlockContentInterface {
    return $this->findBlockContentOnNode($node)
      ?? $this->fail('No block_content entity embedded in the landing page. Ensure the DB is a production clone with block content assigned.');
  }

  // ---------------------------------------------------------------------------
  // Data providers
  // ---------------------------------------------------------------------------

  /**
   * Data provider: bill and one representative content type.
   *
   * The node:{nid} cache tag invalidation mechanism is identical for all
   * content types — it is provided automatically by Drupal core. bill is
   * included because BillsHelper runs complex save-time logic; a silent
   * exception there could prevent the save completing and leave the cache
   * un-invalidated. article represents the standard save path for all other
   * types.
   */
  public static function representativeContentTypeProvider(): array {
    return [
      'bill'    => ['bill'],
      'article' => ['article'],
    ];
  }

}
