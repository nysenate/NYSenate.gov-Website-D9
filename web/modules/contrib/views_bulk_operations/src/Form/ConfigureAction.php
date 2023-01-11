<?php

namespace Drupal\views_bulk_operations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Action configuration form.
 */
class ConfigureAction extends FormBase {

  use ViewsBulkOperationsFormTrait;

  /**
   * The tempstore service.
   */
  protected PrivateTempStoreFactory $tempStoreFactory;

  /**
   * Views Bulk Operations action manager.
   */
  protected ViewsBulkOperationsActionManager $actionManager;

  /**
   * Views Bulk Operations action processor.
   */
  protected ViewsBulkOperationsActionProcessorInterface $actionProcessor;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   User private temporary storage factory.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager $actionManager
   *   Extended action manager object.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $actionProcessor
   *   Views Bulk Operations action processor.
   */
  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory,
    ViewsBulkOperationsActionManager $actionManager,
    ViewsBulkOperationsActionProcessorInterface $actionProcessor
  ) {
    $this->tempStoreFactory = $tempStoreFactory;
    $this->actionManager = $actionManager;
    $this->actionProcessor = $actionProcessor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('plugin.manager.views_bulk_operations_action'),
      $container->get('views_bulk_operations.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_bulk_operations_configure_action';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = NULL, $display_id = NULL) {

    $form_data = $this->getFormData($view_id, $display_id);

    if (!isset($form_data['action_id'])) {
      return [
        '#markup' => $this->t('No items selected. Go back and try again.'),
      ];
    }

    $form['#title'] = $this->t('Configure "%action" action applied to the selection', ['%action' => $form_data['action_label']]);

    $form['list'] = $this->getListRenderable($form_data);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Apply'),
    ];
    $this->addCancelButton($form);

    $action = $this->actionManager->createInstance($form_data['action_id']);

    if (\method_exists($action, 'setContext')) {
      $action->setContext($form_data);
    }

    $form_state->set('views_bulk_operations', $form_data);
    $form = $action->buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_data = $form_state->get('views_bulk_operations');

    $action = $this->actionManager->createInstance($form_data['action_id']);
    if (\method_exists($action, 'validateConfigurationForm')) {
      $action->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_data = $form_state->get('views_bulk_operations');

    $action = $this->actionManager->createInstance($form_data['action_id']);
    if (\method_exists($action, 'submitConfigurationForm')) {
      $action->submitConfigurationForm($form, $form_state);
      $form_data['configuration'] = $action->getConfiguration();
    }
    else {
      $form_state->cleanValues();
      $form_data['configuration'] = $form_state->getValues();
    }

    if (!empty($form_data['confirm_route'])) {
      // Update tempStore data.
      $this->setTempstoreData($form_data, $form_data['view_id'], $form_data['display_id']);
      // Go to the confirm route.
      $form_state->setRedirect($form_data['confirm_route'], [
        'view_id' => $form_data['view_id'],
        'display_id' => $form_data['display_id'],
      ]);
    }
    else {
      $this->deleteTempstoreData($form_data['view_id'], $form_data['display_id']);
      $response = $this->actionProcessor->executeProcessing($form_data);
      $url = Url::fromUri($response->getTargetUrl());
      $form_state->setRedirectUrl($url);
    }
  }

}
