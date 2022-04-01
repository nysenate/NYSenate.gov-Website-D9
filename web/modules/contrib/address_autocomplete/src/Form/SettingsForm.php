<?php

namespace Drupal\address_autocomplete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class SettingsForm extends ConfigFormBase {

  /**
   * Name of the config.
   *
   * @var string
   */
  public static $configName = 'address_autocomplete.settings';

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames() {
    return [SettingsForm::$configName];
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'address_autocomplete_settings_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(SettingsForm::$configName);

    $header = [
      'provider' => t('Provider'),
      'operations' => t('Operations'),
    ];

    $providers = \Drupal::service('plugin.manager.address_provider');
    $pluginDefinitions = $providers->getDefinitions();

    $options = [];

    foreach ($pluginDefinitions as $id => $pluginDefinition) {
      $options[$pluginDefinition['id']] = [
        'provider' => $pluginDefinition['label'],
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'settings' => [
                'title' => t('Settings'),
                'url' => URL::fromRoute("address_autocomplete.address_provider.$id"),
              ],
            ],
          ],
        ],
      ];
    }

    $form['active_plugin'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#multiple' => FALSE,
      '#default_value' => $config->get('active_plugin') ? $config->get('active_plugin') : NULL,
      '#empty' => t('No plugins found'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(SettingsForm::$configName);
    $config->set('active_plugin', $form_state->getValue(['active_plugin']));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
