<?php

namespace Drupal\node_export\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Session\SessionManagerInterface;

/**
 * Update Fields.
 *
 * @Action(
 *   id = "bulk_node_export",
 *   label = "Node Export",
 *   type = "node",
 *   confirm_form_route_name = "node_export.bulk_export_form"
 * )
 */
class BulkNodeExport extends ActionBase implements ContainerFactoryPluginInterface {
  /**
   * The plugin_id.
   *
   * @var pluginId
   */
  protected $pluginId;

  /**
   * The plugin implementation definition.
   *
   * @var pluginDefinition
   */
  protected $pluginDefinition;

  /**
   * Configuration information passed into the plugin.
   *
   * When using an interface like
   * \Drupal\Component\Plugin\ConfigurablePluginInterface, this is where the
   * configuration should be stored.
   *
   * Plugin configuration is optional, so plugin implementations must provide
   * their own setters and getters.
   *
   * @var configuration
   */
  protected $configuration;

  /**
   * The tempstore factory.
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
   * Constructs a BulkUpdateFields object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The session.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The session.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, AccountInterface $current_user) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('tempstore.private'),
      $container->get('session_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $ids = [];
    foreach ($entities as $entity) {
      $ids[$entity->id()] = $entity;
    }

    $this->tempStoreFactory->get('node_export_ids')
      ->set($this->currentUser->id(), $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ContentEntityInterface $entity = NULL) {
    $this->executeMultiple([$entity]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }

}
