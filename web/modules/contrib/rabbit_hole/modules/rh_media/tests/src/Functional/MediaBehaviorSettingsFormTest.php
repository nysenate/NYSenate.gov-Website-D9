<?php

namespace Drupal\Tests\rh_media\Functional;

use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\rabbit_hole\Functional\RabbitHoleBehaviorSettingsFormTestBase;

/**
 * Test the functionality of the rabbit hole form additions to the media.
 *
 * @group rh_media
 */
class MediaBehaviorSettingsFormTest extends RabbitHoleBehaviorSettingsFormTestBase {

  use MediaTypeCreationTrait;

  /**
   * Test media type.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $bundle;

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'media';

  /**
   * {@inheritdoc}
   */
  protected $bundleEntityTypeName = 'media_type';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rh_media', 'media', 'media_test_source'];

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundle() {
    // TODO: Remove 2nd parameter once https://www.drupal.org/node/3174874 is
    // resolved.
    $this->bundle = $this->createMediaType('test', [
      'id' => mb_strtolower($this->randomMachineName()),
    ]);
    return $this->bundle->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundleFormSubmit($action, $override) {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/structure/media/add');

    $this->assertRabbitHoleSettings();
    $machine_name = mb_strtolower($this->randomMachineName());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('label')->setValue($this->randomString());
    $this->assertSession()->fieldExists('id')->setValue($machine_name);
    $this->assertSession()->selectExists('source')->selectOption('test');
    $this->assertSession()->fieldExists('rh_action')->setValue($action);
    $this->assertSession()->fieldExists('rh_override')->setValue($override);
    $this->assertSession()->buttonExists('Save')->press();
    // To actually save the bundle we need to hit save again.
    $this->assertSession()->buttonExists('Save')->press();
    $this->bundle = $this->loadBundle($machine_name);
    return $machine_name;
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($action = NULL) {
    $values = [
      'bundle' => $this->bundle->id(),
      'name' => $this->randomString(),
      'field_media_test' => $this->randomMachineName(),
    ];
    if (isset($action)) {
      $values['rh_action'] = $action;
    }

    $media = Media::create($values);
    $media->save();

    return $media->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function getCreateEntityUrl() {
    return Url::fromRoute('entity.media.add_form', ['media_type' => $this->bundle->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditEntityUrl($id) {
    return Url::fromRoute('entity.media.edit_form', ['media' => $id]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditBundleUrl($bundle) {
    return Url::fromRoute('entity.media_type.edit_form', ['media_type' => $bundle]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdminPermissions() {
    return [
      'access media overview',
      'administer media',
      'administer media types',
      'view media',
      'rabbit hole bypass media',
    ];
  }

}
