<?php

declare(strict_types=1);

namespace Drupal\twitter_api_block\Plugin\KeyInput;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyInputBase;

/**
 * Input for Twitter API v2.
 *
 * @KeyInput(
 *   id = "twitter_api_app",
 *   label = @Translation("Twitter API app")
 * )
 */
class TwitterApiAppKeyInput extends KeyInputBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'client_id' => '',
      'client_secret' => '',
      'bearer_token' => '',
      'access_token' => '',
      'access_secret' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $this->configuration['client_id'],
      '#required' => TRUE,
    ];

    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#default_value' => $this->configuration['client_secret'],
      '#required' => TRUE,
    ];

    $form['bearer_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bearer token'),
      '#default_value' => $this->configuration['bearer_token'],
    ];

    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access token'),
      '#default_value' => $this->configuration['access_token'],
    ];

    $form['access_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Secret'),
      '#default_value' => $this->configuration['access_secret'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function processSubmittedKeyValue(FormStateInterface $form_state) {
    $values = $form_state->getValues();
    return [
      'submitted' => $values,
      'processed_submitted' => $values,
    ];
  }

}
