<?php

namespace Drupal\Tests\media_migration\Unit\Plugin\migrate\process;

use Drupal\Core\Site\Settings;
use Drupal\media_migration\MediaMigration;
use Drupal\media_migration\Plugin\migrate\process\MediaWysiwygFilter;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;

/**
 * Tests the MediaWysiwygFilter migration process plugin.
 *
 * @coversDefaultClass \Drupal\media_migration\Plugin\migrate\process\MediaWysiwygFilter
 * @group media_migration
 */
class MediaWysiwygFilterTest extends ProcessTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    new Settings([]);
    $this->setSetting(MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_SETTINGS, MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED);
  }

  /**
   * Test the MediaWysiwygFilter plugin's transform.
   *
   * @param string|string[] $input_value
   *   The transformed value.
   * @param string|string[] $expected_value
   *   The expected value.
   *
   * @dataProvider providerTransformTest
   */
  public function testMediaWysiwygFilterTransform($input_value, $expected_value): void {
    $migration_plugin_manager = $this->prophesize(MigrationPluginManagerInterface::class);

    $this->plugin = new MediaWysiwygFilter(
      [],
      'media_wysiwyg_filter',
      [],
      $this->migration,
      NULL,
      $migration_plugin_manager->reveal(),
      $this->uuidOracle->reveal(),
      $this->migrateLookup->reveal(),
      $this->entityTypeManager->reveal()
    );

    $this->assertEquals(
      $expected_value,
      $this->plugin->transform(
        $input_value,
        $this->migrateExecutable,
        $this->row,
        'destination_property'
      )
    );
  }

  /**
   * Data provider for ::testMediaWysiwygFilterTransform.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTransformTest(): array {
    return [
      'Plain text' => [
        'input' => ['value' => 'Lorem ipsum dolor sit amet.'],
        'expected' => ['value' => 'Lorem ipsum dolor sit amet.'],
      ],
      'Plain text with non-media token' => [
        'input' => 'Blah
[[nid:1]]
Blah',
        'expected' => 'Blah
[[nid:1]]
Blah',
      ],
      'Plain text with non-media JSON' => [
        'input' => 'Blah
[[{"nid":"1"}]]
Blah',
        'expected' => 'Blah
[[{"nid":"1"}]]
Blah',
      ],
      'HTML text with non-media JSON' => [
        'input' => '<p>Blah</p>
[[{"nid":"1"}]]
<p><strong>Blah</strong></p>',
        'expected' => '<p>Blah</p>
[[{"nid":"1"}]]
<p><strong>Blah</strong></p>',
      ],
      'Plain text with non-media and media JSON tokens' => [
        'input' => '[[{"nid":"1"}]]
Lorem ipsum dolor sit amet.
[[{"fid":"3","view_mode":"default","type":"media"}]]
Nam finibus elit nec ipsum feugiat convallis.
[[{"fid":"1","view_mode":"wysiwyg","type":"media"}]]
Aliquam tellus nisi.
[[nid:1]]',
        'expected' => '[[{"nid":"1"}]]
Lorem ipsum dolor sit amet.
<drupal-entity data-embed-button="media" data-entity-type="media" data-entity-id="3" data-entity-embed-display="view_mode:media.default"></drupal-entity>
Nam finibus elit nec ipsum feugiat convallis.
<drupal-entity data-embed-button="media" data-entity-type="media" data-entity-id="1" data-entity-embed-display="view_mode:media.wysiwyg"></drupal-entity>
Aliquam tellus nisi.
[[nid:1]]',
      ],
      'HTML text with non-media and media JSON tokens' => [
        'input' => '<p class="lead">[[{"foo":"bar"}]]</p>
<p>Lorem ipsum dolor sit amet.</p>
[[{"fid":"453","view_mode":"default","type":"media"}]]
<p>Nam finibus elit nec ipsum feugiat convallis.</p>
<p>[[{"fid":"154","view_mode":"wysiwyg","type":"media"}]]</p>
<ul>
  <li>Aliquam tellus nisi.</li>
</ul>
[[tid:15]]
<p></p>',
        'expected' => '<p class="lead">[[{"foo":"bar"}]]</p>
<p>Lorem ipsum dolor sit amet.</p>
<drupal-entity data-embed-button="media" data-entity-type="media" data-entity-id="453" data-entity-embed-display="view_mode:media.default"></drupal-entity>
<p>Nam finibus elit nec ipsum feugiat convallis.</p>
<p><drupal-entity data-embed-button="media" data-entity-type="media" data-entity-id="154" data-entity-embed-display="view_mode:media.wysiwyg"></drupal-entity></p>
<ul>
  <li>Aliquam tellus nisi.</li>
</ul>
[[tid:15]]
<p></p>',
      ],
      'Text with invalid (?) JSON' => [
        'input' => '<p>Foo?</p>
[[{ "fid":"123456","view_mode":"default","type":"media","attributes":{ "class":"css-class1
css-class2 css-class3" ,"alt":"Overridden alt attribute for embed" ,"title":"Overridden title attribute for embed" },
"fields":{ "format":"default" ,"alt":"Overridden alt attribute for embed","field_file_image_alt_text[und][0][value]":"Media alt attribute" ,"title":"Overridden title attribute for embed","field_file_image_title_text[und][0][value]":"Media title attribute"} }]]
<p>Bar baz!</p>',
        'expected' => '<p>Foo?</p>
<drupal-entity data-embed-button="media" data-entity-type="media" data-entity-id="123456" data-entity-embed-display="view_mode:media.default" alt="Overridden alt attribute for embed" title="Overridden title attribute for embed"></drupal-entity>
<p>Bar baz!</p>',
      ],
    ];
  }

}
