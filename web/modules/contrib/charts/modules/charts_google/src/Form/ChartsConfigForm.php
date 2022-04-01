<?php

namespace Drupal\charts_google\Form;


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
    return 'charts_google_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['charts_google.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('charts_google.settings');

    $form['placeholder'] = [
      '#title' => $this->t('Placeholder'),
      '#type' => 'fieldset',
      '#description' => $this->t(
        'This is a placeholder for Google-specific library options. If you would like to help build this out, please work from <a href="@issue_link">this issue</a>.', [
        '@issue_link' => Url::fromUri('https://www.drupal.org/project/charts/issues/3046980')
          ->toString(),
      ]),
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('charts_google.settings')
      // Set the submitted configuration setting
      ->set('placeholder', $form_state->getValue('placeholder'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
