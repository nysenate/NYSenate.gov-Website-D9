<?php

namespace Drupal\node_Export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Multiple Node Export form using Nids.
 */
class MultipleNidsExportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multiple_nids_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['nids'] = [
      '#type' => 'textarea',
      '#title' => 'Node Ids',
      '#cols' => 10,
      '#rows' => 10,
      '#description' => $this->t('Enter the line separated node ids'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $nids = $form_state->getValue('nids');
    $array = explode("\n", $nids);

    foreach ($array as $nid) {
      $nid = trim($nid);
      if (!filter_var($nid, FILTER_VALIDATE_INT)) {
        $form_state->setErrorByName('NIDS', $this->t('Please enter valid list of Node Ids. "@node_id" is not in right format', ['@node_id' => $nid]));
      }
    }
    if (count($array) != count(array_unique($array))) {
      $form_state->setErrorByName('NIDS', $this->t('You have entered same Node IDs.'));

    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nid = $form_state->getValue('nids');
    $nids = [];
    $nids = explode("\n", $nid);

    $batch = [
      'title' => $this->t('Exporting Nodes...'),
      'operations' => [],
      'init_message'     => $this->t('Commencing'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message'    => $this->t('An error occurred during processing'),
      'finished' => '\Drupal\node_export\NodeExport::nodeExportFinishedCallback',
    ];
    foreach ($nids as $nid) {
      $nidt = trim($nid);
      $batch['operations'][] = ['\Drupal\node_export\NodeExport::nodeExport', [$nidt]];
    }

    batch_set($batch);
    $this->messenger()->addStatus($this->t('The File with export code has been saved in your public directory'));
  }

}
