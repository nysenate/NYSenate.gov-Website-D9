<?php

namespace Drupal\Tests\moderation_sidebar\FunctionalJavascript;

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
  public static $modules = [
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
  protected function setUp() {
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
    $this->drupalPostForm('admin/appearance', $edit, 'Save configuration');
    // Add German language.
    $edit = ['predefined_langcode' => 'de'];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));
    // Enable translations for nodes.
    $edit = ['entity_types[node]' => 'node', 'settings[node][article][translatable]' => TRUE];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, 'Save configuration');

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
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Archived transitions should not be visible based on our permissions.
    $this->assertSession()->elementNotExists('css', '.moderation-sidebar-link#published_archived');
    // Create a draft of the article.
    $this->submitForm([], 'Create New Draft');
    $this->assertSession()->addressEquals('node/' . $node->id() . '/latest');

    // Publish the draft.
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextNotContains('View existing draft');
    $this->submitForm([], 'Publish');
    $this->assertSession()->addressEquals('node/' . $node->id());

    // Create another draft, then discard it.
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Create New Draft');
    $this->assertSession()->addressEquals('node/' . $node->id() . '/latest');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->click('#moderation-sidebar-discard-draft');
    $this->assertSession()->pageTextContains('The draft has been discarded successfully');

    $this->drupalGet('admin/config/user-interface/moderation-sidebar');
    $this->assertSession()->checkboxNotChecked('workflows[editorial_workflow][disabled_transitions][create_new_draft]');
    $this->submitForm(['workflows[editorial_workflow][disabled_transitions][create_new_draft]' => TRUE], 'Save configuration');

    $this->drupalGet('node/' . $node->id());
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->buttonNotExists('create_new_draft');

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
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Actions for a published node.
    $assert_session->elementExists('css', '.moderation-sidebar-link#create_new_draft');
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
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Actions for a draft node.
    $assert_session->elementExists('css', '.moderation-sidebar-link#publish');
    $assert_session->elementExists('css', '.moderation-sidebar-link#moderation-sidebar-discard-draft');
    $assert_session->pageTextContainsOnce('View live content');
    $assert_session->pageTextContainsOnce('Edit draft');
    // Actions for published that should not be present.
    $assert_session->elementNotExists('css', '.moderation-sidebar-link#create_new_draft');

    // Node draft, Draft available tray.
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-draft-available[data-label="Draft available"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
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
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Publish');
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Actions for a published node.
    $assert_session->elementExists('css', '.moderation-sidebar-link#create_new_draft');
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
    $this->assertSession()->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '.moderation-sidebar-link#create_new_draft');

    // SCENARIO 2: Published EN, Published DE, Draft EN.
    $this->drupalGet('node/add/article');
    $this->clickLink('URL alias');
    $this->submitForm([
      'title[0][value]' => 'Llama EN',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Llama EN');
    $this->clickLink('Translate');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->clickLink('Create translation');
    $this->submitForm([
      'title[0][value]' => 'Llama DE',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Llama DE');
    $node = $this->getNodeByTitle('Llama EN');
    $this->drupalGet('node/' . $node->id());
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Create New Draft');

    // Draft EN, Draft tray.
    $this->drupalGet('node/' . $node->id() . '/latest');
    $assert_session->elementExists('css', '.moderation-label-draft[data-label="Draft"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Llama EN');
    $assert_session->elementExists('css', '.moderation-sidebar-link#publish');
    $assert_session->elementExists('css', '.moderation-sidebar-link#moderation-sidebar-discard-draft');
    $assert_session->pageTextContainsOnce('View live content');
    $assert_session->pageTextContainsOnce('Edit draft');

    // Published EN, Draft available tray.
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-draft-available[data-label="Draft available"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Llama EN');
    $assert_session->pageTextContainsOnce('View existing draft');

    // Published DE, Published tray.
    $this->drupalGet('de/node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Llama DE');
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
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Alpaca EN');
    $this->clickLink('Translate');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->clickLink('Create translation');
    $this->submitForm([
      'title[0][value]' => 'Alpaca DE',
      'moderation_state[0][state]' => 'draft',
    ], 'Save');

    // DE Draft, Draft tray.
    $assert_session->elementExists('css', '.moderation-label-draft[data-label="Draft"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Alpaca DE');
    $assert_session->elementExists('css', '.moderation-sidebar-link#publish');
    $assert_session->elementExists('css', '.moderation-sidebar-link#moderation-sidebar-discard-draft');
    $assert_session->pageTextContainsOnce('View live content');
    $assert_session->pageTextContainsOnce('Edit draft');

    // EN Published, Published tray.
    $node = $this->getNodeByTitle('Alpaca EN');
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Alpaca EN');
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
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Create New Draft');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Vicuna EN');
    $this->clickLink('Translate');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->clickLink('Create translation');
    $this->submitForm([
      'title[0][value]' => 'Vicuna DE',
      'moderation_state[0][state]' => 'published',
    ], 'Save');

    // EN Published, Draft available tray.
    $node = $this->getNodeByTitle('Vicuna EN');
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-draft-available[data-label="Draft available"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Vicuna EN');
    $assert_session->pageTextContainsOnce('View existing draft');

    // EN Draft, Draft tray.
    $this->drupalGet('node/' . $node->id() . '/latest');
    $assert_session->elementExists('css', '.moderation-label-draft[data-label="Draft"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Vicuna EN');
    $assert_session->elementExists('css', '.moderation-sidebar-link#publish');
    $assert_session->elementExists('css', '.moderation-sidebar-link#moderation-sidebar-discard-draft');
    $assert_session->pageTextContainsOnce('View live content');
    $assert_session->pageTextContainsOnce('Edit draft');

    // DE Published, Published tray.
    $this->drupalGet('de/node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Vicuna DE');
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
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Create New Draft');
    $assert_session->elementExists('css', '.moderation-label-draft[data-label="Draft"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Camel EN');
    $node = $this->getNodeByTitle('Camel EN');
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-draft-available[data-label="Draft available"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->clickLink('Translate');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->clickLink('Create translation');
    $this->submitForm([
      'title[0][value]' => 'Camel DE',
      'moderation_state[0][state]' => 'published',
    ], 'Save');

    // EN Published, Draft available tray.
    $node = $this->getNodeByTitle('Camel EN');
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-draft-available[data-label="Draft available"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Camel EN');
    $assert_session->pageTextContainsOnce('View existing draft');

    // EN Draft, Draft tray.
    $this->drupalGet('node/' . $node->id() . '/latest');
    $assert_session->elementExists('css', '.moderation-label-draft[data-label="Draft"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Camel EN');
    $assert_session->elementExists('css', '.moderation-sidebar-link#publish');
    $assert_session->elementExists('css', '.moderation-sidebar-link#moderation-sidebar-discard-draft');
    $assert_session->pageTextContainsOnce('View live content');
    $assert_session->pageTextContainsOnce('Edit draft');

    // DE Published, Published tray.
    $this->drupalGet('de/node/' . $node->id());
    $assert_session->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Camel DE');
    $assert_session->elementExists('css', '.moderation-sidebar-link#create_new_draft');

    // SCENARIO 6: Published EN, Published DE, Removed DE.
    $this->drupalGet('node/add/article');
    $this->clickLink('URL alias');
    $this->submitForm([
      'title[0][value]' => 'Guanaco EN',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $this->assertSession()->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Guanaco EN');
    $this->clickLink('Translate');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->clickLink('Create translation');
    $this->submitForm([
      'title[0][value]' => 'Guanaco DE',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $this->assertSession()->elementExists('css', '.moderation-label-published[data-label="Published"]');
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $title = $this->getSession()->getPage()->find('css', '.ui-dialog-title');
    $this->assertEquals($title->getText(), 'Guanaco DE');
    $node = $this->getNodeByTitle('Guanaco EN');
    $node->removeTranslation('de');
    $node->save();
    $this->drupalGet('node/' . $node->id());
    $this->clickLink('Tasks');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->clickLink('Translate');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->clickLink('Create translation');
    $this->assertSession()->pageTextContains('Create German translation of Guanaco EN');
  }

}
