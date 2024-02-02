<?php

namespace Drupal\node_access_rebuild_progressive\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for our module.
 */
class NodeAccessRebuildProgressiveSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_access_rebuild_progressive_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'node_access_rebuild_progressive.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('node_access_rebuild_progressive.settings');

    $form['cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable progressive node access rebuild at cron time'),
      '#description' => $this->t('It is recommended that you run cron via drush for using this feature.'),
      '#default_value' => $config->get('cron'),
    ];

    $form['chunk'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of nodes to process in each chunk'),
      '#default_value' => $config->get('chunk'),
      '#description' => $this->t('The number of nodes that will be processed per cron run. Make sure it can safely fit in memory, and in the cron run time if you are not running cron via drush.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $chunk_size = $form_state->getValue('chunk');
    if (!is_numeric($chunk_size) || $chunk_size <= 0) {
      $form_state->setErrorByName('chunk', $this->t('Chunk size must be a positive integer.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('node_access_rebuild_progressive.settings')
      ->set('chunk', $form_state->getValue('chunk'))
      ->set('cron', $form_state->getValue('cron'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
