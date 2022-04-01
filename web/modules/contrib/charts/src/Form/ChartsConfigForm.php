<?php

namespace Drupal\charts\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\charts\Settings\ChartsBaseSettingsForm;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\charts\Settings\ChartsDefaultSettings;

/**
 * Charts Config Form.
 */
class ChartsConfigForm extends ConfigFormBase {

  protected $config;

  protected $defaults;

  protected $chartsBaseSettingsForm;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->config = $this->configFactory->getEditable('charts.settings');
    $this->defaults = new ChartsDefaultSettings();
    $this->chartsBaseSettingsForm = new ChartsBaseSettingsForm();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'charts_form_base';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['charts.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $parents = ['charts_default_settings'];
    $default_config = $this->config->get('charts_default_settings');
    if (!isset($default_config)) {
      $defaultSettings = new ChartsDefaultSettings();
      $default_config = $defaultSettings->getDefaults();
    }

    $defaults = array_merge($this->defaults->getDefaults(), $default_config);

    $field_options = [];
    $form['help'] = [
      '#type' => 'markup',
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => $this->t('The settings on this page are used to set
        <strong>default</strong> settings. They do not affect existing charts.
        To make a new chart, <a href="@create">create a new view</a> and select
        the display format of "Chart".', [
          '@create' => Url::fromRoute('views_ui.add')
            ->toString(),
        ]),
      '#weight' => -100,
    ];
    // Reuse the global settings form for defaults, but remove JS classes.
    $form = $this->chartsBaseSettingsForm->getChartsBaseSettingsForm($form, 'config_form', $defaults, $field_options, $parents);
    $form['xaxis']['#attributes']['class'] = [];
    $form['yaxis']['#attributes']['class'] = [];
    $form['display']['colors']['#prefix'] = NULL;
    $form['display']['colors']['#suffix'] = NULL;
    // Put settings into vertical tabs.
    $form['display']['#group'] = 'defaults';
    $form['xaxis']['#group'] = 'defaults';
    $form['yaxis']['#group'] = 'defaults';
    $form['defaults'] = ['#type' => 'vertical_tabs'];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config->set('charts_default_settings', $form_state->getValue('charts_default_settings'));
    $this->config->save();

    parent::submitForm($form, $form_state);
  }

}
