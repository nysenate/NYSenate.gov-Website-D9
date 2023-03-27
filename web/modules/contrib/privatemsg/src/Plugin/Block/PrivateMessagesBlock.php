<?php

namespace Drupal\privatemsg\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\privatemsg\PrivateMsgService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a private messages block.
 *
 * @Block(
 *   id = "privatemsg_block",
 *   admin_label = @Translation("Private Messages"),
 *   category = @Translation("Private Messages")
 * )
 */
class PrivateMessagesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The privatemsg.common service.
   */
  protected PrivateMsgService $common;

  /**
   * The current user account.
   */
  protected AccountInterface $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PrivateMsgService $common, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->common = $common;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('privatemsg.common'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $markup = '<p>' . $this->t('New messages:') . ' ' . $this->common->getUnreadCountForUser($this->currentUser->id()) . '</p>';
    $markup .= '<p><a href="/messages">' . $this->t('All messages') . '</a></p>';
    $markup .= '<p><a href="/messages/new">' . $this->t('New message') . '</a></p>';

    $build['content'] = [
      '#markup' => $markup,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
