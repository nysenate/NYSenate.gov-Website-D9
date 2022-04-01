<?php

namespace Drupal\Tests\charts\Unit\Settings;

use Drupal\charts\Settings\ChartsBaseSettingsForm;
use Drupal\Tests\UnitTestCase;
use Drupal\charts\Settings\ChartsDefaultSettings;
use Drupal\charts\Settings\ChartsTypeInfo;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\charts\Plugin\chart\ChartManager;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Tests the ChartsBaseSettingsForm class.
 *
 * @coversDefaultClass \Drupal\charts\Settings\ChartsBaseSettingsForm
 * @group charts
 */
class ChartsBaseSettingsFormTest extends UnitTestCase {

  /**
   * @var \Drupal\charts\Settings\ChartsBaseSettingsForm
   */
  private $chartsBaseSettingsForm;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('plugin.manager.charts', $this->getChartsStub());
    \Drupal::setContainer($container);

    $this->chartsBaseSettingsForm = new ChartsBaseSettingsForm();

    $chartsDefaultSettingsMock = $this->getChartsDefaultSettingsMock();
    $defaultSettingsProperty = new \ReflectionProperty(ChartsBaseSettingsForm::class, 'defaultSettings');
    $defaultSettingsProperty->setAccessible(TRUE);
    $defaultSettingsProperty->setValue($this->chartsBaseSettingsForm, $chartsDefaultSettingsMock);

    $chartsTypeInfoMock = $this->getChartsTypeInfoMock();
    $chartsTypesProperty = new \ReflectionProperty(ChartsBaseSettingsForm::class, 'chartsTypes');
    $chartsTypesProperty->setAccessible(TRUE);
    $chartsTypesProperty->setValue($this->chartsBaseSettingsForm, $chartsTypeInfoMock);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    parent::tearDown();
    $this->chartsBaseSettingsForm = NULL;
  }

  /**
   * Get a chart manager mock.
   */
  private function getChartsStub() {
    $markup = $this->prophesize(TranslatableMarkup::class)->reveal();
    $chartManager = $this->prophesize(ChartManager::class);
    $chartManager->getDefinitions()->willReturn(['google' => ['id' => 'google', 'name' => $markup]]);
    return $chartManager->reveal();
  }

  /**
   * Get a default settings mock.
   */
  private function getChartsDefaultSettingsMock() {
    $chartsDefaultSettings = $this->prophesize(ChartsDefaultSettings::class);
    $chartsDefaultSettings->getDefaults()->willReturn([
      'type' => 'line',
      'library' => NULL,
      'grouping' => FALSE,
      'label_field' => NULL,
      'data_fields' => NULL,
      'field_colors' => NULL,
      'title' => '',
      'title_position' => 'out',
      'data_labels' => FALSE,
      'data_markers' => TRUE,
      'legend' => TRUE,
      'legend_position' => 'right',
      'background' => '',
      'three_dimensional' => FALSE,
      'polar' => FALSE,
      'tooltips' => TRUE,
      'tooltips_use_html' => FALSE,
      'width' => NULL,
      'width_units' => '%',
      'height' => NULL,
      'height_units' => 'px',
      'xaxis_title' => '',
      'xaxis_labels_rotation' => 0,
      'yaxis_title' => '',
      'yaxis_min' => '',
      'yaxis_max' => '',
      'yaxis_prefix' => '',
      'yaxis_suffix' => '',
      'yaxis_decimal_count' => '',
      'yaxis_labels_rotation' => 0,
      'green_to' => 100,
      'green_from' => 85,
      'yellow_to' => 85,
      'yellow_from' => 50,
      'red_to' => 50,
      'red_from' => 0,
      'max' => 100,
      'min' => 0,
    ]);
    return $chartsDefaultSettings->reveal();
  }

  /**
   * Get a charts types info mock.
   */
  private function getChartsTypeInfoMock() {
    $chartsTypeInfo = $this->prophesize(ChartsTypeInfo::class);
    $chart_types['area'] = [
      'label' => 'Area',
      'axis' => 'xy',
      'stacking' => TRUE,
    ];
    $chart_types['bar'] = [
      'label' => 'Bar',
      'axis' => 'xy',
      'axis_inverted' => TRUE,
      'stacking' => TRUE,
    ];
    $chart_types['column'] = [
      'label' => 'Column',
      'axis' => 'xy',
      'stacking' => TRUE,
    ];
    $chart_types['donut'] = [
      'label' => 'Donut',
      'axis' => 'y_only',
    ];
    $chart_types['gauge'] = [
      'label' => 'Gauge',
      'axis' => 'y_only',
      'stacking' => FALSE,
    ];
    $chart_types['line'] = [
      'label' => 'Line',
      'axis' => 'xy',
    ];
    $chart_types['pie'] = [
      'label' => 'Pie',
      'axis' => 'y_only',
    ];
    $chart_types['scatter'] = [
      'label' => 'Scatter',
      'axis' => 'xy',
    ];
    $chart_types['spline'] = [
      'label' => 'Spline',
      'axis' => 'xy',
    ];
    $chartsTypeInfo->getChartTypes()->willReturn($chart_types);
    return $chartsTypeInfo->reveal();
  }

  /**
   * Tests getChartsBaseSettingsForm().
   *
   * @param mixed $form
   *   The form array to which this form will be added.
   * @param string $pluginType
   *   A string to determine which layout to use.
   * @param array $defaults
   *   An array of existing values which will be used to populate defaults.
   * @param array $field_options
   *   An array of key => value names of fields within this chart.
   * @param array $parents
   *   If all the contents of this form should be parented under a particular
   *   namespace, an array of parent names that will be prepended to each
   *   element's #parents property.
   *
   * @dataProvider formProvider
   */
  public function testGetChartsBaseSettingsForm(array $form, $pluginType, array $defaults, array $field_options, array $parents) {
    $settingsForm = $this->chartsBaseSettingsForm->getChartsBaseSettingsForm($form, $pluginType, $defaults, $field_options, $parents);

    $this->assertIsArray($settingsForm);
    $this->assertArrayHasKey('library', $settingsForm);
    $this->assertArrayHasKey('type', $settingsForm);
    $this->assertArrayHasKey('display', $settingsForm);
    $this->assertArrayHasKey('xaxis', $settingsForm);
    $this->assertArrayHasKey('yaxis', $settingsForm);
    $this->assertArrayHasKey('xaxis_title', $settingsForm['xaxis']);
    $this->assertArrayHasKey('labels_rotation', $settingsForm['xaxis']);
    $this->assertArrayHasKey('title', $settingsForm['yaxis']);
    $this->assertArrayHasKey('minmax', $settingsForm['yaxis']);
    $this->assertArrayHasKey('prefix', $settingsForm['yaxis']);
    $this->assertArrayHasKey('suffix', $settingsForm['yaxis']);
    $this->assertArrayHasKey('decimal_count', $settingsForm['yaxis']);
    $this->assertArrayHasKey('labels_rotation', $settingsForm['yaxis']);
    $this->assertArrayHasKey('title_position', $settingsForm['display']);
    $this->assertArrayHasKey('tooltips', $settingsForm['display']);
    $this->assertArrayHasKey('data_labels', $settingsForm['display']);
    $this->assertArrayHasKey('data_markers', $settingsForm['display']);
    $this->assertArrayHasKey('legend_position', $settingsForm['display']);
    $this->assertArrayHasKey('background', $settingsForm['display']);
    $this->assertArrayHasKey('three_dimensional', $settingsForm['display']);
    $this->assertArrayHasKey('polar', $settingsForm['display']);
    $this->assertArrayHasKey('dimensions', $settingsForm['display']);
    $this->assertArrayHasKey('width', $settingsForm['display']['dimensions']);
    $this->assertArrayHasKey('width_units', $settingsForm['display']['dimensions']);
    $this->assertArrayHasKey('height', $settingsForm['display']['dimensions']);
    $this->assertArrayHasKey('height_units', $settingsForm['display']['dimensions']);
    $this->assertArrayHasKey('xaxis_title', $settingsForm['xaxis']);
    $this->assertArrayHasKey('labels_rotation', $settingsForm['xaxis']);
    $this->assertArrayHasKey('title', $settingsForm['yaxis']);
    $this->assertArrayHasKey('minmax', $settingsForm['yaxis']);
    $this->assertArrayHasKey('prefix', $settingsForm['yaxis']);
    $this->assertArrayHasKey('suffix', $settingsForm['yaxis']);
    $this->assertArrayHasKey('decimal_count', $settingsForm['yaxis']);
    $this->assertArrayHasKey('labels_rotation', $settingsForm['yaxis']);
    $this->assertArrayHasKey('gauge', $settingsForm['display']);
    $this->assertArrayHasKey('max', $settingsForm['display']['gauge']);
    $this->assertArrayHasKey('min', $settingsForm['display']['gauge']);
    $this->assertArrayHasKey('green_from', $settingsForm['display']['gauge']);
    $this->assertArrayHasKey('green_to', $settingsForm['display']['gauge']);
    $this->assertArrayHasKey('yellow_from', $settingsForm['display']['gauge']);
    $this->assertArrayHasKey('yellow_to', $settingsForm['display']['gauge']);
    $this->assertArrayHasKey('red_from', $settingsForm['display']['gauge']);
    $this->assertArrayHasKey('red_to', $settingsForm['display']['gauge']);

    if ($pluginType === 'view') {
      $this->assertArrayHasKey('label_field', $settingsForm['fields']);
      $this->assertArrayHasKey('table', $settingsForm['fields']);
      $this->assertArrayHasKey(0, $settingsForm['fields']['table']);
      $this->assertArrayHasKey('label_label', $settingsForm['fields']['table'][0]);
      $this->assertArrayHasKey('data_fields', $settingsForm['fields']['table'][0]);
      $this->assertArrayHasKey('field_colors', $settingsForm['fields']['table'][0]);
    }

    if ($pluginType === 'block') {
      $this->assertArrayHasKey('inherit_yaxis', $settingsForm['yaxis']);
      $this->assertArrayHasKey('secondary_yaxis', $settingsForm['yaxis']);
      $this->assertArrayHasKey('title', $settingsForm['yaxis']['secondary_yaxis']);
      $this->assertArrayHasKey('minmax', $settingsForm['yaxis']['secondary_yaxis']);
      $this->assertArrayHasKey('prefix', $settingsForm['yaxis']['secondary_yaxis']);
      $this->assertArrayHasKey('suffix', $settingsForm['yaxis']['secondary_yaxis']);
      $this->assertArrayHasKey('decimal_count', $settingsForm['yaxis']['secondary_yaxis']);
      $this->assertArrayHasKey('labels_rotation', $settingsForm['yaxis']['secondary_yaxis']);
    }

    if ($pluginType === 'config_form') {
      $this->assertArrayHasKey('title', $settingsForm['display']);
      $this->assertArrayHasKey('colors', $settingsForm['display']);
      $this->assertArrayHasKey(0, $settingsForm['display']['colors']);
    }

    $defaultValues = $this->getChartsDefaultSettingsMock()->getDefaults();
    $this->assertEquals($defaultValues['library'], $settingsForm['library']['#default_value']);
    $this->assertEquals($defaultValues['type'], $settingsForm['type']['#default_value']);
    $this->assertEquals($defaultValues['title'], $settingsForm['display']['title']['#default_value']);
    $this->assertEquals($defaultValues['title_position'], $settingsForm['display']['title_position']['#default_value']);
    $this->assertEquals($defaultValues['tooltips'], $settingsForm['display']['tooltips']['#default_value']);
    $this->assertEquals($defaultValues['data_labels'], $settingsForm['display']['data_labels']['#default_value']);
    $this->assertEquals($defaultValues['data_markers'], $settingsForm['display']['data_markers']['#default_value']);
    $this->assertEquals($defaultValues['legend_position'], $settingsForm['display']['legend_position']['#default_value']);
    $this->assertEquals($defaultValues['background'], $settingsForm['display']['background']['#default_value']);
    $this->assertEquals($defaultValues['three_dimensional'], $settingsForm['display']['three_dimensional']['#default_value']);
    $this->assertEquals($defaultValues['polar'], $settingsForm['display']['polar']['#default_value']);
    $this->assertEquals($defaultValues['width'], $settingsForm['display']['dimensions']['width']['#default_value']);
    $this->assertEquals($defaultValues['width_units'], $settingsForm['display']['dimensions']['width_units']['#default_value']);
    $this->assertEquals($defaultValues['height'], $settingsForm['display']['dimensions']['height']['#default_value']);
    $this->assertEquals($defaultValues['height_units'], $settingsForm['display']['dimensions']['height_units']['#default_value']);
    $this->assertEquals($defaultValues['xaxis_title'], $settingsForm['xaxis']['xaxis_title']['#default_value']);
    $this->assertEquals($defaultValues['xaxis_labels_rotation'], $settingsForm['xaxis']['labels_rotation']['#default_value']);
    $this->assertEquals($defaultValues['yaxis_title'], $settingsForm['yaxis']['title']['#default_value']);
    $this->assertEquals($defaultValues['yaxis_min'], $settingsForm['yaxis']['minmax']['min']['#default_value']);
    $this->assertEquals($defaultValues['yaxis_max'], $settingsForm['yaxis']['minmax']['max']['#default_value']);
    $this->assertEquals($defaultValues['yaxis_prefix'], $settingsForm['yaxis']['prefix']['#default_value']);
    $this->assertEquals($defaultValues['yaxis_suffix'], $settingsForm['yaxis']['suffix']['#default_value']);
    $this->assertEquals($defaultValues['yaxis_decimal_count'], $settingsForm['yaxis']['decimal_count']['#default_value']);
    $this->assertEquals($defaultValues['yaxis_labels_rotation'], $settingsForm['yaxis']['labels_rotation']['#default_value']);
    $this->assertEquals($defaultValues['max'], $settingsForm['display']['gauge']['max']['#default_value']);
    $this->assertEquals($defaultValues['min'], $settingsForm['display']['gauge']['min']['#default_value']);
    $this->assertEquals($defaultValues['green_from'], $settingsForm['display']['gauge']['green_from']['#default_value']);
    $this->assertEquals($defaultValues['green_to'], $settingsForm['display']['gauge']['green_to']['#default_value']);
    $this->assertEquals($defaultValues['yellow_from'], $settingsForm['display']['gauge']['yellow_from']['#default_value']);
    $this->assertEquals($defaultValues['yellow_to'], $settingsForm['display']['gauge']['yellow_to']['#default_value']);
    $this->assertEquals($defaultValues['red_from'], $settingsForm['display']['gauge']['red_from']['#default_value']);
    $this->assertEquals($defaultValues['red_to'], $settingsForm['display']['gauge']['red_to']['#default_value']);
  }

  /**
   * Data provider for testGetChartsBaseSettingsForm().
   */
  public function formProvider() {
    yield [
        [],
      'block',
        [
          'inherit_yaxis' => 1,
          'secondary_yaxis' => [
            'yaxis_title' => $this->prophesize(TranslatableMarkup::class)->reveal(),
            'yaxis_min' => '',
            'yaxis_max' => '',
            'yaxis_prefix' => '',
            'yaxis_suffix' => '',
            'yaxis_decimal_count' => '',
            'yaxis_labels_rotation' => 0,
          ],
        ],
        [],
        [],
    ];
    yield [
        [],
      'view',
        ['colors' => ['00FF00']],
        ['title' => $this->prophesize(TranslatableMarkup::class)->reveal()],
        [],
    ];
    yield [
        [],
      'config_form',
        ['colors' => ['00FF00', '00FF00', '00FF00', '00FF00', '00FF00', '00FF00', '00FF00', '00FF00', '00FF00', '00FF00']],
        [],
        [],
    ];
  }

}
