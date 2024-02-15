<?php

namespace Drupal\node_Export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Node Export form.
 */
class MultipleNodeExportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multiple_node_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Loads all the content typess in the drupal site.
    $contentTypes = \Drupal::service('entity_type.manager')->getStorage('node_type')->loadMultiple();
    $contentTypesList = [];

    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }

    $form['ct'] = [
      '#markup' => $this->t('Select the content type of the node you want to export : '),
    ];
    $form['export_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Type'),
      '#options' => $contentTypesList,
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $export_type = $form_state->getValue('export_type');
    // Loads all the node of selected content type.
    $nids = \Drupal::entityQuery('node')
        ->accessCheck()
        ->condition('type', $export_type)
        ->execute();
    $batch = [
      'title' => $this->t('Generating Export Code...'),
      'operations' => [],
      'init_message'     => $this->t('Exporting '),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message'    => $this->t('An error occurred during processing'),
      'finished' => '\Drupal\node_export\NodeExport::nodeExportFinishedCallback',
    ];
    if (!empty($nids)) {
      foreach ($nids as $nid) {
        $batch['operations'][] = ['\Drupal\node_export\NodeExport::nodeExport', [$nid]];
      }
      batch_set($batch);
      $this->messenger()->addStatus($this->t('The File with export code has been saved in your public directory'));
    }
    else {
      $this->messenger()->addError($this->t('There are no nodes to export.'));
    }
  }

}
