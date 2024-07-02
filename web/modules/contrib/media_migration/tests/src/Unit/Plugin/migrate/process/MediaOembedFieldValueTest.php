<?php

namespace Drupal\Tests\media_migration\Unit\Plugin\migrate\process;

use Drupal\media_migration\Plugin\migrate\process\MediaOembedFieldValue;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the MediaOembedFieldValue migration process plugin.
 *
 * @coversDefaultClass \Drupal\media_migration\Plugin\migrate\process\MediaOembedFieldValue
 *
 * @group media_migration
 */
class MediaOembedFieldValueTest extends MigrateProcessTestCase {

  /**
   * Tests the process plugin.
   *
   * @dataProvider providerTestTransform
   */
  public function testTransform(string $value, string $expected_value): void {
    $plugin = new MediaOembedFieldValue([], 'media_oembed_field_value', []);

    $this->assertEquals(
      $expected_value,
      $plugin->transform($value, $this->migrateExecutable, $this->row, 'd')
    );
  }

  /**
   * Data provider for ::testTransform.
   *
   * @return string[][]
   *   The test cases.
   */
  public function providerTestTransform(): array {
    return [
      'A Vimeo oembed url' => [
        'value' => 'oembed://https%3A//player.vimeo.com/video/268828727',
        'expected' => 'https://player.vimeo.com/video/268828727',
      ],
      'A YouTube oembed url' => [
        'value' => 'oembed://https%3A//youtu.be/RosijHlrgBI',
        'expected' => 'https://youtu.be/RosijHlrgBI',
      ],
    ];
  }

}
