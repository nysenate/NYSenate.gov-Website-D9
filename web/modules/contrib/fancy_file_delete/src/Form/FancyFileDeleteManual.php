<?php

namespace Drupal\fancy_file_delete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Class FancyFileDeleteManual.
 */
class FancyFileDeleteManual extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fancy_file_delete_manual';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fancy_file_delete.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['force'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('FORCE file deletion?'),
    ];

    $form['delete_textarea'] = [
      '#type' => 'textarea',
      '#title' => $this->t('FID Numbers'),
      '#default_value' => '',
      '#description' => $this->t('Provide the fid numbers, one per line.'),
      '#attributes' => [
        'style' => 'font-family:"Courier New", Courier, monospace',
      ],
      '#rows' => 10,
    ];

    // $form['#validate'][] = 'fancy_file_delete_manual_validate';.
    // $form['#submit'][] = 'fancy_file_delete_manual_submit';.
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Required doesn't work well with states it seemz.
    if (!$form_state->getValue(['delete_textarea'])) {
      $form_state->setErrorByName('delete_textarea', $this->t('You can not leave this blank, what are you thinking?'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('fancy_file_delete.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function _submitForm(array &$form, FormStateInterface $form_state) {
    $values = [];

    $force = ($form_state->getValue(['force'])) ? TRUE : FALSE;
    $fids = preg_split("/\r?\n/", $form_state->getValue(['delete_textarea']));
    foreach ($fids as $fid) {
      $values[] = $fid;
    }

    // Send to batch.
    \Drupal::service('fancy_file_delete.batch')->setBatch($values, $force);
  }

}
