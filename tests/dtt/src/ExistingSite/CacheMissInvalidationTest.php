<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\block_content\BlockContentInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Verifies that cache is invalidated (MISS) when relevant content changes.
 *
 * Top-level pages:
 *  - / is invalidated by article edits, event edits, and homepage_hero queue changes.
 *  - /news-and-issues is invalidated by article edits.
 *  - /senators-committees is invalidated by senator or committee term edits.
 *  - /legislation is invalidated by bill edits.
 *  - /events is invalidated by event node edits.
 *  - /about is invalidated by landing page node edits and embedded block_content edits.
 *
 * Content type display pages — node edit:
 *  - Every cacheable primary content type display page is invalidated when the node is saved.
 *
 * Content type display pages — related entity edit:
 *  - Article and in_the_news pages are invalidated by senator term edits.
 *  - Event, meeting, and public_hearing pages are invalidated by committee term edits.
 *  - Resolution pages are invalidated by senator term edits via field_ol_sponsor.
 *
 * Test pattern: warm → HIT → saveViaWebRequest() → MISS → HIT, encapsulated
 * by assertCacheMissOnSave(). saveViaWebRequest() submits the entity edit form
 * as a real HTTP POST so that kernel.terminate fires and Fastly BANs are
 * dispatched before the next poll. CLI saves ($entity->save()) must not be
 * used here.
 *
 * @group cache_regression
 */
class CacheMissInvalidationTest extends CacheTestBase {

  /**
   * Administrator user created for web-form–based entity saves.
   *
   * @var \Drupal\user\UserInterface|null
   */
  protected ?UserInterface $adminUser = NULL;

  /**
   * {@inheritdoc}
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
  // Homepage ( / )
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
   * Changing the homepage_hero queue invalidates the homepage.
   *
   * The production trigger is the "Add item" button on the entity subqueue
   * edit form. nys_homepage_hero_form_entity_subqueue_homepage_hero_edit_form_alter()
   * registers HomepageHeroController::homepageHeroAddItem() as a #submit
   * callback on that button, which calls invalidateTags(['views:homepage_hero']).
   *
   * Although the button also carries a #ajax key, Drupal's #submit callbacks
   * run identically for plain HTTP POST requests (Goutte) and AJAX requests —
   * #ajax only changes what the client receives in response. Pressing the
   * button via Goutte therefore exercises the real production code path end-to-
   * end: HTTP POST → Drupal form pipeline → homepageHeroAddItem() →
   * invalidateTags → kernel.terminate → Fastly BAN → x-cache: MISS.
   *
   * The "Add item" button does NOT invoke the main entity save handler, so
   * the queue contents are NOT permanently modified; this test is side-effect-free.
   */
  public function testHomepageMissOnHomepageHeroQueueChange(): void {
    $this->assertNotNull(
      \Drupal::entityTypeManager()->getStorage('entity_subqueue')->load('homepage_hero'),
      "homepage_hero entity_subqueue not found."
    );

    $node = $this->requireHomepageHeroQueueItem();

    $this->warmCache('/');
    $this->assertAnonymousCacheHit('/');

    // Navigate to the entity subqueue edit form and submit the "Add item"
    // button. The autocomplete field expects "Entity Label (entity_id)" format.
    // Pressing the button (not the main Save) fires homepageHeroAddItem() and
    // rebuilds the form without persisting changes to the database.
    $this->visit('/admin/structure/entityqueue/homepage_hero/homepage_hero');
    $page = $this->getSession()->getPage();
    $page->fillField(
      'items[add_more][new_item][target_id]',
      $node->label() . ' (' . $node->id() . ')'
    );
    $page->pressButton('Add item');

    $this->assertAnonymousCacheMiss('/');
    $this->assertAnonymousCacheHit('/');
  }

  // ---------------------------------------------------------------------------
  // /news-and-issues
  // ---------------------------------------------------------------------------

  /**
   * Editing an article invalidates /news-and-issues (articles feed the page views).
   */
  public function testNewsAndIssuesMissOnArticleEdit(): void {
    $article = $this->requireNodeByType('article');

    $this->assertCacheMissOnSave('/news-and-issues', $article);
  }

  // ---------------------------------------------------------------------------
  // /senators-committees
  // ---------------------------------------------------------------------------

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

  // ---------------------------------------------------------------------------
  // /legislation
  // ---------------------------------------------------------------------------

  /**
   * Editing a bill node invalidates /legislation.
   */
  public function testLegislationMissOnBillEdit(): void {
    $bill = $this->requireSaveableBillNode();

    $this->assertCacheMissOnSave('/legislation', $bill);
  }

  // ---------------------------------------------------------------------------
  // /events
  // ---------------------------------------------------------------------------

  /**
   * Editing an event node invalidates /events.
   */
  public function testEventsMissOnEventNodeEdit(): void {
    $event = $this->requireNodeByType('event');

    $this->assertCacheMissOnSave('/events', $event);
  }

  // ---------------------------------------------------------------------------
  // /about and shared landing page patterns
  // ---------------------------------------------------------------------------

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
   * @dataProvider contentTypeProvider
   */
  public function testContentTypeDisplayPageMissOnNodeEdit(string $type): void {
    $node = ($type === 'bill') ? $this->requireSaveableBillNode() : $this->requireNodeByType($type);
    $path = $node->toUrl('canonical')->setAbsolute(FALSE)->toString();

    // Bill display pages are not anonymously page-cacheable (they return
    // Cache-Control: private, no-cache due to session-dependent rendering).
    // There is no HIT to bust, so this test only applies to cacheable types.
    if ($type === 'bill') {
      $this->markTestSkipped('Bill display pages are not anonymously page-cacheable; no HIT/MISS cycle to test.');
    }

    $this->assertCacheMissOnSave($path, $node);
  }

  // ---------------------------------------------------------------------------
  // Content type display pages — related entity edit
  // ---------------------------------------------------------------------------

  /**
   * Editing a senator term referenced by an article invalidates its display page.
   *
   * Article display pages carry taxonomy_term:{tid} tags for each senator
   * referenced via field_senator_multiref. Saving the referenced senator term
   * must invalidate those tags and bust the page.
   */
  public function testArticlePageMissOnSenatorEdit(): void {
    [$article, $senator] = $this->requireNodeAndValidTermByField('article', 'field_senator_multiref');
    $path = $article->toUrl('canonical')->setAbsolute(FALSE)->toString();

    $this->assertCacheMissOnSave($path, $senator);
  }

  /**
   * Editing a committee term referenced by an event invalidates its display page.
   *
   * Event display pages carry taxonomy_term:{tid} for field_committee. Saving
   * the referenced committee term must bust the page.
   */
  public function testEventPageMissOnCommitteeEdit(): void {
    $event = $this->requireNodeByTypeWithField('event', 'field_committee');
    $committee = $this->requireReferencedTerm($event, 'field_committee');
    $path = $event->toUrl('canonical')->setAbsolute(FALSE)->toString();

    $this->assertCacheMissOnSave($path, $committee);
  }

  /**
   * Editing a senator term referenced by an in_the_news node invalidates its display page.
   *
   * in_the_news display pages carry taxonomy_term:{tid} for field_senator_multiref.
   */
  public function testInTheNewsPageMissOnSenatorEdit(): void {
    [$node, $senator] = $this->requireNodeAndValidTermByField('in_the_news', 'field_senator_multiref');
    $path = $node->toUrl('canonical')->setAbsolute(FALSE)->toString();

    $this->assertCacheMissOnSave($path, $senator);
  }

  /**
   * Editing a committee term referenced by a meeting invalidates its display page.
   *
   * Meeting display pages carry taxonomy_term:{tid} for field_committee, both
   * directly on the node and via embedded committee_meetings views.
   */
  public function testMeetingPageMissOnCommitteeEdit(): void {
    $meeting = $this->requireNodeByTypeWithField('meeting', 'field_committee');
    $committee = $this->requireReferencedTerm($meeting, 'field_committee');
    $path = $meeting->toUrl('canonical')->setAbsolute(FALSE)->toString();

    $this->assertCacheMissOnSave($path, $committee);
  }

  /**
   * Editing a committee term referenced by a public hearing invalidates its display page.
   *
   * Public hearing display pages carry taxonomy_term:{tid} for field_committee.
   */
  public function testPublicHearingPageMissOnCommitteeEdit(): void {
    $node = $this->requireNodeByTypeWithField('public_hearing', 'field_committee');
    $committee = $this->requireReferencedTerm($node, 'field_committee');
    $path = $node->toUrl('canonical')->setAbsolute(FALSE)->toString();

    $this->assertCacheMissOnSave($path, $committee);
  }

  /**
   * Editing a senator term referenced by a resolution invalidates its display page.
   *
   * Resolution display pages carry taxonomy_term:{tid} for the sponsoring senator
   * via field_ol_sponsor (resolutions have no field_senator_multiref; the sponsor
   * is stored as an OpenLeg-imported taxonomy_term reference).
   */
  public function testResolutionPageMissOnSenatorEdit(): void {
    $resolution = $this->requireNodeByTypeWithField('resolution', 'field_ol_sponsor');
    $senator = $this->requireReferencedTerm($resolution, 'field_ol_sponsor');
    $path = $resolution->toUrl('canonical')->setAbsolute(FALSE)->toString();

    $this->assertCacheMissOnSave($path, $senator);
  }

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  /**
   * Loads a node by its URL alias, or NULL if not found.
   *
   * @return \Drupal\node\NodeInterface|null
   */
  protected function findNodeByAlias(string $alias): ?NodeInterface {
    $path = \Drupal::service('path_alias.manager')->getPathByAlias($alias);
    if (preg_match('/node\/(\d+)/', $path, $matches)) {
      return \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load((int) $matches[1]);
    }
    return NULL;
  }

  /**
   * Loads a node by its URL alias, or fails the test.
   */
  protected function requireNodeByAlias(string $alias): NodeInterface {
    return $this->findNodeByAlias($alias)
      ?? $this->fail("No node found with alias '{$alias}'.");
  }

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

}
