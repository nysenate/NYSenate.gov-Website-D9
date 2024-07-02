<?php

namespace Drupal\private_message\Plugin\views\field;

use Drupal\Core\Session\AccountInterface;
use Drupal\private_message\Mapper\PrivateMessageMapperInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Outputs thread new messages count.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("private_message_thread_has_new_message_marker")
 */
class PrivateMessageThreadHasNewMessage extends FieldPluginBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The private message mapper service.
   *
   * @var \Drupal\private_message\Mapper\PrivateMessageMapperInterface
   */
  protected PrivateMessageMapperInterface $mapper;

  /**
   * Creates an instance of PrivateMessageThreadHasNewMessage.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountInterface $current_user,
    PrivateMessageMapperInterface $mapper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->mapper = $mapper;
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
      $container->get('private_message.mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Disable query.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\private_message\Entity\PrivateMessageThread $thread */
    $thread = $this->getEntity($values);

    return $this->mapper->getThreadUnreadMessageCount($this->currentUser->id(), $thread->id()) > 0 ?
      $this->t('New message!') :
      $this->t('No new messages');
  }

}
