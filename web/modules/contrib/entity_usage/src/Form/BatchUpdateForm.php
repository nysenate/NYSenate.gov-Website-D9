<?php

namespace Drupal\entity_usage\Form;

use Drupal\entity_usage\EntityUsageBatchManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to launch batch tracking of existing entities.
 */
class BatchUpdateForm extends FormBase {

  /**
   * The EntityUsageBatchManager service.
   *
   * @var \Drupal\entity_usage\EntityUsageBatchManager
   */
  protected $batchManager;

  /**
   * BatchUpdateForm constructor.
   *
   * @param \Drupal\entity_usage\EntityUsageBatchManager $batch_manager
   *   The entity usage batch manager.
   */
  public function __construct(EntityUsageBatchManager $batch_manager) {
    $this->batchManager = $batch_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_usage.batch_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_update_batch_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => $this->t("This page allows you to delete and re-generate again all entity usage statistics in your system.<br /><br />You may want to check the settings page to fine-tune what entities should be tracked, and other options."),
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Recreate all entity usage statistics'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->batchManager->recreate();
  }

}
