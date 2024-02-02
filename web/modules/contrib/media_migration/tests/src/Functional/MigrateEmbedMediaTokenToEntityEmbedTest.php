<?php

namespace Drupal\Tests\media_migration\Functional;

/**
 * Tests the transformation of embed image media tokens to entity embed.
 *
 * @group media_migration
 *
 * @group legacy
 */
class MigrateEmbedMediaTokenToEntityEmbedTest extends MigrateEmbedMediaTokenTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_embed',
  ];

  /**
   * {@inheritdoc}
   */
  protected $embedMediaCssSelector = '.field--name-body.field--type-text-with-summary [data-entity-type="media"] .field--name-field-media-image';

  /**
   * {@inheritdoc}
   */
  protected function getExpectedEntities() {
    $expected_entities = parent::getExpectedEntities();
    $expected_entities['embed_button'] = ['media' => 'Media'];
    return $expected_entities;
  }

  /**
   * Tests the result of Media Migration's embed media token transform.
   *
   * @param string $reference_method
   *   The method of how embed media is referenced.
   * @param string|null $destination_format_plugin_id
   *   The embed token transformation's destination format plugin ID to write
   *   into settings.php, or NULL.
   * @param string|bool[][] $expected_embed_html_properties
   *   The expected attributes of the embed entity HTML tags, keyed by their
   *   delta (from their order in node with ID '1').
   * @param bool $preexisting_media_types
   *   Whether media types should be present before the migration.
   *
   * @dataProvider providerEntityEmbedTransform
   */
  public function testMediaTokenToEntityEmbedTransform(string $reference_method, $destination_format_plugin_id, array $expected_embed_html_properties, bool $preexisting_media_types) {
    // Delete preexisting media types.
    $media_types = $this->container->get('entity_type.manager')
      ->getStorage('media_type')
      ->loadMultiple();
    foreach ($media_types as $media_type) {
      $media_type->delete();
    }

    if ($preexisting_media_types) {
      $this->createStandardMediaTypes(TRUE);
    }

    // Set the reference method.
    $this->setEmbedMediaReferenceMethod($reference_method);

    // Set the token transformation's destination filter plugin ID.
    $this->setEmbedTokenDestinationFilterPlugin($destination_format_plugin_id);

    // Delete preexisting embed_button config entities.
    // Entity Embed module has an optional 'node' embed button that is installed
    // when node module is enabled. We don't want to depend on Entity Embed's
    // default embed_button config entities since those can be changed in the
    // future. But we need to assert that the 'media' embed button (that we
    // migrate conditionally) exists after the migration.
    if ($storage = $this->getEntityStorage('embed_button')) {
      foreach ($storage->loadMultiple() as $embed_button) {
        $embed_button->delete();
      }
    }

    // Run the migration.
    $this->assertMigrateUpgradeViaUi();
    $this->assertMediaMigrationResults();
    $this->assertMediaTokenTransform($expected_embed_html_properties);
    $this->assertNode1FieldValues($expected_embed_html_properties);
  }

  /**
   * Data provider for ::testMediaTokenToEntityEmbedTransform().
   *
   * @return array
   *   The test cases.
   */
  public function providerEntityEmbedTransform() {
    $default_attributes = [
      'data-embed-button' => 'media',
      'data-entity-embed-display' => 'view_mode:media.wysiwyg',
      'data-entity-type' => 'media',
      'alt' => 'Different alternative text about blue.png in the test article',
      'title' => 'Different title copy for blue.png in the test article',
      'data-align' => 'center',
    ];

    $test_cases = [
      '"ID" reference method, non-defined destination format plugin ID, preexisting media types' => [
        'reference_method' => 'id',
        'destination_filter' => NULL,
        'expected_embed_html_attributes' => [
          0 => ['data-entity-id' => '1'] + $default_attributes,
          1 => [
            'data-entity-type' => 'media',
            'data-entity-id' => '7',
            'data-entity-embed-display' => 'view_mode:media.full',
            'data-embed-button' => 'media',
            'alt' => 'A yellow image',
            'title' => 'This is a yellow image',
          ],
        ],
        'Preexisting media types' => TRUE,
      ],
      '"ID" reference method, "entity_embed" destination format plugin ID, preexisting media types' => [
        'reference_method' => 'id',
        'destination_filter' => 'entity_embed',
        'expected_embed_html_attributes' => [
          0 => ['data-entity-id' => '1'] + $default_attributes,
          1 => [
            'data-entity-type' => 'media',
            'data-entity-id' => '7',
            'data-entity-embed-display' => 'view_mode:media.full',
            'data-embed-button' => 'media',
            'alt' => 'A yellow image',
            'title' => 'This is a yellow image',
          ],
        ],
        'Preexisting media types' => TRUE,
      ],
      '"UUID" reference method, non-defined destination format plugin ID, preexisting media types' => [
        'reference_method' => 'uuid',
        'destination_filter' => NULL,
        'expected_embed_html_attributes' => [
          0 => ['data-entity-uuid' => TRUE] + $default_attributes,
          1 => [
            'data-entity-type' => 'media',
            'data-entity-uuid' => TRUE,
            'data-entity-embed-display' => 'view_mode:media.full',
            'data-embed-button' => 'media',
            'alt' => 'A yellow image',
            'title' => 'This is a yellow image',
          ],
        ],
        'Preexisting media types' => TRUE,
      ],
      '"UUID" reference method, "entity_embed" destination format plugin ID, preexisting media types' => [
        'reference_method' => 'uuid',
        'destination_filter' => 'entity_embed',
        'expected_embed_html_attributes' => [
          0 => ['data-entity-uuid' => TRUE] + $default_attributes,
          1 => [
            'data-entity-type' => 'media',
            'data-entity-uuid' => TRUE,
            'data-entity-embed-display' => 'view_mode:media.full',
            'data-embed-button' => 'media',
            'alt' => 'A yellow image',
            'title' => 'This is a yellow image',
          ],
        ],
        'Preexisting media types' => TRUE,
      ],
    ];

    // Add 'no initial media types' test cases.
    $test_cases_without_media_types = [];
    foreach ($test_cases as $test_case_label => $test_case) {
      $without_media_label = preg_replace('/preexisting media types$/', 'no media types', $test_case_label);
      $test_case['Preexisting media types'] = FALSE;
      $test_cases_without_media_types[$without_media_label] = $test_case;
    }

    $test_cases += $test_cases_without_media_types;

    return $test_cases;
  }

}
