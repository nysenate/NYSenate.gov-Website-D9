<?php

namespace Drupal\Tests\filefield_paths\Functional;

/**
 * Test update functionality.
 *
 * @group File (Field) Paths
 */
class FileFieldPathsUpdateTest extends FileFieldPathsTestBase {

  /**
   * Test behaviour of Retroactive updates when no updates are needed.
   */
  public function testRetroEmpty() {
    // Create a File field.
    $field_name = mb_strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'node', $this->contentType);

    // Trigger retroactive updates.
    $edit = [
      'third_party_settings[filefield_paths][retroactive_update]' => TRUE,
    ];
    $this->drupalGet("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$field_name}");
    $this->submitForm($edit, 'Save settings');

    // Ensure that no errors are thrown.
    // No errors were found.
    $this->assertSession()->pageTextNotContains('The website encountered an unexpected error.');
    $this->assertSession()->pageTextContains("Saved {$field_name} configuration.");
  }

  /**
   * Test basic Retroactive updates functionality.
   */
  public function testRetroBasic() {
    // Create an Image field.
    $field_name = mb_strtolower($this->randomMachineName());
    $this->createImageField($field_name, $this->contentType, []);

    // Modify display settings.
    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $display */
    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load("node.{$this->contentType}.default");
    $display->setComponent($field_name, [
      'settings' => [
        'image_style' => 'thumbnail',
        'image_link'  => 'content',
      ],
    ])->save();

    $this->drupalGet("admin/structure/types/manage/{$this->contentType}/display");
    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $original_display */
    $original_display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load("node.{$this->contentType}.default");

    // Create a node with a test file.
    /** @var \Drupal\file\Entity\File $test_file */
    $test_file = $this->getTestFile('image');
    $nid = $this->uploadNodeFile($test_file, $field_name, $this->contentType);
    $this->submitForm(["{$field_name}[0][alt]" => $this->randomString()], 'Save');

    // Ensure that the file is in the default path.
    $this->drupalGet("node/{$nid}");
    $date = date('Y-m');
    // The File is in the default path.
    $this->assertSession()->responseContains("{$this->publicFilesDirectory}/styles/thumbnail/public/{$date}/{$test_file->getFilename()}");

    // Trigger retroactive updates.
    $this->drupalGet("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$field_name}");
    $edit['third_party_settings[filefield_paths][retroactive_update]'] = TRUE;
    $edit['third_party_settings[filefield_paths][file_path][value]'] = 'node/[node:nid]';
    $this->submitForm($edit, 'Save settings');

    // Ensure display settings haven't changed.
    // @see https://www.drupal.org/node/2276435
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load("node.{$this->contentType}.default");
    $this->assertSame($original_display->getComponent($field_name), $display->getComponent($field_name), 'Display settings have not changed.');

    // Ensure that the file path has been retroactively updated.
    $this->drupalGet("node/{$nid}");
    // The File path has been retroactively updated.
    $this->assertSession()->responseContains("{$this->publicFilesDirectory}/styles/thumbnail/public/node/{$nid}/{$test_file->getFilename()}");
  }

}
