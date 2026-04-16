<?php

namespace Drupal\nys_views\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter by webform entity status (open/closed/scheduled).
 *
 * Webforms are config entities so status is not in any DB table. This filter
 * resolves webform IDs matching the selected status at query time and adds a
 * WHERE IN clause on node__webform.webform_target_id.
 *
 * This reflects the webform's own setting, not any node-level override
 * (see webform_status for that).
 *
 * @ViewsFilter("webform_entity_status")
 */
class WebformEntityStatus extends FilterPluginBase {

  /**
   * Constructs a WebformEntityStatus filter plugin.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary(): string {
    $val = is_array($this->value) ? reset($this->value) : $this->value;
    return match($val) {
      'open' => (string) $this->t('Open'),
      'closed' => (string) $this->t('Closed'),
      'scheduled' => (string) $this->t('Scheduled'),
      default => (string) $this->t('All'),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state): void {
    $val = is_array($this->value) ? reset($this->value) : ($this->value ?? 'All');
    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Webform entity status'),
      '#options' => [
        'All' => $this->t('- Any -'),
        'open' => $this->t('Open'),
        'closed' => $this->t('Closed'),
        'scheduled' => $this->t('Scheduled'),
      ],
      '#default_value' => $val,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // Views wraps exposed filter values in an array via acceptExposedInput().
    $val = is_array($this->value) ? reset($this->value) : $this->value;
    if ($val === NULL || $val === '' || $val === FALSE || $val === 'All') {
      return;
    }

    $storage = $this->entityTypeManager->getStorage('webform');
    $query = $storage->getQuery()->accessCheck(FALSE);
    $query->condition('status', $val);
    $webform_ids = $query->execute();

    $this->ensureMyTable();
    $field = "$this->tableAlias.webform_target_id";

    if (empty($webform_ids)) {
      // No webforms match — add an always-false condition.
      $this->query->addWhereExpression($this->options['group'], '1 = 0');
      return;
    }

    $this->query->addWhere($this->options['group'], $field, array_values($webform_ids), 'IN');
  }

}
