<?php

namespace Drupal\Tests\rh_taxonomy\Functional;

use Drupal\Core\Url;
use Drupal\rabbit_hole\Entity\BehaviorSettings;
use Drupal\Tests\rabbit_hole\Functional\RabbitHoleBehaviorSettingsFormTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Test the functionality of the rabbit hole form additions to the taxonomy.
 *
 * @group rh_taxonomy
 */
class TaxonomyBehaviorSettingsFormTest extends RabbitHoleBehaviorSettingsFormTestBase {

  use TaxonomyTestTrait;

  /**
   * Test taxonomy vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $bundle;

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'taxonomy_term';

  /**
   * {@inheritdoc}
   */
  protected $bundleEntityTypeName = 'taxonomy_vocabulary';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rh_taxonomy', 'taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundle() {
    $this->bundle = $this->createVocabulary();
    return $this->bundle->id();
  }

  /**
   * Test redirect after entity form save.
   */
  public function testEntityFormSaveRedirect() {
    $override = BehaviorSettings::OVERRIDE_DISALLOW;
    $action = 'access_denied';
    $this->createEntityBundleFormSubmit($action, $override);
    $this->loadCreateEntityForm();
    $this->assertNoRabbitHoleSettings();
    // Workaround for taxonomy terms: https://www.drupal.org/i/3277238.
    $this->submitForm(['name[0][value]' => $this->randomString()], $this->getEntityFormSubmit());

    // Make sure the editor didn't hit error page after the form save in case
    // there is no Rabbit Hole actions available.
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundleFormSubmit($action, $override) {
    $this->drupalLogin($this->adminUser);
    $edit = [
      'name' => $this->randomString(),
      'vid' => mb_strtolower($this->randomMachineName()),
      'rh_action' => $action,
      'rh_override' => $override,
    ];
    $this->drupalGet('/admin/structure/taxonomy/add');
    $this->assertRabbitHoleSettings();
    $this->submitForm($edit, 'Save');
    $this->bundle = $this->loadBundle($edit['vid']);
    return $edit['vid'];
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($action = NULL) {
    $values = [];
    if (isset($action)) {
      $values['rh_action'] = $action;
    }
    return $this->createTerm($this->bundle, $values)->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function getCreateEntityUrl() {
    return Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $this->bundle->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditEntityUrl($id) {
    return Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $id]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditBundleUrl($bundle) {
    return Url::fromRoute('entity.taxonomy_vocabulary.edit_form', ['taxonomy_vocabulary' => $bundle]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdminPermissions() {
    return ['administer taxonomy', 'access taxonomy overview'];
  }

}
