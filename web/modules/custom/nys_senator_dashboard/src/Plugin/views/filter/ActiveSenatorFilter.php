<?php

namespace Drupal\nys_senator_dashboard\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler;

/**
 * Provides an exposed filter for filtering records linked to an active senator.
 *
 * @ViewsFilter("nys_senator_dashboard_active_senator_filter")
 */
class ActiveSenatorFilter extends FilterPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Managed Senators Handler service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler
   */
  protected ManagedSenatorsHandler $managedSenatorsHandler;

  /**
   * Constructs the ActiveSenatorFilter plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler $managedSenatorsHandler
   *   The managed senators handler service.
   */
  public function __construct(array $configuration, string $plugin_id, mixed $plugin_definition, ManagedSenatorsHandler $managedSenatorsHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->managedSenatorsHandler = $managedSenatorsHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActiveSenatorFilter {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('nys_senator_dashboard.managed_senators_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state): void {
    $senator = $this->managedSenatorsHandler->getActiveSenator(FALSE);
    if (empty($senator)) {
      return;
    }

    $form[$this->options['expose']['identifier']] = [
      '#type' => 'select',
      '#options' => [$senator->id() => $senator->label()],
      '#default_value' => $senator->id(),
      '#empty_option' => $this->t('- All senators -'),
      '#empty_value' => 'All',
    ];
  }

}
