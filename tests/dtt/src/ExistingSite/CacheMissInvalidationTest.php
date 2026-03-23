<?php

namespace Drupal\Tests\nys\ExistingSite;

use Drupal\block_content\BlockContentInterface;
use Drupal\node\NodeInterface;

/**
 * Verifies that cache is invalidated (MISS) when relevant content changes.
 *
 * Each test follows the same pattern:
 *  1. warmCache() — primes the page cache (first request, MISS, discarded).
 *  2. assertAnonymousCacheHit() — confirms the page is now cached.
 *  3. Trigger a relevant change via the Drupal API.
 *  4. assertAnonymousCacheMiss() — cache tags invalidated, fresh MISS.
 *  5. assertAnonymousCacheHit() — cache rebuilt correctly after the MISS.
 *
 * Note: assertAnonymousCacheHit() does NOT internally warm the cache.
 * Every test must call warmCache() explicitly (step 1) to avoid false
 * failures from cross-test cache contamination.
 *
 * Most tests trigger a no-op entity re-save (no field values are altered).
 * Tests that cannot use a re-save (e.g. Views cache tag invalidation) call
 * the cache_tags.invalidator service directly, mirroring the production code.
 *
 * @group cache_regression
 */
class CacheMissInvalidationTest extends CacheTestBase {

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
    $article->save();
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
    $event->save();
    $this->assertAnonymousCacheMiss('/');
    $this->assertAnonymousCacheHit('/');
  }

  /**
   * Adding a session node to the homepage_hero queue invalidates the homepage.
   *
   * Saving the entity_subqueue entity alone does not invalidate the page
   * cache; HomepageHeroController::homepageHeroAddItem() explicitly invalidates
   * the views:homepage_hero tag whenever the queue changes. This test confirms
   * the homepage page cache is properly tagged and misses when that tag is
   * invalidated.
   */
  public function testHomepageMissOnHomepageHeroQueueChange(): void {
    $this->assertNotNull(
      \Drupal::entityTypeManager()->getStorage('entity_subqueue')->load('homepage_hero'),
      "homepage_hero entity_subqueue not found."
    );

    $this->warmCache('/');
    $this->assertAnonymousCacheHit('/');
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['views:homepage_hero']);
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
    $article->save();
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
    $senator->save();
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
    $committee->save();
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
    $bill->save();
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
    $event->save();
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
    $node->save();
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
    $blockContent->save();
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
