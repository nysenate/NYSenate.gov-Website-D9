<?php

namespace Drupal\Tests\address_map_link\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests address_map_links module's configuration.
 *
 * @group address_map_link
 */
class AddressMapLinkConfigureTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'language',
    'user',
    'field',
    'field_ui',
    'node',
    'address',
    'address_map_link',
  ];

  /**
   * The node we're editing.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * The theme to install as the default for testing.
   *
   * Defaults to the install profile's default theme, if it specifies any.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * User with permission to administer entites.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Address field instance.
   *
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $field;

  /**
   * Entity form display.
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected $formDisplay;

  /**
   * URL to add new content.
   *
   * @var string
   */
  protected $nodeAddUrl;

  /**
   * URL to edit node display.
   *
   * @var string
   */
  protected $nodeDisplayEditUrl;

  /**
   * Entity view Display.
   *
   * @var \Drupal\Core\Entity\Entity\EntityViewDisplay
   */
  protected $nodeViewDisplay;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create content type.
    $type = NodeType::create(['name' => 'Article', 'type' => 'article']);
    $type->save();

    // Create user that will be used for tests.
    $this->adminUser = $this->drupalCreateUser([
      'create article content',
      'edit own article content',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer display modes',
    ]);
    $this->drupalLogin($this->adminUser);

    // Add the address field to the article content type.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_address',
      'entity_type' => 'node',
      'type' => 'address',
    ]);
    $field_storage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => 'Address',
    ]);
    $this->field->save();

    // Set article's form display.
    $this->formDisplay = EntityFormDisplay::load('node.article.default');

    if (!$this->formDisplay) {
      EntityFormDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => 'article',
        'mode' => 'default',
        'status' => TRUE,
      ])->save();
      $this->formDisplay = EntityFormDisplay::load('node.article.default');
    }
    $this->formDisplay->setComponent($this->field->getName(), [
      'type' => 'address_default',
      'settings' => [
        'default_country' => 'US',
      ],
    ])->save();

    // Configure default display mode settings.
    $this->nodeViewDisplay = EntityViewDisplay::load('node.article.default');
    if (!$this->nodeViewDisplay) {
      EntityViewDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => 'article',
        'mode' => 'default',
        'status' => TRUE,
        'content' => [
          $this->field->getName() => [
            'type' => 'address_plain',
            'settings' => [],
            'label' => 'hidden',
            'third_party_settings' => [],
            'weight' => 0,
          ],
        ],
      ])->save();
      $this->nodeViewDisplay = EntityViewDisplay::load('node.article.default');
    }

    $this->nodeAddUrl = 'node/add/article';
    $this->nodeDisplayEditUrl = 'admin/structure/types/manage/article/display';
  }

  /**
   * Test configuration form display on entity view edit page.
   */
  public function testConfigureAddressMapLink(): void {
    $field_name = $this->field->getName();
    $this->drupalGet($this->nodeDisplayEditUrl);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNotEmpty((bool) $this->xpath('//select[@name="fields[' . $field_name . '][type]"]'), 'Address field formatter shown as required.');

    // Test that the summary is correct when no settings have been set.
    $this->assertSession()->pageTextContains('Linked Address: Not Linked');
  }

}
