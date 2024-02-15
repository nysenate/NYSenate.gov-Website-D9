<?php

namespace Drupal\views_bulk_edit\Plugin\Action;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\views_bulk_edit\Form\BulkEditFormTrait;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Views;
use Drupal\views_bulk_operations\Service\ViewsbulkOperationsViewData;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessor;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Connection;

/**
 * Modify entity field values.
 *
 * @Action(
 *   id = "views_bulk_edit",
 *   label = @Translation("Modify field values"),
 *   type = ""
 * )
 */
class ModifyEntityValues extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface, ViewsBulkOperationsPreconfigurationInterface {

  use BulkEditFormTrait;

  /**
   * VBO views data service.
   *
   * @var \Drupal\views_bulk_operations\Service\ViewsbulkOperationsViewData
   */
  protected $viewDataService;

  /**
   * VBO action processor.
   *
   * @var \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessor
   */
  protected $actionProcessor;

  /**
   * The bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Object constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin Id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\views_bulk_operations\Service\ViewsbulkOperationsViewData $viewDataService
   *   The VBO view data service.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessor $actionProcessor
   *   The VBO action processor.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   Bundle info object.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ViewsbulkOperationsViewData $viewDataService,
    ViewsBulkOperationsActionProcessor $actionProcessor,
    EntityTypeManagerInterface $entityTypeManager,
    EntityTypeBundleInfoInterface $bundleInfo,
    Connection $database,
    TimeInterface $time,
    AccountInterface $currentUser,
    EntityRepositoryInterface $entityRepository,
    EntityFieldManagerInterface $entityFieldManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->viewDataService = $viewDataService;
    $this->actionProcessor = $actionProcessor;
    $this->entityTypeManager = $entityTypeManager;
    $this->bundleInfo = $bundleInfo;
    $this->database = $database;
    $this->time = $time;
    $this->currentUser = $currentUser;
    $this->entityRepository = $entityRepository;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('views_bulk_operations.data'),
      $container->get('views_bulk_operations.processor'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('database'),
      $container->get('datetime.time'),
      $container->get('current_user'),
      $container->get('entity.repository'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state): array {
    $form['get_bundles_from_results'] = [
      '#title' => $this->t('Get entity bundles from results'),
      '#type' => 'checkbox',
      '#default_value' => isset($values['get_bundles_from_results']) ? $values['get_bundles_from_results'] : TRUE,
      '#description' => $this->t('NOTE: If performance issues are observed when using "All results in this view" selector in case of large result sets, uncheck this and use a bundle filter (node type, taxonomy vocabulary etc.) on the view.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Get view bundles.
    $bundle_data = $this->getViewBundles();
    return $this->buildBundleForms($form, $form_state, $bundle_data);
  }

  /**
   * Helper method to get bundles displayed by the view.
   *
   * @return array
   *   Array of entity bundles returned by the current view
   *   keyed by entity type IDs.
   */
  protected function getViewBundles() {

    // Get a list of all entity types and bundles of the view.
    $bundle_data = [];
    $bundle_info = $this->bundleInfo->getAllBundleInfo();

    // If the list of selected results is available,
    // query db for selected bundles.
    if (!empty($this->context['list'])) {
      $query_data = [];
      foreach ($this->context['list'] as $item) {
        list(,, $entity_type_id, $id,) = $item;
        $query_data[$entity_type_id][$id] = $id;
      }
      foreach ($query_data as $entity_type_id => $entity_ids) {
        $entityTypeDefinition = $this->entityTypeManager->getDefinition($entity_type_id);
        if ($bundle_key = $entityTypeDefinition->getKey('bundle')) {
          $id_key = $entityTypeDefinition->getKey('id');

          $results = $this->database->select($entityTypeDefinition->getBaseTable(), 'base')
            ->fields('base', [$bundle_key])
            ->condition($id_key, $entity_ids, 'IN')
            ->execute()
            ->fetchCol();

          foreach ($results as $bundle_id) {
            if (!isset($bundle_data[$entity_type_id][$bundle_id])) {
              $bundle_data[$entity_type_id][$bundle_id] = $bundle_info[$entity_type_id][$bundle_id]['label'];
            }
          }
        }
        else {
          $bundle_data[$entity_type_id][$entity_type_id] = '';
        }
      }
    }

    // If not, fallback to other methods.
    else {
      // Initialize view and VBO view data service.
      $view = Views::getView($this->context['view_id']);
      $view->setDisplay($this->context['display_id']);
      if (!empty($this->context['arguments'])) {
        $view->setArguments($this->context['arguments']);
      }
      if (!empty($this->context['exposed_input'])) {
        $view->setExposedInput($this->context['exposed_input']);
      }
      $view->build();

      $this->viewDataService->init($view, $view->getDisplay(), $this->context['relationship_id']);

      // If administrator chose this method, get bundles from actual
      // view results.
      // NOTE: This can cause performance problems in case of large result sets!
      if (!empty($this->context['preconfiguration']['get_bundles_from_results'])) {
        $entities = [];
        if (empty($this->context['list'])) {
          $view->query->setLimit(0);
          $view->query->setOffset(0);
          $view->query->execute($view);

          foreach ($view->result as $row) {
            $entities[] = $this->viewDataService->getEntity($row);
          }

        }
        else {
          foreach ($this->context['list'] as $item) {
            $entities[] = $this->actionProcessor->getEntity($item);
          }
        }

        if (!empty($entities)) {
          foreach ($entities as $entity) {
            $entity_type_id = $entity->getEntityTypeId();
            $bundle = $entity->bundle();
            if (!isset($bundle_data[$entity_type_id][$bundle])) {
              $bundle_data[$entity_type_id][$bundle] = $bundle_info[$entity_type_id][$bundle]['label'];
            }
          }
        }
      }

      // The painless way, may display form for more bundles
      // than the actual result set has.
      else {
        // Try to get possible bundles from a bundle filter, fixed or exposed,
        // if exists (hopefully).
        foreach ($this->viewDataService->getEntityTypeIds() as $entity_type_id) {
          $entityTypeDefinition = $this->entityTypeManager->getDefinition($entity_type_id);
          $bundle_key = $entityTypeDefinition->getKey('bundle');
          if (isset($view->filter[$bundle_key]) && !empty($view->filter[$bundle_key]->value)) {
            foreach ($view->filter[$bundle_key]->value as $bundle) {
              $bundle_data[$entity_type_id][$bundle] = $bundle_info[$entity_type_id][$bundle]['label'];
            }
          }

          // If previous failed and admin did not set to get bundles
          // from view results, get all bundles of displayed entity types.
          elseif (empty($this->context['preconfiguration']['get_bundles_from_results'])) {
            if (isset($bundle_info[$entity_type_id])) {
              foreach ($bundle_info[$entity_type_id] as $bundle => $label) {
                $bundle_data[$entity_type_id][$bundle] = $bundle_info[$entity_type_id][$bundle]['label'];
              }
            }
          }
        }
      }
    }
    return $bundle_data;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = $object->access('update', $account, TRUE);
    return $return_as_object ? $access : $access->isAllowed();
  }

}
