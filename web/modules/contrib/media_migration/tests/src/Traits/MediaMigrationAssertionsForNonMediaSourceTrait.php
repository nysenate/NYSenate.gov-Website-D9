<?php

namespace Drupal\Tests\media_migration\Traits;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ImageToolkit\ImageToolkitInterface;
use Drupal\Core\ImageToolkit\ImageToolkitManager;
use Drupal\field\FieldConfigInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media_migration\MediaMigration;
use Drupal\node\NodeInterface;
use Masterminds\HTML5;
use Masterminds\HTML5\Parser\StringInputStream;

/**
 * Trait for non-media source to media migration tests.
 */
trait MediaMigrationAssertionsForNonMediaSourceTrait {

  use MediaMigrationAssertionsBaseTrait;

  /**
   * Asserts the migration result from file ID 1 to media 1.
   */
  protected function assertNonMediaToMedia1FieldValues($name = 'blue.png') {
    $dest_id = $this->getDestinationIdFromSourceId(1);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'image']],
      'name' => [['value' => $name]],
      'uid' => [['target_id' => '2']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1594368799']],
      'field_media_image' => [
        [
          'target_id' => '1',
          'alt' => 'Alt for blue.png',
          'title' => NULL,
          'width' => '1280',
          'height' => '720',
        ],
      ],
    ], $this->getImportantEntityProperties($media));

    // Check the media field.
    $media_field = $this->getReferencedEntities($media, 'field_media_image', 1);
    assert($media_field[0] instanceof FileInterface);
    // The referenced file should exist.
    $this->assertTrue(file_exists($media_field[0]->getFileUri()));
  }

  /**
   * Asserts the migration result from file ID 2 to media 2.
   */
  protected function assertNonMediaToMedia2FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(2);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'image']],
      'name' => [['value' => 'green.jpg']],
      'uid' => [['target_id' => '2']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1594368799']],
      'field_media_image' => [
        [
          'target_id' => '2',
          'alt' => 'Alt for green.jpg',
          'title' => NULL,
          'width' => '720',
          'height' => '960',
        ],
      ],
    ], $this->getImportantEntityProperties($media));

    // Check the media field.
    $media_field = $this->getReferencedEntities($media, 'field_media_image', 1);
    assert($media_field[0] instanceof FileInterface);
    // The referenced file should exist.
    $this->assertTrue(file_exists($media_field[0]->getFileUri()));
  }

  /**
   * Asserts the migration result from file ID 3 to media 3.
   */
  protected function assertNonMediaToMedia3FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(3);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'image']],
      'name' => [['value' => 'red.jpeg']],
      'uid' => [['target_id' => '2']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1594368881']],
      'field_media_image' => [
        [
          'target_id' => '3',
          'alt' => 'Alt for red.jpeg',
          'title' => NULL,
          'width' => '1280',
          'height' => '720',
        ],
      ],
    ], $this->getImportantEntityProperties($media));

    // Check the media field.
    $media_field = $this->getReferencedEntities($media, 'field_media_image', 1);
    assert($media_field[0] instanceof FileInterface);
    // The referenced file should exist.
    $this->assertTrue(file_exists($media_field[0]->getFileUri()));
  }

  /**
   * Asserts the migration result from file ID 6 to media 6.
   */
  protected function assertNonMediaToMedia6FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(6);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'document']],
      'name' => [['value' => 'LICENSE.txt']],
      'uid' => [['target_id' => '2']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1594368799']],
      'field_media_document' => [
        [
          'target_id' => '6',
          'display' => '1',
          'description' => NULL,
        ],
      ],
    ], $this->getImportantEntityProperties($media));

    // Check the media field.
    $media_field = $this->getReferencedEntities($media, 'field_media_document', 1);
    assert($media_field[0] instanceof FileInterface);
    // The referenced file should exist.
    $this->assertTrue(file_exists($media_field[0]->getFileUri()));
  }

  /**
   * Asserts the migration result from file ID 7 to media 7.
   */
  protected function assertNonMediaToMedia7FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(7);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'image']],
      'name' => [['value' => 'yellow.jpg']],
      'uid' => [['target_id' => '2']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1594368799']],
      'field_media_image' => [
        [
          'target_id' => '7',
          'alt' => 'Alt for yellow.jpg',
          'title' => NULL,
          'width' => '640',
          'height' => '400',
        ],
      ],
    ], $this->getImportantEntityProperties($media));

    // Check the media field.
    $media_field = $this->getReferencedEntities($media, 'field_media_image', 1);
    assert($media_field[0] instanceof FileInterface);
    // The referenced file should exist.
    $this->assertTrue(file_exists($media_field[0]->getFileUri()));
  }

  /**
   * Asserts the migration result from file ID 8 to media 8.
   */
  protected function assertNonMediaToMedia8FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(8);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'video']],
      'name' => [['value' => 'video.webm']],
      'uid' => [['target_id' => '1']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1597409263']],
      'field_media_video_file' => [
        [
          'target_id' => '8',
          'display' => '1',
          'description' => NULL,
        ],
      ],
    ], $this->getImportantEntityProperties($media));

    // Check the media field.
    $media_field = $this->getReferencedEntities($media, 'field_media_video_file', 1);
    assert($media_field[0] instanceof FileInterface);
    // The referenced file should exist.
    $this->assertTrue(file_exists($media_field[0]->getFileUri()));
  }

  /**
   * Asserts the migration result from file ID 9 to media 9.
   */
  protected function assertNonMediaToMedia9FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(9);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'video']],
      'name' => [['value' => 'video.mp4']],
      'uid' => [['target_id' => '1']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1597409263']],
      'field_media_video_file' => [
        [
          'target_id' => '9',
          'display' => '1',
          'description' => 'Tiny video about kittens',
        ],
      ],
    ], $this->getImportantEntityProperties($media));

    // Check the media field.
    $media_field = $this->getReferencedEntities($media, 'field_media_video_file', 1);
    assert($media_field[0] instanceof FileInterface);
    // The referenced file should exist.
    $this->assertTrue(file_exists($media_field[0]->getFileUri()));
  }

  /**
   * Asserts the migration result from file ID 10 to media 10.
   */
  protected function assertNonMediaToMedia10FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(10);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);

    $expected_entity_properties = [
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'image']],
      'name' => [['value' => 'yellow.webp']],
      'uid' => [['target_id' => '2']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1594368881']],
      'field_media_image' => [
        [
          'target_id' => '10',
          'alt' => 'Description of yellow.webp',
          'title' => NULL,
          'width' => NULL,
          'height' => NULL,
        ],
      ],
    ];

    $toolkit_manager = $this->container->get('image.toolkit.manager');
    assert($toolkit_manager instanceof ImageToolkitManager);
    $toolkit = $toolkit_manager->getDefaultToolkit();
    if (
      $toolkit instanceof ImageToolkitInterface &&
      in_array('webp', $toolkit::getSupportedExtensions())
    ) {
      $expected_entity_properties['field_media_image'][0]['width'] = 640;
      $expected_entity_properties['field_media_image'][0]['height'] = 400;
    }

    $this->assertEquals($expected_entity_properties, $this->getImportantEntityProperties($media));

    // Check the media field.
    $media_field = $this->getReferencedEntities($media, 'field_media_image', 1);
    assert($media_field[0] instanceof FileInterface);
    // The referenced file should exist.
    $this->assertTrue(file_exists($media_field[0]->getFileUri()));
  }

  /**
   * Asserts the migration result from file ID 11 to media 11.
   */
  protected function assertNonMediaToMedia11FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(11);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'audio']],
      'name' => [['value' => 'audio.m4a']],
      'uid' => [['target_id' => '1']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1597409263']],
      'field_media_audio_file' => [
        [
          'target_id' => '11',
          'display' => '1',
          'description' => NULL,
        ],
      ],
    ], $this->getImportantEntityProperties($media));

    // Check the media field.
    $media_field = $this->getReferencedEntities($media, 'field_media_audio_file', 1);
    assert($media_field[0] instanceof FileInterface);
    // The referenced file should exist.
    $this->assertTrue(file_exists($media_field[0]->getFileUri()));
  }

  /**
   * Asserts the migration result from file ID 12 to media 12.
   */
  protected function assertNonMediaToMedia12FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(12);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'document']],
      'name' => [['value' => 'document.odt']],
      'uid' => [['target_id' => '2']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1594368799']],
      'field_media_document' => [
        [
          'target_id' => '12',
          'display' => '1',
          'description' => NULL,
        ],
      ],
    ], $this->getImportantEntityProperties($media));

    // Check the media field.
    $media_field = $this->getReferencedEntities($media, 'field_media_document', 1);
    assert($media_field[0] instanceof FileInterface);
    // The referenced file should exist.
    $this->assertTrue(file_exists($media_field[0]->getFileUri()));
  }

  /**
   * Assertions of node 1.
   */
  protected function assertNonMediaToMediaNode1FieldValues() {
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load(1);
    assert($node instanceof NodeInterface);

    $props = $this->getImportantEntityProperties($node);
    $node_body_text = $props['body'][0]['value'];
    $html5 = new HTML5(['disable_html_ns' => TRUE]);
    // Compatibility for older HTML5 versions (e.g. in Drupal core 8.9.x).
    $dom_text = '<html><body>' . $node_body_text . '</body></html>';
    try {
      $node_body_html = $html5->parse($dom_text);
    }
    catch (\TypeError $e) {
      $text_stream = new StringInputStream($dom_text);
      $node_body_html = $html5->parse($text_stream);
    }
    foreach ($node_body_html->getElementsByTagName('a') as $anchor_node) {
      assert($anchor_node instanceof \DOMNode);
      if ($anchor_node->hasAttribute('data-entity-uuid')) {
        $anchor_node->setAttribute('data-entity-uuid', 'uuid');
      }
    }
    $props['body'][0]['value'] = $html5->saveHTML($node_body_html->documentElement->firstChild->childNodes);

    $this->assertEquals([
      'nid' => [['value' => 1]],
      'type' => [['target_id' => 'article']],
      'status' => [['value' => 1]],
      'uid' => [['target_id' => 2]],
      'title' => [['value' => 'Article with images and files']],
      'created' => [['value' => 1594368799]],
      'changed' => [['value' => 1594368881]],
      'promote' => [['value' => 1]],
      'sticky' => [['value' => 0]],
      'body' => [
        [
          'value' => '<p>Nulla tempor, nunc eu mollis finibus, risus nunc <a href="/file/7" data-entity-substitution="media" data-entity-type="media" data-entity-uuid="uuid">venenatis nulla</a>, in ullamcorper nisl nulla et nisi. Cras vel urna risus. Cras in sem a nulla aliquet pretium.</p><p>Quisque tortor libero, vulputate sit amet augue dictum, posuere bibendum lectus. Nunc fermentum justo odio, ut fermentum purus fermentum a. Aenean congue fringilla arcu sit amet pellentesque.</p>',
          'summary' => '',
          'format' => 'filtered_html',
        ],
      ],
      'field_file' => [
        ['target_id' => $this->getDestinationIdFromSourceId(6)],
      ],
      'field_file_multi' => [
        ['target_id' => $this->getDestinationIdFromSourceId(12)],
        ['target_id' => $this->getDestinationIdFromSourceId(10)],
      ],
      'field_image' => [
        ['target_id' => $this->getDestinationIdFromSourceId(1)],
      ],
      'field_image_multi' => [
        ['target_id' => $this->getDestinationIdFromSourceId(2)],
        ['target_id' => $this->getDestinationIdFromSourceId(7)],
        ['target_id' => $this->getDestinationIdFromSourceId(3)],
      ],
    ], $props);

    // Test that the image and file fields are referencing media entities.
    $media_fields = [
      'field_file' => 1,
      'field_file_multi' => 2,
      'field_image' => 1,
      'field_image_multi' => 3,
    ];
    foreach ($media_fields as $field_name => $expected_count) {
      $referred_entities = $this->getReferencedEntities($node, $field_name, $expected_count);
      $this->assertInstanceOf(MediaInterface::class, $referred_entities[0]);
    }
  }

  /**
   * Assertions of node 2.
   */
  protected function assertNonMediaToMediaNode2FieldValues() {
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load(2);
    assert($node instanceof NodeInterface);

    $props = $this->getImportantEntityProperties($node);
    $node_body_text = $props['body'][0]['value'];
    $html5 = new HTML5(['disable_html_ns' => TRUE]);
    // Compatibility for older HTML5 versions (e.g. in Drupal core 8.9.x).
    $dom_text = '<html><body>' . $node_body_text . '</body></html>';
    try {
      $node_body_html = $html5->parse($dom_text);
    }
    catch (\TypeError $e) {
      $text_stream = new StringInputStream($dom_text);
      $node_body_html = $html5->parse($text_stream);
    }
    foreach ($node_body_html->getElementsByTagName('a') as $anchor_node) {
      assert($anchor_node instanceof \DOMNode);
      if ($anchor_node->hasAttribute('data-entity-uuid')) {
        $anchor_node->setAttribute('data-entity-uuid', 'uuid');
      }
    }
    $props['body'][0]['value'] = $html5->saveHTML($node_body_html->documentElement->firstChild->childNodes);

    $this->assertEquals([
      'nid' => [['value' => 2]],
      'type' => [['target_id' => 'article']],
      'status' => [['value' => 1]],
      'uid' => [['target_id' => 1]],
      'title' => [['value' => 'Another article with audio and video files']],
      'created' => [['value' => 1597409263]],
      'changed' => [['value' => 1597409263]],
      'promote' => [['value' => 1]],
      'sticky' => [['value' => 0]],
      'body' => [
        [
          'value' => '<p>Aliquam <a href="/file/1" data-entity-substitution="media" data-entity-type="media" data-entity-uuid="uuid">efficitur fermentum</a> nisi ut sagittis. Nullam pharetra nisi venenatis sodales tincidunt. Mauris sit amet metus arcu.</p>',
          'summary' => '',
          'format' => 'filtered_html',
        ],
      ],
      'field_file' => [
        ['target_id' => $this->getDestinationIdFromSourceId(9)],
      ],
      'field_file_multi' => [
        ['target_id' => $this->getDestinationIdFromSourceId(8)],
        ['target_id' => $this->getDestinationIdFromSourceId(11)],
      ],
      'field_image' => [],
      'field_image_multi' => [],
    ], $props);

    // Test that the image and file fields are referencing media entities.
    $media_fields = [
      'field_file' => 1,
      'field_file_multi' => 2,
      'field_image' => 0,
      'field_image_multi' => 0,
    ];
    foreach ($media_fields as $field_name => $expected_count) {
      $referred_entities = $this->getReferencedEntities($node, $field_name, $expected_count);
      if ($expected_count) {
        $this->assertInstanceOf(MediaInterface::class, $referred_entities[0]);
      }
    }
  }

  /**
   * Checks the properties of the image media type's source field config.
   */
  protected function assertNonMediaToMediaImageMediaBundleSourceFieldProperties() {
    $field_config = $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->load('media.image.field_media_image');
    assert($field_config instanceof FieldConfigInterface);

    $this->assertEquals([
      'id' => 'media.image.field_media_image',
      'status' => TRUE,
      'field_name' => 'field_media_image',
      'entity_type' => 'media',
      'bundle' => 'image',
      'label' => 'Image',
      'description' => '',
      'required' => TRUE,
      'translatable' => TRUE,
      'default_value' => [],
      'default_value_callback' => '',
      'settings' => [
        'alt_field' => TRUE,
        'alt_field_required' => TRUE,
        'title_field' => FALSE,
        'title_field_required' => FALSE,
        'max_resolution' => '',
        'min_resolution' => '',
        'default_image' => [
          'uuid' => NULL,
          'alt' => '',
          'title' => '',
          'width' => NULL,
          'height' => NULL,
        ],
        'file_directory' => '[date:custom:Y]-[date:custom:m]',
        'file_extensions' => 'png gif jpg jpeg webp',
        'max_filesize' => '',
        'handler' => 'default:file',
        'handler_settings' => [],
      ],
      'field_type' => 'image',
    ], $this->getImportantEntityProperties($field_config));
  }

  /**
   * Checks the properties of the document media type's source field config.
   */
  protected function assertNonMediaToMediaDocumentMediaBundleSourceFieldProperties() {
    $field_config = $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->load('media.document.field_media_document');
    assert($field_config instanceof FieldConfigInterface);

    $this->assertEquals([
      'id' => 'media.document.field_media_document',
      'status' => TRUE,
      'field_name' => 'field_media_document',
      'entity_type' => 'media',
      'bundle' => 'document',
      'label' => 'Document',
      'description' => '',
      'required' => TRUE,
      'translatable' => TRUE,
      'default_value' => [],
      'default_value_callback' => '',
      'settings' => [
        'description_field' => TRUE,
        'file_directory' => '[date:custom:Y]-[date:custom:m]',
        'file_extensions' => 'txt doc docx pdf odt',
        'max_filesize' => '',
        'handler' => 'default:file',
        'handler_settings' => [],
      ],
      'field_type' => 'file',
    ], $this->getImportantEntityProperties($field_config));
  }

  /**
   * Checks the properties of the audio media type's source field config.
   */
  protected function assertNonMediaToMediaAudioMediaBundleSourceFieldProperties() {
    $field_config = $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->load('media.audio.field_media_audio_file');
    assert($field_config instanceof FieldConfigInterface);

    $this->assertEquals([
      'id' => 'media.audio.field_media_audio_file',
      'status' => TRUE,
      'field_name' => 'field_media_audio_file',
      'entity_type' => 'media',
      'bundle' => 'audio',
      'label' => 'Audio file',
      'description' => '',
      'required' => TRUE,
      'translatable' => TRUE,
      'default_value' => [],
      'default_value_callback' => '',
      'settings' => [
        'description_field' => TRUE,
        'file_directory' => '[date:custom:Y]-[date:custom:m]',
        'file_extensions' => 'mp3 wav aac m4a',
        'max_filesize' => '',
        'handler' => 'default:file',
        'handler_settings' => [],
      ],
      'field_type' => 'file',
    ], $this->getImportantEntityProperties($field_config));
  }

  /**
   * Checks the properties of the audio media type's source field config.
   */
  protected function assertNonMediaToMediaVideoMediaBundleSourceFieldProperties() {
    $field_config = $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->load('media.video.field_media_video_file');
    assert($field_config instanceof FieldConfigInterface);

    $this->assertEquals([
      'id' => 'media.video.field_media_video_file',
      'status' => TRUE,
      'field_name' => 'field_media_video_file',
      'entity_type' => 'media',
      'bundle' => 'video',
      'label' => 'Video file',
      'description' => '',
      'required' => TRUE,
      'translatable' => TRUE,
      'default_value' => [],
      'default_value_callback' => '',
      'settings' => [
        'description_field' => TRUE,
        'file_directory' => '[date:custom:Y]-[date:custom:m]',
        'file_extensions' => 'mp4 webm',
        'max_filesize' => '',
        'handler' => 'default:file',
        'handler_settings' => [],
      ],
      'field_type' => 'file',
    ], $this->getImportantEntityProperties($field_config));
  }

  /**
   * Tests the migrated filter formats.
   */
  protected function assertFilterFormats() {
    $entity_type_manager = $this->container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);
    $filter_formats = $entity_type_manager->getStorage('filter_format')->loadMultiple();
    $allowed_html = MediaMigration::getEmbedTokenDestinationFilterPlugin() === MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED
      ? '<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd> <drupal-entity data-*>'
      : '<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd> <drupal-media data-* alt title>';

    $this->assertEquals(
      [
        'status' => TRUE,
        'name' => 'Filtered HTML',
        'format' => 'filtered_html',
        'weight' => 0,
        'filters' => [
          'filter_autop' => [
            'id' => 'filter_autop',
            'provider' => 'filter',
            'status' => TRUE,
            'weight' => 2,
            'settings' => [],
          ],
          'filter_html' => [
            'id' => 'filter_html',
            'provider' => 'filter',
            'status' => TRUE,
            'weight' => 1,
            'settings' => [
              'allowed_html' => $allowed_html,
              'filter_html_help' => TRUE,
              'filter_html_nofollow' => FALSE,
            ],
          ],
          'filter_htmlcorrector' => [
            'id' => 'filter_htmlcorrector',
            'provider' => 'filter',
            'status' => TRUE,
            'weight' => 10,
            'settings' => [],
          ],
          'filter_url' => [
            'id' => 'filter_url',
            'provider' => 'filter',
            'status' => TRUE,
            'weight' => 0,
            'settings' => ['filter_url_length' => 72],
          ],
          'linkit' => [
            'id' => 'linkit',
            'provider' => 'linkit',
            'status' => TRUE,
            'weight' => 0,
            'settings' => [
              'title' => TRUE,
            ],
          ],
        ],
      ],
      $this->getImportantEntityProperties($filter_formats['filtered_html'])
    );
  }

}
