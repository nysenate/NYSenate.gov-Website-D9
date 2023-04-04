<?php

namespace Drupal\nys_senators\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Defines the nys_senators.dashboard.overview.stats.alter event.
 *
 * This event is dispatched as a senator's dashboard overview page is built.
 */
class OverviewStatsAlterEvent extends Event {

  /**
   * The aggregated stats blocks.
   *
   * @var array
   */
  public array $stats;

  /**
   * Constructor.
   *
   * @param array $stats
   *   The aggregated stats blocks.
   */
  public function __construct(array $stats) {
    $this->stats = $stats;
  }

}
