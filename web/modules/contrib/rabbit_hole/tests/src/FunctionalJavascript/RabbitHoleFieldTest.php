<?php

namespace Drupal\Tests\rabbit_hole\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;

/**
 * Tests Rabbit Hole field type/widget.
 *
 * @group rabbit_hole
 */
class RabbitHoleFieldTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'rabbit_hole',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create "article" content type and Rabbit Hole field.
    $this->drupalCreateContentType(['type' => 'article']);

    // @todo Create API for this.
    $admin_user = $this->createUser(['administer rabbit_hole settings']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/content/rabbit-hole');
    $this->submitForm([
      'entity_types[node]' => TRUE,
    ], 'Save configuration');
    $this->drupalGet('admin/config/content/rabbit-hole/node');
    $this->submitForm([
      'bundles[article][allow_override]' => TRUE,
    ], 'Save configuration');
  }

  /**
   * Tests to confirm the widget respects permissions and working properly.
   *
   * @covers \Drupal\rabbit_hole\Plugin\Field\FieldWidget\RabbitHoleDefaultWidget
   */
  public function testRabbitHoleWidget() {
    // Try user without "Rabbit Hole" permission - they shouldn't see the field.
    $user1 = $this->drupalCreateUser([
      'create article content',
      'edit own article content',
    ]);
    $this->drupalLogin($user1);
    $this->drupalGet('node/add/article');
    $this->assertSession()->fieldNotExists('rabbit_hole__settings');

    $user2 = $this->drupalCreateUser([
      'create article content',
      'edit own article content',
      'rabbit hole administer node',
    ]);
    $this->drupalLogin($user2);
    $this->drupalGet('node/add/article');
    $this->getSession()->getPage()->fillField('title[0][value]', $this->randomString());
    $this->assertSession()->fieldValueEquals('rabbit_hole__settings[0][action]', 'bundle_default');
    $this->getSession()->getPage()->selectFieldOption('rabbit_hole__settings[0][action]', 'page_redirect');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->fillField('rabbit_hole__settings[0][settings][redirect]', 'https://www.example.com');
    $this->getSession()->getPage()->selectFieldOption('rabbit_hole__settings[0][settings][redirect_code]', '303');
    $this->getSession()->getPage()->pressButton('Save');

    // Verify saved field values.
    $this->drupalGet('node/1/edit');
    $this->assertSession()->fieldValueEquals('rabbit_hole__settings[0][action]', 'page_redirect');
    $this->assertSession()->fieldValueEquals('rabbit_hole__settings[0][settings][redirect]', 'https://www.example.com');
    $this->assertSession()->fieldValueEquals('rabbit_hole__settings[0][settings][redirect_code]', '303');

    // Verify field summary.
    $this->assertSession()->responseContains('<span class="vertical-tabs__menu-item-summary">Page redirect</span>');

    // Change action and make sure the editor didn't hit error page after the
    // form save.
    $this->getSession()->getPage()->selectFieldOption('rabbit_hole__settings[0][action]', 'access_denied');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->fieldValueEquals('rabbit_hole__settings[0][action]', 'access_denied');
  }

  /**
   * Tests the translation of "Rabbit Hole" field.
   */
  public function testRabbitHoleFieldTranslatable() {
    ConfigurableLanguage::create(['id' => 'uk'])->save();
    ConfigurableLanguage::create(['id' => 'pl'])->save();

    $config = $this->config('language.negotiation');
    $config->set('url.prefixes', ['uk' => 'uk', 'pl' => 'pl'])
      ->save();
    \Drupal::service('kernel')->rebuildContainer();

    $node1 = Node::create([
      'type' => 'article',
      'title' => 'Article 1',
      'rabbit_hole__settings' => [
        'action' => 'display_page',
      ],
    ]);
    $node1->addTranslation('uk', [
      'title' => 'Article 1 - Ukrainian',
      'rabbit_hole__settings' => [
        'action' => 'page_not_found',
      ],
    ]);
    $node1->addTranslation('pl', [
      'title' => 'Article 1 - Polish',
      'rabbit_hole__settings' => [
        'action' => 'page_redirect',
        'settings' => [
          'redirect' => '/node',
          'redirect_code' => 301,
        ],
      ],
    ]);
    $node1->save();

    // Verify that each translation has different settings.
    $this->drupalGet($node1->toUrl());
    $this->assertSession()->addressEquals('node/1');
    $this->assertSession()->pageTextContains('Article 1');

    $this->drupalGet($node1->getTranslation('uk')->toUrl());
    $this->assertSession()->addressEquals('uk/node/1');
    $this->assertSession()->pageTextContains('Page not found');

    $this->drupalGet($node1->getTranslation('pl')->toUrl());
    $this->assertSession()->addressEquals('node');
  }

}
