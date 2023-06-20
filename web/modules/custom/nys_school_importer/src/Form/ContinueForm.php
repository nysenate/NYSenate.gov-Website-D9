<?php

namespace Drupal\nys_school_importer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\nys_school_importer\SchoolImporterHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The ContinueForm class.
 */
class ContinueForm extends FormBase {

  use StringTranslationTrait;

  /**
   * Default object for nys_school_importer.school_importer service.
   *
   * @var \Drupal\nys_school_importer\SchoolImporterHelper
   */
  protected $schoolImporterHelper;

  /**
   * The constructor method.
   *
   * @param \Drupal\nys_school_importer\SchoolImporterHelper $school_importer_helper
   *   The importer helper service.
   */
  public function __construct(SchoolImporterHelper $school_importer_helper) {
    $this->schoolImporterHelper = $school_importer_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container_interface) {
    return new static(
          $container_interface->get('nys_school_importer.school_importer')
      );
  }

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
    $form['from'] = [
      '#type' => 'item',
      '#title' => $this->t('Remaining Schools With Name Issues'),
      '#markup' => $this->schoolImporterHelper->getOffendingSchoolNamesMarkup(5),
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
    $this->schoolImporterHelper->processImport();
  }

}
