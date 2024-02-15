<?php

namespace Drupal\Tests\media_migration\Functional;

/**
 * Tests the transformation of embed image media tokens to media_embed.
 *
 * @group media_migration
 *
 * @group legacy
 */
class MigrateEmbedMediaTokenToMediaEmbedTest extends MigrateEmbedMediaTokenTestBase {

  /**
   * {@inheritdoc}
   */
  protected $embedTokenDestinationFilterPlugin = 'media_embed';

  /**
   * {@inheritdoc}
   */
  protected $embedMediaCssSelector = '.field--name-body.field--type-text-with-summary .media.media--type-image .field--name-field-media-image';

  /**
   * Tests the result of Media Migration's embed media token transform.
   *
   * @param string $reference_method
   *   The method of how embed media is referenced.
   * @param array $extra_modules
   *   Additional modules to enable before the migration.
   * @param bool $preexisting_media_types
   *   Whether media types should be present before the migration.
   *
   * @dataProvider providerMediaEmbedTransform
   */
  public function testMediaTokenToMediaEmbedTransform(string $reference_method, array $extra_modules, bool $preexisting_media_types) {
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

    if (!empty($extra_modules)) {
      $this->container->get('module_installer')->install($extra_modules);
    }

    $this->assertMigrateUpgradeViaUi();
    $this->assertMediaMigrationResults();
    $expected_node1_embed_attributes = [
      0 => [
        'data-view-mode' => 'wysiwyg',
        'data-entity-type' => 'media',
        'data-entity-uuid' => TRUE,
        'alt' => 'Different alternative text about blue.png in the test article',
        'title' => 'Different title copy for blue.png in the test article',
        'data-align' => 'center',
      ],
      1 => [
        'data-entity-type' => 'media',
        'data-entity-uuid' => TRUE,
        'data-view-mode' => 'default',
        'alt' => 'A yellow image',
        'title' => 'This is a yellow image',
      ],
    ];
    $this->assertMediaTokenTransform($expected_node1_embed_attributes);
    $this->assertNode1FieldValues($expected_node1_embed_attributes);
  }

  /**
   * Data provider for ::testMediaTokenToMediaEmbedTransform().
   *
   * @return array
   *   The test cases.
   */
  public function providerMediaEmbedTransform() {
    $test_cases = [
      // ID reference method. This should be neutral for media_embed token
      // transform destination.
      'ID reference method, no additional modules, preexisting media types' => [
        'reference method' => 'id',
        'additional modules' => [],
        'Preexisting media types' => TRUE,
      ],
      'ID reference method, Entity Embed installed, preexisting media types' => [
        'reference method' => 'id',
        'additional modules' => ['entity_embed'],
        'Preexisting media types' => TRUE,
      ],
      // UUID reference method.
      'UUID reference method, no additional modules, preexisting media types' => [
        'reference method' => 'uuid',
        'additional modules' => [],
        'Preexisting media types' => TRUE,
      ],
      'UUID reference method, Entity Embed installed, preexisting media types' => [
        'reference method' => 'uuid',
        'additional modules' => ['entity_embed'],
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
