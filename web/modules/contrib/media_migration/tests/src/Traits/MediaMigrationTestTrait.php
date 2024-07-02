<?php

namespace Drupal\Tests\media_migration\Traits;

use Drupal\field\FieldConfigInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaSourceInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Trait for media migration tests.
 */
trait MediaMigrationTestTrait {

  use MediaTypeCreationTrait;

  /**
   * Returns the path to the Drupal 7 migration database fixture.
   *
   * @return string
   *   Path to the database fixture file.
   */
  protected function getFixtureFilePath() {
    return \Drupal::service('extension.list.module')->getPath('media_migration') . '/tests/fixtures/drupal7_media.php';
  }

  /**
   * Creates the media types which are shipped with the core standard profile.
   *
   * @param bool $only_allow_default_extensions
   *   Whether only the default file extension settings should be used. If this
   *   is set to TRUE, then only the source plugin's corresponding field's
   *   default extension list will be used when creating the media source
   *   fields. If this is FALSE, then we will use the settings of Standard
   *   profile's optional media configurations. Defaults to FALSE.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createStandardMediaTypes(bool $only_allow_default_extensions = FALSE) {
    $this->createMediaType('image', ['id' => 'image']);

    // We want to test with the same document media type that is shipped with
    // the standard profile, so this special type needs a special treatment:
    // MediaTypeCreationTrait::createMediaType would create a source field
    // "field_media_file", but we need "field_media_document".
    $media_type = MediaType::create([
      'id' => 'document',
      'label' => 'Document',
      'source' => 'file',
    ]);
    assert($media_type instanceof MediaTypeInterface);
    $source = $media_type->getSource();
    $source_field = $source->createSourceField($media_type);
    $source_field->getFieldStorageDefinition()->set('field_name', 'field_media_document');
    $source_field->set('field_name', 'field_media_document');
    $source_field->set('label', 'Document');
    if (!$only_allow_default_extensions) {
      $source_field->setSetting('file_extensions', 'txt rtf doc docx ppt pptx xls xlsx pdf odf odg odp ods odt fodt fods fodp fodg key numbers pages');
    }
    $source_configuration = $source->getConfiguration();
    $source_configuration['source_field'] = $source_field->getName();
    $source->setConfiguration($source_configuration);

    $this->assertSame(SAVED_NEW, $media_type->save());

    // The media type form creates a source field if it does not exist yet. The
    // same must be done in a kernel test, since it does not use that form.
    // @see \Drupal\media\MediaTypeForm::save()
    $source_field->getFieldStorageDefinition()->save();
    // The source field storage has been created, now the field can be saved.
    $source_field->save();

    // Add the source field to the form display for the media type.
    $form_display = \Drupal::service('entity_display.repository')->getFormDisplay('media', $media_type->id(), 'default');
    $source->prepareFormDisplay($media_type, $form_display);
    $form_display->save();

    // We will need audio, video and remote video bundles later.
    $this->createMediaType('video_file', ['id' => 'video']);
    $this->createMediaType('audio_file', ['id' => 'audio']);
    $this->createMediaType('oembed:video', ['id' => 'remote_video']);

    // Add 'media' module as enforced dependency to the source field instances.
    // The source fields of "image" and "document" media types have an enforced
    // dependency on the Media module.
    foreach (['image', 'document'] as $media_type_id) {
      $media_type = MediaType::load($media_type_id);
      $source = $media_type->getSource();
      assert($source instanceof MediaSourceInterface);
      $source_field_name = $source->getConfiguration()['source_field'];
      $source_field_id = implode('.', [
        'media',
        $media_type_id,
        $source_field_name,
      ]);
      $source_field = $this->container->get('entity_type.manager')->getStorage('field_config')->load($source_field_id);
      assert($source_field instanceof FieldConfigInterface);

      $dependencies = $source_field->getDependencies() + [
        'enforced' => ['module' => ['media']],
      ];
      $source_field->set('dependencies', $dependencies)->save();
    }
  }

  /**
   * Get the major and minor version of Drupal i.e. 10.1
   *
   * @return string
   *   The major and minor version of Drupal.
   */
  protected function coreMajorMinorVersion(): string {
    return implode(
      '.',
      [
        explode('.', \Drupal::VERSION)[0],
        explode('.', \Drupal::VERSION)[1],
      ]
    );
  }

}
