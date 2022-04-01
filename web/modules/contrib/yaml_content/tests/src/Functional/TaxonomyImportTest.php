<?php

namespace Drupal\Tests\yaml_content\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests general Node creation functionality.
 *
 * @group yaml_content
 */
class TaxonomyImportTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Directory where test files are to be created.
   *
   * @var \org\bovigo\vfs\vfsStreamContent $contentDirectory
   */
  protected $contentDirectory;

  /**
   * Prepared Content Loader service for testing.
   *
   * @var \Drupal\yaml_content\ContentLoader\ContentLoader $contentLoader
   */
  protected $contentLoader;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    // Core dependencies.
    'taxonomy',

    // This module.
    'yaml_content',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Prepare the content loader.
    $this->contentLoader = \Drupal::service('yaml_content.content_loader');
    // Look for content files in the tests directory.
    $this->contentLoader->setContentPath(drupal_get_path('module', 'yaml_content') . '/tests');
  }

  /**
   * Create a basic node.
   */
  public function testCanCreateNode() {
    /** @var \Drupal\Core\Entity\Entity[] $entities */
    $entities = $this->contentLoader->loadContent('basic_taxonomy.content.yml');

    $this->assertTrue(is_array($entities), 'An array was not returned from loadContent().');
    // 3 should have been created but references don't get returned.
    $this->assertEquals(2, count($entities), 'No entity IDs were returned from loadContent().');

    $this->assertTaxonomy($entities[0], 'Generated content', 'tags');
    $this->assertEquals(['target_id' => 0], $entities[0]->parent->get(0)->getValue(), 'Unspecified parent should be root. (0)');

    $this->assertTaxonomy($entities[1], 'Imported demo content', 'tags');
    // Parent will be created first as a dependency so it will have an id one less.
    $this->assertEquals(['target_id' => $entities[1]->id() - 1], $entities[1]->parent->get(0)->getValue(), 'Specified parent reference should be populated with a id.');
  }

  /**
   * Assert that a given entity is a taxonomy term.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object being checked.
   * @param string $title
   *   The expected label of the entity argument.
   * @param int $vid
   *   The expected vocabulary ID of the entity argument.
   */
  protected function assertTaxonomy(EntityInterface $entity, $title, $vid) {
    $this->assertEquals('taxonomy_term', $entity->getEntityTypeId(), 'Entity type should be taxonomy_term');
    $this->assertEquals(['target_id' => $vid], $entity->get('vid')->get(0)->getValue(), 'Vocabulary id is populated');
    $this->assertEquals($title, $entity->label(), 'Term name is populated.');
  }

}
