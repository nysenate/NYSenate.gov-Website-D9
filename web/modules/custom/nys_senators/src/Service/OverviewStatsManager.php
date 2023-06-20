<?php

namespace Drupal\nys_senators\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\nys_senators\Event\OverviewStatsAlterEvent;
use Drupal\nys_senators\Events;
use Drupal\taxonomy\TermInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Plugin manager for the senator dashboard stats block.
 */
class OverviewStatsManager extends DefaultPluginManager {

  /**
   * Instances of each definition.
   *
   * @var array
   */
  protected array $instances = [];

  /**
   * Drupal's Event Dispatcher service.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $dispatcher;

  /**
   * {@inheritDoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EventDispatcherInterface $dispatcher) {
    parent::__construct(
          'Plugin/NysDashboard',
          $namespaces,
          $module_handler,
          'Drupal\nys_senators\OverviewStatInterface',
          'Drupal\nys_senators\Annotation\OverviewStat'
      );
    $this->setCacheBackend($cache_backend, 'nys_senators.dashboard.overview.stats');
    $this->dispatcher = $dispatcher;
  }

  /**
   * Gets all the stat plugins.
   */
  public function getInstances(): array {
    if (!$this->instances) {
      $this->instances = [];
      foreach ($this->getDefinitions() as $key => $val) {
        $this->instances[$key] = $this->createInstance($key);
      }
    }
    return $this->instances;
  }

  /**
   * Gets an array of stat blocks.
   *
   * Each value is the array of variables necessary to render the template,
   * including the calculated value.  The return is ordered by weight.
   *
   * E.g.,
   * <code>
   *   $one_stat = [
   *     'label' => 'Stat Name',
   *     'description' => 'Stats Counted',
   *     'url' => '/some/url',
   *     'classes' => ['class1', 'class2'],
   *     'weight' => '0',
   *     'stat' => '300',
   *   ];
   * </code>
   */
  public function getStats(TermInterface $senator): array {
    $ret = [];
    $a = $this->getInstances();
    $url = $senator->toUrl()->toString();
    /**
     * @var \Drupal\nys_senators\OverviewStatInterface $stat
*/
    foreach ($a as $key => $stat) {
      $content = $stat->getContent($senator);
      if (!is_null($content)) {
        $ret[$key] = $stat->getDefinition() + ['stat' => $content];
        // Insert the base senator dashboard url.
        $ret[$key]['url'] = $url . $ret[$key]['url'];
      }
    }
    $event = new OverviewStatsAlterEvent($ret);
    // @phpstan-ignore-next-line
    $this->dispatcher->dispatch($event, Events::OVERVIEW_STATS_ALTER);

    usort(
          $event->stats, function ($a, $b) {
              $a = $a['weight'] ?? 0;
              $b = $b['weight'] ?? 0;
              return ($a == $b) ? 0 : ($a < $b ? -1 : 1);
          }
      );

    return $event->stats;
  }

}
