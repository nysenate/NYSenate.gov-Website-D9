<?php

namespace Drupal\nys_senator_dashboard\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a menu link for editing the active senator's information.
 */
class EditSenatorInformationMenuLink extends MenuLinkDefault {

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
   * @param \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler $managedSenatorsHandler
   *   The Managed Senators Handler service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    StaticMenuLinkOverridesInterface $static_override,
    ManagedSenatorsHandler $managedSenatorsHandler,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $static_override);
    $this->managedSenatorsHandler = $managedSenatorsHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): EditSenatorInformationMenuLink {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu_link.static.overrides'),
      $container->get('nys_senator_dashboard.managed_senators_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    try {
      $senator_tid = $this->managedSenatorsHandler->ensureAndGetActiveSenator();
      return [
        'taxonomy_term' => $senator_tid,
      ];
    }
    catch (AccessDeniedHttpException) {
      return [
        'taxonomy_term' => 0,
      ];
    }
  }

}
