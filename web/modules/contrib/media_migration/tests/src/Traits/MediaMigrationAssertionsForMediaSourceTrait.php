<?php

namespace Drupal\Tests\media_migration\Traits;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media_migration\MediaMigration;
use Drupal\node\NodeInterface;

/**
 * Trait for media migration tests.
 */
trait MediaMigrationAssertionsForMediaSourceTrait {

  use MediaMigrationAssertionsBaseTrait;

  /**
   * Assertions of media 1.
   *
   * Assert "Blue PNG" image media properties; including alt, title and the
   * custom 'integer' field.
   */
  protected function assertMedia1FieldValues($name = 'Blue PNG') {
    $media1 = $this->container->get('entity_type.manager')->getStorage('media')->load($this->getDestinationIdFromSourceId(1));
    $this->assertInstanceOf(MediaInterface::class, $media1);
    $this->assertEquals($name, $media1->label());
    $this->assertEquals('image', $media1->bundle());

    // Check 'field media image' field.
    $source_field = $media1->getSource()->getConfiguration()['source_field'];
    $media1_image = $this->getReferencedEntities($media1, $source_field, 1);
    assert($media1_image[0] instanceof FileInterface);
    $this->assertEquals('1', $media1_image[0]->id());
    $this->assertEquals('1', $media1->$source_field->target_id);

    // Alt and title properties should be migrated to the corresponding media
    // image field and have to be editable on the UI.
    $this->assertEquals('Alternative text about blue.png', $media1->$source_field->alt);
    $this->assertEquals('Title copy for blue.png', $media1->$source_field->title);
    $this->assertEquals('1000', $media1->field_media_integer->value);

    // The following fields should not be present.
    $this->assertFalse($media1->hasField('field_file_image_alt_text'));
    $this->assertFalse($media1->hasField('field_file_image_title_text'));

    // Author should be user 1.
    $this->assertEquals('1', $media1->getOwnerId());

    // Assert authored on date.
    $this->assertEquals('1587725909', $media1->getCreatedTime());

    // The image file should exist.
    $this->assertTrue(file_exists($media1_image[0]->getFileUri()));
  }

  /**
   * Assertions of media 2.
   *
   * Assert that the image that was the content of the field_image field of the
   * test article with node ID 1 was migrated successfully, and make sure that
   * its original alt and title properties from the image field are present.
   */
  protected function assertMedia2FieldValues() {
    $media2 = $this->container->get('entity_type.manager')->getStorage('media')->load($this->getDestinationIdFromSourceId(2));
    assert($media2 instanceof MediaInterface);
    $this->assertEquals('green.jpg', $media2->label());
    $this->assertEquals('image', $media2->bundle());

    // Check 'field media image' field.
    $source_field = $media2->getSource()->getConfiguration()['source_field'];
    $media2_image = $this->getReferencedEntities($media2, $source_field, 1);
    assert($media2_image[0] instanceof FileInterface);
    $this->assertEquals('2', $media2_image[0]->id());
    $this->assertEquals('2', $media2->$source_field->target_id);

    // Alt and title properties should be migrated to the corresponding media
    // image field and have to be editable on the UI.
    $this->assertEquals('Alternate text for green.jpg image', $media2->$source_field->alt);
    $this->assertEquals('Title text for green.jpg image', $media2->$source_field->title);
    $this->assertEquals('', $media2->field_media_integer->value);

    // The following fields should not be present.
    $this->assertFalse($media2->hasField('field_file_image_alt_text'));
    $this->assertFalse($media2->hasField('field_file_image_title_text'));

    // Author should be user 1.
    $this->assertEquals('1', $media2->getOwnerId());

    // Assert authored on date.
    $this->assertEquals('1587730322', $media2->getCreatedTime());

    // The image file should exist.
    $this->assertTrue(file_exists($media2_image[0]->getFileUri()));
  }

  /**
   * Assertions of media 3.
   *
   * Assert "red.jpeg" image media properties with alt, title and integer.
   */
  protected function assertMedia3FieldValues() {
    $media3 = $this->container->get('entity_type.manager')->getStorage('media')->load($this->getDestinationIdFromSourceId(3));
    assert($media3 instanceof MediaInterface);
    $this->assertEquals('red.jpeg', $media3->label());
    $this->assertEquals('image', $media3->bundle());

    // Check 'field media image' field.
    $source_field = $media3->getSource()->getConfiguration()['source_field'];
    $media3_image = $this->getReferencedEntities($media3, $source_field, 1);
    assert($media3_image[0] instanceof FileInterface);
    $this->assertEquals('3', $media3_image[0]->id());
    $this->assertEquals('3', $media3->$source_field->target_id);

    // Alt and title properties should be migrated to the corresponding media
    // image field and have to be editable on the UI.
    $this->assertEquals('Alternative text about red.jpeg', $media3->$source_field->alt);
    $this->assertEquals('Title copy for red.jpeg', $media3->$source_field->title);
    $this->assertEquals('333', $media3->field_media_integer->value);

    // The following fields should not be present.
    $this->assertFalse($media3->hasField('field_file_image_alt_text'));
    $this->assertFalse($media3->hasField('field_file_image_title_text'));

    // Author should be user 1.
    $this->assertEquals('1', $media3->getOwnerId());

    // Assert authored on date.
    $this->assertEquals('1587726037', $media3->getCreatedTime());

    // The image file should exist.
    $this->assertTrue(file_exists($media3_image[0]->getFileUri()));
  }

  /**
   * Assertions of media 4 (Youtube Apqd4ff0NRI â€“ 2019 Amsterdam Driesnote).
   */
  protected function assertMedia4FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(4);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);
    $source_field = $media->getSource()->getConfiguration()['source_field'];

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'remote_video']],
      'name' => [['value' => 'DrupalCon Amsterdam 2019: Keynote - Driesnote']],
      'uid' => [['target_id' => '1']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1587726087']],
      $source_field => [
        [
          'value' => 'https://www.youtube.com/watch?v=Apqd4ff0NRI',
        ],
      ],
    ], $this->getImportantEntityProperties($media));
  }

  /**
   * Assertions of media 5 (Vimeo 204517230 â€“ Responsive Images).
   */
  protected function assertMedia5FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(5);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);
    $source_field = $media->getSource()->getConfiguration()['source_field'];

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'remote_video']],
      'name' => [['value' => 'Responsive Images in Drupal 8']],
      'uid' => [['target_id' => '1']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1587730964']],
      $source_field => [
        [
          'value' => 'https://vimeo.com/204517230',
        ],
      ],
    ], $this->getImportantEntityProperties($media));
  }

  /**
   * Assertions of media 6 (LICENSE.txt).
   */
  protected function assertMedia6FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(6);
    $media6 = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media6 instanceof MediaInterface);
    $this->assertEquals('LICENSE.txt', $media6->label());
    $this->assertEquals('document', $media6->bundle());
    $source_field = $media6->getSource()->getConfiguration()['source_field'];

    // Check 'field media file' field.
    $media6_file = $this->getReferencedEntities($media6, $source_field, 1);
    assert($media6_file[0] instanceof FileInterface);
    $this->assertEquals('6', $media6_file[0]->id());
    $this->assertEquals('6', $media6->$source_field->target_id);

    // Author should be user 1.
    $this->assertEquals('1', $media6->getOwnerId());

    // Assert authored on date.
    $this->assertEquals('1587731111', $media6->getCreatedTime());

    // The file should exist.
    $this->assertTrue(file_exists($media6_file[0]->getFileUri()));
  }

  /**
   * Assertions of media 7.
   *
   * "yellow.jpg"'s' alt and title properties should be empty, as well as its
   * integer field.
   */
  protected function assertMedia7FieldValues() {
    $media7 = $this->container->get('entity_type.manager')->getStorage('media')->load($this->getDestinationIdFromSourceId(7));
    assert($media7 instanceof MediaInterface);
    $this->assertEquals('yellow.jpg', $media7->label());
    $this->assertEquals('image', $media7->bundle());
    $source_field = $media7->getSource()->getConfiguration()['source_field'];

    // Check 'field media image' field.
    $media7_image = $this->getReferencedEntities($media7, $source_field, 1);
    assert($media7_image[0] instanceof FileInterface);
    $this->assertEquals('7', $media7_image[0]->id());
    $this->assertEquals('7', $media7->$source_field->target_id);

    // Alt, title and integer must be empty.
    $this->assertEquals('', $media7->$source_field->alt);
    $this->assertEquals('', $media7->$source_field->title);
    $this->assertEquals('', $media7->field_media_integer->value);

    // The following fields should not be present.
    $this->assertFalse($media7->hasField('field_file_image_alt_text'));
    $this->assertFalse($media7->hasField('field_file_image_title_text'));

    // Author should be user 2.
    $this->assertEquals('2', $media7->getOwnerId());

    // Assert authored on date.
    $this->assertEquals('1588600435', $media7->getCreatedTime());

    // The image file should exist.
    $this->assertTrue(file_exists($media7_image[0]->getFileUri()));
  }

  /**
   * Assertions of media 8 ("video.webm").
   */
  protected function assertMedia8FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(8);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);
    $source_field = $media->getSource()->getConfiguration()['source_field'];

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'video']],
      'name' => [['value' => 'video.webm']],
      'uid' => [['target_id' => '2']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1594037784']],
      $source_field => [
        [
          'target_id' => '8',
          'display' => NULL,
          'description' => NULL,
        ],
      ],
    ], $this->getImportantEntityProperties($media));

    // Check the media field.
    $media_field = $this->getReferencedEntities($media, $source_field, 1);
    assert($media_field[0] instanceof FileInterface);
    // The referenced file should exist.
    $this->assertTrue(file_exists($media_field[0]->getFileUri()));
  }

  /**
   * Assertions of media 9 ("video.mp4").
   */
  protected function assertMedia9FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(9);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);
    $source_field = $media->getSource()->getConfiguration()['source_field'];

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'video']],
      'name' => [['value' => 'video.mp4']],
      'uid' => [['target_id' => '2']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1594117700']],
      $source_field => [
        [
          'target_id' => '9',
          'display' => NULL,
          'description' => NULL,
        ],
      ],
    ], $this->getImportantEntityProperties($media));

    // Check the media field.
    $media_field = $this->getReferencedEntities($media, $source_field, 1);
    assert($media_field[0] instanceof FileInterface);
    // The referenced file should exist.
    $this->assertTrue(file_exists($media_field[0]->getFileUri()));
  }

  /**
   * Assertions of media 10 ("yellow.webp").
   */
  protected function assertMedia10FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(10);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);
    $source_field = $media->getSource()->getConfiguration()['source_field'];

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'image']],
      'name' => [['value' => 'yellow.webp']],
      'uid' => [['target_id' => '2']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1594191582']],
      $source_field => [
        [
          'target_id' => '10',
          'alt' => 'Alternative text about yellow.webp',
          'title' => 'Title copy for yellow.webp',
          'width' => '640',
          'height' => '400',
        ],
      ],
      'field_media_integer' => [],
    ], $this->getImportantEntityProperties($media));

    // Check the media field.
    $media_field = $this->getReferencedEntities($media, $source_field, 1);
    assert($media_field[0] instanceof FileInterface);
    // The referenced file should exist.
    $this->assertTrue(file_exists($media_field[0]->getFileUri()));
  }

  /**
   * Assertions of media 11 ("audio.m4a").
   */
  protected function assertMedia11FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(11);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);
    $source_field = $media->getSource()->getConfiguration()['source_field'];

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'audio']],
      'name' => [['value' => 'audio.m4a']],
      'uid' => [['target_id' => '1']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1594193701']],
      $source_field => [
        [
          'target_id' => '11',
          'display' => NULL,
          'description' => NULL,
        ],
      ],
    ], $this->getImportantEntityProperties($media));

    // Check the media field.
    $media_field = $this->getReferencedEntities($media, $source_field, 1);
    assert($media_field[0] instanceof FileInterface);
    // The referenced file should exist.
    $this->assertTrue(file_exists($media_field[0]->getFileUri()));
  }

  /**
   * Assertions of media 12 ("document.odt").
   */
  protected function assertMedia12FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(12);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);
    $source_field = $media->getSource()->getConfiguration()['source_field'];

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'document']],
      'name' => [['value' => 'document.odt']],
      'uid' => [['target_id' => '2']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1594201103']],
      $source_field => [
        [
          'target_id' => '12',
          'display' => NULL,
          'description' => NULL,
        ],
      ],
    ], $this->getImportantEntityProperties($media));

    // Check the media field.
    $media_field = $this->getReferencedEntities($media, $source_field, 1);
    assert($media_field[0] instanceof FileInterface);
    // The referenced file should exist.
    $this->assertTrue(file_exists($media_field[0]->getFileUri()));
  }

  /**
   * Assertions of media 101 ("yellow_0.webp").
   */
  protected function assertMedia101FieldValues() {
    $dest_id = $this->getDestinationIdFromSourceId(101);
    $media = $this->container->get('entity_type.manager')->getStorage('media')->load($dest_id);
    assert($media instanceof MediaInterface);
    $source_field = $media->getSource()->getConfiguration()['source_field'];

    $this->assertEquals([
      'mid' => [['value' => $dest_id]],
      'bundle' => [['target_id' => 'image']],
      'name' => [['value' => 'another-yellow.webp']],
      'uid' => [['target_id' => '1']],
      'status' => [['value' => '1']],
      'created' => [['value' => '1600000000']],
      $source_field => [
        [
          'target_id' => '101',
          'alt' => NULL,
          'title' => NULL,
          'width' => '640',
          'height' => '400',
        ],
      ],
      'field_media_integer' => [],
    ], $this->getImportantEntityProperties($media));

    // Check the media field.
    $media_field = $this->getReferencedEntities($media, $source_field, 1);
    assert($media_field[0] instanceof FileInterface);
    // The referenced file should exist.
    $this->assertTrue(file_exists($media_field[0]->getFileUri()));
  }

  /**
   * Assertions of node 1.
   */
  protected function assertNode1FieldValues(array $expected_node1_embed_attributes = []) {
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load(1);
    assert($node instanceof NodeInterface);

    // Ignore body field.
    $important_properties = $this->getImportantEntityProperties($node);
    unset($important_properties['body']);

    $this->assertEquals([
      'nid' => [['value' => 1]],
      'type' => [['target_id' => 'article']],
      'status' => [['value' => 1]],
      'uid' => [['target_id' => 1]],
      'title' => [['value' => 'Article with embed image media']],
      'created' => [['value' => 1587730322]],
      'changed' => [['value' => 1587730609]],
      'promote' => [['value' => 1]],
      'sticky' => [['value' => 0]],
      'field_image' => [['target_id' => $this->getDestinationIdFromSourceId(2)]],
      'field_media' => [
        ['target_id' => $this->getDestinationIdFromSourceId(3)],
        ['target_id' => $this->getDestinationIdFromSourceId(4)],
      ],
    ], $important_properties);

    // Test that the image and file fields are referencing media entities.
    $media_fields = [
      'field_image' => 1,
      'field_media' => 2,
    ];
    foreach ($media_fields as $field_name => $expected_count) {
      $referred_entities = $this->getReferencedEntities($node, $field_name, $expected_count);
      assert($referred_entities[0] instanceof MediaInterface);
    }

    if (!empty($expected_node1_embed_attributes)) {
      $node_body_text = preg_replace('/\s+/', ' ', $node->body->value);
      $this->assertEmbedTokenHtmlTags($node_body_text, $expected_node1_embed_attributes);
    }
  }

  /**
   * Tests article's "field_image" media reference field's allowed media types.
   */
  protected function assertArticleImageFieldsAllowedTypes() {
    $this->assertMediaFieldsAllowedTypes('node', 'article', 'field_image', ['image']);
  }

  /**
   * Tests article's "field_media" media reference field's allowed media types.
   */
  protected function assertArticleMediaFieldsAllowedTypes() {
    $entity_type_manager = $this->container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);
    $media_types = array_keys($entity_type_manager->getStorage('media_type')->loadMultiple());

    $this->assertMediaFieldsAllowedTypes('node', 'article', 'field_media', $media_types);
  }

  /**
   * Tests the migrated filter formats.
   */
  protected function assertFilterFormats() {
    $entity_type_manager = $this->container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);
    $filter_formats = $entity_type_manager->getStorage('filter_format')->loadMultiple();

    $destination_filter_plugin = MediaMigration::getEmbedTokenDestinationFilterPlugin();

    $embed_filter = $destination_filter_plugin === MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED
      ? [
        'id' => $destination_filter_plugin,
        'provider' => 'entity_embed',
        'status' => TRUE,
        'weight' => 3,
        'settings' => [],
      ]
      : [
        'id' => $destination_filter_plugin,
        'provider' => 'media',
        'status' => TRUE,
        'weight' => 3,
        'settings' => [
          'default_view_mode' => 'default',
          'allowed_view_modes' => [],
          'allowed_media_types' => [],
        ],
      ];

    $allowed_html = $destination_filter_plugin === MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED
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
            'weight' => -1,
            'settings' => [],
          ],
          'filter_html' => [
            'id' => 'filter_html',
            'provider' => 'filter',
            'status' => TRUE,
            'weight' => -4,
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
            'weight' => 0,
            'settings' => [],
          ],
          'filter_url' => [
            'id' => 'filter_url',
            'provider' => 'filter',
            'status' => TRUE,
            'weight' => -2,
            'settings' => ['filter_url_length' => 72],
          ],
          $destination_filter_plugin => $embed_filter,
          'filter_align' => [
            'id' => 'filter_align',
            'provider' => 'filter',
            'status' => TRUE,
            'weight' => 1,
            'settings' => [],
          ],
          'filter_caption' => [
            'id' => 'filter_caption',
            'provider' => 'filter',
            'status' => TRUE,
            'weight' => 2,
            'settings' => [],
          ],
        ],
      ],
      $this->getImportantEntityProperties($filter_formats['filtered_html'])
    );
  }

}
