<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigrateRollbackEvent;
use Drupal\migrate\Event\MigrateRowDeleteEvent;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Event\MigrateEvents as MigratePlusEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Import and sync source and destination.
 */
class MigrationImportSync implements EventSubscriberInterface {

  protected EventDispatcherInterface $dispatcher;

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * MigrationImportSync constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param Drupal\Core\State\StateInterface $state
   *   The Key/Value Store to use for tracking synced source rows.
   */
  public function __construct(EventDispatcherInterface $dispatcher, StateInterface $state) {
    $this->dispatcher = $dispatcher;
    $this->state = $state;
    $this->state->set('migrate_tools_sync', []);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[MigrateEvents::PRE_IMPORT][] = ['sync'];
    return $events;
  }

  /**
   * Event callback to sync source and destination.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The migration import event.
   */
  public function sync(MigrateImportEvent $event): void {
    $migration = $event->getMigration();
    if (!empty($migration->syncSource)) {

      // Loop through the source to register existing source ids.
      // @see migrate_tools_migrate_prepare_row().
      // Clone so that any generators aren't initialized prematurely.
      $source = clone $migration->getSourcePlugin();
      $source->rewind();

      while ($source->valid()) {
        $source->next();
      }

      $source_id_values = $this->state->get('migrate_tools_sync', []);

      $id_map = $migration->getIdMap();
      $id_map->rewind();
      $destination = $migration->getDestinationPlugin();

      while ($id_map->valid()) {
        $map_source_id = $id_map->currentSource();

        foreach ($source->getIds() as $id_key => $id_config) {
          if ($id_config['type'] === 'string') {
            $map_source_id[$id_key] = (string) $map_source_id[$id_key];
          }
          elseif ($id_config['type'] === 'integer') {
            $map_source_id[$id_key] = (int) $map_source_id[$id_key];
          }
        }

        if (!in_array($map_source_id, $source_id_values, TRUE)) {
          $destination_ids = $id_map->currentDestination();
          if ($destination_ids !== NULL) {
            $this->dispatchRowDeleteEvent(MigrateEvents::PRE_ROW_DELETE, $migration, $destination_ids);
            if (class_exists(MigratePlusEvents::class)) {
              $this->dispatchRowDeleteEvent(MigratePlusEvents::MISSING_SOURCE_ITEM, $migration, $destination_ids);
            }
            $destination->rollback($destination_ids);
            $this->dispatchRowDeleteEvent(MigrateEvents::POST_ROW_DELETE, $migration, $destination_ids);
          }
          $id_map->delete($map_source_id);
        }
        $id_map->next();
      }
      $this->dispatcher->dispatch(new MigrateRollbackEvent($migration), MigrateEvents::POST_ROLLBACK);
    }
  }

  /**
   * Dispatches MigrateRowDeleteEvent event.
   *
   * @param string $event_name
   *   The event name to dispatch.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The active migration.
   * @param array $destination_ids
   *   The destination identifier values of the record.
   */
  protected function dispatchRowDeleteEvent(string $event_name, MigrationInterface $migration, array $destination_ids): void {
    // Symfony changing dispatcher so implementation could change.
    $this->dispatcher->dispatch(new MigrateRowDeleteEvent($migration, $destination_ids), $event_name);
  }

}
