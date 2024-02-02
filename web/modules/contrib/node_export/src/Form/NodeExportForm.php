<?php

namespace Drupal\node_Export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node_export\NodeExport;

/**
 * Provides a Node Export form.
 */
class NodeExportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Reads the Node id from URL.
    $nid = \Drupal::routeMatch()->getParameter('node');
    $json = NodeExport::export([$nid], 'json', FALSE);
    $form['export_code'] = [
      '#type' => 'textarea',
      '#value' => $json,
      '#title' => $this->t('Node Export Code is :'),
      '#description' => 'Copy this Code and paste to the import form of new website so as to add these nodes to your new website',
      '#rows' => '15',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download'),
    ];
    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $nid,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nid = $form_state->getValue('nid');
    $data = $form_state->getValue('export_code');
    $filename = sprintf('node_%s.json', $nid);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . basename($filename));
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($data));
    print($data);
    exit;
  }

}
