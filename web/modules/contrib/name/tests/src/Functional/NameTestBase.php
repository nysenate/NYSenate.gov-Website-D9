<?php

namespace Drupal\Tests\name\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Helper test class with some added functions for testing.
 */
abstract class NameTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'field_ui',
    'node',
    'name',
  ];

  /**
   * Web user to run the tests for.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $webUser;

  /**
   * Admin user to run the tests for.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->webUser = $this->drupalCreateUser([]);
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'administer content types',
      'access content',
      'access administration pages',
      'administer node fields',
      'bypass node access'
    ]);
  }

  /**
   * Helper function.
   *
   * @todo Replace calls to this method using Mink's NodeElement::isChecked().
   */
  protected function assertNoFieldCheckedByName($name, $message = '') {
    $elements = $this->xpath('//input[@name=:name]', [':name' => $name]);
    return $this->assertTrue(isset($elements[0]) && empty($elements[0]['checked']), $message ? $message : t('Checkbox field @name is not checked.', [
      '@name' => $name,
    ]), t('Browser'));
  }

  /**
   * Helper function.
   *
   * @todo Replace calls to this method using Mink's NodeElement::isChecked().
   */
  protected function assertFieldCheckedByName($name, $message = '') {
    $elements = $this->xpath('//input[@name=:name]', [':name' => $name]);
    return $this->assertTrue(isset($elements[0]) && !empty($elements[0]['checked']), $message ? $message : t('Checkbox field @name is checked.', [
      '@name' => $name,
    ]), t('Browser'));
  }

  /**
   * Helper function.
   *
   * @todo describe this method.
   */
  protected function assertNameFormat($name_components, $type, $object, $format, $expected, array $options = []) {
    $this->assertNameFormats($name_components, $type, $object, [$format => $expected], $options);
  }

  /**
   * Helper function.
   *
   * @todo describe this method.
   */
  protected function assertNameFormats($name_components, $type, $object, array $names, array $options = []) {
    foreach ($names as $format => $expected) {
      $value = \Drupal::service('name.format_parser')->parse($name_components, $format);
      $this->assertSame($value, $expected, t("Name value for '@name' was '@actual', expected value '@expected'. Components were: %components", [
        '@name' => $format,
        '@actual' => $value,
        '@expected' => $expected,
        '%components' => implode(' ', $name_components),
      ]));
    }
  }

}
