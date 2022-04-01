<?php

namespace Drupal\charts_highcharts\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Charts Config Form.
 */
class ChartsConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'charts_highcharts_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['charts_highcharts.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('charts_highcharts.settings');

    $form['placeholder'] = [
      '#title' => $this->t('Placeholder'),
      '#type' => 'fieldset',
      '#description' => $this->t(
        'This is a placeholder for Highcharts-specific library options. If you would like to help build this out, please work from <a href="@issue_link">this issue</a>.', [
          '@issue_link' => Url::fromUri('https://www.drupal.org/project/charts/issues/3046981')->toString(),
        ]),
    ];

    $form['legend'] = [
      '#title' => $this->t('Legend Settings'),
      '#type' => 'fieldset',
    ];

    $form['legend']['legend_layout'] = [
      '#title' => $this->t('Legend layout'),
      '#type' => 'select',
      '#options' => [
        'vertical' => t('Vertical'),
        'horizontal' => t('Horizontal'),
      ],
      '#default_value' => $config->get('legend_layout'),
    ];

    $form['legend']['legend_background_color'] = [
      '#title' => $this->t('Legend background color'),
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 7,
      '#attributes' => ['placeholder' => t('transparent')],
      '#description' => t('Leave blank for a transparent background.'),
      '#default_value' => $config->get('legend_background_color'),
    ];

    $form['legend']['legend_border_width'] = [
      '#title' => $this->t('Legend border width'),
      '#type' => 'select',
      '#options' => [
        0 => t('None'),
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
      ],
      '#default_value' => $config->get('legend_border_width'),
    ];

    $form['legend']['legend_shadow'] = [
      '#title' => $this->t('Legend shadow'),
      '#type' => 'select',
      '#options' => [
        'FALSE' => t('Disabled'),
        'TRUE' => t('Enabled'),
      ],
      '#default_value' => $config->get('legend_shadow'),
    ];

    $form['legend']['item_style'] = [
      '#title' => $this->t('Item Style'),
      '#type' => 'fieldset',
    ];

    $form['legend']['item_style']['item_style_color'] = [
      '#title' => $this->t('Item style color'),
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 7,
      '#attributes' => ['placeholder' => '#333333'],
      '#description' => t('Leave blank for a dark gray font.'),
      '#default_value' => $config->get('item_style_color'),
    ];

    $form['legend']['item_style']['text_overflow'] = [
      '#title' => $this->t('Text overflow'),
      '#type' => 'select',
      '#options' => [
        'FALSE' => t('False'),
        'ellipsis' => t('Ellipsis'),
      ],
      '#default_value' => $config->get('text_overflow'),
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('charts_highcharts.settings')
      // Set the submitted configuration setting.
      ->set('placeholder', $form_state->getValue('placeholder'))
      ->set('legend_layout', $form_state->getValue('legend_layout'))
      ->set('legend_background_color', $form_state->getValue('legend_background_color'))
      ->set('legend_border_width', $form_state->getValue('legend_border_width'))
      ->set('legend_shadow', $form_state->getValue('legend_shadow'))
      ->set('item_style_color', $form_state->getValue('item_style_color'))
      ->set('text_overflow', $form_state->getValue('text_overflow'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
