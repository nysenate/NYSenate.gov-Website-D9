<?php

namespace Drupal\Tests\paragraphs_features\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\editor\Entity\Editor;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\paragraphs\Traits\ParagraphsCoreVersionUiTestTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\LoginAdminTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Base class for Javascript tests for paragraphs features module.
 *
 * @package Drupal\Tests\paragraphs_features\FunctionalJavascript
 */
abstract class ParagraphsFeaturesJavascriptTestBase extends WebDriverTestBase {

  use LoginAdminTrait;
  use ParagraphsTestBaseTrait;
  use ParagraphsCoreVersionUiTestTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'field',
    'field_ui',
    'link',
    'node',
    'ckeditor',
    'paragraphs',
    'paragraphs_test',
    'paragraphs_features',
    'paragraphs_features_test',
    'shortcut',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    if ($theme = getenv('THEME')) {
      $this->assertTrue(\Drupal::service('theme_installer')->install([$theme]));
      $this->container->get('config.factory')
        ->getEditable('system.theme')
        ->set('default', $theme)
        ->set('admin', $theme)
        ->save();
    }

    // Place the breadcrumb, tested in fieldUIAddNewField().
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * {@inheritdoc}
   */
  protected function initFrontPage() {
    parent::initFrontPage();
    // Set a standard window size so that all javascript tests start with the
    // same viewport.
    $windowSize = $this->getWindowSize();
    $this->getSession()->resizeWindow($windowSize['width'], $windowSize['height']);
  }

  /**
   * Get base window size.
   *
   * @return array
   *   Return
   */
  protected function getWindowSize() {
    return [
      'width' => 1280,
      'height' => 768,
    ];
  }

  /**
   * Create content type with paragraph field and additional paragraph types.
   *
   * Paragraph types are prefixed with "test_" and for text types index will be
   * used. (fe. "$num_of_test_paragraphs = 3" will provide following test
   * paragraphs: test_1, test_2, test_3.
   *
   * Nested paragraph has type ID: test_nested.
   *
   * @param string $content_type
   *   ID for new testing content type.
   * @param int $num_of_test_paragraphs
   *   Number of additional test paragraph types beside nested one.
   */
  protected function createTestConfiguration($content_type, $num_of_test_paragraphs = 1) {
    $this->addParagraphedContentType($content_type);
    $this->loginAsAdmin([
      "administer content types",
      "administer node form display",
      "edit any $content_type content",
      "create $content_type content",
    ]);

    // Add a paragraph types.
    for ($paragraph_type_index = 1; $paragraph_type_index <= $num_of_test_paragraphs; $paragraph_type_index++) {
      $this->addParagraphsType("test_$paragraph_type_index");
      $this->addFieldtoParagraphType("test_$paragraph_type_index", "text_$paragraph_type_index", 'text_long');
    }

    // Create nested paragraph type.
    $this->addParagraphsType('test_nested');

    // Add a paragraphs field.
    $this->addParagraphsField('test_nested', 'field_paragraphs', 'paragraph');

    // Set cardinality to 4, because it's used in tests.
    $field_storage = FieldStorageConfig::loadByName('paragraph', 'field_paragraphs');
    $field_storage->set('cardinality', 4);
    $field_storage->save();

    // Set the settings for the field in the nested paragraph.
    $component = [
      'type' => 'paragraphs',
      'region' => 'content',
      'settings' => [
        'edit_mode' => 'closed',
        'add_mode' => 'modal',
        'form_display_mode' => 'default',
      ],
    ];
    EntityFormDisplay::load('paragraph.test_nested.default')
      ->setComponent('field_paragraphs', $component)
      ->save();
  }

  /**
   * Create CKEditor for testing of CKEditor integration.
   */
  protected function createEditor() {
    // Create a text format and associate CKEditor.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
    ]);
    $filtered_html_format->save();

    Editor::create([
      'format' => 'filtered_html',
      'editor' => 'ckeditor',
    ])->save();

    // After createTestConfiguration, $this->admin_user will be created by
    // LoginAdminTrait used in base class.
    $this->admin_user->addRole($this->createRole(['use text format filtered_html']));
    $this->admin_user->save();
  }

  /**
   * Get CKEditor ID, that can be used to get CKEditor objects in JavaScript.
   *
   * @param int $paragraph_index
   *   Text paragraph index.
   *
   * @return string
   *   Returns Id for CKEditor.
   */
  protected function getCkEditorId($paragraph_index) {
    return $this->getSession()->getPage()->find('xpath', '//*[@data-drupal-selector="edit-field-paragraphs-' . $paragraph_index . '"]//textarea')->getAttribute('id');
  }

  /**
   * Scroll element in middle of browser view.
   *
   * @param string $selector
   *   Selector engine name.
   * @param string|array $locator
   *   Selector locator.
   */
  public function scrollElementInView($selector, $locator) {
    if ($selector === 'xpath') {
      $this->getSession()
        ->executeScript('
          var element = document.evaluate(\'' . addcslashes($locator, '\'') . '\', document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
          element.scrollIntoView({block: "center"});
          element.focus({preventScroll:true});
        ');
    }
    else {
      $this->getSession()
        ->executeScript('
          var element = document.querySelector(\'' . addcslashes($locator, '\'') . '\');
          element.scrollIntoView({block: "center"});
        ');
    }
  }

  /**
   * Scroll element in middle of browser view and click it.
   *
   * @param string $selector
   *   Selector engine name.
   * @param string|array $locator
   *   Selector locator.
   *
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   */
  public function scrollClick($selector, $locator) {
    $this->scrollElementInView($selector, $locator);
    if ($selector === 'xpath') {
      $this->getSession()->getDriver()->click($locator);
    }
    else {
      $this->click($locator);
    }
  }

}
