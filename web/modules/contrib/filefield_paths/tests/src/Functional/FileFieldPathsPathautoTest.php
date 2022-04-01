<?php

namespace Drupal\Tests\filefield_paths\Functional;

use Drupal\Component\Utility\Unicode;

/**
 * Test pathauto integration.
 *
 * @group File (Field) Paths
 */
class FileFieldPathsPathautoTest extends FileFieldPathsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'filefield_paths_test',
    'file_test',
    'image',
    'pathauto',
    'token',
  ];

  /**
   * Test File (Field) Paths Pathauto UI.
   */
  public function testUi() {
    // Create a File field.
    $field_name = mb_strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'node', $this->contentType);

    // Ensure File (Field) Paths Pathauto settings are present and available.
    $this->drupalGet("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$field_name}");
    $session = $this->assertSession();
    foreach (['path', 'name'] as $field) {
      // Pathauto checkbox is present in File settings.
      $session->fieldExists("third_party_settings[filefield_paths][file_{$field}][options][pathauto]");

      $element = $this->xpath('//input[@name=:name]/@disabled', [':name' => "third_party_settings[filefield_paths][file_{$field}][options][pathauto]"]);
      $this->assertEmpty($element, 'Pathauto checkbox is not disabled in File ' . Unicode::ucfirst($field) . ' settings.');
    }
  }

  /**
   * Test Pathauto cleanup in File (Field) Paths.
   */
  public function testPathauto() {
    // Create a File field.
    $field_name = mb_strtolower($this->randomMachineName());

    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[node:title]';
    $third_party_settings['filefield_paths']['file_path']['options']['pathauto'] = TRUE;
    $third_party_settings['filefield_paths']['file_name']['value'] = '[node:title].[file:ffp-extension-original]';
    $third_party_settings['filefield_paths']['file_name']['options']['pathauto'] = TRUE;

    $this->createFileField($field_name, 'node', $this->contentType, [], [], $third_party_settings);

    // Create a node with a test file.
    /** @var \Drupal\file\Entity\File $test_file */
    $test_file = $this->getTestFile('text');
    $node_title = $this->randomString() . ' ' . $this->randomString();
    $edit['title[0][value]'] = $node_title;

    $edit['files[' . $field_name . '_0]'] = \Drupal::service('file_system')
      ->realpath($test_file->getFileUri());
    $this->drupalGet("node/add/{$this->contentType}");
    $this->submitForm($edit, 'Save');

    // Ensure that file path/name have been processed correctly by Pathauto.
    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = \Drupal::service('entity_type.manager')->getStorage('node')
      ->loadByProperties(['title' => $node_title]);
    $node = reset($nodes);

    $parts = explode('/', $node->getTitle());
    foreach ($parts as &$part) {
      $part = \Drupal::service('pathauto.alias_cleaner')->cleanString($part);
    }
    $title = implode('/', $parts);

    $this->assertSame("public://node/{$title}/{$title}.txt", $node->{$field_name}[0]->entity->getFileUri(), 'File path/name has been processed correctly by Pathauto');
  }

}
