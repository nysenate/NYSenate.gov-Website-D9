<?php

namespace Drupal\private_message\Plugin\views\filter;

use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\ViewsHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters threads by the fact they are unread.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("private_message_thread_is_unread")
 */
class PrivateMessageThreadIsUnread extends FilterPluginBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Views Handler Plugin Manager.
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $joinHandler;

  /**
   * Creates an instance of PrivateMessageThreadCleanHistory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountInterface $current_user,
    ViewsHandlerManager $join_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->joinHandler = $join_handler;
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
      $container->get('plugin.manager.views.join')
    );
  }

  /**
   * Override the views query.
   */
  public function query() {
    $current_user_id = $this->currentUser->id();

    $definition = [
      'table' => 'pm_thread_history',
      'field' => 'thread_id',
      'left_table' => 'private_message_threads',
      'left_field' => 'id',
      'operator' => '=',
      'extra' => 'pm_thread_history.access_timestamp < private_message_threads.updated',
    ];

    $join = $this->joinHandler->createInstance('standard', $definition);
    $this->query->addRelationship('pm_thread_history', $join, 'access_timestamp');
    $this->query->addWhere(NULL, 'pm_thread_history.uid', $current_user_id);

    if (!empty($this->value)) {
      parent::query();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {}

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

}
