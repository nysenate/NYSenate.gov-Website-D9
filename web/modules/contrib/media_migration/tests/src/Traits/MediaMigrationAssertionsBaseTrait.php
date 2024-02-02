<?php

namespace Drupal\Tests\media_migration\Traits;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Variable;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\field\FieldConfigInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaTypeInterface;
use Drupal\media_migration\MediaMigration;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Trait for media migration tests.
 */
trait MediaMigrationAssertionsBaseTrait {

  use MediaMigrationTestTrait;

  /**
   * List of media properties whose value shouldn't have to be checked.
   *
   * @var string[]
   */
  protected $mediaUnconcernedProperties = [
    'uuid',
    'vid',
    'langcode',
    'revision_created',
    'revision_user',
    'revision_log_message',
    'thumbnail',
    'default_langcode',
    'revision_default',
    'revision_translation_affected',
    'path',
    // Changed gets updated for e.g. change tracking.
    'changed',
  ];

  /**
   * List of node properties whose value shouldn't have to be checked.
   *
   * @var string[]
   */
  protected $nodeUnconcernedProperties = [
    'uuid',
    'vid',
    'langcode',
    'revision_timestamp',
    'revision_uid',
    'revision_log',
    'default_langcode',
    'revision_default',
    'revision_translation_affected',
    'path',
    'comment_node_article',
    'field_tags',
  ];

  /**
   * List of migration conf properties whose value shouldn't have to be checked.
   *
   * @var string[]
   */
  protected $migrationUnconcernedProperties = [
    'uuid',
    'label',
    'langcode',
    'status',
    'class',
    'field_plugin_method',
    'cck_plugin_method',
  ];

  /**
   * Tests the allowed media types of a media reference field.
   *
   * @param string $entity_type_id
   *   The entity type ID of the entity that's field is checked.
   * @param string $bundle
   *   The bundle of the entity where the field is present.
   * @param string $field_name
   *   The name of the media reference field to check.
   * @param string[] $expected_media_types
   *   The list of the expected allowed media types.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function assertMediaFieldsAllowedTypes(string $entity_type_id, string $bundle, string $field_name, array $expected_media_types) {
    $entity_type_manager = $this->container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);
    $field_config_id = implode('.', [$entity_type_id, $bundle, $field_name]);
    $field_media_config = $entity_type_manager->getStorage('field_config')->load($field_config_id);
    $this->assertInstanceOf(FieldConfigInterface::class, $field_media_config, sprintf("The '%s' field of '%s' with type '%s' does not exist.", $field_name, $entity_type_id, $bundle));
    $this->assertSame('entity_reference', $field_media_config->getType(), sprintf("The '%s' field of '%s' with type '%s' is not an entity reference field.", $field_name, $entity_type_id, $bundle));
    $handler_settings = $field_media_config->getSetting('handler_settings');
    $allowed_media_types = array_values($handler_settings['target_bundles']);
    sort($expected_media_types);
    sort($allowed_media_types);
    // @todo test the target entity type ID (=== media) as well.
    $this->assertEquals($expected_media_types, $allowed_media_types, sprintf("The '%s' field of '%s' with type '%s' doesn't allows referring the expected media bundles.", $field_name, $entity_type_id, $bundle));
  }

  /**
   * Assert that embed HTML tags exist in the given text.
   *
   * Tests that the given set of embed HTML tags and their attributes exists in
   * a text field.
   *
   * @param string $body_text
   *   The content of the text field to check.
   * @param array $embed_code_html_properties
   *   Array of HTML entity attributes to search for in the given text.
   */
  protected function assertEmbedTokenHtmlTags($body_text, array $embed_code_html_properties) {
    $body_text_dom = Html::load($body_text);
    $embed_tag = MediaMigration::getEmbedTokenDestinationFilterPlugin() === MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_MEDIA_EMBED
      ? 'drupal-media' : 'drupal-entity';
    $embed_entity_html_tag_list = $body_text_dom->getElementsByTagName($embed_tag);
    $expected_parsed_embed_tags = [];

    foreach ($embed_entity_html_tag_list as $tag_index => $embed_tag) {
      assert($embed_tag instanceof \DomNode);
      $actual_embed_tags[$tag_index] = [];
      if ($embed_tag->hasAttributes() && !is_null($embed_tag->attributes)) {
        foreach ($embed_tag->attributes as $attr) {
          $actual_embed_tags[$tag_index][$attr->name] = $attr->value;
        }
      }
    }

    // Asserting that the expected attributes exist or even that they have the
    // expected values.
    foreach ($embed_code_html_properties as $delta => $embed_tag_attributes) {
      $embed_tag_domnode = $embed_entity_html_tag_list->item($delta);
      assert($embed_tag_domnode instanceof \DOMNode);

      foreach ($embed_tag_attributes as $attribute_name => $attribute_value) {
        // If the value is TRUE, we only check whether the attribute exists.
        if ($attribute_value === TRUE) {
          $actual_parsed_embed_tags[$delta][$attribute_name] = TRUE;
          $expected_parsed_embed_tags[$delta][$attribute_name] = !empty($embed_tag_domnode->getAttribute($attribute_name));
        }
        else {
          $attribute_value = $attribute_name === 'data-entity-id'
            ? $this->getDestinationIdFromSourceId($attribute_value)
            : $attribute_value;
          $actual_parsed_embed_tags[$delta][$attribute_name] = $attribute_value;
          $expected_parsed_embed_tags[$delta][$attribute_name] = $embed_tag_domnode->getAttribute($attribute_name);
        }
      }
    }

    $this->assertEquals($expected_parsed_embed_tags, $actual_parsed_embed_tags, sprintf("Expected embed tags are different than the actual ones. These tags are migrated: '%s'", Variable::export($actual_embed_tags)));

    // Ensure that we have the expected number of embed tags.
    $this->assertCount(count($embed_code_html_properties), $embed_entity_html_tag_list, sprintf("Different number of embed tags were migrated than expected. These tags are migrated: '%s'", Variable::export($actual_embed_tags)));
  }

  /**
   * Tests media audio's default form and view mode configuration.
   */
  protected function assertMediaAudioDisplayModes() {
    $source_field_name = $this->getFinalSourceFieldName('audio');
    $entity_form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load(implode('.', [
        'media',
        'audio',
        'default',
      ]));
    $this->assertEquals([
      'status' => TRUE,
      'id' => 'media.audio.default',
      'targetEntityType' => 'media',
      'bundle' => 'audio',
      'mode' => 'default',
      'content' => [
        'created' => [
          'type' => 'datetime_timestamp',
          'weight' => 10,
          'region' => 'content',
          'settings' => [],
          'third_party_settings' => [],
        ],
        $source_field_name => [
          'type' => 'file_generic',
          'weight' => 0,
          'settings' => [
            'progress_indicator' => 'throbber',
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'name' => [
          'type' => 'string_textfield',
          'weight' => -5,
          'settings' => [
            'size' => 60,
            'placeholder' => '',
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'status' => [
          'type' => 'boolean_checkbox',
          'weight' => 100,
          'settings' => [
            'display_label' => TRUE,
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'uid' => [
          'type' => 'entity_reference_autocomplete',
          'weight' => 5,
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => 60,
            'placeholder' => '',
            'match_limit' => 10,
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
      ],
      'hidden' => [],
    ], $this->getImportantEntityProperties($entity_form_display));

    $entity_view_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load(implode('.', [
        'media',
        'audio',
        'default',
      ]));
    $this->assertEquals([
      'status' => TRUE,
      'id' => 'media.audio.default',
      'targetEntityType' => 'media',
      'bundle' => 'audio',
      'mode' => 'default',
      'content' => [
        $source_field_name => [
          'type' => 'file_audio',
          'weight' => 0,
          'settings' => [
            'controls' => TRUE,
            'autoplay' => FALSE,
            'loop' => FALSE,
            'multiple_file_display_type' => 'tags',
          ],
          'third_party_settings' => [],
          'region' => 'content',
          'label' => 'visually_hidden',
        ],
      ],
      'hidden' => [
        'created' => TRUE,
        'name' => TRUE,
        'thumbnail' => TRUE,
        'uid' => TRUE,
      ],
    ], $this->getImportantEntityProperties($entity_view_display));
  }

  /**
   * Tests media documents's default form and view mode configuration.
   */
  protected function assertMediaDocumentDisplayModes() {
    $source_field_name = $this->getFinalSourceFieldName('document');
    $entity_form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load(implode('.', [
        'media',
        'document',
        'default',
      ]));
    $this->assertEquals([
      'status' => TRUE,
      'id' => 'media.document.default',
      'targetEntityType' => 'media',
      'bundle' => 'document',
      'mode' => 'default',
      'content' => [
        'created' => [
          'type' => 'datetime_timestamp',
          'weight' => 10,
          'region' => 'content',
          'settings' => [],
          'third_party_settings' => [],
        ],
        $source_field_name => [
          'type' => 'file_generic',
          'weight' => 0,
          'settings' => [
            'progress_indicator' => 'throbber',
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'name' => [
          'type' => 'string_textfield',
          'weight' => -5,
          'settings' => [
            'size' => 60,
            'placeholder' => '',
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'status' => [
          'type' => 'boolean_checkbox',
          'weight' => 100,
          'settings' => [
            'display_label' => TRUE,
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'uid' => [
          'type' => 'entity_reference_autocomplete',
          'weight' => 5,
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => 60,
            'placeholder' => '',
            'match_limit' => 10,
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
      ],
      'hidden' => [],
    ], $this->getImportantEntityProperties($entity_form_display));

    $entity_view_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load(implode('.', [
        'media',
        'document',
        'default',
      ]));
    $this->assertEquals([
      'status' => TRUE,
      'id' => 'media.document.default',
      'targetEntityType' => 'media',
      'bundle' => 'document',
      'mode' => 'default',
      'content' => [
        $source_field_name => [
          'type' => 'file_default',
          'weight' => 0,
          'settings' => [
            'use_description_as_link_text' => TRUE,
          ],
          'third_party_settings' => [],
          'region' => 'content',
          'label' => 'visually_hidden',
        ],
      ],
      'hidden' => [
        'created' => TRUE,
        'name' => TRUE,
        'thumbnail' => TRUE,
        'uid' => TRUE,
      ],
    ], $this->getImportantEntityProperties($entity_view_display));
  }

  /**
   * Tests media image's default form and view mode configuration.
   *
   * @param bool $with_integer_field
   *   If this is a image migrated from the media source DB fixture, the image
   *   bundle will have an additional image field.
   */
  protected function assertMediaImageDisplayModes(bool $with_integer_field = FALSE) {
    $source_field_name = $this->getFinalSourceFieldName('image');
    $entity_form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load(implode('.', [
        'media',
        'image',
        'default',
      ]));
    $form_display_expectation = [
      'status' => TRUE,
      'id' => 'media.image.default',
      'targetEntityType' => 'media',
      'bundle' => 'image',
      'mode' => 'default',
      'content' => [
        'created' => [
          'type' => 'datetime_timestamp',
          'weight' => 10,
          'region' => 'content',
          'settings' => [],
          'third_party_settings' => [],
        ],
        $source_field_name => [
          'type' => 'image_image',
          'weight' => 0,
          'settings' => [
            'progress_indicator' => 'throbber',
            'preview_image_style' => 'thumbnail',
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'name' => [
          'type' => 'string_textfield',
          'weight' => -5,
          'settings' => [
            'size' => 60,
            'placeholder' => '',
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'status' => [
          'type' => 'boolean_checkbox',
          'weight' => 100,
          'settings' => [
            'display_label' => TRUE,
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'uid' => [
          'type' => 'entity_reference_autocomplete',
          'weight' => 5,
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => 60,
            'placeholder' => '',
            'match_limit' => 10,
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
      ],
      'hidden' => [],
    ];
    if ($with_integer_field) {
      $form_display_expectation['content']['field_media_integer'] = [
        'type' => 'number',
        'weight' => -2,
        'settings' => [
          'placeholder' => '',
        ],
        'third_party_settings' => [],
        'region' => 'content',
      ];
    }
    $this->assertEquals($form_display_expectation, $this->getImportantEntityProperties($entity_form_display));

    $entity_view_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load(implode('.', [
        'media',
        'image',
        'default',
      ]));
    $view_display_expectation = [
      'status' => TRUE,
      'id' => 'media.image.default',
      'targetEntityType' => 'media',
      'bundle' => 'image',
      'mode' => 'default',
      'content' => [
        $source_field_name => [
          'type' => 'image',
          'weight' => 0,
          'settings' => [
            'image_style' => 'large',
            'image_link' => '',
          ],
          'third_party_settings' => [],
          'region' => 'content',
          'label' => 'visually_hidden',
        ],
      ],
      'hidden' => [
        'created' => TRUE,
        'name' => TRUE,
        'thumbnail' => TRUE,
        'uid' => TRUE,
      ],
    ];
    if ($with_integer_field) {
      $view_display_expectation['content']['field_media_integer'] = [
        'type' => 'number_integer',
        'weight' => 2,
        'settings' => [
          'thousand_separator' => '',
          'prefix_suffix' => TRUE,
        ],
        'third_party_settings' => [],
        'region' => 'content',
        'label' => 'above',
      ];
    }

    if (version_compare(\Drupal::VERSION, '9.4.0-dev', 'ge')) {
      $view_display_expectation['content'][$source_field_name]['settings']['image_loading'] = [
        'attribute' => 'lazy',
      ];
    }

    $this->assertEquals($view_display_expectation, $this->getImportantEntityProperties($entity_view_display));
  }

  /**
   * Tests media video's default form and view mode configuration.
   */
  protected function assertMediaVideoDisplayModes() {
    $source_field_name = $this->getFinalSourceFieldName('video');
    $entity_form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load(implode('.', [
        'media',
        'video',
        'default',
      ]));
    $this->assertEquals([
      'status' => TRUE,
      'id' => 'media.video.default',
      'targetEntityType' => 'media',
      'bundle' => 'video',
      'mode' => 'default',
      'content' => [
        'created' => [
          'type' => 'datetime_timestamp',
          'weight' => 10,
          'region' => 'content',
          'settings' => [],
          'third_party_settings' => [],
        ],
        $source_field_name => [
          'type' => 'file_generic',
          'weight' => 0,
          'settings' => [
            'progress_indicator' => 'throbber',
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'name' => [
          'type' => 'string_textfield',
          'weight' => -5,
          'settings' => [
            'size' => 60,
            'placeholder' => '',
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'status' => [
          'type' => 'boolean_checkbox',
          'weight' => 100,
          'settings' => [
            'display_label' => TRUE,
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'uid' => [
          'type' => 'entity_reference_autocomplete',
          'weight' => 5,
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => 60,
            'placeholder' => '',
            'match_limit' => 10,
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
      ],
      'hidden' => [],
    ], $this->getImportantEntityProperties($entity_form_display));

    $entity_view_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load(implode('.', [
        'media',
        'video',
        'default',
      ]));
    $this->assertEquals([
      'status' => TRUE,
      'id' => 'media.video.default',
      'targetEntityType' => 'media',
      'bundle' => 'video',
      'mode' => 'default',
      'content' => [
        $source_field_name => [
          'type' => 'file_video',
          'weight' => 0,
          'settings' => [
            'muted' => FALSE,
            'width' => 640,
            'height' => 480,
            'controls' => TRUE,
            'autoplay' => FALSE,
            'loop' => FALSE,
            'multiple_file_display_type' => 'tags',
          ],
          'third_party_settings' => [],
          'region' => 'content',
          'label' => 'visually_hidden',
        ],
      ],
      'hidden' => [
        'created' => TRUE,
        'name' => TRUE,
        'thumbnail' => TRUE,
        'uid' => TRUE,
      ],
    ], $this->getImportantEntityProperties($entity_view_display));
  }

  /**
   * Tests media remote_video's default form and view mode configuration.
   */
  protected function assertMediaRemoteVideoDisplayModes() {
    $source_field_name = $this->getFinalSourceFieldName('remote_video');
    $entity_form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load(implode('.', [
        'media',
        'remote_video',
        'default',
      ]));
    // Unfortunately, MediaSourceInterface::prepareFormDisplay() is only invoked
    // on Drupal UI and in MediaTypeCreationTrait we use in our tests. If media
    // displays are created somehow else (API, migration), then it isn't called.
    // This doesn't seem to cause issues with other media sources, but the
    // oembed plugin explicitly hides the name field with this method.
    // This means that we get a different result if we migrate into pre-existing
    // media types than if we migrate without existing media types.
    $actual_entity_form_display = $this->getImportantEntityProperties($entity_form_display);
    unset($actual_entity_form_display['content']['name']);
    unset($actual_entity_form_display['hidden']['name']);
    $this->assertEquals([
      'status' => TRUE,
      'id' => 'media.remote_video.default',
      'targetEntityType' => 'media',
      'bundle' => 'remote_video',
      'mode' => 'default',
      'content' => [
        'created' => [
          'type' => 'datetime_timestamp',
          'weight' => 10,
          'region' => 'content',
          'settings' => [],
          'third_party_settings' => [],
        ],
        $source_field_name => [
          'type' => 'string_textfield',
          'weight' => 0,
          'settings' => [
            'size' => 60,
            'placeholder' => '',
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'status' => [
          'type' => 'boolean_checkbox',
          'weight' => 100,
          'settings' => [
            'display_label' => TRUE,
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'uid' => [
          'type' => 'entity_reference_autocomplete',
          'weight' => 5,
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => 60,
            'placeholder' => '',
            'match_limit' => 10,
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
      ],
      'hidden' => [],
    ], $actual_entity_form_display);

    $entity_view_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load(implode('.', [
        'media',
        'remote_video',
        'default',
      ]));
    $settings = [
      'max_width' => 0,
      'max_height' => 0,
    ];

    // Lazy loading was added to Drupal 10.1.
    if (version_compare($this->coreMajorMinorVersion(), '10.1', '>=')) {
      $settings['loading'] = ['attribute' => 'lazy'];
    }
    $this->assertEquals([
      'status' => TRUE,
      'id' => 'media.remote_video.default',
      'targetEntityType' => 'media',
      'bundle' => 'remote_video',
      'mode' => 'default',
      'content' => [
        $source_field_name => [
          'type' => 'oembed',
          'weight' => 0,
          'settings' => $settings,
          'third_party_settings' => [],
          'region' => 'content',
          'label' => 'visually_hidden',
        ],
      ],
      'hidden' => [
        'created' => TRUE,
        'name' => TRUE,
        'thumbnail' => TRUE,
        'uid' => TRUE,
      ],
    ], $this->getImportantEntityProperties($entity_view_display));
  }

  /**
   * Tests the processes of the body field of the given article migration.
   *
   * @param string $article_node_migration_id
   *   The ID of the node migration plugin instance.
   * @param array|string|null $body_migration_process
   *   The expected process(es) of the body field.
   *
   * @throws \PHPUnit\Framework\ExpectationFailedException
   */
  protected function assertArticleBodyFieldMigrationProcesses(string $article_node_migration_id, $body_migration_process = NULL) {
    $migration_plugin_manager = $this->container->get('plugin.manager.migration');
    assert($migration_plugin_manager instanceof MigrationPluginManagerInterface);
    try {
      $article_migration = $migration_plugin_manager->getDefinition($article_node_migration_id);
    }
    catch (PluginException $e) {
      throw new ExpectationFailedException(sprintf("The article node migration with the given ID '%s' does not exist.", $article_node_migration_id));
    }

    $migration_process = $body_migration_process ?? [
      [
        'plugin' => 'get',
        'source' => 'body',
      ],
      [
        'plugin' => 'media_wysiwyg_filter',
      ],
      [
        'plugin' => 'img_tag_to_embed',
      ],
    ];

    $this->assertEquals(
      $migration_process,
      $article_migration['process']['body'],
      sprintf(
        "Article node's body field has different migration processes than expected: %s",
        Variable::export($article_migration['process']['body'])
      )
    );
  }

  /**
   * Get the referred entities.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The parent entity.
   * @param string $field_name
   *   The name of the entity reference field.
   * @param int $expected_count
   *   The expected number of the referenced entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entity objects keyed by field item deltas.
   */
  protected function getReferencedEntities(ContentEntityInterface $entity, $field_name, int $expected_count) {
    $entity_field = $entity->hasField($field_name) ?
      $entity->get($field_name) :
      NULL;
    $this->assertInstanceOf(EntityReferenceFieldItemList::class, $entity_field);
    $entity_field_entities = $entity_field->referencedEntities();
    $this->assertCount($expected_count, $entity_field_entities);

    return $entity_field_entities;
  }

  /**
   * Filters out unconcerned properties from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity instance.
   *
   * @return array
   *   The important entity property values as array.
   */
  protected function getImportantEntityProperties(EntityInterface $entity) {
    $entity_type_id = $entity->getEntityTypeId();
    $property_filter_preset_property = "{$entity_type_id}UnconcernedProperties";
    $entity_array = $entity->toArray();
    $unconcerned_properties = property_exists(get_class($this), $property_filter_preset_property)
      ? $this->$property_filter_preset_property
      : [
        'uuid',
        'langcode',
        'dependencies',
        '_core',
      ];

    foreach ($unconcerned_properties as $item) {
      unset($entity_array[$item]);
    }

    return $entity_array;
  }

  /**
   * Returns the source field name of the specified media type.
   *
   * @param string $media_type_id
   *   The media type ID.
   *
   * @return string
   *   The name of the source field.
   */
  protected function getFinalSourceFieldName(string $media_type_id): string {
    $type = MediaType::load($media_type_id);
    assert($type instanceof MediaTypeInterface);
    return $type->getSource()->getConfiguration()['source_field'];
  }

  /**
   * Returns the ID of the migrated media entity based on its source identifier.
   *
   * @param string|int $source_id
   *   The media entity's source ID.
   *
   * @return string|null
   *   The ID of the migrated media entity, or NULL if it cannot be found.
   */
  protected function getDestinationIdFromSourceId($source_id): ?string {
    $media_migrations = \Drupal::service('plugin.manager.migration')->createInstancesByTag(MediaMigration::MIGRATION_TAG_CONTENT);
    $lookup = \Drupal::service('migrate.lookup');
    assert($lookup instanceof MigrateLookupInterface);
    foreach (array_keys($media_migrations) as $media_migration_id) {
      $destination_ids = $lookup->lookup([$media_migration_id], [$source_id]);
      if (!empty($destination_ids) && isset(reset($destination_ids)['mid'])) {
        return reset($destination_ids)['mid'];
      }
    }
    return NULL;
  }

}
