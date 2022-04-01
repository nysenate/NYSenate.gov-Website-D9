<?php

namespace Drupal\Tests\node_revision_generate\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\node_revision_generate\Traits\NodeRevisionGenerateTestTrait;

/**
 * Test the node_revision_generate_generate_revisions form.
 *
 * @group node_revision_generate
 */
class NodeRevisionGenerateTest extends BrowserTestBase {

  use NodeRevisionGenerateTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node_revision_delete',
    'node_revision_generate',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Creating content types.
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Article',
      ]);
    $type->save();
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'page',
        'name' => 'Page',
      ]);
    $type->save();
  }

  /**
   * Creates an older node.
   *
   * @param string $type
   *   Node's type.
   * @param int $timestamp
   *   Timestamp for the node creation date and revision date. One year ago by
   *   default.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created node.
   *
   * @throws \Exception
   */
  public function createOlderNode($type, $timestamp = 60 * 60 * 24 * 30 * 12) {
    return $this->drupalCreateNode([
      'type' => $type,
      'created' => time() - $timestamp,
      'revision_timestamp' => time() - $timestamp,
    ]);
  }

  /**
   * Adds a revision to a node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node.
   * @param int $timestamp
   *   The revision creation date.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addRevision(Node &$node, $timestamp) {
    $node->setNewRevision();
    $node->setRevisionCreationTime($timestamp);
    $node->save();
  }

  /**
   * Tests the form, the permission and the link.
   */
  public function testGenerationForm() {
    // Going to the config page.
    $this->drupalGet('admin/config/content/node_revision_delete/generate_revisions');

    // Checking that the page is not accesible for anonymous users.
    $this->assertSession()->statusCodeEquals(403);

    // Creating a user with the module permission.
    $account = $this->drupalCreateUser(
      [
        'administer node_revision_delete',
        'generate revisions',
        'access administration pages',
      ]
    );
    // Log in.
    $this->drupalLogin($account);

    // @TODO Check the module local task.
    // Going to the config page.
    $this->drupalGet('admin/config/content/node_revision_delete/generate_revisions');
    // Checking that the request has succeeded.
    $this->assertSession()->statusCodeEquals(200);

    // Checking the page title.
    $this->assertSession()
      ->elementTextContains('css', 'h1', 'Node Revision Generate');

    // Checking the content types are availables.
    $this->assertSession()->checkboxNotChecked('bundles[article]');
    $this->assertSession()->checkboxNotChecked('bundles[page]');

    // Checking the field are disabled.
    $this->assertSession()->fieldDisabled('bundles[article]');
    $this->assertSession()->fieldDisabled('bundles[page]');

    // Checking the message for content types without nodes.
    $this->assertSession()->pageTextContains('Article. There are no nodes.');
    $this->assertSession()->pageTextContains('Page. There are no nodes.');

    // Defining one day.
    $one_day = 60 * 60 * 24;
    $time = time();

    // Test when is not possible to create new revisions.
    // Creating nodes.
    $node_article = $this->createOlderNode('article');
    $node_page = $this->createOlderNode('page');

    // Reloading the page now that we have nodes for the content types.
    $this->drupalGet('admin/config/content/node_revision_delete/generate_revisions');

    // Checking the message for content types without nodes not exists.
    $this->assertSession()->pageTextNotContains('Article. There are no nodes.');
    $this->assertSession()->pageTextNotContains('Page. There are no nodes.');

    // Getting the config factory service.
    $entity_type_manager = $this->container->get('entity_type.manager');
    // Getting the node storage.
    $node_storage = $entity_type_manager->getStorage('node');
    // Getting the revision list for article.
    $vids_article = $node_storage->revisionIds($node_article);
    // At this point we should only have 1 revision, the default revision.
    $this->assertCount(1, $vids_article, 'There must exists only one revision for the Article content type.');
    // Getting the revision list for page.
    $vids_page = $node_storage->revisionIds($node_page);
    // At this point we should only have 1 revision, the default revision.
    $this->assertCount(1, $vids_page, 'There must exists only one revision for the Page content type.');

    // Getting the revisions age.
    $revision_age = $this->getRevisionAge();

    $days = 12;
    // Revision number start in 1 as the default revision is created when
    // you create the node.
    $number_of_revisions = 1;
    for ($i = 2; $i >= 0; $i--) {
      // First iteration. $days == 12 and revision_age == month, so we can't
      // create a new revision, we need to wait at least more than 30 days.
      // Second iteration. $days == 6 and revision_age == week, so we can't
      // create a new revision, we need to wait at least more than 7 days.
      // Third iteration. $days == 0 and revision_age == day, so we can't
      // create a new revision, we need to wait at least more than 1 day.
      // Creating a revision for article.
      $this->addRevision($node_article, $time - ($one_day * $days));
      // Creating a revision for page.
      $this->addRevision($node_page, $time - ($one_day * $days) + $one_day);

      $days -= 6;
      // We try to create first one revision, then two and three in the third
      // test.
      ++$number_of_revisions;

      // Form values to send.
      $form_values = [
        'bundles[article]' => 'article',
        'bundles[page]' => 'page',
        'revisions_number' => $i + 1,
        'number' => 1,
        // $revision_age[0] == day.
        // $revision_age[1] == week.
        // $revision_age[2] == month.
        'time' => $revision_age[$i],
      ];

      // Sending the form.
      $this->drupalPostForm(NULL, $form_values, 'op');

      // Getting the revision list for article.
      $vids_article = $node_storage->revisionIds($node_article);
      // Checking the number of revisions for article.
      // We should have only one revision, the default revision created when we
      // create the node. As this test is to test when is not possible to create
      // any revision and we don't have nodes meeting the requirements for
      // revision creation.
      $this->assertCount($number_of_revisions, $vids_article, 'There must exists only ' . $number_of_revisions . ' revisions for Article.');
      // Getting the revision list for page.
      $vids_page = $node_storage->revisionIds($node_page);
      // Checking the number of revisions for page.
      // We should have only one revision, the default revision created when we
      // create the node. As this test is to test when is not possible to create
      // any revision and we don't have nodes meeting the requirements for
      // revision creation.
      $this->assertCount($number_of_revisions, $vids_page, 'There must exists only ' . $number_of_revisions . ' revisions for Page.');

      // Verifying the save message.
      $this->assertSession()->pageTextContains('There are not more available nodes to generate revisions of the selected content types and specified options.');
    }

    // Test when is possible to create 1 revision.
    // Creating a node.
    $node_article = $this->createOlderNode('article');

    // Revision number start in 1 as the default revision is created when
    // you create the node.
    $number_of_revisions = 1;

    for ($i = 2; $i >= 0; $i--) {
      // Form values to send.
      $form_values = [
        'bundles[article]' => 'article',
        'bundles[page]' => 'page',
        'revisions_number' => 1,
        'number' => 1,
        // $revision_age[0] == day.
        // $revision_age[1] == week.
        // $revision_age[2] == month.
        'time' => $revision_age[$i],
      ];

      // Sending the form.
      $this->drupalPostForm(NULL, $form_values, 'op');

      ++$number_of_revisions;

      // Getting the revision list for article.
      $vids_article = $node_storage->revisionIds($node_article);
      // Checking the number of revisions for article.
      $this->assertCount($number_of_revisions, $vids_article, 'There must exists only ' . $number_of_revisions . ' revisions for Article.');

      // Verifying the save message.
      $this->assertSession()->pageTextContains('One revision has been created for the selected content types.');
      $this->assertSession()->pageTextContains('Revisions were generated up to the current date, no revisions were generated with a date in the future. So, depending on this maybe we will not generate the number of revisions you expect.');
    }

    // Test when is possible to create more than one revision.
    // Deleting the last article created.
    $node_article->delete();
    // Creating nodes.
    $node_article = $this->createOlderNode('article');
    $node_page = $this->createOlderNode('page');

    // Revision number start in 1 as the default revision is created when
    // you create the node.
    $number_of_revisions = 1;
    $revisions_number = 1;
    $number = 1;

    for ($i = 2; $i >= 0; $i--) {
      // Form values to send.
      $form_values = [
        'bundles[article]' => 'article',
        'bundles[page]' => 'page',
        'revisions_number' => $revisions_number,
        'number' => $number,
        // $revision_age[0] == day.
        // $revision_age[1] == week.
        // $revision_age[2] == month.
        'time' => $revision_age[$i],
      ];

      $number_of_revisions += $revisions_number;

      // Sending the form.
      $this->drupalPostForm(NULL, $form_values, 'op');

      // Getting the revision list for article.
      $vids_article = $node_storage->revisionIds($node_article);
      // Checking the number of revisions for article.
      $this->assertCount($number_of_revisions, $vids_article, 'There must exists only ' . $number_of_revisions . ' revisions for Article.');
      // Getting the revision list for article.
      $vids_page = $node_storage->revisionIds($node_page);
      // Checking the number of revisions for article.
      $this->assertCount($number_of_revisions, $vids_page, 'There must exists only ' . $number_of_revisions . ' revisions for Page.');

      // The created revisions is equal to the number of generated revisions in
      // this case we generate for 2 content types.
      $created_revisions = $revisions_number * 2;
      // Verifying the save message.
      $this->assertSession()->pageTextContains('A total of ' . $created_revisions . ' revisions were created for the selected content types.');
      $this->assertSession()->pageTextContains('Revisions were generated up to the current date, no revisions were generated with a date in the future. So, depending on this maybe we will not generate the number of revisions you expect.');

      $revisions_number += 2;
      $number++;
    }

    // Test when we create less revisions than expected because the last
    // revision before creation is not very old.
    // Deleting the last nodes created.
    $node_article->delete();
    $node_page->delete();
    // Creating nodes.
    $node_article = $this->createOlderNode('article', $one_day * 237);
    $node_page = $this->createOlderNode('page', $one_day * 237);

    // 7 revisions should be created for months. 237 - 210 (7 months) = 27 days
    // 3 revisions should be created for weeks. 27 - 21 (3 weeks) = 6 days
    // 6 revisions should be created for days. 6 days available for revisions.
    $revisions_number = [6, 3, 7];
    // Revision number start in 1 as the default revision is created when
    // you create the node.
    $number_of_revisions = 1;

    for ($i = 2; $i >= 0; $i--) {
      // Form values to send.
      $form_values = [
        'bundles[article]' => 'article',
        'bundles[page]' => 'page',
        'revisions_number' => 10,
        'number' => 1,
        // $revision_age[0] == day.
        // $revision_age[1] == week.
        // $revision_age[2] == month.
        'time' => $revision_age[$i],
      ];

      // Sending the form.
      $this->drupalPostForm(NULL, $form_values, 'op');

      $number_of_revisions += $revisions_number[$i];
      // Getting the revision list for article.
      $vids_article = $node_storage->revisionIds($node_article);
      // Checking the number of revisions for article.
      $this->assertCount($number_of_revisions, $vids_article, 'There must exists only ' . $number_of_revisions . ' revisions for Article.');
      // Getting the revision list for article.
      $vids_page = $node_storage->revisionIds($node_page);
      // Checking the number of revisions for article.
      $this->assertCount($number_of_revisions, $vids_page, 'There must exists only ' . $number_of_revisions . ' revisions for Page.');

      // The created revisions is equal to the number of generated revisions in
      // this case we generate for 2 content types.
      $created_revisions = $revisions_number[$i] * 2;
      // Verifying the save message.
      $this->assertSession()->pageTextContains('A total of ' . $created_revisions . ' revisions were created for the selected content types.');
      $this->assertSession()->pageTextContains('Revisions were generated up to the current date, no revisions were generated with a date in the future. So, depending on this maybe we will not generate the number of revisions you expect.');
    }
  }

}
