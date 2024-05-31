<?php

namespace Drupal\Tests\rabbit_hole\Functional;

use Drupal\rabbit_hole\BehaviorSettingsManager;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Test the RabbitHoleSettingsForm form functionality.
 *
 * @group rabbit_hole
 *
 * @coversDefaultClass \Drupal\rabbit_hole\Form\RabbitHoleSettingsForm
 */
class RabbitHoleSettingsFormTest extends BrowserTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rabbit_hole'];

  /**
   * Tests basic interactions with Rabbit Hole settings page.
   */
  public function testRabbitHoleSettingsForm() {
    $admin_user = $this->createUser(['administer rabbit_hole settings']);
    $this->drupalLogin($admin_user);

    // Make sure the page do not display entity types before they're available.
    $this->drupalGet('admin/config/content/rabbit-hole');
    $this->assertSession()->fieldNotExists('entity_types[node]');
    $this->assertSession()->fieldNotExists('entity_types[media]');

    \Drupal::service('module_installer')
      ->install(['user', 'node', 'taxonomy']);

    $this->drupalGet('admin/config/content/rabbit-hole');
    $this->assertSession()->fieldExists('entity_types[user]');
    $this->assertSession()->fieldExists('entity_types[node]');
    $this->assertSession()->fieldExists('entity_types[taxonomy_term]');
    // Make sure unsupported entity types are not available.
    $this->assertSession()->fieldNotExists('entity_types[media]');

    // Verify form submit.
    $this->submitForm([
      'entity_types[user]' => TRUE,
      'entity_types[taxonomy_term]' => TRUE,
    ], 'Save configuration');
    $this->assertSession()->checkboxChecked('entity_types[taxonomy_term]');
    $this->assertSession()->checkboxChecked('entity_types[user]');
    $this->assertSession()->checkboxNotChecked('entity_types[node]');

    // Check operations.
    $this->assertSession()->elementExists('css', 'tr[data-drupal-selector="edit-table-user"] a');
    $this->assertSession()->elementNotExists('css', 'tr[data-drupal-selector="edit-table-node"] a');

    // Check entity type settings page.
    $this->click('tr[data-drupal-selector="edit-table-user"] a');
    $this->assertSession()->fieldExists('bundles[user][action]');
    $this->assertSession()->fieldExists('bundles[user][allow_override]');

    // Check entity type settings page without bundles.
    $this->drupalGet('admin/config/content/rabbit-hole');
    $this->click('tr[data-drupal-selector="edit-table-taxonomy-term"] a');
    $this->assertSession()->pageTextContains('No bundles available.');
    $this->submitForm([], 'Save configuration');

    // Add a couple of taxonomy vocabularies.
    $voc1 = $this->createVocabulary();
    $voc2 = $this->createVocabulary();

    // Make sure vocabularies do not have 'Rabbit hole' field because override
    // options is disabled by default.
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('user', 'user');
    $this->assertArrayNotHasKey(BehaviorSettingsManager::FIELD_NAME, $field_definitions);
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('taxonomy_term', $voc1->id());
    $this->assertArrayNotHasKey(BehaviorSettingsManager::FIELD_NAME, $field_definitions);

    // Make sure that default behavior is applied.
    $this->drupalGet('admin/config/content/rabbit-hole');
    $this->assertSession()->responseContains('<em class="placeholder">' . $voc1->label() . '</em> (Behavior: <em class="placeholder">Display the page</em>; Allow overrides: <em class="placeholder">No</em>)');
    $this->assertSession()->responseContains('<em class="placeholder">' . $voc2->label() . '</em> (Behavior: <em class="placeholder">Display the page</em>; Allow overrides: <em class="placeholder">No</em>)');

    $this->click('tr[data-drupal-selector="edit-table-taxonomy-term"] a');
    $this->submitForm([
      'bundles[' . $voc1->id() . '][action]' => 'page_not_found',
      'bundles[' . $voc1->id() . '][allow_override]' => TRUE,
    ], 'Save configuration');
    $this->assertSession()->responseContains('<em class="placeholder">' . $voc1->label() . '</em> (Behavior: <em class="placeholder">Page not found</em>; Allow overrides: <em class="placeholder">Yes</em>)');
    $this->assertSession()->responseContains('<em class="placeholder">' . $voc2->label() . '</em> (Behavior: <em class="placeholder">Display the page</em>; Allow overrides: <em class="placeholder">No</em>)');

    // When override option was enabled the Rabbit hole field must exist.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('taxonomy_term', $voc1->id());
    $this->assertArrayHasKey(BehaviorSettingsManager::FIELD_NAME, $field_definitions);

    // Disable the entity type and make sure that bundle configuration items
    // were removed as well.
    $this->drupalGet('admin/config/content/rabbit-hole');
    // Confirm that taxonomy bundle settings exist.
    $bundle_settings = \Drupal::entityTypeManager()
      ->getStorage('behavior_settings')
      ->loadByProperties([
        'targetEntityType' => 'taxonomy_term',
      ]);
    $this->assertNotEmpty($bundle_settings);
    $this->submitForm([
      'entity_types[taxonomy_term]' => FALSE,
    ], 'Save configuration');
    // Make sure they were deleted.
    $bundle_settings = \Drupal::entityTypeManager()
      ->getStorage('behavior_settings')
      ->loadByProperties([
        'targetEntityType' => 'taxonomy_term',
      ]);
    $this->assertEmpty($bundle_settings);

    // When entity type was disabled the Rabbit hole fields must be removed
    // from all bundles.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('taxonomy_term', $voc1->id());
    $this->assertArrayNotHasKey(BehaviorSettingsManager::FIELD_NAME, $field_definitions);
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('taxonomy_term', $voc2->id());
    $this->assertArrayNotHasKey(BehaviorSettingsManager::FIELD_NAME, $field_definitions);
  }

}
