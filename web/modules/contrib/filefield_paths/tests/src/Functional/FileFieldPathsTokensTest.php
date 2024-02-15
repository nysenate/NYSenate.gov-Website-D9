<?php

namespace Drupal\Tests\filefield_paths\Functional;

/**
 * Test token functionality.
 *
 * @group File (Field) Paths
 */
class FileFieldPathsTokensTest extends FileFieldPathsTestBase {

  /**
   * Assert that the provided token matches the provided value.
   *
   * @param string $token
   *   The token to test.
   * @param string $value
   *   The value to check against the token.
   * @param array $data
   *   The data to process the token with.
   */
  public function assertToken($token, $value, array $data) {
    $result = \Drupal::token()->replace($token, $data);

    $this->assertEquals($value, $result, "Token {$token} equals {$value}");
  }

  /**
   * Test token values with a text file.
   */
  public function testTokensBasic() {
    // Prepare a test text file.
    /** @var \Drupal\file\Entity\File $text_file */
    $text_file = $this->getTestFile('text');
    $text_file->save();

    // Ensure tokens are processed correctly.
    $data = ['file' => $text_file];
    $this->assertToken('[file:ffp-name-only]', 'text-0', $data);
    $this->assertToken('[file:ffp-name-only-original]', 'text-0', $data);
    $this->assertToken('[file:ffp-extension-original]', 'txt', $data);
  }

  /**
   * Test token values with a moved text file.
   */
  public function testTokensMoved() {
    // Prepare a test text file.
    /** @var \Drupal\file\Entity\File $text_file */
    $text_file = $this->getTestFile('text');
    $text_file->save();

    // Move the text file.
    $moved_file = \Drupal::service('file.repository')->move($text_file, 'public://moved.diff');

    // Ensure tokens are processed correctly.
    $data = ['file' => $moved_file];
    $this->assertToken('[file:ffp-name-only]', 'moved', $data);
    $this->assertToken('[file:ffp-name-only-original]', 'text-0', $data);
    $this->assertToken('[file:ffp-extension-original]', 'txt', $data);
  }

  /**
   * Test token values with a multi-extension text file.
   */
  public function testTokensMultiExtension() {
    // Prepare a test text file.
    /** @var \Drupal\file\Entity\File $text_file */
    $text_file = $this->getTestFile('text');
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $file_system->copy($text_file->getFileUri(), 'public://text.multiext.txt');
    $files = $file_system->scanDirectory('public://', '/text\.multiext\.txt/');
    $multiext_file = current($files);
    /** @var \Drupal\file\Entity\File $multiext_file */
    $multiext_file = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->create((array) $multiext_file);
    $multiext_file->save();

    // Ensure tokens are processed correctly.
    $data = ['file' => $multiext_file];
    $this->assertToken('[file:ffp-name-only]', 'text.multiext', $data);
    $this->assertToken('[file:ffp-name-only-original]', 'text.multiext', $data);
    $this->assertToken('[file:ffp-extension-original]', 'txt', $data);
  }

  /**
   * Test token value with a UTF file.
   *
   * @see https://www.drupal.org/node/1292436
   */
  public function testTokensUtf() {
    // Prepare a test text file.
    /** @var \Drupal\file\Entity\File $text_file */
    $text_file = $this->getTestFile('text');
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $file_system->copy($text_file->getFileUri(), 'public://тест.txt');
    $files = $file_system->scanDirectory('public://', '/тест\.txt/');
    $utf_file = current($files);
    /** @var \Drupal\file\Entity\File $utf_file */
    $utf_file = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->create((array) $utf_file);
    $utf_file->save();

    // Ensure tokens are processed correctly.
    $data = ['file' => $utf_file];
    $this->assertToken('[file:ffp-name-only]', 'тест', $data);
  }

}
