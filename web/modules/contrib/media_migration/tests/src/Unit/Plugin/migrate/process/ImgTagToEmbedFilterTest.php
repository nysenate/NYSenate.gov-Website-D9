<?php

namespace Drupal\Tests\media_migration\Unit\Plugin\migrate\process;

use Drupal\Core\Site\Settings;
use Drupal\media_migration\MediaMigration;
use Drupal\media_migration\Plugin\migrate\process\ImgTagToEmbedFilter;
use Prophecy\Argument;

/**
 * Tests the ImgTagToEmbedFilter migration process plugin.
 *
 * @coversDefaultClass \Drupal\media_migration\Plugin\migrate\process\ImgTagToEmbedFilter
 * @group media_migration
 */
class ImgTagToEmbedFilterTest extends ProcessTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    new Settings([]);
    $this->setSetting(MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_SETTINGS, MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_MEDIA_EMBED);
    $this->setSetting(MediaMigration::MEDIA_REFERENCE_METHOD_SETTINGS, MediaMigration::EMBED_MEDIA_REFERENCE_METHOD_UUID);

    $this->plugin = new ImgTagToEmbedFilter(
      [],
      'img_tag_to_embed',
      [],
      $this->migration,
      $this->uuidOracle->reveal(),
      $this->logger->reveal(),
      NULL,
      $this->migrateLookup->reveal(),
      $this->entityTypeManager->reveal()
    );
  }

  /**
   * Tests transform to media embed.
   *
   * @dataProvider providerTestTransform
   */
  public function testTransform(string $input_value, string $expected_value) {
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
   * Tests transform to entity embed.
   *
   * @dataProvider providerTestTransformToEntityEmbed
   */
  public function testTransformToEntityEmbed(string $input_value, string $expected_value) {
    $this->setSetting(MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_SETTINGS, MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED);

    $this->plugin = new ImgTagToEmbedFilter(
      [],
      'img_tag_to_embed',
      [],
      $this->migration,
      $this->uuidOracle->reveal(),
      $this->logger->reveal(),
      NULL,
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
   * Tests transform without watchdog table.
   */
  public function testTransformWithoutWatchdog() {
    $db_array = $this->testDatabase;
    unset($db_array['watchdog']);
    $this->sourcePlugin->getDatabase()->willReturn($this->getDatabase($db_array));

    $this->assertEquals(
      [
        'value' => 'Duis sed dignissim lectus. <drupal-media data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-view-mode="default" alt="This is an alt text"></drupal-media> Vel facilisis sapien.',
      ],
      $this->plugin->transform(
        [
          'value' => 'Duis sed dignissim lectus. <img src="http://example.com/files/jpeg1.jpeg" alt="This is an alt text"/> Vel facilisis sapien.',
        ],
        $this->migrateExecutable,
        $this->row,
        'destination_property'
      )
    );
  }

  /**
   * Tests logging transform edge cases.
   *
   * @dataProvider providerTestTransformLogging
   */
  public function testTransformLogging(string $input_value, string $expected_value, array $expected_log_messages) {
    $actual_logged_messages = NULL;
    $this->logger
      ->log(Argument::type('integer'), Argument::type('string'), Argument::cetera())
      ->will(
        function ($args) use (&$actual_logged_messages) {
          $actual_logged_messages[] = $args[1];
        }
      );

    $this->testTransform($input_value, $expected_value);

    $this->assertEquals($expected_log_messages, $actual_logged_messages);
  }

  /**
   * Data provider for ::testTransform().
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTestTransform(): array {
    return [
      'Style regular expression testcase' => [
        'Input' => <<<END
<img src="/files/jpeg1.jpeg" style="float:leftright"/>
<img src="/files/jpeg1.jpeg" style="flex-float:right"/>
<img src="/files/jpeg1.jpeg" style="flex float: left right"/>
<img src="/files/jpeg1.jpeg" style="float:right;" data-something/>
<img src="/missing/image.gif" alt="Do not touch this!"/>
<img src="/files/jpeg1.jpeg" style="float: left-;"/>
<img src="/files/jpeg1.jpeg" style="align: center; float:left; background: red"/>
<img src="/files/jpeg1.jpeg" style="float:left; float: right"/>
END
        ,
        'Expected' => <<<END
<drupal-media data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-view-mode="default" style="float:leftright"></drupal-media>
<drupal-media data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-view-mode="default" style="flex-float:right"></drupal-media>
<drupal-media data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-view-mode="default" style="flex float: left right"></drupal-media>
<drupal-media data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-view-mode="default" data-align="right" data-something></drupal-media>
<img src="/missing/image.gif" alt="Do not touch this!">
<drupal-media data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-view-mode="default" style="float: left-;"></drupal-media>
<drupal-media data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-view-mode="default" data-align="left" style="align: center; background: red"></drupal-media>
<drupal-media data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-view-mode="default" data-align="right"></drupal-media>
END
        ,
      ],
      'Plain text only' => [
        'Input' => 'Lorem ipsum dolor sit amet.',
        'Expected' => 'Lorem ipsum dolor sit amet.',
      ],

      'Plain text with unrelated tokens and JSONs (before MediaWysiwyg)' => [
        'Input' => <<<END
Sed hendrerit.
[[{"nid":"1"}]]
Nam scelerisque viverra.
[[nid:1]] Curabitur.
END
        ,
        'Expected' => <<<END
Sed hendrerit.
[[{"nid":"1"}]]
Nam scelerisque viverra.
[[nid:1]] Curabitur.
END
        ,
      ],

      'Plain text with a single image tag' => [
        'Input' => <<<END
Nulla est rhoncus est?

Eleifend non nulla! <img src="/files/jpeg1.jpeg" alt="Pellentesque alt text">
Mauris efficitur metus.
END
        ,
        'Expected' => <<<END
Nulla est rhoncus est?

Eleifend non nulla! <drupal-media data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-view-mode="default" alt="Pellentesque alt text"></drupal-media>
Mauris efficitur metus.
END
        ,
      ],

      'Plain text with multiple image- and other kind of tags' => [
        'Input' => <<<END
Fusce semper rutrum blandit. Sed nec <a href="/node">semper</a> eros.

Quisque molestie.

<img src="https://www.example.com/files/png2_0.png" align="right">

<figure><img src="/files/subfolder/another/svg3.svg" alt="Suspendisse alt text"/></figure>

Aliquam lacus arcu!
<img src="https://www.drupal.org/sites/all/themes/bluecheese/images/icon-w-drupal.svg" alt="Duis egestas Drupalusorgus logo">
END
        ,
        'Expected' => <<<END
Fusce semper rutrum blandit. Sed nec <a href="/node">semper</a> eros.

Quisque molestie.

<drupal-media data-entity-type="media" data-entity-uuid="png2-uuid" data-view-mode="default" data-align="right"></drupal-media>

<drupal-media data-entity-type="media" data-entity-uuid="svg3-uuid" data-view-mode="default" alt="Suspendisse alt text"></drupal-media>

Aliquam lacus arcu!
<img src="https://www.drupal.org/sites/all/themes/bluecheese/images/icon-w-drupal.svg" alt="Duis egestas Drupalusorgus logo">
END
        ,
      ],

      'HTML text with JSON tokens and with embed code' => [
        'Input' => <<<END
<p class="lead">Some text. [[{"foo":"bar"}]]</p>
Lorem ipsum dolor sit amet.
[[{"fid":"3","view_mode":"default","type":"media"}]]
Nam finibus elit nec ipsum feugiat convallis.
<drupal-entity data-embed-button="media" data-entity-type="media" data-entity-id="1" data-entity-embed-display="view_mode:media.wysiwyg"></drupal-entity>
Aliquam tellus nisi.
[[nid:1]]
<p>Lorem ipsum dolor sit amet.</p>
<drupal-entity data-embed-button="media" data-entity-type="media" data-entity-id="453" data-entity-embed-display="view_mode:media.default"></drupal-entity>
<p>Nam finibus elit nec ipsum feugiat convallis.</p>
<p>[[{"fid":"154","view_mode":"wysiwyg","type":"media"}]]</p>
<ul>
  <li>Aliquam tellus nisi.</li>
</ul>
[[tid:15]]
<p></p>
END
        ,
        'Expected' => <<<END
<p class="lead">Some text. [[{"foo":"bar"}]]</p>
Lorem ipsum dolor sit amet.
[[{"fid":"3","view_mode":"default","type":"media"}]]
Nam finibus elit nec ipsum feugiat convallis.
<drupal-entity data-embed-button="media" data-entity-type="media" data-entity-id="1" data-entity-embed-display="view_mode:media.wysiwyg"></drupal-entity>
Aliquam tellus nisi.
[[nid:1]]
<p>Lorem ipsum dolor sit amet.</p>
<drupal-entity data-embed-button="media" data-entity-type="media" data-entity-id="453" data-entity-embed-display="view_mode:media.default"></drupal-entity>
<p>Nam finibus elit nec ipsum feugiat convallis.</p>
<p>[[{"fid":"154","view_mode":"wysiwyg","type":"media"}]]</p>
<ul>
  <li>Aliquam tellus nisi.</li>
</ul>
[[tid:15]]
<p></p>
END
        ,
      ],

      'HTML with a single image tag' => [
        'Input' => <<<END
<p>Sed nec <a href="https://example.com/">quis</a>?</p>
<p>Consectetur aliquam.</p>
<p><img src="/files/subfolder/another/svg3.svg"/></p>
<p>Aliquam lacus arcu, fermentum eu!</p>
END
        ,
        'Expected' => <<<END
<p>Sed nec <a href="https://example.com/">quis</a>?</p>
<p>Consectetur aliquam.</p>
<p><drupal-media data-entity-type="media" data-entity-uuid="svg3-uuid" data-view-mode="default"></drupal-media></p>
<p>Aliquam lacus arcu, fermentum eu!</p>
END
        ,
      ],

      'HTML with multiple image- and other kind of tags' => [
        'Input' => <<<END
<p>A lead with two short sentences. This is the other <a href="/node">sentence</a>.</p>
<p><img src="/files/jpeg1.jpeg" alt="This is an alt text"></p>
<p>Blah blah.</p>
<figure><img src="https://example.com/files/png2_0.png" title="This is an another title text" style="float:left"></figure>
<figure><img src="/files/subfolder/another/svg3.svg" alt="Yet another alt text"><figcaption>This is a caption</figcaption></figure>

<img src="https://www.drupal.org/sites/all/themes/bluecheese/images/icon-w-drupal.svg" alt="Hotlinked Drupal org logo">
END
        ,
        'Expected' => <<<END
<p>A lead with two short sentences. This is the other <a href="/node">sentence</a>.</p>
<p><drupal-media data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-view-mode="default" alt="This is an alt text"></drupal-media></p>
<p>Blah blah.</p>
<drupal-media data-entity-type="media" data-entity-uuid="png2-uuid" data-view-mode="default" data-align="left" title="This is an another title text"></drupal-media>
<drupal-media data-entity-type="media" data-entity-uuid="svg3-uuid" data-view-mode="default" data-caption="This is a caption" alt="Yet another alt text"></drupal-media>

<img src="https://www.drupal.org/sites/all/themes/bluecheese/images/icon-w-drupal.svg" alt="Hotlinked Drupal org logo">
END
        ,
      ],
    ];
  }

  /**
   * Data provider for ::testTransformLogging().
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTestTransformLogging(): array {
    return [
      'Missing file referenced with absolute URI' => [
        'Input' => '<img src="http://example.com/files/missing.jpg">',
        'Output' => '<img src="http://example.com/files/missing.jpg">',
        'Expected messages' => [
          "No file found for the absolute image URL in tag '<img src=\"http://example.com/files/missing.jpg\">' used in the 'test_content_migration' migration's source row with source ID array( 'nid' => 123, 'vid' => 456, 'language' => 'hu', ) while processing the destination property 'destination_property'.",
        ],
      ],
      'Missing file referenced with relative URI' => [
        'Input' => '<img src="/files/missing.jpg">',
        'Output' => '<img src="/files/missing.jpg">',
        'Expected messages' => [
          "No file found for the relative image URL in tag '<img src=\"/files/missing.jpg\">' used in the 'test_content_migration' migration's source row with source ID array( 'nid' => 123, 'vid' => 456, 'language' => 'hu', ) while processing the destination property 'destination_property'.",
        ],
      ],
    ];
  }

  /**
   * Data provider for ::testTransformToEntityEmbed().
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTestTransformToEntityEmbed(): array {
    $cases = $this->providerTestTransform();
    $cases['Style regular expression testcase']['Expected'] = <<<END
<drupal-entity data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-entity-embed-display="view_mode:media.default" data-embed-button="media" style="float:leftright"></drupal-entity>
<drupal-entity data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-entity-embed-display="view_mode:media.default" data-embed-button="media" style="flex-float:right"></drupal-entity>
<drupal-entity data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-entity-embed-display="view_mode:media.default" data-embed-button="media" style="flex float: left right"></drupal-entity>
<drupal-entity data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-entity-embed-display="view_mode:media.default" data-embed-button="media" data-align="right" data-something></drupal-entity>
<img src="/missing/image.gif" alt="Do not touch this!">
<drupal-entity data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-entity-embed-display="view_mode:media.default" data-embed-button="media" style="float: left-;"></drupal-entity>
<drupal-entity data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-entity-embed-display="view_mode:media.default" data-embed-button="media" data-align="left" style="align: center; background: red"></drupal-entity>
<drupal-entity data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-entity-embed-display="view_mode:media.default" data-embed-button="media" data-align="right"></drupal-entity>
END;
    $cases['Plain text with a single image tag']['Expected'] = <<<END
Nulla est rhoncus est?

Eleifend non nulla! <drupal-entity data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-entity-embed-display="view_mode:media.default" data-embed-button="media" alt="Pellentesque alt text"></drupal-entity>
Mauris efficitur metus.
END;
    $cases['Plain text with multiple image- and other kind of tags']['Expected'] = <<<END
Fusce semper rutrum blandit. Sed nec <a href="/node">semper</a> eros.

Quisque molestie.

<drupal-entity data-entity-type="media" data-entity-uuid="png2-uuid" data-entity-embed-display="view_mode:media.default" data-embed-button="media" data-align="right"></drupal-entity>

<drupal-entity data-entity-type="media" data-entity-uuid="svg3-uuid" data-entity-embed-display="view_mode:media.default" data-embed-button="media" alt="Suspendisse alt text"></drupal-entity>

Aliquam lacus arcu!
<img src="https://www.drupal.org/sites/all/themes/bluecheese/images/icon-w-drupal.svg" alt="Duis egestas Drupalusorgus logo">
END;
    $cases['HTML with a single image tag']['Expected'] = <<<END
<p>Sed nec <a href="https://example.com/">quis</a>?</p>
<p>Consectetur aliquam.</p>
<p><drupal-entity data-entity-type="media" data-entity-uuid="svg3-uuid" data-entity-embed-display="view_mode:media.default" data-embed-button="media"></drupal-entity></p>
<p>Aliquam lacus arcu, fermentum eu!</p>
END;
    $cases['HTML with multiple image- and other kind of tags']['Expected'] = <<<END
<p>A lead with two short sentences. This is the other <a href="/node">sentence</a>.</p>
<p><drupal-entity data-entity-type="media" data-entity-uuid="jpeg1-uuid" data-entity-embed-display="view_mode:media.default" data-embed-button="media" alt="This is an alt text"></drupal-entity></p>
<p>Blah blah.</p>
<drupal-entity data-entity-type="media" data-entity-uuid="png2-uuid" data-entity-embed-display="view_mode:media.default" data-embed-button="media" data-align="left" title="This is an another title text"></drupal-entity>
<drupal-entity data-entity-type="media" data-entity-uuid="svg3-uuid" data-entity-embed-display="view_mode:media.default" data-embed-button="media" data-caption="This is a caption" alt="Yet another alt text"></drupal-entity>

<img src="https://www.drupal.org/sites/all/themes/bluecheese/images/icon-w-drupal.svg" alt="Hotlinked Drupal org logo">
END;
    return $cases;
  }

  /**
   * {@inheritdoc}
   */
  protected $testDatabase = [
    'file_managed' => [
      [
        'fid' => 1,
        'filename' => 'jpeg1.jpeg',
        'uri' => 'public://jpeg1.jpeg',
      ],
      [
        'fid' => 2,
        'filename' => 'png2.png',
        'uri' => 'public://png2_0.png',
      ],
      [
        'fid' => 3,
        'filename' => 'svg3.svg',
        'uri' => 'public://subfolder/another/svg3.svg',
      ],
    ],
    'variable' => [
      [
        'name' => 'file_public_path',
        'value' => 's:5:"files";',
      ],
    ],
    'watchdog' => [
      ['location' => 'http://example.com/'],
      ['location' => 'https://example.com/'],
      ['location' => 'https://www.example.com/user/login?destination=user/dashboard'],
    ],
  ];

}
