<?php

namespace Drupal\node_Export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\node_export\NodeExport;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;

/**
 * Provides a Node Export form.
 */
class BulkNodeExport extends FormBase {
  /**
   * Set a var to make stepthrough form.
   *
   * @var step
   */
  protected $step = 1;
  /**
   * Keep track of user input.
   *
   * @var userInput
   */
  protected $userInput = [];

  /**
   * Tempstorage.
   *
   * @var tempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Session.
   *
   * @var sessionManager
   */
  private $sessionManager;

  /**
   * User.
   *
   * @var currentUser
   */
  private $currentUser;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Constructs a \Drupal\node_export\Form\BulkNodeExport.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   Temp storage.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   Session.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   User.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   Route.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, AccountInterface $current_user, RouteBuilderInterface $route_builder) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;
    $this->routeBuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('session_manager'),
      $container->get('current_user'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->messenger()->addWarning($this->t('This module is experiemental. PLEASE do not use on production databases without prior testing and a complete database dump.'));
    $this->userInput['entities'] = $this->tempStoreFactory
      ->get('node_export_ids')
      ->get($this->currentUser->id());
    $json = NodeExport::export(array_keys($this->userInput['entities']), 'json', FALSE);
    $form['export_code'] = [
      '#type' => 'textarea',
      '#value' => $json,
      '#title' => $this->t('Node Export Code is :'),
      '#rows' => '15',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = $form_state->getValue('export_code');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . basename('node.json'));
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize('node.json'));
    print($data);
    exit;
  }

}
