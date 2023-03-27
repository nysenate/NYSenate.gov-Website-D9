<?php

namespace Drupal\Tests\media_migration\Functional;

use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests migration from file entities to media.
 *
 * @group media_migration
 *
 * @group legacy
 */
class MigrateMediaTest extends MigrateMediaTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function getExpectedEntities() {
    return [];
  }

  /**
   * Tests the result of the media migration.
   *
   * @dataProvider providerTestMediaMigrate
   */
  public function testMediaMigrate(bool $classic_node_migration, bool $preexisting_media_types) {
    $this->setClassicNodeMigration($classic_node_migration);
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

    $this->assertMigrateUpgradeViaUi(FALSE);
    $this->assertMediaMigrationResults();

    $assert_session = $this->assertSession();

    // Check configurations.
    $this->assertArticleImageFieldsAllowedTypes();
    $this->assertArticleMediaFieldsAllowedTypes();

    // Check the migrated media entities.
    //
    // Assert "Blue PNG" image media properties; including alt, title and the
    // custom 'integer' field.
    $this->assertMedia1FieldValues();
    $this->drupalGet('media/' . $this->getDestinationIdFromSourceId(1) . '/edit');
    $assert_session->statusCodeEquals(200);
    $this->assertPageTitle('Edit Image Blue PNG');
    $assert_session->hiddenFieldValueEquals('field_media_image[0][fids]', '1');
    // Alt and title properties should be migrated to the corresponding media
    // image field and have to be editable on the UI.
    $assert_session->fieldValueEquals('field_media_image[0][alt]', 'Alternative text about blue.png');
    $assert_session->fieldValueEquals('field_media_image[0][title]', 'Title copy for blue.png');
    $assert_session->fieldValueEquals('field_media_integer[0][value]', '1000');
    // The following fields should not be present.
    $assert_session->fieldNotExists('field_file_image_alt_text[0][value]');
    $assert_session->fieldNotExists('field_file_image_title_text[0][value]');
    // Author should be user 1.
    $assert_session->fieldValueEquals('uid[0][target_id]', 'user (1)');
    // Assert authored on date.
    $assert_session->fieldValueEquals('created[0][value][date]', '2020-04-24');
    $assert_session->fieldValueEquals('created[0][value][time]', '06:58:29');
    // The link to the image file has to be present and should be reachable.
    $this->getSession()->getPage()->clickLink('Blue PNG');
    $this->assertSession()->statusCodeEquals(200);

    // Assert that the image that was the content of the field_image field of
    // the test article with node ID 1 was migrated successfully, and make sure
    // that its original alt and title properties from the image field are
    // present.
    $this->assertMedia2FieldValues();
    $this->drupalGet('media/' . $this->getDestinationIdFromSourceId(2) . '/edit');
    $assert_session->statusCodeEquals(200);
    $this->assertPageTitle('Edit Image green.jpg');
    $assert_session->hiddenFieldValueEquals('field_media_image[0][fids]', '2');
    // Alt and title properties at the right place.
    $assert_session->fieldValueEquals('field_media_image[0][alt]', 'Alternate text for green.jpg image');
    $assert_session->fieldValueEquals('field_media_image[0][title]', 'Title text for green.jpg image');
    $assert_session->fieldValueEquals('field_media_integer[0][value]', '');
    // The following fields should not be present.
    $assert_session->fieldNotExists('field_file_image_alt_text[0][value]');
    $assert_session->fieldNotExists('field_file_image_title_text[0][value]');
    // Author should be user 1.
    $assert_session->fieldValueEquals('uid[0][target_id]', 'user (1)');
    // Assert created date.
    $assert_session->fieldValueEquals('created[0][value][date]', '2020-04-24');
    $assert_session->fieldValueEquals('created[0][value][time]', '08:12:02');
    // The link to the image file has to be present and should be reachable.
    $this->getSession()->getPage()->clickLink('green.jpg');
    $this->assertSession()->statusCodeEquals(200);

    // Assert "red.jpeg" image media properties with alt, title and integer.
    $this->assertMedia3FieldValues();
    $this->drupalGet('media/' . $this->getDestinationIdFromSourceId(3) . '/edit');
    $assert_session->statusCodeEquals(200);
    $this->assertPageTitle('Edit Image red.jpeg');
    $assert_session->hiddenFieldValueEquals('field_media_image[0][fids]', '3');
    // Alt and title properties at the right place.
    $assert_session->fieldValueEquals('field_media_image[0][alt]', 'Alternative text about red.jpeg');
    $assert_session->fieldValueEquals('field_media_image[0][title]', 'Title copy for red.jpeg');
    $assert_session->fieldValueEquals('field_media_integer[0][value]', '333');
    // The following fields should not be present.
    $assert_session->fieldNotExists('field_file_image_alt_text[0][value]');
    $assert_session->fieldNotExists('field_file_image_title_text[0][value]');
    // Author should be user 1.
    $assert_session->fieldValueEquals('uid[0][target_id]', 'user (1)');
    // Assert created date.
    $assert_session->fieldValueEquals('created[0][value][date]', '2020-04-24');
    $assert_session->fieldValueEquals('created[0][value][time]', '07:00:37');
    // The link to the image file has to be present and should be reachable.
    $this->getSession()->getPage()->clickLink('red.jpeg');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertMedia4FieldValues();
    $this->assertMedia5FieldValues();

    $this->assertMedia6FieldValues();
    $this->drupalGet('media/' . $this->getDestinationIdFromSourceId(6) . '/edit');
    $assert_session->statusCodeEquals(200);
    $this->assertPageTitle('Edit Document LICENSE.txt');
    $assert_session->hiddenFieldValueEquals('field_media_document[0][fids]', '6');
    // Author should be user 1.
    $assert_session->fieldValueEquals('uid[0][target_id]', 'user (1)');
    // Assert created date.
    $assert_session->fieldValueEquals('created[0][value][date]', '2020-04-24');
    $assert_session->fieldValueEquals('created[0][value][time]', '08:25:11');
    // The link to the image file has to be present and should be reachable.
    $this->getSession()->getPage()->clickLink('LICENSE.txt');
    $this->assertSession()->statusCodeEquals(200);

    // "yellow.jpg" should be migrated as well, but its alt and title properties
    // should be empty, as well as its integer field.
    $this->assertMedia7FieldValues();
    $this->drupalGet('media/' . $this->getDestinationIdFromSourceId(7) . '/edit');
    $assert_session->statusCodeEquals(200);
    $this->assertPageTitle('Edit Image yellow.jpg');
    $assert_session->hiddenFieldValueEquals('field_media_image[0][fids]', '7');
    // Alt and title properties at the right place.
    $assert_session->fieldValueEquals('field_media_image[0][alt]', '');
    $assert_session->fieldValueEquals('field_media_image[0][title]', '');
    $assert_session->fieldValueEquals('field_media_integer[0][value]', '');
    // The following fields should not be present.
    $assert_session->fieldNotExists('field_file_image_alt_text[0][value]');
    $assert_session->fieldNotExists('field_file_image_title_text[0][value]');
    // Author should be user 2.
    $assert_session->fieldValueEquals('uid[0][target_id]', 'editor (2)');
    // Authored on date.
    $assert_session->fieldValueEquals('created[0][value][date]', '2020-05-04');
    $assert_session->fieldValueEquals('created[0][value][time]', '09:53:55');
    // The link to the image file has to be present and should be reachable.
    $this->getSession()->getPage()->clickLink('yellow.jpg');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertMedia8FieldValues();
    $this->assertMedia9FieldValues();
    $this->assertMedia10FieldValues();
    $this->assertMedia11FieldValues();
    $this->assertMedia12FieldValues();

    $this->assertNode1FieldValues();

    $this->drupalGet('/media/add/image');
    $this->submitForm(
      [
        'name[0][value]' => 'New media',
        'files[field_media_image_0]' => \Drupal::service('file_system')->realpath($this->getTestFiles('image')[0]->uri),
      ],
      'Save'
    );
    $this->assertSession()->pageTextNotContains('The file could not be uploaded because the destination');
    $this->assertSession()->pageTextContains('Image New media has been created.');
  }

  /**
   * Data provider for ::testMediaMigrate().
   *
   * @return array
   *   The test cases.
   */
  public function providerTestMediaMigrate() {
    $test_cases = [
      'Classic node migration, no initial media types' => [
        'Classic node migration' => TRUE,
        'Preexisting media types' => FALSE,
      ],
      'Complete node migration, no initial media types' => [
        'Classic node migration' => FALSE,
        'Preexisting media types' => FALSE,
      ],
      'Classic node migration, preexisting media types' => [
        'Classic node migration' => TRUE,
        'Preexisting media types' => TRUE,
      ],
      'Complete node migration, preexisting media types' => [
        'Classic node migration' => FALSE,
        'Preexisting media types' => TRUE,
      ],
    ];

    // Drupal 8.8.x only has 'classic' node migrations.
    // @see https://www.drupal.org/node/3105503
    if (version_compare(\Drupal::VERSION, '8.9', '<')) {
      $test_cases = array_filter($test_cases, function ($test_case) {
        return $test_case['Classic node migration'];
      });
    }

    return $test_cases;
  }

}
