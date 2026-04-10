<?php

namespace Drupal\nys_views\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter by webform archive status.
 *
 * Webforms are config entities so archive status is not in any DB table.
 * This filter resolves archived/non-archived webform IDs at query time and
 * adds a WHERE IN clause on node__webform.webform_target_id.
 *
 * @ViewsFilter("webform_archive_status")
 */
class WebformArchiveStatus extends FilterPluginBase {

  /**
   * Constructs a WebformArchiveStatus filter plugin.
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
      '1' => (string) $this->t('Archived'),
      '0' => (string) $this->t('Not archived'),
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
      '#title' => $this->t('Archived'),
      '#options' => [
        'All' => $this->t('- Any -'),
        '1' => $this->t('Yes'),
        '0' => $this->t('No'),
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

    if ($val === '1') {
      $query->condition('archive', TRUE);
    }
    else {
      $query->condition('archive', FALSE);
    }

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
