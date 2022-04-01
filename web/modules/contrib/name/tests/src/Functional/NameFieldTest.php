<?php

namespace Drupal\Tests\name\Functional;

use Drupal\Component\Utility\Html;
use Drupal\node\Entity\NodeType;

/**
 * Various tests on creating a name field on a node.
 *
 * @group name
 */
class NameFieldTest extends NameTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'field_ui',
    'node',
    'name',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create content-type: page.
    $page = NodeType::create([
      'type' => 'page',
      'name' => 'Basic page',
    ]);
    $page->save();
  }

  /**
   * The most basic test.
   */
  public function testFieldEntry() {
    $this->drupalLogin($this->adminUser);

    $new_name_field = [
      'label' => 'Test name',
      'field_name' => 'name_test',
      'new_storage_type' => 'name',
    ];

    $this->drupalPostForm('admin/structure/types/manage/page/fields/add-field', $new_name_field, t('Save and continue'));
    $storage_settings = [];
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test/storage', $storage_settings, t('Save field settings'));
    $this->resetAll();

    // Required test.
    $field_settings = [];
    foreach ($this->nameGetFieldStorageSettings() as $key => $value) {
      $field_settings[$key] = '';
    }
    foreach ($this->nameGetFieldStorageSettingsCheckboxes() as $key => $value) {
      $field_settings[$key] = FALSE;
    }

    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test', $field_settings, t('Save settings'));

    $n = _name_translations();
    $required_messages = [
      t('Label for @field field is required.', ['@field' => $n['title']]),
      t('Label for @field field is required.', ['@field' => $n['given']]),
      t('Label for @field field is required.', ['@field' => $n['middle']]),
      t('Label for @field field is required.', ['@field' => $n['family']]),
      t('Label for @field field is required.', ['@field' => $n['generational']]),
      t('Label for @field field is required.', ['@field' => $n['credentials']]),

      t('Maximum length for @field field is required.', ['@field' => $n['title']]),
      t('Maximum length for @field field is required.', ['@field' => $n['given']]),
      t('Maximum length for @field field is required.', ['@field' => $n['middle']]),
      t('Maximum length for @field field is required.', ['@field' => $n['family']]),
      t('Maximum length for @field field is required.', ['@field' => $n['generational']]),
      t('Maximum length for @field field is required.', ['@field' => $n['credentials']]),
      t('@field options are required.', ['@field' => $n['title']]),
      t('@field options are required.', ['@field' => $n['generational']]),

      t('@field field is required.', ['@field' => t('Components')]),
      t('@field must have one of the following components: @components', [
        '@field' => t('Minimum components'),
        '@components' => Html::escape(implode(', ', [$n['given'], $n['family']])),
      ]),
    ];
    foreach ($required_messages as $message) {
      $this->assertText($message);
    }
    $field_settings = [
      'settings[components][title]' => FALSE,
      'settings[components][given]' => TRUE,
      'settings[components][middle]' => FALSE,
      'settings[components][family]' => TRUE,
      'settings[components][generational]' => FALSE,
      'settings[components][credentials]' => FALSE,

      'settings[minimum_components][title]' => TRUE,
      'settings[minimum_components][given]' => FALSE,
      'settings[minimum_components][middle]' => FALSE,
      'settings[minimum_components][family]' => FALSE,
      'settings[minimum_components][generational]' => TRUE,
      'settings[minimum_components][credentials]' => TRUE,

      'settings[max_length][title]' => 0,
      'settings[max_length][given]' => -456,
      'settings[max_length][middle]' => 'asdf',
      'settings[max_length][family]' => 3454,
      'settings[max_length][generational]' => 4.5,
      'settings[max_length][credentials]' => 'NULL',

      'settings[title_options]' => "-- --\nMr.\nMrs.\nMiss\nMs.\nDr.\nProf.\n[vocabulary:machine]",
      'settings[generational_options]' => "-- --\nJr.\nSr.\nI\nII\nIII\nIV\nV\nVI\nVII\nVIII\nIX\nX\n[vocabulary:123]",
    ];
    $this->resetAll();
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test', $field_settings, t('Save settings'));

    $required_messages = [
      t('@field must be higher than or equal to 1.', ['@field' => $n['title']]),
      t('@field must be higher than or equal to 1.', ['@field' => $n['given']]),
      t('@field must be a number.', ['@field' => $n['middle']]),
      t('@field must be lower than or equal to 255.', ['@field' => $n['family']]),
      t('@field is not a valid number.', ['@field' => $n['generational']]),
      t('@field must be a number.', ['@field' => $n['credentials']]),
      t('@field must have one of the following components: @components', [
        '@field' => t('Minimum components'),
        '@components' => Html::escape(implode(', ', [$n['given'], $n['family']])),
      ]),
      t("The vocabulary 'machine' in @field could not be found.", [
        '@field' => t('@title options', ['@title' => $n['title']]),
      ]),
      t("The vocabulary '123' in @field could not be found.", [
        '@field' => t('@generational options', ['@generational' => $n['generational']]),
      ]),
    ];
    foreach ($required_messages as $message) {
      $this->assertText($message);
    }

    // Make sure option lengths do not exceed the title lengths.
    $field_settings = [
      'settings[max_length][title]' => 5,
      'settings[max_length][generational]' => 3,
      'settings[title_options]' => "Aaaaa.\n-- --\nMr.\nMrs.\nBbbbbbbb\nMiss\nMs.\nDr.\nProf.\nCcccc.",
      'settings[generational_options]' => "AAAA\n-- --\nJr.\nSr.\nI\nII\nIII\nIV\nV\nVI\nVII\nVIII\nIX\nX\nBBBB",
    ];
    $this->resetAll();
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test', $field_settings, t('Save settings'));
    $required_messages = [
      t('The following options exceed the maximum allowed @field length: Aaaaa., Bbbbbbbb, Ccccc.', [
        '@field' => t('@title options', ['@title' => $n['title']]),
      ]),
      t('The following options exceed the maximum allowed @field length: AAAA, VIII, BBBB', [
        '@field' => t('@generational options', ['@generational' => $n['generational']]),
      ]),
    ];

    foreach ($required_messages as $message) {
      $this->assertText($message);
    }

    // Make sure option have at least one valid option.
    $field_settings = [
      'settings[title_options]' => " \n-- --\n ",
      'settings[generational_options]' => " \n-- --\n ",
    ];
    $this->resetAll();
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test', $field_settings, t('Save settings'));
    $required_messages = [
      t('@field are required.', ['@field' => t('@title options', ['@title' => $n['title']])]),
      t('@field are required.', ['@field' => t('@generational options', ['@generational' => $n['generational']])]),
    ];
    foreach ($required_messages as $message) {
      $this->assertText($message);
    }

    // Make sure option have at least one valid only have one default value.
    $field_settings = [
      'settings[title_options]' => "-- --\nMr.\nMrs.\nMiss\n-- Bob\nDr.\nProf.",
      'settings[generational_options]' => "-- --\nJr.\nSr.\nI\nII\nIII\nIV\nV\nVI\n--",
    ];
    $this->resetAll();
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test', $field_settings, t('Save settings'));
    $required_messages = [
      t('@field can only have one blank value assigned to it.', [
        '@field' => t('@title options', [
          '@title' => $n['title'],
        ]),
      ]),
      t('@field can only have one blank value assigned to it.', [
        '@field' => t('@generational options', [
          '@generational' => $n['generational'],
        ]),
      ]),
    ];
    foreach ($required_messages as $message) {
      $this->assertText($message);
    }

    // Save the field again with the default values.
    $this->resetAll();
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test', $this->nameGetFieldStorageSettings(), t('Save settings'));

    $this->assertText(t('Saved Test name configuration.'));

    // First, check that field validation is working.
    $field_settings = [
      'settings[components][title]' => FALSE,
      'settings[components][given]' => TRUE,
      'settings[components][middle]' => FALSE,
      'settings[components][family]' => TRUE,
      'settings[components][generational]' => FALSE,
      'settings[components][credentials]' => FALSE,

      'settings[minimum_components][title]' => TRUE,
      'settings[minimum_components][given]' => FALSE,
      'settings[minimum_components][middle]' => FALSE,
      'settings[minimum_components][family]' => FALSE,
      'settings[minimum_components][generational]' => TRUE,
      'settings[minimum_components][credentials]' => TRUE,

      'settings[max_length][title]' => 0,
      'settings[max_length][given]' => -456,
      'settings[max_length][middle]' => 'asdf',
      'settings[max_length][family]' => 3454,
      'settings[max_length][generational]' => 4.5,
      'settings[max_length][credentials]' => 'NULL',

      'settings[title_options]' => "-- --\nMr.\nMrs.\nMiss\nMs.\nDr.\nProf.\n[vocabulary:machine]",
      'settings[generational_options]' => "-- --\nJr.\nSr.\nI\nII\nIII\nIV\nV\nVI\nVII\nVIII\nIX\nX\n[vocabulary:123]",

    ];
    $this->resetAll();
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test', $field_settings, t('Save settings'));

    $required_messages = [
      t('Maximum length for @field must be higher than or equal to 1.', ['@field' => $n['title']]),
      t('Maximum length for @field must be higher than or equal to 1.', ['@field' => $n['given']]),
      t('Maximum length for @field must be a number.', ['@field' => $n['middle']]),
      t('Maximum length for @field must be lower than or equal to 255.', ['@field' => $n['family']]),
      t('Maximum length for @field is not a valid number.', ['@field' => $n['generational']]),
      t('Maximum length for @field must be a number.', ['@field' => $n['credentials']]),
      t('@field must have one of the following components: @components', [
        '@field' => t('Minimum components'),
        '@components' => Html::escape(implode(', ', [$n['given'], $n['family']])),
      ]),
      t("The vocabulary 'machine' in @field could not be found.", [
        '@field' => t('@title options', [
          '@title' => $n['title'],
        ]),
      ]),
      t("The vocabulary '123' in @field could not be found.", [
        '@field' => t('@generational options', ['@generational' => $n['generational']]),
      ]),
    ];
    foreach ($required_messages as $message) {
      $this->assertText($message);
    }

    $field_settings = [
    // title, description, none.
      'settings[title_display][title]' => 'description',
      'settings[title_display][given]' => 'description',
      'settings[title_display][middle]' => 'description',
      'settings[title_display][family]' => 'description',
      'settings[title_display][generational]' => 'description',
      'settings[title_display][credentials]' => 'description',

      'settings[size][title]' => 6,
      'settings[size][given]' => 20,
      'settings[size][middle]' => 20,
      'settings[size][family]' => 20,
      'settings[size][generational]' => 5,
      'settings[size][credentials]' => 35,
    ];

    $this->resetAll();
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_name_test');

    foreach ($field_settings as $name => $value) {
      $this->assertFieldByName($name, $value);
    }

    // Check help text display.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_name_test');
    $edit = [
      'description' => 'This is a description.',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_name_test/storage');
    $edit = [
      'cardinality' => 'number',
      'cardinality_number' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->drupalGet('node/add/page');
    $this->assertUniqueText('This is a description.', 'Field description is shown once when field cardinality is 1.');

    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_name_test/storage');
    $edit = [
      'cardinality' => 'number',
      'cardinality_number' => 3,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->drupalGet('node/add/page');
    $this->assertUniqueText('This is a description.', 'Field description is shown once when field cardinality is 3.');

    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_name_test/storage');
    $edit = [
      'cardinality' => '-1',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->drupalGet('node/add/page');
    $this->assertUniqueText('This is a description.', 'Field description is shown once when field cardinality is unlimited.');
  }

  /**
   * Helper function to get the field storage settings.
   *
   * @return array
   *   The settings.
   */
  public function nameGetFieldStorageSettings() {
    return [
      'settings[components][title]' => TRUE,
      'settings[components][given]' => TRUE,
      'settings[components][middle]' => TRUE,
      'settings[components][family]' => TRUE,
      'settings[components][generational]' => TRUE,
      'settings[components][credentials]' => TRUE,

      'settings[minimum_components][title]' => FALSE,
      'settings[minimum_components][given]' => TRUE,
      'settings[minimum_components][middle]' => FALSE,
      'settings[minimum_components][family]' => TRUE,
      'settings[minimum_components][generational]' => FALSE,
      'settings[minimum_components][credentials]' => FALSE,

      'settings[max_length][title]' => 31,
      'settings[max_length][given]' => 63,
      'settings[max_length][middle]' => 127,
      'settings[max_length][family]' => 63,
      'settings[max_length][generational]' => 15,
      'settings[max_length][credentials]' => 255,

      'settings[labels][title]' => t('Title'),
      'settings[labels][given]' => t('Given'),
      'settings[labels][middle]' => t('Middle name(s)'),
      'settings[labels][family]' => t('Family'),
      'settings[labels][generational]' => t('Generational'),
      'settings[labels][credentials]' => t('Credentials'),

      'settings[sort_options][title]' => TRUE,
      'settings[sort_options][generational]' => FALSE,

      'settings[title_options]' => "-- --\nMr.\nMrs.\nMiss\nMs.\nDr.\nProf.",
      'settings[generational_options]' => "-- --\nJr.\nSr.\nI\nII\nIII\nIV\nV\nVI\nVII\nVIII\nIX\nX",
    ];
  }

  /**
   * Helper function to get the field storage checkbox settings.
   *
   * @return array
   *   The settings.
   */
  public function nameGetFieldStorageSettingsCheckboxes() {
    return [
      'settings[components][title]' => TRUE,
      'settings[components][given]' => TRUE,
      'settings[components][middle]' => TRUE,
      'settings[components][family]' => TRUE,
      'settings[components][generational]' => TRUE,
      'settings[components][credentials]' => TRUE,

      'settings[minimum_components][title]' => FALSE,
      'settings[minimum_components][given]' => TRUE,
      'settings[minimum_components][middle]' => FALSE,
      'settings[minimum_components][family]' => TRUE,
      'settings[minimum_components][generational]' => FALSE,
      'settings[minimum_components][credentials]' => FALSE,

      'settings[sort_options][title]' => TRUE,
      'settings[sort_options][generational]' => FALSE,
    ];
  }

}
