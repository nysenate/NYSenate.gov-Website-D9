<?php

namespace Drupal\Tests\htmlpurifier\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Form\FormState;
use Drupal\filter\FilterPluginCollection;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests htmlpurifier filter.
 *
 * @group HtmlPurifier
 */
class HtmlPurifierFilterTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'filter', 'htmlpurifier'];

  /**
   * @var \Drupal\htmlpurifier\Plugin\Filter\HtmlPurifierFilter
   */
  protected $filter;

  protected function setUp(): void {
    parent::setUp();

    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, []);
    $this->filter = $bag->get('htmlpurifier');
  }

  public function testMaliciousCode() {
    $input = '<img src="javascript:evil();" onload="evil();" />';
    $expected = '';
    $processed = $this->filter->process($input, 'und')->getProcessedText();
    $this->assertSame($expected, $processed);
  }

  public function testRemoveEmpty() {
    $input = '<a></a>';
    $expected = '<a></a>';
    $processed = $this->filter->process($input, 'und')->getProcessedText();
    $this->assertSame($expected, $processed);

    $configuration = [
      'AutoFormat' => [
        'RemoveEmpty' => TRUE,
      ],
    ];
    $this->filter->settings['htmlpurifier_configuration'] = Yaml::encode($configuration);

    $expected = '';
    $processed = $this->filter->process($input, 'und')->getProcessedText();
    $this->assertSame($expected, $processed);
  }

  /**
   * Test configuration validation for the filter settings form.
   *
   * @param string $configuration
   *   The HTMLPurifier configuration.
   * @param string[] $expected_errors
   *   The expected errors.
   *
   * @dataProvider providerTestConfigurationValidation
   */
  public function testConfigurationValidation(string $configuration, array $expected_errors) {
    $element = [
      '#parents' => [
        'filters',
        'htmlpurifier',
        'settings',
        'htmlpurifier_configuration',
      ],
    ];
    $form_state = new FormState();
    $filters['htmlpurifier']['settings']['htmlpurifier_configuration'] = $configuration;
    $form_state->setValue('filters', $filters);

    $this->filter->settingsFormConfigurationValidate($element, $form_state);
    $errors = $form_state->getErrors();
    if (!empty($expected_errors)) {
      $this->assertNotEmpty($errors);
      $this->assertStringContainsString($expected_errors[0], array_values($errors)[0]);
    }
    else {
      $this->assertSame($expected_errors, $errors);
    }
  }

  public function providerTestConfigurationValidation() {
    $purifier_config = \HTMLPurifier_Config::createDefault();
    $default_configuration = Yaml::encode($purifier_config->getAll());

    return [
      'invalid empty configuration' => [
        '',
        ['HTMLPurifier configuration is not valid'],
      ],
      'default configuration' => [
        $default_configuration,
        [],
      ],
      'undefined directive' => [
        str_replace('RemoveEmpty:', 'FakeDirective:', $default_configuration),
        ['Cannot set undefined directive'],
      ],
      'malformed yaml' => [
        str_replace('RemoveEmpty: false', 'UnexpectedString', $default_configuration),
        ['pars'],
      ],
    ];
  }

}
