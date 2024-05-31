<?php

namespace Drupal\node_Export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Node Import form.
 */
class NodeImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['paste'] = [
      '#type' => 'textarea',
      '#title' => 'Exported Code',
      '#default_value' => '',
      '#rows' => 15,
      '#description' => $this->t('Paste the code of a node export here and check that new nodes are created or not after clicking on content.'),
      '#wysiwyg' => FALSE,
      '#required' => TRUE,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $json = $form_state->getValue('paste');
    $nodes = json_decode($json, TRUE);
    $batch = [
      'title' => $this->t('Importing Nodes...'),
      'operations' => [],
      'init_message' => $this->t('Imporitng'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('An error occurred during processing'),
      'finished' => '\Drupal\node_export\NodeImport::nodeImportFinishedCallback',
    ];
    foreach ($nodes as $node) {
      $batch['operations'][] = [
        '\Drupal\node_export\NodeImport::nodeImport',
        [$node],
      ];
    }
    batch_set($batch);
    $this->messenger()->addStatus($this->t('Node has been imported succesfully.'));
  }

}
