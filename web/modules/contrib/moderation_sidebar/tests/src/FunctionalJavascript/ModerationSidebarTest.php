<?php

namespace Drupal\Tests\moderation_sidebar\FunctionalJavascript;

use Behat\Mink\Element\ElementInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Contains Moderation Sidebar integration tests.
 *
 * @group moderation_sidebar
 */
class ModerationSidebarTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'toolbar',
    'moderation_sidebar',
    'node',
    'moderation_sidebar_test',
    'content_translation',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a Content Type with moderation enabled.
    $node_type = $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    /* @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->container->get('entity_type.manager')->getStorage('workflow')->load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'article');
    $workflow->save();
    $node_type->setNewRevision(TRUE);
    $node_type->save();

    // Create a user who can use the Moderation Sidebar.
    $user = $this->drupalCreateUser([
      'access toolbar',
      'use moderation sidebar',
      'administer moderation sidebar',
      'access content',
      'create article content',
      'edit any article content',
      'delete any article content',
      'view any unpublished content',
      'view latest version',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
      'create url aliases',
      'administer themes',
      'administer languages',
      'administer content translation',
      'create content translations',
      'update content translations',
      'delete content translations',
      'translate any entity',
    ]);
    $this->drupalLogin($user);

    // Enable admin theme for content forms.
    $edit = ['use_admin_theme' => TRUE];
    $this->drupalGet('admin/appearance');
    $this->submitForm($edit, 'Save configuration');
    // Add German language.
    $edit = ['predefined_langcode' => 'de'];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, t('Add language'));
    // Enable translations for nodes.
    $edit = ['entity_types[node]' => 'node', 'settings[node][article][translatable]' => TRUE];
    $this->drupalGet('admin/config/regional/content-language');
    $this->submitForm($edit, 'Save configuration');

    drupal_flush_all_caches();
  }

  /**
   * Tests that the Moderation Sidebar is working as expected.
   */
  public function testModerationSidebar() {
    $assert_session = $this->assertSession();
    // Create a new article.
    $node = $this->createNode([
      'type' => 'article',
      'moderation_state' => 'published',
    ]);
    $this->drupalGet('node/' . $node->id());

    // Open the moderation sidebar.
    $this->clickLink('Tasks');
    $this->waitForDialog();
    // Archived transitions should not be visible based on our permissions.
    $assert_session->elementNotExists('css', '.moderation-sidebar-link#published_archived');
    // Create a draft of the article.
    $this->submitForm([], 'Create New Draft');
    $assert_session->addressEquals('node/' . $node->id() . '/latest');

    // Publish the draft.
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->pageTextNotContains('View existing draft');
    $this->submitForm([], 'Publish');
    $assert_session->addressEquals('node/' . $node->id());

    // Create another draft, then discard it.
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $this->submitForm([], 'Create New Draft');
    $assert_session->addressEquals('node/' . $node->id() . '/latest');
    $this->clickLink('Tasks');
    $this->waitForElement('css', '#moderation-sidebar-discard-draft')->click();
    $assert_session->pageTextContains('The draft has been discarded successfully');

    $this->drupalGet('admin/config/user-interface/moderation-sidebar');
    $assert_session->checkboxNotChecked('workflows[editorial_workflow][disabled_transitions][create_new_draft]');
    $this->submitForm(['workflows[editorial_workflow][disabled_transitions][create_new_draft]' => TRUE], 'Save configuration');

    $this->drupalGet('node/' . $node->id());
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->buttonNotExists('create_new_draft');

    $this->drupalGet('admin/config/user-interface/moderation-sidebar');
    $this->submitForm(['workflows[editorial_workflow][disabled_transitions][create_new_draft]' => FALSE], 'Save configuration');

    // SCENARIO 1: Published EN, Draft EN, Published EN.
    // Create a new article.
    $node = $this->createNode([
      'type' => 'article',
      'moderation_state' => 'published',
    ]);
    // Node published, Published tray.
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    // Actions for a published node.
    $this->waitForElement('css', '.moderation-sidebar-link#create_new_draft');
    $assert_session->pageTextContainsOnce('Delete content');
    // Actions for draft that should not be present.
    $assert_session->elementNotExists('css', '.moderation-sidebar-link#publish');
    $assert_session->elementNotExists('css', '.moderation-sidebar-link#moderation-sidebar-discard-draft');
    $assert_session->pageTextNotContains('View live content');
    $assert_session->pageTextNotContains('Edit draft');

    // Node draft, Draft tray.
    $this->submitForm([], 'Create New Draft');
    $this->drupalGet('node/' . $node->id() . '/latest');
    $assert_session->elementExists('css', '.moderation-label-draft[data-label="Draft"]');
    $this->clickLink('Tasks');
    // Actions for a draft node.
    $this->waitForElement('css', '.moderation-sidebar-link#publish');
    $assert_session->elementExists('css', '.moderation-sidebar-link#moderation-sidebar-discard-draft');
    $assert_session->pageTextContainsOnce('View live content');
    $assert_session->pageTextContainsOnce('Edit draft');
    // Actions for published that should not be present.
    $assert_session->elementNotExists('css', '.moderation-sidebar-link#create_new_draft');

    // Node draft, Draft available tray.
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-draft-available[data-label="Draft available"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    // Actions when there is a draft available node.
    $assert_session->pageTextContainsOnce('View existing draft');
    // Actions for draft that should not be present.
    $assert_session->elementNotExists('css', '.moderation-sidebar-link#publish');
    $assert_session->elementNotExists('css', '.moderation-sidebar-link#moderation-sidebar-discard-draft');
    $assert_session->pageTextNotContains('View live content');
    $assert_session->pageTextContains('Edit draft');
    // Actions for published that should not be present.
    $assert_session->elementNotExists('css', '.moderation-sidebar-link#create_new_draft');

    // Node published, Published tray.
    $this->drupalGet('node/' . $node->id() . '/latest');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $this->submitForm([], 'Publish');
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    // Actions for a published node.
    $this->waitForElement('css', '.moderation-sidebar-link#create_new_draft');
    $assert_session->pageTextContainsOnce('Delete content');
    // Actions for draft that should not be present.
    $assert_session->elementNotExists('css', '.moderation-sidebar-link#publish');
    $assert_session->elementNotExists('css', '.moderation-sidebar-link#moderation-sidebar-discard-draft');
    $assert_session->pageTextNotContains('View live content');
    $assert_session->pageTextNotContains('Edit draft');

    // Viewing the node in an not existent translation should show the original.
    $this->drupalGet('de/node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->waitForElement('css', '.moderation-sidebar-link#create_new_draft');

    // SCENARIO 2: Published EN, Published DE, Draft EN.
    $this->drupalGet('node/add/article');
    $this->clickLink('URL alias');
    $this->submitForm([
      'title[0][value]' => 'Llama EN',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Llama EN');
    $this->clickLink('Translate');
    $this->waitForLink('Create translation')->click();
    $this->submitForm([
      'title[0][value]' => 'Llama DE',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Llama DE');
    $node = $this->getNodeByTitle('Llama EN');
    $this->drupalGet('node/' . $node->id());
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $this->submitForm([], 'Create New Draft');

    // Draft EN, Draft tray.
    $this->drupalGet('node/' . $node->id() . '/latest');
    $element = $assert_session->waitForElementVisible('css', '.moderation-label-draft[data-label="Draft"]');
    $this->assertNotEmpty($element);
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Llama EN');
    $assert_session->elementExists('css', '.moderation-sidebar-link#publish');
    $assert_session->elementExists('css', '.moderation-sidebar-link#moderation-sidebar-discard-draft');
    $assert_session->pageTextContainsOnce('View live content');
    $assert_session->pageTextContainsOnce('Edit draft');

    // Published EN, Draft available tray.
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-draft-available[data-label="Draft available"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Llama EN');
    $assert_session->pageTextContainsOnce('View existing draft');

    // Published DE, Published tray.
    $this->drupalGet('de/node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Llama DE');
    $assert_session->elementExists('css', '.moderation-sidebar-link#create_new_draft');

    // SCENARIO 3: Published EN, Draft DE.
    $this->drupalGet('node/add/article');
    $this->clickLink('URL alias');
    $this->submitForm([
      'title[0][value]' => 'Alpaca EN',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Alpaca EN');
    $this->clickLink('Translate');
    $this->waitForLink('Create translation')->click();
    $this->submitForm([
      'title[0][value]' => 'Alpaca DE',
      'moderation_state[0][state]' => 'draft',
    ], 'Save');

    // DE Draft, Draft tray.
    $assert_session->elementExists('css', '.moderation-label-draft[data-label="Draft"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Alpaca DE');
    $assert_session->elementExists('css', '.moderation-sidebar-link#publish');
    $assert_session->elementExists('css', '.moderation-sidebar-link#moderation-sidebar-discard-draft');
    $assert_session->pageTextContainsOnce('View live content');
    $assert_session->pageTextContainsOnce('Edit draft');

    // EN Published, Published tray.
    $node = $this->getNodeByTitle('Alpaca EN');
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Alpaca EN');
    $assert_session->elementExists('css', '.moderation-sidebar-link#create_new_draft');
    $assert_session->pageTextContainsOnce('Delete content');

    // SCENARIO 4: Published EN, Draft EN, Published DE (from draft).
    $this->drupalGet('node/add/article');
    $this->clickLink('URL alias');
    $this->submitForm([
      'title[0][value]' => 'Vicuna EN',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $this->submitForm([], 'Create New Draft');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Vicuna EN');
    $this->clickLink('Translate');
    $this->waitForLink('Create translation')->click();
    $this->submitForm([
      'title[0][value]' => 'Vicuna DE',
      'moderation_state[0][state]' => 'published',
    ], 'Save');

    // EN Published, Draft available tray.
    $node = $this->getNodeByTitle('Vicuna EN');
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-draft-available[data-label="Draft available"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Vicuna EN');
    $assert_session->pageTextContainsOnce('View existing draft');

    // EN Draft, Draft tray.
    $this->drupalGet('node/' . $node->id() . '/latest');
    $assert_session->elementExists('css', '.moderation-label-draft[data-label="Draft"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Vicuna EN');
    $assert_session->elementExists('css', '.moderation-sidebar-link#publish');
    $assert_session->elementExists('css', '.moderation-sidebar-link#moderation-sidebar-discard-draft');
    $assert_session->pageTextContainsOnce('View live content');
    $assert_session->pageTextContainsOnce('Edit draft');

    // DE Published, Published tray.
    $this->drupalGet('de/node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Vicuna DE');
    $assert_session->elementExists('css', '.moderation-sidebar-link#create_new_draft');
    $assert_session->pageTextContainsOnce('Delete content');

    // SCENARIO 5: Published EN, Draft EN, Published DE (from published).
    $this->drupalGet('node/add/article');
    $this->clickLink('URL alias');
    $this->submitForm([
      'title[0][value]' => 'Camel EN',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $this->submitForm([], 'Create New Draft');
    $assert_session->elementExists('css', '.moderation-label-draft[data-label="Draft"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Camel EN');
    $node = $this->getNodeByTitle('Camel EN');
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-draft-available[data-label="Draft available"]');
    $this->clickLink('Tasks');
    $this->waitForLink('Translate')->click();
    $this->waitForLink('Create translation')->click();
    $this->submitForm([
      'title[0][value]' => 'Camel DE',
      'moderation_state[0][state]' => 'published',
    ], 'Save');

    // EN Published, Draft available tray.
    $node = $this->getNodeByTitle('Camel EN');
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-draft-available[data-label="Draft available"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Camel EN');
    $assert_session->pageTextContainsOnce('View existing draft');

    // EN Draft, Draft tray.
    $this->drupalGet('node/' . $node->id() . '/latest');
    $assert_session->elementExists('css', '.moderation-label-draft[data-label="Draft"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Camel EN');
    $assert_session->elementExists('css', '.moderation-sidebar-link#publish');
    $assert_session->elementExists('css', '.moderation-sidebar-link#moderation-sidebar-discard-draft');
    $assert_session->pageTextContainsOnce('View live content');
    $assert_session->pageTextContainsOnce('Edit draft');

    // DE Published, Published tray.
    $this->drupalGet('de/node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Camel DE');
    $assert_session->elementExists('css', '.moderation-sidebar-link#create_new_draft');

    // SCENARIO 6: Published EN, Published DE, Removed DE.
    $this->drupalGet('node/add/article');
    $this->clickLink('URL alias');
    $this->submitForm([
      'title[0][value]' => 'Guanaco EN',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Guanaco EN');
    $this->clickLink('Translate');
    $this->waitForLink('Create translation')->click();
    $this->submitForm([
      'title[0][value]' => 'Guanaco DE',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Guanaco DE');
    $node = $this->getNodeByTitle('Guanaco EN');
    $node->removeTranslation('de');
    $node->save();
    $this->drupalGet('node/' . $node->id());
    $this->clickLink('Tasks');
    $this->waitForLink('Translate')->click();
    $this->waitForLink('Create translation')->click();
    $assert_session->pageTextContains('Create German translation of Guanaco EN');

    // SCENARIO 6: Published EN, Published DE, Draft EN, Draft DE, Discarded
    // Draft DE.
    // Create an EN node and translate it, both published.
    $this->drupalGet('node/add/article');
    $this->clickLink('URL alias');
    $this->submitForm([
      'title[0][value]' => 'Dromedary EN',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $assert_session->elementTextEquals('css', '.ui-dialog-title', 'Dromedary EN');
    $this->clickLink('Translate');
    $this->waitForLink('Create translation')->click();
    $this->submitForm([
      'title[0][value]' => 'Dromedary DE',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');

    // Create a Draft for the EN node.
    $node = $this->drupalGetNodeByTitle('Dromedary EN');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([
      'moderation_state[0][state]' => 'draft',
    ], 'Save (this translation)');
    // Create a Draft for the DE node.
    $this->drupalGet('de/node/' . $node->id() . '/edit');
    $this->submitForm([
      'moderation_state[0][state]' => 'draft',
    ], 'Save (this translation)');

    // Discard the DE Draft.
    $this->drupalGet('de/node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-draft-available[data-label="Draft available"]');
    $this->drupalGet('de/node/' . $node->id() . '/latest');
    $this->clickLink('Tasks');
    $this->waitForDialog();
    $this->submitForm([], 'Discard draft');
    $assert_session->pageTextContains('The draft has been discarded successfully');
    // Assert that DE translation is Published.
    $this->drupalGet('de/node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    // Assert that the EN translation has a Draft available.
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-draft-available[data-label="Draft available"]');
  }

  /**
   * Waits for a link to be visible.
   *
   * @param string $locator
   *   The link locator (e.g., its text content).
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The link element.
   */
  private function waitForLink(string $locator): ElementInterface {
    return $this->waitForElement('named', ['link', $locator]);
  }

  /**
   * Waits for the off-canvas dialog to be visible.
   */
  private function waitForDialog(): void {
    $this->waitForElement('css', '.ui-dialog-title');
  }

  /**
   * Waits for an element to become visible.
   *
   * @param string $selector
   *   The selector to use to locate the element.
   * @param mixed $locator
   *   The locator for the element, depending on the given selector.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The element.
   */
  private function waitForElement(string $selector, $locator): ElementInterface {
    $element = $this->assertSession()
      ->waitForElementVisible($selector, $locator);
    $this->assertNotEmpty($element);
    return $element;
  }

}
