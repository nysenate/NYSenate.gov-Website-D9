<?php

namespace Drupal\nys_views\Plugin\views\field;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\nys_bills\BillsHelper;
use Drupal\nys_bills\Milestones;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler for a bill's past/current/future status history.
 *
 * Reuses \Drupal\nys_bills\BillsHelper::calculateMilestones() - the same
 * service the unlabeled-dots status graph (nys-bill-status.html.twig) uses -
 * but splits the flat 6-pip data into three labeled groups instead of
 * rendering it as a row of dots.
 *
 * @ViewsField("bill_milestones")
 */
class BillMilestones extends FieldPluginBase {

  /**
   * Maps a raw last-status enum value to a status modifier class.
   */
  const STATUS_CLASS_MAP = [
    'INTRODUCED' => 'introduced',
    'IN_ASSEMBLY_COMM' => 'committee',
    'IN_SENATE_COMM' => 'committee',
    'ASSEMBLY_FLOOR' => 'floor',
    'SENATE_FLOOR' => 'floor',
    'PASSED_ASSEMBLY' => 'passed',
    'PASSED_SENATE' => 'passed',
    'DELIVERED_TO_GOV' => 'passed',
    'POCKET_APPROVAL' => 'passed',
    'SIGNED_BY_GOV' => 'passed',
    'STRICKEN' => 'dead',
    'VETOED' => 'dead',
    'LOST' => 'dead',
  ];

  /**
   * Constructs a BillMilestones field plugin.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected BillsHelper $billsHelper,
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

    $resolved = $this->billsHelper->resolveBillSubstitution($node);
    $pips = $this->billsHelper->calculateMilestones($resolved);

    // The pip grid assumes strictly increasing progress, but a bicameral
    // bill (e.g. one that passed the Senate and is now in Assembly
    // committee) has milestones marked passed in BOTH chambers' tracks at
    // once - a later pip index (Passed Senate) can have happened before an
    // earlier one (In Assembly Committee). So "current" can't be inferred
    // from "furthest passed pip"; it has to come from the bill's own
    // last-status field, matched back into the grid by label.
    $status_value = $node->hasField('field_ol_last_status') ? $node->get('field_ol_last_status')->value : '';
    $current_text = Milestones::STATUS_TEXT[$status_value] ?? NULL;

    $current_pos = -1;
    foreach ($pips as $pos => $items) {
      foreach ($items as $item) {
        if ($current_text !== NULL && $item['text'] === $current_text) {
          $current_pos = $pos;
          break 2;
        }
      }
    }

    $past = $future = [];
    foreach ($pips as $pos => $items) {
      foreach ($items as $item) {
        if ($current_text !== NULL && $item['text'] === $current_text) {
          // This is the current milestone itself - already captured above.
          continue;
        }
        if (!empty($item['pass'])) {
          $past[] = $item;
        }
        elseif ($current_pos !== -1 && $pos > $current_pos) {
          $future[] = $item['text'];
        }
      }
    }

    // Order "already happened" chronologically (by the milestone's own
    // recorded action date) rather than by pip index, since pip index
    // doesn't reflect real order for bicameral bills.
    usort($past, function (array $a, array $b): int {
      return ($a['data']->actionDate ?? '') <=> ($b['data']->actionDate ?? '');
    });
    $past = array_map(static fn(array $item) => $item['text'], $past);

    if (!$past && !$current_text && !$future) {
      return '';
    }

    // Link to the committee's own page as part of the expanded detail,
    // rather than on the "In Senate Committee" summary text itself - a
    // nested link there fought with <summary>'s native click-to-toggle,
    // so only the small arrow reliably opened the dropdown for Senate-
    // committee bills specifically. Matches the /committees/ URL pattern
    // from AmendmentsBlock.php. Only Senate committees have pages on
    // this site, so Assembly committee status doesn't get a link.
    $committee_link = NULL;
    if ($status_value === 'IN_SENATE_COMM' && $node->hasField('field_ol_latest_status_committee')) {
      $committee_name = $node->get('field_ol_latest_status_committee')->value;
      if ($committee_name) {
        $target = Url::fromUserInput('/committees/' . Html::getClass($committee_name));
        $committee_link = Link::fromTextAndUrl($this->t('@name Committee', ['@name' => $committee_name]), $target)->toString();
      }
    }

    return [
      '#theme' => 'nys_bill_milestones',
      '#past' => $past,
      '#current' => $current_text,
      '#future' => $future,
      '#committee_link' => $committee_link,
      '#status_class' => self::STATUS_CLASS_MAP[$status_value] ?? 'introduced',
    ];
  }

}
