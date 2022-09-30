<?php

namespace Drupal\Tests\entity_usage\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests layout builder usage through Inline Blocks displays in UI.
 *
 * @group entity_usage
 * @group layout_builder
 * @coversDefaultClass \Drupal\entity_usage\Plugin\EntityUsage\Track\LayoutBuilder
 */
class EntityUsageLayoutBuilderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_usage',
    'entity_test',
    'block_content',
    'block',
    'text',
    'user',
    'layout_builder',
    'layout_discovery',
    'field',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ])
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $this->config('entity_usage.settings')
      ->set('local_task_enabled_entity_types', ['entity_test'])
      ->set('track_enabled_source_entity_types', ['entity_test', 'block_content'])
      ->set('track_enabled_target_entity_types', ['entity_test', 'block_content'])
      ->set('track_enabled_plugins', ['layout_builder', 'entity_reference'])
      ->save();

    /** @var \Drupal\Core\Routing\RouteBuilderInterface $routerBuilder */
    $routerBuilder = \Drupal::service('router.builder');
    $routerBuilder->rebuild();
  }

  /**
   * Test entities referenced by block content in LB are shown on usage page.
   *
   * E.g, if entityHost (with LB) -> Block Content -> entityInner, when
   * navigating to entityInner, the source relationship is shown as ultimately
   * coming from entityHost (via Block Content).
   */
  public function testLayoutBuilderInlineBlockUsage() {
    $innerEntity = EntityTest::create(['name' => $this->randomMachineName()]);
    $innerEntity->save();

    $type = BlockContentType::create([
      'id' => 'foo',
      'label' => 'Foo',
    ]);
    $type->save();

    $fieldStorage = FieldStorageConfig::create([
      'field_name' => 'myref',
      'entity_type' => 'block_content',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'entity_test',
      ],
    ]);
    $fieldStorage->save();
    $field = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => $type->id(),
    ]);
    $field->save();

    $block = BlockContent::create([
      'type' => $type->id(),
      'reusable' => 0,
      'myref' => $innerEntity,
    ]);
    $block->save();

    $sectionData = [
      new Section('layout_onecol', [], [
        'first-uuid' => new SectionComponent('first-uuid', 'content', [
          'id' => 'inline_block:' . $type->id(),
          'block_revision_id' => $block->getRevisionId(),
        ]),
      ]),
    ];

    $entityHost = EntityTest::create([
      'name' => $this->randomMachineName(),
      OverridesSectionStorage::FIELD_NAME => $sectionData,
    ]);
    $entityHost->save();

    $this->drupalLogin($this->drupalCreateUser([
      'access entity usage statistics',
      'view test entity',
    ]));

    $this->drupalGet(Url::fromRoute('entity.entity_test.entity_usage', ['entity_test' => $innerEntity->id()]));
    $this->assertSession()->statusCodeEquals(200);

    $row1 = $this->assertSession()->elementExists('css', 'table tbody tr:nth-child(1)');

    $link = $this->assertSession()->elementExists('css', 'td:nth-child(1) a', $row1);
    $this->assertEquals($entityHost->label(), $link->getText());
    $this->assertEquals($link->getAttribute('href'), $entityHost->toUrl()->toString());
  }

}
