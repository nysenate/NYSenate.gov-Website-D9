<?php

namespace Drupal\Tests\filefield_paths\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\node\Entity\Node;

/**
 * Test general functionality.
 *
 * @group File (Field) Paths
 */
class FileFieldPathsGeneralTest extends FileFieldPathsTestBase {

  /**
   * Test that the File (Field) Paths UI works as expected.
   */
  public function testAddField() {
    $session = $this->assertSession();
    // Create a File field.
    $field_name = mb_strtolower($this->randomMachineName());
    $field_settings = ['file_directory' => "fields/{$field_name}"];
    $this->createFileField($field_name, 'node', $this->contentType, [], $field_settings);

    // Ensure File (Field) Paths settings are present.
    $this->drupalGet("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$field_name}");
    // File (Field) Path settings are present.
    $session->responseContains('Enable File (Field) Paths?');

    // Ensure that 'Enable File (Field) Paths?' is a direct sibling of
    // 'File (Field) Path settings'.
    /* @var \Behat\Mink\Element\NodeElement[] $element */
    $element = $this->xpath('//div[contains(@class, :class)]/following-sibling::*[1][@id=\'edit-third-party-settings-filefield-paths--2\']', [':class' => 'form-item-third-party-settings-filefield-paths-enabled']);
    $this->assertNotEmpty($element, 'Enable checkbox is next to settings fieldset.');

    // Ensure that the File path used the File directory as it's default value.
    $session->fieldValueEquals('third_party_settings[filefield_paths][file_path][value]', "fields/{$field_name}");
  }

  /**
   * Test File (Field) Paths works as normal when no file uploaded.
   *
   * This test is simply to prove that there are no exceptions/errors when
   * submitting a form with no File (Field) Paths affected files attached.
   */
  public function testNoFile() {
    // Create a File field.
    $field_name = mb_strtolower($this->randomMachineName());
    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[node:nid]';
    $third_party_settings['filefield_paths']['file_name']['value'] = '[node:nid].[file:ffp-extension-original]';
    $this->createFileField($field_name, 'node', $this->contentType, [], [], $third_party_settings);

    // Create a node without a file attached.
    $this->drupalGet('node/add/' . $this->contentType);
    $this->submitForm(
      ['title[0][value]' => $this->randomMachineName(8)],
      'Save'
    );
  }

  /**
   * Test a basic file upload with File (Field) Paths.
   */
  public function testUploadFile() {
    $session = $this->assertSession();
    $file_system = \Drupal::service('file_system');

    // Create a File field with 'node/[node:nid]' as the File path and
    // '[node:nid].[file:ffp-extension-original]' as the File name.
    $field_name = mb_strtolower($this->randomMachineName());
    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[node:nid]';
    $third_party_settings['filefield_paths']['file_name']['value'] = '[node:nid].[file:ffp-extension-original]';
    $this->createFileField($field_name, 'node', $this->contentType, [], [], $third_party_settings);

    // Create a node with a test file.
    /** @var \Drupal\file\Entity\File $test_file */
    $test_file = $this->getTestFile('text');
    $this->drupalGet("node/add/{$this->contentType}");
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit["files[{$field_name}_0]"] = $file_system->realpath($test_file->getFileUri());
    $this->submitForm($edit, 'Upload');

    // Ensure that the file was put into the Temporary file location.
    $config = $this->config('filefield_paths.settings');
    $session->responseContains(\Drupal::service('file_url_generator')->generateString("{$config->get('temp_location')}/{$test_file->getFilename()}"), 'File has been uploaded to the temporary file location.');

    // Save the node.
    $this->submitForm([], 'Save');

    // Get created Node ID.
    $matches = [];
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    $nid = $matches[1];

    // Ensure that the File path has been processed correctly.
    $session->responseContains("{$this->publicFilesDirectory}/node/{$nid}/{$nid}.txt", 'The File path has been processed correctly.');
  }

  /**
   * Tests a multivalue file upload with File (Field) Paths.
   */
  public function testUploadFileMultivalue() {
    $file_system = \Drupal::service('file_system');

    // Create a multivalue File field with 'node/[node:nid]' as the File path
    // and '[file:fid].txt' as the File name.
    $field_name = mb_strtolower($this->randomMachineName());
    $storage_settings['cardinality'] = FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[node:nid]';
    $third_party_settings['filefield_paths']['file_name']['value'] = '[file:fid].txt';
    $this->createFileField($field_name, 'node', $this->contentType, $storage_settings, [], $third_party_settings);

    // Create a node with three (3) test files.
    $text_files = $this->drupalGetTestFiles('text');
    $this->drupalGet("node/add/{$this->contentType}");
    $this->submitForm(["files[{$field_name}_0][]" => $file_system->realpath($text_files[0]->uri)], 'Upload');
    $this->submitForm(["files[{$field_name}_1][]" => $file_system->realpath($text_files[1]->uri)], 'Upload');
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      "files[{$field_name}_2][]" => $file_system->realpath($text_files[1]->uri),
    ];
    $this->submitForm($edit, 'Save');

    // Get created Node ID.
    $matches = [];
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    $nid = $matches[1];

    $session = $this->assertSession();
    // Ensure that the File path has been processed correctly.
    $session->responseContains("{$this->publicFilesDirectory}/node/{$nid}/1.txt", 'The first File path has been processed correctly.');
    $session->responseContains("{$this->publicFilesDirectory}/node/{$nid}/2.txt", 'The second File path has been processed correctly.');
    $session->responseContains("{$this->publicFilesDirectory}/node/{$nid}/3.txt", 'The third File path has been processed correctly.');
  }

  /**
   * Test File (Field) Paths with a very long path.
   */
  public function testLongPath() {
    // Create a File field with 'node/[random:hash:sha256]' as the File path.
    $field_name = mb_strtolower($this->randomMachineName());
    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[random:hash:sha512]/[random:hash:sha512]';
    $this->createFileField($field_name, 'node', $this->contentType, [], [], $third_party_settings);

    // Create a node with a test file.
    /** @var \Drupal\file\Entity\File $test_file */
    $test_file = $this->getTestFile('text');
    $nid = $this->uploadNodeFile($test_file, $field_name, $this->contentType);

    // Ensure file path is no more than 255 characters.
    $node = Node::load($nid);
    $this->assertLessThanOrEqual(255, mb_strlen($node->{$field_name}->uri), 'File path is no more than 255 characters');
  }

  /**
   * Test File (Field) Paths on a programmatically added file.
   */
  public function testProgrammaticAttach() {
    // Create a File field with 'node/[node:nid]' as the File path and
    // '[node:nid].[file:ffp-extension-original]' as the File name.
    $field_name = mb_strtolower($this->randomMachineName());
    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[node:nid]';
    $third_party_settings['filefield_paths']['file_name']['value'] = '[node:nid].[file:ffp-extension-original]';
    $this->createFileField($field_name, 'node', $this->contentType, [], [], $third_party_settings);

    // Create a node without an attached file.
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->drupalCreateNode(['type' => $this->contentType]);

    // Create a file object.
    /** @var \Drupal\file\Entity\File $test_file */
    $test_file = $this->getTestFile('text');
    $test_file->setPermanent();
    $test_file->save();

    // Attach the file to the node.
    $node->{$field_name}->setValue([
      'target_id' => $test_file->id(),
    ]);
    $node->save();

    // Ensure that the File path has been processed correctly.
    $node = Node::load($node->id());
    $this->assertSame("public://node/{$node->id()}/{$node->id()}.txt", $node->{$field_name}[0]->entity->getFileUri(), 'The File path has been processed correctly.');
  }

  /**
   * Test File (Field) Paths slashes cleanup functionality.
   */
  public function testSlashes() {
    $file_system = \Drupal::service('file_system');
    $etm = \Drupal::entityTypeManager();

    // Create a File field with 'node/[node:title]' as the File path and
    // '[node:title].[file:ffp-extension-original]' as the File name.
    $field_name = mb_strtolower($this->randomMachineName());
    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[node:title]';
    $third_party_settings['filefield_paths']['file_name']['value'] = '[node:title].[file:ffp-extension-original]';
    $this->createFileField($field_name, 'node', $this->contentType, [], [], $third_party_settings);

    // Create a node with a test file.
    /** @var \Drupal\file\Entity\File $test_file */
    $test_file = $this->getTestFile('text');

    $title = "{$this->randomMachineName()}/{$this->randomMachineName()}";
    $edit['title[0][value]'] = $title;
    $edit["body[0][value]"] = '';
    $edit["files[{$field_name}_0]"] = $file_system->realpath($test_file->getFileUri());
    $this->drupalGet('node/add/' . $this->contentType);
    $this->submitForm($edit, 'Save');

    // Get created Node ID.
    $matches = [];
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    $nid = $matches[1];

    // Ensure slashes are present in file path and name.
    $node = $etm->getStorage('node')->load($nid);
    $this->assertSame("public://node/{$title}/{$title}.txt", $node->get($field_name)->referencedEntities()[0]->getFileUri());

    // Remove slashes.
    $edit = [
      'third_party_settings[filefield_paths][file_path][options][slashes]' => TRUE,
      'third_party_settings[filefield_paths][file_name][options][slashes]' => TRUE,
      'third_party_settings[filefield_paths][retroactive_update]' => TRUE,
    ];
    $this->drupalGet("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$field_name}");
    $this->submitForm($edit, 'Save settings');
    $etm->getStorage('file')
      ->resetCache([$node->{$field_name}->target_id]);
    $node_storage = $etm->getStorage('node');
    $node_storage->resetCache([$nid]);

    // Ensure slashes are not present in file path and name.
    $node = $node_storage->load($nid);
    $title = str_replace('/', '', $title);
    $this->assertSame("public://node/{$title}/{$title}.txt", $node->{$field_name}[0]->entity->getFileUri());
  }

  /**
   * Test a file usage of a basic file upload with File (Field) Paths.
   */
  public function testFileUsage() {
    /** @var \Drupal\node\NodeStorage $node_storage */
    $node_storage = $this->container->get('entity_type.manager')
      ->getStorage('node');
    /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
    $file_usage = $this->container->get('file.usage');

    // Create a File field with 'node/[node:nid]' as the File path.
    $field_name = mb_strtolower($this->randomMachineName());
    $third_party_settings['filefield_paths']['file_path']['value'] = 'node/[node:nid]';
    $this->createFileField($field_name, 'node', $this->contentType, [], [], $third_party_settings);

    // Create a node with a test file.
    /** @var \Drupal\file\Entity\File $test_file */
    $test_file = $this->getTestFile('text');
    $nid = $this->uploadNodeFile($test_file, $field_name, $this->contentType);

    // Get file usage for uploaded file.
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $file = $node->{$field_name}->entity;
    $usage = $file_usage->listUsage($file);

    // Ensure file usage count for new node is correct.
    $this->assertNotEmpty($usage['file']['node'][$nid]);
    $this->assertSame(1, (int) $usage['file']['node'][$nid], 'File usage count for new node is correct.');

    // Update node.
    $this->drupalGet("node/{$nid}/edit");
    $this->submitForm(['revision' => FALSE], 'Save');
    $usage = $file_usage->listUsage($file);

    // Ensure file usage count for updated node is correct.
    $this->assertNotEmpty($usage['file']['node'][$nid]);
    $this->assertSame(1, (int) $usage['file']['node'][$nid], 'File usage count for updated node is correct.');

    // Update node with revision.
    $this->drupalGet("node/{$nid}/edit");
    $this->submitForm(['revision' => TRUE], 'Save');
    $usage = $file_usage->listUsage($file);

    // Ensure file usage count for updated node with revision is correct.
    $this->assertNotEmpty($usage['file']['node'][$nid]);
    $this->assertSame(2, (int) $usage['file']['node'][$nid], 'File usage count for updated node with revision is correct.');
  }

  /**
   * Test File (Field) Paths works with read-only stream wrappers.
   */
  public function testReadOnly() {
    // Create a File field.
    $field_name = mb_strtolower($this->randomMachineName());
    $field_settings = ['uri_scheme' => 'ffp-dummy-readonly'];
    $instance_settings = ['file_directory' => "fields/{$field_name}"];
    $this->createFileField($field_name, 'node', $this->contentType, $field_settings, $instance_settings);

    // Get a test file.
    /** @var \Drupal\file\Entity\File $file */
    $file = $this->getTestFile('image');

    // Prepare the file for the test 'ffp-dummy-readonly://' read-only stream
    // wrapper.
    $file->setFileUri(str_replace('public', 'ffp-dummy-readonly', $file->getFileUri()));
    $file->save();

    // Attach the file to a node.
    $node['type'] = $this->contentType;
    $node[$field_name][0]['target_id'] = $file->id();

    $node = $this->drupalCreateNode($node);

    // Ensure file has been attached to a node.
    $this->assertNotEmpty($node->{$field_name}[0], 'Read-only file is correctly attached to a node.');

    $edit['third_party_settings[filefield_paths][retroactive_update]'] = TRUE;
    $edit['third_party_settings[filefield_paths][file_path][value]'] = 'node/[node:nid]';
    $this->drupalGet("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$field_name}");
    $this->submitForm($edit, 'Save settings');

    // Ensure file is still in original location.
    $this->drupalGet("node/{$node->id()}");
    // Read-only file not affected by Retroactive updates.
    $this->assertSession()
      ->responseContains("{$this->publicFilesDirectory}/{$file->getFilename()}");
  }

}
