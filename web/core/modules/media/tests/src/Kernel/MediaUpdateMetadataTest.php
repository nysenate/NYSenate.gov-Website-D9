<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\media\Plugin\media\Source\File;

/**
 * Tests the update metadata method on Media entities.
 *
 * @group media
 */
class MediaUpdateMetadataTest extends MediaKernelTestBase {

  /**
   * Tests the update metadata operation.
   */
  public function testUpdateMetadata() {
    $media_type = $this->createMediaType('file');
    $source_plugin = $media_type->getSource();

    // Initially the "name" metadata is the filename.
    $media = $this->generateMedia('foo.txt', $media_type);
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

    // Update metadata, we should now pick the new name.
    $media->enforceMetadataUpdate()->save();

    $name_metadata = $source_plugin->getMetadata($media, File::METADATA_ATTRIBUTE_NAME);
    $this->assertSame('bar.txt', $name_metadata);
  }

}
