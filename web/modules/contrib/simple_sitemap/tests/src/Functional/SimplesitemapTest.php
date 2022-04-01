<?php

namespace Drupal\Tests\simple_sitemap\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\simple_sitemap\Queue\QueueWorker;

/**
 * Tests Simple XML Sitemap functional integration.
 *
 * @group simple_sitemap
 */
class SimplesitemapTest extends SimplesitemapTestBase {

  /**
   * Verify sitemap.xml has the link to the front page after first generation.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testInitialGeneration() {
    $this->generator->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);
    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('urlset');
    $this->assertSession()->responseContains(
      Url::fromRoute('<front>')->setAbsolute()->toString()
    );
    $this->assertSession()->responseContains('1.0');
    $this->assertSession()->responseContains('daily');
  }

  /**
   * Test custom link.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAddCustomLink() {
    $this->generator->addCustomLink(
      '/node/' . $this->node->id(),
      ['priority' => 0.2, 'changefreq' => 'monthly']
    )->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('node/' . $this->node->id());
    $this->assertSession()->responseContains('0.2');
    $this->assertSession()->responseContains('monthly');

    $this->drupalLogin($this->privilegedUser);

    $this->drupalGet('admin/config/search/simplesitemap/custom');
    $this->assertSession()->pageTextContains(
      '/node/' . $this->node->id() . ' 0.2 monthly'
    );

    $this->generator->addCustomLink(
      '/node/' . $this->node->id(),
      ['changefreq' => 'yearly']
    )->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet('admin/config/search/simplesitemap/custom');
    $this->assertSession()->pageTextContains(
      '/node/' . $this->node->id() . ' yearly'
    );
  }

  /**
   * Test default settings of custom links.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAddCustomLinkDefaults() {
    $this->generator->removeCustomLinks()
      ->addCustomLink('/node/' . $this->node->id())
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('node/' . $this->node->id());
    $this->assertSession()->responseContains('0.5');
    $this->assertSession()->responseNotContains('changefreq');
  }

  /**
   * Tests locks
   */
  public function testLocking() {
    $this->generator->removeCustomLinks()
      ->addCustomLink('/node/' . $this->node->id())
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);
    $this->drupalLogin($this->createUser(['administer sitemap settings']));

    $this->drupalGet('/admin/config/search/simplesitemap/settings');
    $this->submitForm(['simple_sitemap_regenerate_now' => TRUE], 'Save configuration');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->pageTextNotContains('Unable to acquire a lock for sitemap generation.');

    \Drupal::lock()->acquire(QueueWorker::LOCK_ID);
    $this->submitForm(['simple_sitemap_regenerate_now' => TRUE], 'Save configuration');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->pageTextContainsOnce('Unable to acquire a lock for sitemap generation.');
  }

  /**
   * Test removing custom paths from the sitemap settings.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testRemoveCustomLinks() {

    // Test removing one custom path from the sitemap.
    $this->generator->addCustomLink('/node/' . $this->node->id())
      ->removeCustomLinks('/node/' . $this->node->id())
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseNotContains('node/' . $this->node->id());

    // Test removing all custom paths from the sitemap.
    $this->generator->removeCustomLinks()
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseNotContains(
      Url::fromRoute('<front>')->setAbsolute()->toString()
    );
  }

  /**
   * Tests setting bundle settings.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Behat\Mink\Exception\ExpectationException
   *
   * @todo Add form tests
   */
  public function testSetBundleSettings() {
    $this->assertFalse($this->generator->bundleIsIndexed('node', 'page'));

    // Index new bundle.
    $this->generator->removeCustomLinks()
      ->setBundleSettings('node', 'page', [
        'index' => TRUE,
        'priority' => 0.5,
        'changefreq' => 'hourly',
      ])
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('node/' . $this->node->id());
    $this->assertSession()->responseContains('0.5');
    $this->assertSession()->responseContains('hourly');

    $this->assertTrue($this->generator->bundleIsIndexed('node', 'page'));

    // Only change bundle priority.
    $this->generator->setBundleSettings('node', 'page', ['priority' => 0.9])
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('node/' . $this->node->id());
    $this->assertSession()->responseNotContains('0.5');
    $this->assertSession()->responseContains('0.9');

    // Only change bundle changefreq.
    $this->generator->setBundleSettings(
      'node',
      'page',
      ['changefreq' => 'daily']
    )->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('node/' . $this->node->id());
    $this->assertSession()->responseNotContains('hourly');
    $this->assertSession()->responseContains('daily');

    // Remove changefreq setting.
    $this->generator->setBundleSettings('node', 'page', ['changefreq' => ''])
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('node/' . $this->node->id());
    $this->assertSession()->responseNotContains('changefreq');
    $this->assertSession()->responseNotContains('daily');

    // Index two bundles.
    $this->drupalCreateContentType(['type' => 'blog']);

    $node3 = $this->createNode(['title' => 'Node3', 'type' => 'blog']);
    $this->generator->setBundleSettings('node', 'page', ['index' => TRUE])
      ->setBundleSettings('node', 'blog', ['index' => TRUE])
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('node/' . $this->node->id());
    $this->assertSession()->responseContains('node/' . $node3->id());

    // Set bundle 'index' setting to false.
    $this->generator
      ->setBundleSettings('node', 'page', ['index' => FALSE])
      ->setBundleSettings('node', 'blog', ['index' => FALSE])
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);

    $this->assertSession()->responseNotContains('node/' . $this->node->id());
    $this->assertSession()->responseNotContains('node/' . $node3->id());
  }

  /**
   * Test default settings of bundles.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSetBundleSettingsDefaults() {
    $this->generator->setBundleSettings('node', 'page')
      ->removeCustomLinks()
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('node/' . $this->node->id());
    $this->assertSession()->responseContains('0.5');
    $this->assertSession()->responseNotContains('changefreq');
  }

  /**
   * Test link count.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testLinkCount() {
    $this->generator->setBundleSettings('node', 'page')
      ->removeCustomLinks()
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalLogin($this->createUser(['administer sitemap settings']));
    $this->drupalGet('admin/config/search/simplesitemap');
    $link_count_elements = $this->xpath('//*[@id="simple-sitemap-sitemaps-form"]//table/tbody/tr/td[3]');
    $this->assertSame('2', $link_count_elements[0]->getText());

    $this->createNode(['title' => 'Another node', 'type' => 'page']);
    $this->generator->setBundleSettings('node', 'page')
      ->removeCustomLinks()
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);
    $this->drupalLogin($this->createUser(['administer sitemap settings']));
    $this->drupalGet('admin/config/search/simplesitemap');
    $link_count_elements = $this->xpath('//*[@id="simple-sitemap-sitemaps-form"]//table/tbody/tr/td[3]');
    $this->assertSame('3', $link_count_elements[0]->getText());

    // Pretend that we've just run the simple_sitemap_update_8305() update on a
    // site with existing sitemaps.
    \Drupal::database()->update('simple_sitemap')
      ->fields(['link_count' => 0])
      ->execute();
    $this->drupalGet('admin/config/search/simplesitemap');
    $link_count_elements = $this->xpath('//*[@id="simple-sitemap-sitemaps-form"]//table/tbody/tr/td[3]');
    $this->assertSame('unavailable', $link_count_elements[0]->getText());
  }

  /**
   * Test the lastmod parameter in different scenarios.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testLastmod() {
    // Entity links should have 'lastmod'.
    $this->generator->setBundleSettings('node', 'page')
      ->removeCustomLinks()
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('lastmod');

    // Entity custom links should have 'lastmod'.
    $this->generator->setBundleSettings('node', 'page', ['index' => FALSE])
      ->addCustomLink('/node/' . $this->node->id())
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('lastmod');

    // Non-entity custom links should not have 'lastmod'.
    $this->generator->removeCustomLinks()
      ->addCustomLink('/')
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseNotContains('lastmod');
  }

  /**
   * Tests the duplicate setting.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testRemoveDuplicatesSetting() {
    $this->generator->setBundleSettings('node', 'page')
      ->addCustomLink('/node/1')
      ->saveSetting('remove_duplicates', TRUE)
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertUniqueTextWorkaround('node/' . $this->node->id());

    $this->generator->saveSetting('remove_duplicates', FALSE)
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertNoUniqueTextWorkaround('node/' . $this->node->id());
  }

  /**
   * Test max links setting and the sitemap index.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testMaxLinksSetting() {
    $this->generator->setBundleSettings('node', 'page')
      ->saveSetting('max_links', 1)
      ->removeCustomLinks()
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('sitemap.xml?page=1');
    $this->assertSession()->responseContains('sitemap.xml?page=2');

    $this->drupalGet('sitemap.xml', ['query' => ['page' => 1]]);
    $this->assertSession()->responseContains('node/' . $this->node->id());
    $this->assertSession()->responseContains('0.5');
    $this->assertSession()->responseNotContains('node/' . $this->node2->id());

    $this->drupalGet('sitemap.xml', ['query' => ['page' => 2]]);
    $this->assertSession()->responseContains('node/' . $this->node2->id());
    $this->assertSession()->responseContains('0.5');
    $this->assertSession()->responseNotContains('node/' . $this->node->id());
  }

  /**
   * @todo testGenerateDurationSetting
   */

  /**
   * Test setting the base URL.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testBaseUrlSetting() {
    $this->generator->setBundleSettings('node', 'page')
      ->saveSetting('base_url', 'http://base_url_test')
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('http://base_url_test');

    // Set base URL in the sitemap index.
    $this->generator->saveSetting('max_links', 1)
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('http://base_url_test/sitemap.xml?page=1');
  }

  /**
   * Test overriding of bundle settings for a single entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Behat\Mink\Exception\ExpectationException
   *
   * @todo: Use form testing instead of responseContains().
   */
  public function testSetEntityInstanceSettings() {
    $this->generator->setBundleSettings('node', 'page')
      ->removeCustomLinks()
      ->setEntityInstanceSettings('node', $this->node->id(), ['priority' => 0.1, 'changefreq' => 'never'])
      ->setEntityInstanceSettings('node', $this->node2->id(), ['index' => FALSE])
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    // Test sitemap result.
    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('node/' . $this->node->id());
    $this->assertSession()->responseContains('0.1');
    $this->assertSession()->responseContains('never');
    $this->assertSession()->responseNotContains('node/' . $this->node2->id());
    $this->assertSession()->responseNotContains('0.5');

    $this->drupalLogin($this->privilegedUser);

    // Test UI changes.
    $this->drupalGet('node/' . $this->node->id() . '/edit');
    $this->assertSession()->responseContains('<option value="0.1" selected="selected">0.1</option>');
    $this->assertSession()->responseContains('<option value="never" selected="selected">never</option>');

    // Test database changes.
    $this->assertEquals(1, $this->getOverridesCount('node', $this->node->id()));

    $this->generator->setBundleSettings('node', 'page', ['priority' => 0.1, 'changefreq' => 'never'])
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    // Test sitemap result.
    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('node/' . $this->node->id());
    $this->assertSession()->responseContains('0.1');
    $this->assertSession()->responseContains('never');
    $this->assertSession()->responseNotContains('node/' . $this->node2->id());
    $this->assertSession()->responseNotContains('0.5');

    // Test UI changes.
    $this->drupalGet('node/' . $this->node->id() . '/edit');
    $this->assertSession()->responseContains('<option value="0.1" selected="selected">0.1 (default)</option>');
    $this->assertSession()->responseContains('<option value="never" selected="selected">never (default)</option>');

    // Test if entity override has been removed from database after its equal to
    // its bundle settings.
    $this->assertEquals(0, $this->getOverridesCount('node', $this->node->id()));

    // Assert that creating a new content type doesn't remove the overrides.
    $this->drupalGet('node/' . $this->node->id() . '/edit');
    $this->submitForm(['index_default_node_settings' => 0], 'Save');
    $this->assertEquals(1, $this->getOverridesCount('node', $this->node->id()));
    // Create a new content type.
    $this->drupalGet('admin/structure/types/add');
    $this->submitForm([
      'name' => 'simple_sitemap_type',
      'type' => 'simple_sitemap_type',
      'index_default_node_settings' => 0,
    ], 'Save content type');
    // The entity override from the other content type should not be affected.
    $this->assertEquals(1, $this->getOverridesCount('node', $this->node->id()));

    // Assert that removing the other content type doesn't remove the overrides.
    $this->drupalGet('admin/structure/types/manage/simple_sitemap_type/delete');
    $this->submitForm([], 'Delete');
    $this->assertEquals(1, $this->getOverridesCount('node', $this->node->id()));
  }

  /**
   * Returns the number of entity overrides for the given entity type/ID.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $entity_id
   *   The entity ID.
   *
   * @return int
   *   The number of overrides for the given entity type ID and entity ID.
   */
  protected function getOverridesCount($entity_type_id, $entity_id) {
    return $this->database->select('simple_sitemap_entity_overrides', 'o')
      ->fields('o', ['inclusion_settings'])
      ->condition('o.entity_type', $entity_type_id)
      ->condition('o.entity_id', $entity_id)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Tests that a page does not break if an entity has its id set.
   */
  public function testNewEntityWithIdSet() {
    $new_node = Node::create([
      'nid' => mt_rand(5, 10),
      'type' => 'page',
    ]);
    // Assert that the form does not break if an entity has an id but is not
    // saved.
    // @see https://www.drupal.org/project/simple_sitemap/issues/3079897
    \Drupal::service('entity.form_builder')->getForm($new_node);
  }

  /**
   * Test indexing an atomic entity (here: a user)
   */
  public function testAtomicEntityIndexation() {
    $user_id = $this->privilegedUser->id();
    $this->generator->setBundleSettings('user')
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseNotContains('user/' . $user_id);

    user_role_grant_permissions('anonymous', ['access user profiles']);
    drupal_flush_all_caches(); //todo Not pretty.

    $this->generator->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('user/' . $user_id);
  }

  /**
   * @todo Test indexing menu.
   */

  /**
   * @todo Test deleting a bundle.
   */

  /**
   * Test disabling sitemap support for an entity type.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testDisableEntityType() {
    $this->generator->setBundleSettings('node', 'page')
      ->disableEntityType('node');

    $this->drupalLogin($this->privilegedUser);
    $this->drupalGet('admin/structure/types/manage/page');
    $this->assertSession()->pageTextNotContains('Simple XML Sitemap');

    $this->generator->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseNotContains('node/' . $this->node->id());

    $this->assertFalse($this->generator->entityTypeIsEnabled('node'));
  }

  /**
   * Test enabling sitemap support for an entity type.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Behat\Mink\Exception\ExpectationException
   *
   * @todo Test admin/config/search/simplesitemap/entities form.
   */
  public function testEnableEntityType() {
    $this->generator->disableEntityType('node')
      ->enableEntityType('node')
      ->setBundleSettings('node', 'page');

    $this->drupalLogin($this->privilegedUser);
    $this->drupalGet('admin/structure/types/manage/page');
    $this->assertSession()->pageTextContains('Simple XML Sitemap');

    $this->generator->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('node/' . $this->node->id());

    $this->assertTrue($this->generator->entityTypeIsEnabled('node'));
  }

  /**
   * @todo testSitemapLanguages
   */

  /**
   * Test adding and removing sitemap variants.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testSitemapVariants() {

    // Test adding a variant.
    $this->generator->getSitemapManager()->addSitemapVariant('test');

    $this->generator
      ->setBundleSettings('node', 'page')
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    $variants = $this->generator->getSitemapManager()->getSitemapVariants();
    $this->assertArrayHasKey('test', $variants);

    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('node/' . $this->node->id());

    // Test if generation affected the default variant only.
    $this->drupalGet('test/sitemap.xml');
    $this->assertSession()->responseNotContains('node/' . $this->node->id());

    $this->generator
      ->setVariants('test')
      ->setBundleSettings('node', 'page')
      ->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);

    // Test if bundle settings have been set for correct variant.
    $this->drupalGet($this->defaultSitemapUrl);
    $this->assertSession()->responseContains('node/' . $this->node->id());

    $this->generator->getSitemapManager()->removeSitemapVariants('test');

    $variants = $this->generator->getSitemapManager()->getSitemapVariants();
    $this->assertArrayNotHasKey('test', $variants);

    // Test if sitemap has been removed along with the variant.
    $this->drupalGet('test/sitemap.xml');
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * @todo Test removeSitemap().
   */

  /**
   * Test cases for ::testGenerationResume.
   */
  public function generationResumeProvider() {
    return [
      [1000, 500, 1],
      [1000, 500, 3, ['de']],
      [1000, 500, 5, ['de', 'es']],
      [10, 10000, 10],
    ];
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @dataProvider generationResumeProvider
   */
  public function testGenerationResume($element_count, $generate_duration, $max_links, $langcodes = []) {

    $this->addLanguages($langcodes);

    $expected_sitemap_count = (int) ceil(($element_count * (count($langcodes) + 1)) / $max_links);

    $this->drupalCreateContentType(['type' => 'blog']);
    for ($i = 1; $i <= $element_count; $i++) {
      $this->createNode(['title' => 'node-' . $i, 'type' => 'blog']);
    }

    $this->generator
      ->removeCustomLinks()
      ->saveSetting('generate_duration', $generate_duration)
      ->saveSetting('max_links', $max_links)
      ->saveSetting('skip_untranslated', FALSE)
      ->setBundleSettings('node', 'blog');

    $queue = $this->generator->getQueueWorker()->rebuildQueue();
    $generate_count = 0;
    while ($queue->generationInProgress()) {
      $generate_count++;
      $this->generator->generateSitemap(QueueWorker::GENERATE_TYPE_BACKEND);
    }

    // Test if sitemap generation has been resumed when time limit is very low.
    $this->assertTrue($generate_duration > $element_count || $generate_count > 1, 'This assertion tests if the sitemap generation is split up into batches due to a low generation time limit setting. The failing of this assertion can mean that the sitemap was wrongfully generated in one go, but it can also mean that the assumed low time setting is still high enough for a one pass generation.');

    // Test if correct number of sitemaps have been created.
    $chunks = $this->database->query('SELECT id FROM {simple_sitemap} WHERE delta != 0 AND status = 1');
    $chunks->allowRowCount = TRUE;
    $chunk_count = $chunks->rowCount();
    $this->assertSame($chunk_count, $expected_sitemap_count);

    // Test if index has been created when necessary.
    $index = $this->database->query('SELECT id FROM {simple_sitemap} WHERE delta = 0 AND status = 1')
      ->fetchField();
    $this->assertTrue($chunk_count > 1 ? (FALSE !== $index) : !$index);
  }

  /**
   * Test the removal of hreflang tags in HTML.
   */
  public function testHrefLangRemoval() {
    // Test the nodes markup contains hreflang with default settings.
    $this->generator->saveSetting('disable_language_hreflang', FALSE);
    $this->drupalGet('node/' . $this->node->id());
    $this->assertNotEmpty($this->xpath("//link[@hreflang]"));

    Cache::invalidateTags($this->node->getCacheTags());

    // Test the hreflang markup gets removed.
    $this->generator->saveSetting('disable_language_hreflang', TRUE);
    $this->drupalGet('node/' . $this->node->id());
    $this->assertEmpty($this->xpath("//link[@hreflang]"));
  }

}

