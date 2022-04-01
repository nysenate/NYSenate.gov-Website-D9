<?php

namespace Drupal\Tests\transliterate_filenames\Unit;

use Drupal\Component\Transliteration\PhpTransliteration;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\transliterate_filenames\SanitizeName;

/**
 * @coversDefaultClass \Drupal\transliterate_filenames\SanitizeName
 * @group transliterate_filenames
 */
class SanitizeNameTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    $container->set('transliterate_filenames.sanitize_name', new SanitizeName(new PhpTransliteration()));
  }

  /**
   * Tests sanitize filename.
   *
   * @param string $filename
   *   The tested file name.
   * @param string $expected
   *   The expected name of sanitized file.
   *
   * @dataProvider providerSanitizeName
   */
  public function testSanitizeName($filename, $expected) {
    $sanitize_filename = \Drupal::service('transliterate_filenames.sanitize_name');
    $this->assertEquals($expected, $sanitize_filename->sanitizeFilename($filename));
  }

  /**
   * Provides data for self::testSanitizeName().
   */
  public function providerSanitizeName() {
    return [
      // Transliterate Non-US-ASCII.
      ['ąęółżźćśń.pdf', 'aeolzzcsn.pdf'],
      // Remove unknown unicodes.
      [chr(0xF8) . chr(0x80) . chr(0x80) . '.txt', '.txt'],
      // Force lowercase.
      ['LOWERCASE.txt', 'lowercase.txt'],
      // Replace whitespace.
      ['test whitespace.txt', 'test-whitespace.txt'],
      ['test   whitespace.txt', 'test-whitespace.txt'],
      // Remove multiple consecutive non-alphabetical characters.
      ['---___.txt', '-_.txt'],
      ['--  --.txt', '-.txt'],
    ];
  }

}
