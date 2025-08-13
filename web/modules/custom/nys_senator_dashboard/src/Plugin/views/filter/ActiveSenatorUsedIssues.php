<?php

namespace Drupal\nys_senator_dashboard\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\query\Sql;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exposed filter to show "Issue" terms linked to the active senator.
 *
 * @ViewsFilter("nys_senator_dashboard_active_senator_used_issues")
 */
class ActiveSenatorUsedIssues extends FilterPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Managed Senators Handler service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler
   */
  protected ManagedSenatorsHandler $managedSenatorsHandler;

  /**
   * Constructs the ActiveSenatorUsedIssues plugin.
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
  public function __construct($configuration, $plugin_id, $plugin_definition, ManagedSenatorsHandler $managedSenatorsHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->managedSenatorsHandler = $managedSenatorsHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActiveSenatorUsedIssues {
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
    $active_senator = $this->managedSenatorsHandler->ensureAndGetActiveSenator(FALSE);
    $form[$this->options['expose']['identifier']] = [
      '#type' => 'select',
      '#title' => $this->t('Filter By'),
      '#options' => [
        $active_senator->id() => $this->t('Issues used by') . ' ' . $active_senator->label(),
      ],
      '#default_value' => !empty($this->value) ? $this->value : '',
      '#empty_option' => $this->t('- All issues -'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    if (!empty($this->value)) {
      $value = reset($this->value);
      $active_senator_id = $this->managedSenatorsHandler->ensureAndGetActiveSenator();
      // These should always match.
      if ($value == $active_senator_id && $this->query instanceof Sql) {
        $this->query->addWhereExpression(
          $this->options['group'],
          "EXISTS (
            SELECT 1 FROM {node_field_data} nfd
            INNER JOIN {taxonomy_index} ti ON nfd.nid = ti.nid
            INNER JOIN {node__field_senator_multiref} senator_ref ON nfd.nid = senator_ref.entity_id
            WHERE ti.tid = taxonomy_term_field_data.tid
              AND senator_ref.field_senator_multiref_target_id = :active_senator
          )",
          [':active_senator' => $value]
        );
      }
    }
  }

}
