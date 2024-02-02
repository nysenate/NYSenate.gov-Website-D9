<?php

namespace Drupal\Tests\rabbit_hole\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the RabbitHoleEntityTypeSettingsForm form functionality.
 *
 * @group rabbit_hole
 *
 * @coversDefaultClass \Drupal\rabbit_hole\Form\RabbitHoleEntityTypeSettingsForm
 */
class RabbitHoleEntityTypeSettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rabbit_hole', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->createUser(['administer rabbit_hole settings']));
  }

  /**
   * Tests "Rabbit Hole" settings form of one entity type.
   */
  public function testRabbitHoleEntityTypeSettingsForm() {
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);

    $this->drupalGet('admin/config/content/rabbit-hole');
    $this->submitForm([
      'entity_types[node]' => TRUE,
    ], 'Save configuration');

    $this->drupalGet('admin/config/content/rabbit-hole/node');
    $this->submitForm([
      'bundles[article][action]' => 'page_not_found',
      'bundles[article][allow_override]' => TRUE,
      'bundles[page][action]' => 'access_denied',
      'bundles[page][allow_override]' => FALSE,
    ], 'Save configuration');

    // Verify the default form values after submit.
    $this->assertSession()->checkboxChecked('bundles[article][allow_override]');
    $this->assertSession()->checkboxNotChecked('bundles[page][allow_override]');
    $this->assertSession()->fieldValueEquals('bundles[article][action]', 'page_not_found');
    $this->assertSession()->fieldValueEquals('bundles[page][action]', 'access_denied');

    // Verify field creation.
    $this->assertTrue(\Drupal::service('rabbit_hole.entity_helper')->hasRabbitHoleField('node', 'article'));
    $this->assertFalse(\Drupal::service('rabbit_hole.entity_helper')->hasRabbitHoleField('node', 'page'));

    // Disable "Allow Override" option with no content - field should be removed
    // on first form submit.
    $this->submitForm([
      'bundles[article][allow_override]' => FALSE,
    ], 'Save configuration');
    $this->assertFalse(\Drupal::service('rabbit_hole.entity_helper')->hasRabbitHoleField('node', 'article'));

    \Drupal::service('rabbit_hole.entity_helper')->createRabbitHoleField('node', 'article');
    \Drupal::service('rabbit_hole.entity_helper')->createRabbitHoleField('node', 'page');

    // Create a couple of test nodes.
    $this->drupalCreateNode([
      'title' => 'Article #1',
      'type' => 'article',
      'rabbit_hole__settings' => [
        'action' => 'page_not_found',
      ],
    ]);
    $this->drupalCreateNode([
      'title' => 'Page #1',
      'type' => 'page',
      'rabbit_hole__settings' => [
        'action' => 'bundle_default',
      ],
    ]);

    $this->drupalGet('admin/config/content/rabbit-hole/node');
    $this->submitForm([
      'bundles[article][allow_override]' => FALSE,
      'bundles[page][allow_override]' => FALSE,
    ], 'Save configuration');
    // Warning message should be displayed. The field shouldn't be removed yet.
    // Only "Article" should be mentioned, because "Page" has no real overrides.
    $this->assertSession()->responseContains('Disabling overrides for <em class="placeholder">Article</em> entities will remove their existing values from the database.');
    $this->assertTrue(\Drupal::service('rabbit_hole.entity_helper')->hasRabbitHoleField('node', 'article'));

    // Another submit should help.
    $this->submitForm([
      'bundles[article][allow_override]' => FALSE,
      'bundles[page][allow_override]' => FALSE,
    ], 'Save configuration');

    $this->assertFalse(\Drupal::service('rabbit_hole.entity_helper')->hasRabbitHoleField('node', 'article'));
    $this->assertFalse(\Drupal::service('rabbit_hole.entity_helper')->hasRabbitHoleField('node', 'page'));
  }

}
