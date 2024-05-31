<?php

namespace Drupal\Tests\twig_field_value\Kernel;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * @coversDefaultClass \Drupal\twig_field_value\Twig\Extension\FieldValueExtension
 * @group twig_field_value
 */
class FieldTargetEntityTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'twig_field_value',
    'twig_field_value_test',
    'user',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => 'field_reference',
      'type' => 'entity_reference',
      'entity_type' => 'entity_test',
      'cardinality' => FieldStorageConfigInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'entity_test',
      ],
    ]);
    $fieldStorage->save();
    $fieldConfig = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'entity_test',
    ]);
    $fieldConfig->save();
    $current_user = $this->container->get('current_user');
    $current_user->setAccount($this->createUser([], ['view test entity']));
  }

  /**
   * Check if inaccessible entity is _not_ displayed.
   *
   * This test uses an entity to which access is denied.
   */
  public function testEntityAccess() {
    $entity1 = EntityTest::create([
      'name' => 'entity1',
    ]);
    $entity1->save();
    // The label is important,
    // \Drupal\entity_test\EntityTestAccessControlHandler returns
    // AccessResult::forbidden for it.
    $entity2 = EntityTest::create([
      'name' => 'forbid_access',
    ]);
    $entity2->save();
    $entity3 = EntityTest::create([
      'name' => 'entity3',
    ]);
    $entity3->save();
    $entity = EntityTest::create([
      'field_reference' => [
        $entity1->id(),
        $entity2->id(),
        $entity3->id(),
      ],
    ]);
    $entity->save();
    $render_field = function (FieldableEntityInterface $entity) {
      return $entity->get('field_reference')->view([
        'type' => 'entity_reference_label',
        'settings' => [
          'link' => FALSE,
        ],
      ]);
    };
    $element = $render_field($entity);

    // Check the field values by rendering the formatter without any filter.
    $content = \Drupal::service('renderer')->renderPlain($element);
    $this->assertStringContainsString('entity1', (string) $content);
    $this->assertStringNotContainsString('forbid_access', (string) $content);
    $this->assertStringContainsString('entity3', (string) $content);

    // Check output of the field_target_entity filter.
    $element = [
      '#type' => 'inline_template',
      '#template' => '{% for target in field|field_target_entity %}{{ target.label }}, {% endfor %}',
      '#context' => [
        'field' => $render_field($entity),
      ],
    ];
    $content = \Drupal::service('renderer')->renderPlain($element);
    $this->assertSame('entity1, entity3, ', (string) $content);
  }

  /**
   * Check if an inaccessible field item is _not_ displayed.
   *
   * This test uses a field item to which access is denied. This is realized
   * with a specially crafted field formatter that denies access to the third
   * field item.
   */
  public function testFieldItemAccess() {
    $entity1 = EntityTest::create([
      'name' => 'entity1',
    ]);
    $entity1->save();
    $entity2 = EntityTest::create([
      'name' => 'entity2',
    ]);
    $entity2->save();
    $entity3 = EntityTest::create([
      'name' => 'entity3',
    ]);
    $entity3->save();
    $entity4 = EntityTest::create([
      'name' => 'entity4',
    ]);
    $entity4->save();
    $entity = EntityTest::create([
      'field_reference' => [
        $entity1->id(),
        $entity2->id(),
        $entity3->id(),
        $entity4->id(),
      ],
    ]);
    $entity->save();
    $render_field = function (FieldableEntityInterface $entity) {
      return $entity->get('field_reference')->view([
        'type' => 'entity_reference_hidden_third_child',
        'settings' => [
          'link' => FALSE,
        ],
      ]);
    };
    $element = $render_field($entity);

    // Check the field values by rendering the formatter without any filter.
    $content = \Drupal::service('renderer')->renderPlain($element);
    $this->assertStringContainsString('entity1', (string) $content);
    $this->assertStringContainsString('entity2', (string) $content);
    $this->assertStringNotContainsString('entity3', (string) $content);
    $this->assertStringContainsString('entity4', (string) $content);

    // Check output of the field_target_entity filter.
    $element = [
      '#type' => 'inline_template',
      '#template' => '{% for target in field|field_target_entity %}{{ target.label }}, {% endfor %}',
      '#context' => [
        'field' => $render_field($entity),
      ],
    ];
    $content = \Drupal::service('renderer')->renderPlain($element);
    $this->assertSame('', (string) $content);
  }

  /**
   * Check if an inaccessible field is _not_ displayed.
   *
   * This test uses a field for which #access is set to false.
   */
  public function testFieldAccess() {
    $entity1 = EntityTest::create([
      'name' => 'entity1',
    ]);
    $entity1->save();
    $entity2 = EntityTest::create([
      'name' => 'entity2',
    ]);
    $entity2->save();
    $entity = EntityTest::create([
      'field_reference' => [
        $entity1->id(),
        $entity2->id(),
      ],
    ]);
    $entity->save();
    $render_field = function (FieldableEntityInterface $entity) {
      return $entity->get('field_reference')->view([
        'type' => 'entity_reference_hidden_field',
        'settings' => [
          'link' => FALSE,
        ],
      ]);
    };
    $element = $render_field($entity);

    // Check the field values by rendering the formatter without any filter.
    $content = \Drupal::service('renderer')->renderPlain($element);
    $this->assertStringNotContainsString('entity1', (string) $content);
    $this->assertStringNotContainsString('entity2', (string) $content);

    // Check output of the field_target_entity filter.
    $element = [
      '#type' => 'inline_template',
      '#template' => '{% for target in field|field_target_entity %}{{ target.label }}, {% endfor %}',
      '#context' => [
        'field' => $render_field($entity),
      ],
    ];
    $content = \Drupal::service('renderer')->renderPlain($element);
    $this->assertSame('', (string) $content);
  }

}
