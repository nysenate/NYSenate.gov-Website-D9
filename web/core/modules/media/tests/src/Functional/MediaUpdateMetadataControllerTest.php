<?php

namespace Drupal\Tests\media\Functional;

use Drupal\file\Entity\File as FileEntity;
use Drupal\media\Entity\Media;
use Drupal\media\Plugin\media\Source\File;

/**
 * Tests the media "Update Metadata" controller.
 *
 * @group media
 */
class MediaUpdateMetadataControllerTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test that by going to the update metadata page, we update the metadata.
   */
  public function testUpdateMetadataController() {
    $assert_session = $this->assertSession();

    $media_type = $this->createMediaType('file');
    $source_plugin = $media_type->getSource();

    // Initially the "name" metadata is the filename.
    $file = FileEntity::create([
      'uri' => 'public://foo.txt',
      'uid' => 1,
    ]);
    $file->setPermanent();
    $file->save();

    /** @var \Drupal\media\MediaInterface $media */
    $media = Media::create([
      'bundle' => $media_type->id(),
      'uid' => 1,
      'field_media_file' => [
        'target_id' => $file->id(),
      ],
    ]);
    $media->save();

    $name_metadata = $source_plugin->getMetadata($media, File::METADATA_ATTRIBUTE_NAME);
    $this->assertSame('foo.txt', $name_metadata);

    // Rename the file, simulating a remote metadata change.
    $file_id = $source_plugin->getSourceFieldValue($media);
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->container->get('entity_type.manager')
      ->getStorage('file')
      ->load($file_id);
    $file->setFilename('bar.txt');
    $file->save();

    // Go to the update metadata page and verify the controller triggered an
    // update.
    $media_id = $media->id();
    $site_base_path = base_path();

    $this->drupalGet("/admin/content/media");
    $this->assertSession()->elementExists(
      'xpath',
      "//ul[contains(@class, dropbutton)]/li/a[starts-with(@href, '{$site_base_path}media/{$media_id}/update-metadata')]"
    )->click();

    $assert_session->statusCodeEquals(200);

    $name_metadata = $source_plugin->getMetadata($media, File::METADATA_ATTRIBUTE_NAME);
    $this->assertSame('bar.txt', $name_metadata);
  }

}
