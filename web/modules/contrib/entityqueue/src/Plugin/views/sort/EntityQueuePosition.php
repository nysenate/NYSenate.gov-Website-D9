<?php

namespace Drupal\entityqueue\Plugin\views\sort;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\entityqueue\Plugin\views\relationship\EntityQueueRelationship;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default implementation of the base sort plugin.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("entity_queue_position")
 */
class EntityQueuePosition extends SortPluginBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, AccountInterface $current_user, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function query() {
    $this->ensureMyTable();

    // Try to find an entity queue relationship in this view, and pick the first
    // one available.
    $entity_queue_relationship = NULL;
    foreach ($this->view->relationship as $id => $relationship) {
      if ($relationship instanceof EntityQueueRelationship) {
        $entity_queue_relationship = $relationship;
        $this->options['relationship'] = $id;
        $this->setRelationship();

        break;
      }
    }

    if ($entity_queue_relationship) {
      // Add the field.
      $subqueue_items_table_alias = $entity_queue_relationship->first_alias;
      $this->query->addOrderBy($subqueue_items_table_alias, $this->realField, $this->options['order']);
    }
    else {
      if ($this->currentUser->hasPermission('administer views')) {
        $this->messenger->addMessage($this->t('In order to sort by the queue position, you need to add an <em>Entityqueue</em> relationship on the %display display of the %view view.', [
          '%view' => $this->view->storage->label(),
          '%display' => $this->view->current_display,
        ]), MessengerInterface::TYPE_ERROR);
      }
    }
  }

}
