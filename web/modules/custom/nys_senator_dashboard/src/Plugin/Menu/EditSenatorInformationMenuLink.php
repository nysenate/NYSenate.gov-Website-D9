<?php

namespace Drupal\nys_senator_dashboard\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler;

/**
 * Provides a menu link for editing the active senator's information.
 */
class EditSenatorInformationMenuLink extends MenuLinkDefault {

  /**
   * The current user proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The Managed Senators Handler service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler
   */
  protected ManagedSenatorsHandler $managedSenatorsHandler;

  /**
   * Constructs a new EditSenatorInformationMenuLink object.
   *
   * @param array $configuration
   *   A configuration array containing plugin configuration.
   * @param string $plugin_id
   *   The plugin ID for the instance of the plugin.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\StaticMenuLinkOverridesInterface $static_override
   *   The static override storage.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user's account proxy.
   * @param \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler $managedSenatorsHandler
   *   The Managed Senators Handler service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    StaticMenuLinkOverridesInterface $static_override,
    AccountProxyInterface $current_user,
    ManagedSenatorsHandler $managedSenatorsHandler,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $static_override);
    $this->currentUser = $current_user;
    $this->managedSenatorsHandler = $managedSenatorsHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu_link.static.overrides'),
      $container->get('current_user'),
      $container->get('nys_senator_dashboard.managed_senators_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    $current_user_id = $this->currentUser->id();
    $senator_tid = $this->managedSenatorsHandler->getOrSetActiveSenator($current_user_id);
    return [
      'taxonomy_term' => $senator_tid ?? 0,
    ];
  }

}
