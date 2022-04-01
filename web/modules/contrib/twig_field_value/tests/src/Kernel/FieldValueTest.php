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
class FieldValueTest extends EntityKernelTestBase {

  public static $modules = [
    'twig_field_value',
    'user',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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
   * Check if inaccessible content is _not_ displayed.
   */
  public function testFieldValue() {
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
    $this->assertContains('entity1', (string) $content);
    $this->assertNotContains('forbid_access', (string) $content);
    $this->assertContains('entity3', (string) $content);

    // Check output of the field_value filter.
    $element = [
      '#type' => 'inline_template',
      '#template' => '{{ field|field_value|safe_join(", ") }}',
      '#context' => [
        'field' => $render_field($entity),
      ],
    ];
    $content = \Drupal::service('renderer')->renderPlain($element);
    $this->assertSame('entity1, entity3', (string) $content);
  }

}
