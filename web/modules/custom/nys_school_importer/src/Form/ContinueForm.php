<?php

namespace Drupal\nys_school_importer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\nys_school_importer\Controller\ImportController;

/**
 * The ContinueForm class.
 */
class ContinueForm extends FormBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nys_school_continue';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $import_controller = new ImportController();

    $form['from'] = [
      '#type' => 'item',
      '#title' => $this->t('Remaining Schools With Name Issues'),
      '#markup' => $import_controller->getOffendingSchoolNamesMarkup(5),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue With Import'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $import_controller = new ImportController();
    $import_controller->processImport();
  }

}
