<?php

namespace Drupal\nys_views\Plugin\views\field;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\nys_bills\BillsHelper;
use Drupal\nys_bills\Milestones;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler for a bill's last-status date, with a data-bug fallback.
 *
 * The field_ol_last_status_date field is frequently empty due to a
 * long-standing gap in the bill import process - not something this
 * display layer can fix.
 * When empty, falls back to the action date recorded for the current status
 * inside field_ol_all_statuses, the same JSON BillsHelper already parses
 * for the status milestones (see BillMilestones).
 *
 * @ViewsField("bill_last_status_date")
 */
class BillLastStatusDate extends FieldPluginBase {

  /**
   * Constructs a BillLastStatusDate field plugin.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected BillsHelper $billsHelper,
    protected DateFormatterInterface $dateFormatter,
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
      $container->get('nys_bill.bills_helper'),
      $container->get('date.formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values): void {
    $ids = [];
    foreach ($values as $row) {
      if ($nid = $this->getValue($row)) {
        $ids[$nid] = $nid;
      }
    }
    if ($ids) {
      $this->entityTypeManager->getStorage('node')->loadMultiple($ids);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $nid = $this->getValue($values);
    if (!$nid) {
      return '';
    }
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if (!$node) {
      return '';
    }

    if ($node->hasField('field_ol_last_status_date') && !$node->get('field_ol_last_status_date')->isEmpty()) {
      return $this->formatDate($node->get('field_ol_last_status_date')->value);
    }

    $status_value = $node->hasField('field_ol_last_status') ? $node->get('field_ol_last_status')->value : '';
    $current_text = Milestones::STATUS_TEXT[$status_value] ?? NULL;
    if ($current_text === NULL) {
      return '';
    }

    $resolved = $this->billsHelper->resolveBillSubstitution($node);
    $pips = $this->billsHelper->calculateMilestones($resolved);
    foreach ($pips as $items) {
      foreach ($items as $item) {
        if ($item['text'] === $current_text && !empty($item['data']->actionDate)) {
          return $this->formatDate($item['data']->actionDate);
        }
      }
    }

    return '';
  }

  /**
   * Formats a date string to match field_ol_last_status_date's own display.
   */
  protected function formatDate(string $date): string {
    $timestamp = strtotime($date);
    return $timestamp ? $this->dateFormatter->format($timestamp, 'custom', 'M j, Y') : '';
  }

}
