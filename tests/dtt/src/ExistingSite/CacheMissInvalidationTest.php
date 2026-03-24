<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\block_content\BlockContentInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Verifies that cache is invalidated (MISS) when relevant content changes.
 *
 * Each test follows the same pattern:
 *  1. warmCache() — primes the page cache (first request, MISS, discarded).
 *  2. assertAnonymousCacheHit() — confirms the page is now cached.
 *  3. saveViaWebRequest() — submits the entity edit form as a real HTTP POST.
 *     On Pantheon this fires kernel.terminate which dispatches a Fastly BAN for
 *     the invalidated cache tags, making the next anonymous request a MISS via
 *     x-cache rather than only via x-drupal-cache. CLI saves ($entity->save())
 *     invalidate Redis but never reach Fastly, so they cannot be used here.
 *  4. assertAnonymousCacheMiss() — polls until x-cache (Fastly) or
 *     x-drupal-cache (DDEV) returns MISS.
 *  5. assertAnonymousCacheHit() — cache rebuilt correctly after the MISS.
 *
 * Note: assertAnonymousCacheHit() does NOT internally warm the cache.
 * Every test must call warmCache() explicitly (step 1) to avoid false
 * failures from cross-test cache contamination.
 *
 * Exception: testHomepageMissOnHomepageHeroQueueChange() uses CLI tag
 * invalidation and checks the Redis page cache bin directly, because the
 * production trigger (HomepageHeroController::homepageHeroAddItem()) is an
 * AJAX form submit handler that cannot be invoked without a JS-capable driver.
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
    $article = $this->findNodeByType('article');
    $this->assertNotNull($article, "No published 'article' node found.");

    $this->warmCache('/');
    $this->assertAnonymousCacheHit('/');
    $this->saveViaWebRequest($article);
    $this->assertAnonymousCacheMiss('/');
    $this->assertAnonymousCacheHit('/');
  }

  /**
   * Editing an event node invalidates the homepage (events appear in homepage views).
   */
  public function testHomepageMissOnEventNodeEdit(): void {
    $event = $this->findNodeByType('event');
    $this->assertNotNull($event, "No published 'event' node found.");

    $this->warmCache('/');
    $this->assertAnonymousCacheHit('/');
    $this->saveViaWebRequest($event);
    $this->assertAnonymousCacheMiss('/');
    $this->assertAnonymousCacheHit('/');
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

    $node = $this->findHomepageHeroQueueItem();
    $this->assertNotNull($node,
      'No published node found with a valid homepage_hero queue bundle (article, event, meeting, public_hearing, session).'
    );

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
    $article = $this->findNodeByType('article');
    $this->assertNotNull($article, "No published 'article' node found.");

    $this->warmCache('/news-and-issues');
    $this->assertAnonymousCacheHit('/news-and-issues');
    $this->saveViaWebRequest($article);
    $this->assertAnonymousCacheMiss('/news-and-issues');
    $this->assertAnonymousCacheHit('/news-and-issues');
  }

  // ---------------------------------------------------------------------------
  // /senators-committees
  // ---------------------------------------------------------------------------

  /**
   * Editing a senator term invalidates /senators-committees.
   */
  public function testSenatorsCommitteesMissOnSenatorEdit(): void {
    $senator = $this->findTermByVocabulary('senator');
    $this->assertNotNull($senator, "No 'senator' taxonomy term found.");

    $this->warmCache('/senators-committees');
    $this->assertAnonymousCacheHit('/senators-committees');
    $this->saveViaWebRequest($senator);
    $this->assertAnonymousCacheMiss('/senators-committees');
    $this->assertAnonymousCacheHit('/senators-committees');
  }

  /**
   * Editing a committee term invalidates /senators-committees.
   */
  public function testSenatorsCommitteesMissOnCommitteeEdit(): void {
    $committee = $this->findTermByVocabulary('committees');
    $this->assertNotNull($committee, "No 'committees' taxonomy term found.");

    $this->warmCache('/senators-committees');
    $this->assertAnonymousCacheHit('/senators-committees');
    $this->saveViaWebRequest($committee);
    $this->assertAnonymousCacheMiss('/senators-committees');
    $this->assertAnonymousCacheHit('/senators-committees');
  }

  // ---------------------------------------------------------------------------
  // /legislation
  // ---------------------------------------------------------------------------

  /**
   * Editing a bill node invalidates /legislation.
   */
  public function testLegislationMissOnBillEdit(): void {
    $bill = $this->findSaveableBillNode();
    if ($bill === NULL) {
      $this->markTestSkipped('No bill with valid print number and session found.');
    }

    $this->warmCache('/legislation');
    $this->assertAnonymousCacheHit('/legislation');
    $this->saveViaWebRequest($bill);
    $this->assertAnonymousCacheMiss('/legislation');
    $this->assertAnonymousCacheHit('/legislation');
  }

  // ---------------------------------------------------------------------------
  // /events
  // ---------------------------------------------------------------------------

  /**
   * Editing an event node invalidates /events.
   */
  public function testEventsMissOnEventNodeEdit(): void {
    $event = $this->findNodeByType('event');
    $this->assertNotNull($event, "No published 'event' node found.");

    $this->warmCache('/events');
    $this->assertAnonymousCacheHit('/events');
    $this->saveViaWebRequest($event);
    $this->assertAnonymousCacheMiss('/events');
    $this->assertAnonymousCacheHit('/events');
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
    $node = $this->findNodeByAlias('/about');
    if ($node === NULL) {
      $this->markTestSkipped('No landing page with alias /about found.');
    }

    $this->warmCache('/about');
    $this->assertAnonymousCacheHit('/about');
    $this->saveViaWebRequest($node);
    $this->assertAnonymousCacheMiss('/about');
    $this->assertAnonymousCacheHit('/about');
  }

  /**
   * Editing an embedded block_content entity invalidates its landing page.
   *
   * block_content entities referenced via field_landing_blocks carry their own
   * cache tags; a save must bubble up and bust the full page. Tested once on
   * /about — the tag-bubbling mechanism is identical for all landing pages.
   */
  public function testAboutMissOnContentBlockEdit(): void {
    $aboutNode = $this->findNodeByAlias('/about');
    if ($aboutNode === NULL) {
      $this->markTestSkipped('No landing page with alias /about found.');
    }

    // Collect block_content entities referenced by the about landing page.
    $blockContent = $this->findBlockContentOnNode($aboutNode);
    if ($blockContent === NULL) {
      $this->markTestSkipped('No block_content entity embedded in /about landing page — skipping. Run against a prod DB clone for full coverage.');
    }

    $this->warmCache('/about');
    $this->assertAnonymousCacheHit('/about');
    $this->saveViaWebRequest($blockContent);
    $this->assertAnonymousCacheMiss('/about');
    $this->assertAnonymousCacheHit('/about');
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
   * Returns a published node suitable for adding to the homepage_hero queue.
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

}
