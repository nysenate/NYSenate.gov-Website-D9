<?php

namespace Drupal\entityqueue_test\Plugin\EntityQueueHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entityqueue\EntityQueueHandlerBase;

/**
 * Defines an entity queue handler for testing.
 *
 * @EntityQueueHandler(
 *   id = "test",
 *   title = @Translation("Test handler")
 * )
 */
class Test extends EntityQueueHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleSubqueues() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAutomatedSubqueues() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'shape' => 'round',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['shape'] = [
      '#type' => 'textfield',
      '#title' => 'Shape',
      '#default_value' => $this->configuration['shape'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('shape') === 'square') {
      $form_state->setErrorByName('shape', $this->t('The shape can not be square.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['shape'] = $form_state->getValue('shape');
  }

}
