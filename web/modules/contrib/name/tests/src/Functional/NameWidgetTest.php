<?php

namespace Drupal\Tests\name\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\node\Entity\NodeType;

/**
 * Various tests on creating a name widget on a node.
 *
 * @group name
 */
class NameWidgetTest extends NameTestBase {

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

    // Set up a field of each label display and test it shows.
    $field_settings = [
      'settings[components][title]' => TRUE,
      'settings[components][given]' => TRUE,
      'settings[components][middle]' => TRUE,
      'settings[components][family]' => TRUE,
      'settings[components][generational]' => TRUE,
      'settings[components][credentials]' => TRUE,

      'settings[minimum_components][title]' => TRUE,
      'settings[minimum_components][given]' => TRUE,
      'settings[minimum_components][middle]' => TRUE,
      'settings[minimum_components][family]' => TRUE,
      'settings[minimum_components][generational]' => TRUE,
      'settings[minimum_components][credentials]' => TRUE,

      'settings[show_component_required_marker]' => TRUE,

      'settings[labels][title]' => t('Title'),
      'settings[labels][given]' => t('Given'),
      'settings[labels][middle]' => t('Middle name(s)'),
      'settings[labels][family]' => t('Family'),
      'settings[labels][generational]' => t('Generational'),
      'settings[labels][credentials]' => t('Credentials'),

      'settings[title_display][title]' => 'title',
      'settings[title_display][given]' => 'title',
      'settings[title_display][middle]' => 'description',
      'settings[title_display][family]' => 'placeholder',
      'settings[title_display][generational]' => 'none',
      'settings[title_display][credentials]' => 'placeholder',

      'settings[field_type][title]' => 'select',
      'settings[field_type][given]' => 'text',
      'settings[field_type][middle]' => 'text',
      'settings[field_type][family]' => 'text',
      'settings[field_type][generational]' => 'autocomplete',
      'settings[field_type][credentials]' => 'text',

      'settings[max_length][title]' => 31,
      'settings[max_length][given]' => 45,
      'settings[max_length][middle]' => 127,
      'settings[max_length][family]' => 63,
      'settings[max_length][generational]' => 15,
      'settings[max_length][credentials]' => 255,

      'settings[size][title]' => 6,
      'settings[size][given]' => 10,
      'settings[size][middle]' => 20,
      'settings[size][family]' => 25,
      'settings[size][generational]' => 5,
      'settings[size][credentials]' => 35,

      'settings[credentials_inline]' => TRUE,

      'settings[sort_options][title]' => TRUE,
      'settings[sort_options][generational]' => FALSE,

      'settings[title_options]' => "-- --\nMr.\nMrs.\nMiss\nMs.\nDr.\nProf.",
      'settings[generational_options]' => "-- --\nJr.\nSr.\nI\nII\nIII\nIV\nV\nVI\nVII\nVIII\nIX\nX",

      'settings[component_layout]' => 'default',
    ];

    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test', $field_settings, t('Save settings'));

    $this->drupalGet('node/add/page');

    $this->assertFieldByTypeAndName('select', 'field_name_test[0][title]');
    $this->assertFieldByTypeAndName('input', 'field_name_test[0][given]');
    $this->assertFieldByTypeAndName('input', 'field_name_test[0][middle]');
    $this->assertFieldByTypeAndName('input', 'field_name_test[0][family]');
    $this->assertFieldByTypeAndName('input', 'field_name_test[0][generational]');
    $this->assertFieldByTypeAndName('input', 'field_name_test[0][credentials]');

    // Checks the existence and positioning of the components.
    foreach (_name_component_keys() as $component) {
      $this->assertComponentSettings($component, $field_settings);
    }

    $this->assertFieldSettings($field_settings);

    // Test the language layouts.
    $this->verbose('Testing asian');
    $field_settings['settings[component_layout]'] = 'asian';
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test', $field_settings, t('Save settings'));
    $this->drupalGet('node/add/page');
    $this->assertFieldSettings($field_settings);

    $this->verbose('Testing eastern');
    $field_settings['settings[component_layout]'] = 'eastern';
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test', $field_settings, t('Save settings'));
    $this->drupalGet('node/add/page');
    $this->assertFieldSettings($field_settings);

    $this->verbose('Testing german');
    $field_settings['settings[component_layout]'] = 'german';
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test', $field_settings, t('Save settings'));
    $this->drupalGet('node/add/page');
    $this->assertFieldSettings($field_settings);

    $this->verbose('Testing show_component_required_marker unchecked.');
    $field_settings = [
      'settings[show_component_required_marker]' => FALSE,
      'settings[component_layout]' => 'default',
      // 'settings[credentials_inline]' => TRUE,
      // 'settings[component_layout]' => 'default',
    ] + $field_settings;
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_name_test', $field_settings, t('Save settings'));
    $this->drupalGet('node/add/page');
    foreach (_name_component_keys() as $component) {
      $this->assertComponentSettings($component, $field_settings);
    }

  }

  /**
   * Asserts that the field settings appear in the correct order.
   *
   * @param array $settings
   *   The field settings, as form post array.
   */
  protected function assertFieldSettings(array $settings) {
    $xpath = '//div[@id="edit-field-name-test-wrapper"]/div/div';
    $elements = $this->xpath($xpath);
    $this->assertNotEmpty($elements, 'No components found.');

    $content = '';
    foreach ($elements as $element) {
      $content .= str_replace(["\n", "\r"], " ", $element->getHtml());
    }

    $this->verbose(Html::escape($content));
    $this->verbose($settings["settings[component_layout]"]);
    switch ($settings["settings[component_layout]"]) {
      case 'asian':
        $regexp = '/name-family-wrapper.*name-middle-wrapper.*name-given-wrapper.*name-title-wrapper.*name-credentials-wrapper.*/';
        if (strpos($content, 'name-generational-wrapper')) {
          $this->assertTrue(FALSE, "Generational field is not rendered with asian layout.");
        }
        break;

      case 'eastern':
        $regexp = '/name-title-wrapper.*name-family-wrapper.*name-given-wrapper.*name-middle-wrapper.*name-generational-wrapper.*name-credentials-wrapper.*/';
        break;

      case 'german':
        $regexp = '/name-title-wrapper.*name-credentials-wrapper.*name-given-wrapper.*name-middle-wrapper.*name-family-wrapper.*/';
        if (strpos($content, 'name-generational-wrapper')) {
          $this->assertTrue(FALSE, "Generational field is not rendered with german layout.");
        }
        break;

      case 'default':
      default:
        $regexp = '/name-title-wrapper.*name-given-wrapper.*name-middle-wrapper.*name-family-wrapper.*name-generational-wrapper.*name-credentials-wrapper.*/';
        break;
    }

    $this->assertTrue((bool) preg_match($regexp, $content), 'Generational field wrapper classes appear to be in the correct order.');

    // @todo: Tests for settings[credentials_inline] setting.
  }

  /**
   * Asserts that the components exists and appear in the right order.
   *
   * @param string $key
   *   The name component key, for example 'given'.
   * @param array $settings
   *   The field settings, as form post array.
   */
  protected function assertComponentSettings($key, array $settings) {
    $xpath = '//div[contains(@class,:value)]';
    $elements = $this->xpath($this->buildXPathQuery($xpath, [':value' => "name-{$key}-wrapper"]));
    $this->assertNotEmpty($elements, "Component $key field found.");
    $object = reset($elements);

    $type = $settings["settings[field_type][{$key}]"] == 'select' ? 'select' : 'input';
    $show_required = $settings['settings[show_component_required_marker]'];
    $is_required = $settings["settings[minimum_components][{$key}]"];
    $content = str_replace(["\n", "\r"], " ", $object->getHtml());

    switch ($settings["settings[title_display][$key]"]) {
      case 'title':
        $result = (bool) preg_match('/<label .*<' . $type . ' /i', $content);
        $this->assertTrue($result, "Testing label is before field of type $type for $key component.");
        if ($result) {
          $required_marker_preg = '@<label .*?class=".*?js-form-required.*form-required.*?".*>@';
          if ($show_required && $is_required) {
            $this->assertTrue((bool) preg_match($required_marker_preg, $content), "Required class is added for $key component in label");
          }
          else {
            $this->assertFalse((bool) preg_match($required_marker_preg, $content), "Required class is not added for $key component in label");
          }
        }
        break;

      case 'description':
        $result = (bool) preg_match('/<' . $type . ' .*<label /i', $content);
        $this->assertTrue($result, "Testing label is after field of type $type for $key component.");
        if ($result) {
          $required_marker_preg = '@<label .*?class=".*?js-form-required.*form-required.*?">@';
          if ($show_required && $is_required) {
            $this->assertTrue((bool) preg_match($required_marker_preg, $content), "Required class is added for $key component in label");
          }
          else {
            $this->assertFalse((bool) preg_match($required_marker_preg, $content), "Required class is not added for $key component in label");
          }
        }
        break;

      case 'placeholder':
        $result = (bool) preg_match('@<' . $type . ' [^>]*?placeholder=".*?' . $settings["settings[labels][$key]"] . '.*?"@', $content);
        $this->assertTrue($result, "Testing label is a placeholder on the field of type $type for $key component.");
        if ($result) {
          $required_marker_preg = '@<' . $type . ' [^>]*?placeholder=".*?Required.*?"@';
          if ($show_required && $is_required) {
            $this->assertTrue((bool) preg_match($required_marker_preg, $content), "Required text is added for $key component in placeholder attribute");
          }
          else {
            $this->assertFalse((bool) preg_match($required_marker_preg, $content), "Required text is added for $key component in placeholder attribute");
          }
        }
        break;

      case 'attribute':
        $result = (bool) preg_match('@<' . $type . ' [^>]*?title=".*?' . $settings["settings[labels][$key]"] . '.*?"@', $content);
        $this->assertTrue($result, "Testing label is a title attribute on the field of type $type for $key component.");
        if ($result) {
          $required_marker_preg = '@<' . $type . ' [^>]*?title=".*?Required.*?"@';
          if ($show_required && $is_required) {
            $this->assertTrue((bool) preg_match($required_marker_preg, $content), "Required text is added for $key component in $type title attribute");
          }
          else {
            $this->assertFalse((bool) preg_match($required_marker_preg, $content), "Required text is added for $key component in $type title attribute");
          }
        }
        break;

      case 'none':
        $result = (bool) preg_match('@<label [^>]*?class=".*?visually-hidden.*?"@', $content);
        $this->assertTrue($result, "Testing label is present but hidden on the field of type $type for $key component.");
        break;

    }

    if (isset($settings["settings[max_length][{$key}]"]) && $type != 'select') {
      $result = (bool) preg_match('@<' . $type . ' [^>]*?maxlength="' . $settings["settings[max_length][{$key}]"] . '"@', $content);
      $this->assertTrue($result, "Testing max_length is set on field of type $type for $key component.");
    }
    if (isset($settings["settings[size][{$key}]"]) && $type != 'select') {
      $result = (bool) preg_match('@<' . $type . ' [^>]*?size="' . $settings["settings[size][{$key}]"] . '"@', $content);
      $this->assertTrue($result, "Testing size is set on field of type $type for $key component.");
    }
  }

  /**
   * Asserts that the given field exists.
   *
   * @param string $type
   *   The form field type, for example 'input' or 'select'.
   * @param string $name
   *   The form field full name.
   * @param mixed $value
   *   The expected value that the form field has.
   * @param string $message
   *   The failure message.
   *
   * @todo Replace calls to this method using Mink's NodeElement::getValue().
   */
  protected function assertFieldByTypeAndName($type, $name, $value = NULL, $message = NULL) {
    if (!isset($message)) {
      if (!isset($value)) {
        $message = new FormattableMarkup('Found @type field with name @name', [
          '@name' => var_export($name, TRUE),
          '@type' => $type,
        ]);
      }
      else {
        $message = new FormattableMarkup('Found @type field with name @name and value @value', [
          '@name' => var_export($name, TRUE),
          '@type' => $type,
          '@value' => var_export($value, TRUE),
        ]);
      }
    }
    return $this->assertFieldByXPath($this->constructFieldXpathByTypeAndAttribute($type, 'name', $name), $value, $message);
  }

  /**
   * Helper: Constructs an XPath for the given set of attributes and value.
   *
   * @param string $type
   *   Type of element.
   * @param string $attribute
   *   Field attributes.
   * @param string $value
   *   Value of field.
   *
   * @return string
   *   XPath for specified values.
   */
  protected function constructFieldXpathByTypeAndAttribute($type, $attribute, $value) {
    $xpath = '//' . $type . '[@' . $attribute . '=:value]';
    return $this->buildXPathQuery($xpath, [':value' => $value]);
  }

}
