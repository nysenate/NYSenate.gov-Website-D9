<?php

namespace Drupal\Tests\sophron\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for Sophron guesser.
 *
 * @coversDefaultClass \Drupal\sophron_guesser\SophronMimeTypeGuesser
 *
 * @group sophron
 */
class SophronGuesserTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['sophron', 'system'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installConfig(['sophron', 'system']);
  }

  /**
   * @covers ::guess
   */
  public function testGuesserNotInstalled(): void {
    $guesser = \Drupal::service('file.mime_type.guesser.extension');
    $this->assertEquals('application/octet-stream', $guesser->guessMimeType('fake.jp2'));
  }

  /**
   * @covers ::guess
   */
  public function testGuesserInstalled(): void {
    \Drupal::service('module_installer')->install(['sophron_guesser']);
    $guesser = \Drupal::service('file.mime_type.guesser.extension');
    $this->assertEquals('image/jp2', $guesser->guessMimeType('fake.jp2'));
  }

  /**
   * @covers ::guess
   */
  public function testGuesserInstallUninstall(): void {
    $guesser = \Drupal::service('file.mime_type.guesser.extension');
    $this->assertEquals('application/octet-stream', $guesser->guessMimeType('fake.jp2'));
    \Drupal::service('module_installer')->install(['sophron_guesser']);
    $guesser = \Drupal::service('file.mime_type.guesser.extension');
    $this->assertEquals('image/jp2', $guesser->guessMimeType('fake.jp2'));
    \Drupal::service('module_installer')->uninstall(['sophron_guesser']);
    $guesser = \Drupal::service('file.mime_type.guesser.extension');
    $this->assertEquals('application/octet-stream', $guesser->guessMimeType('fake.jp2'));
  }

  /**
   * Test mapping of mimetypes from filenames.
   *
   * Mostly a copy of the equivalent method at
   * \Drupal\KernelTests\Core\File\MimeTypeTest::testFileMimeTypeDetection.
   */
  public function testFileMimeTypeDetection(): void {
    $prefixes = ['public://', 'private://', 'temporary://', 'dummy-remote://'];

    $test_case = [
      'test.jar' => 'application/java-archive',
      'test.jpeg' => 'image/jpeg',
      'test.JPEG' => 'image/jpeg',
      'test.jpg' => 'image/jpeg',
      'test.jar.jpg' => 'image/jpeg',
      'test.jpg.jar' => 'application/java-archive',
      'test.pcf.Z' => 'application/x-font',
      'pcf.z' => 'application/octet-stream',
      'jar' => 'application/octet-stream',
      'some.junk' => 'application/octet-stream',
    ];

    $guesser = $this->container->get('file.mime_type.guesser');
    // Test using default mappings.
    foreach ($test_case as $input => $expected) {
      // Test stream [URI].
      foreach ($prefixes as $prefix) {
        $output = $guesser->guessMimeType($prefix . $input);
        $this->assertSame($expected, $output, sprintf("Mimetype for '%s' is '%s' (expected: '%s').", $prefix . $input, $output, $expected));
      }

      // Test normal path equivalent.
      $output = $guesser->guessMimeType($input);
      $this->assertSame($expected, $output, sprintf("Mimetype (using default mappings) for '%s' is '%s' (expected: '%s').", $input, $output, $expected));
    }
  }

}
