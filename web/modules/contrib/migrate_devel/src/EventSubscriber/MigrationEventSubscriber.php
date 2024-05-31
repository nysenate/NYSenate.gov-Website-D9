<?php

namespace Drupal\migrate_devel\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * MigrationEventSubscriber for Debugging Migrations.
 *
 * @class MigrationEventSubscriber
 */
class MigrationEventSubscriber implements EventSubscriberInterface {

  /**
   * Pre Row Save Function for --migrate-debug-pre.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *    Pre-Row-Save Migrate Event.
   */
  public function debugRowPreSave(MigratePreRowSaveEvent $event) {
    if (PHP_SAPI !== 'cli') {
      return;
    }

    $row = $event->getRow();

    if (in_array('migrate-debug-pre', \Drush\Drush::config()->get('runtime.options'))) {
      // Start with capital letter for variables since this is actually a label.
      $Source = $row->getSource();
      $Destination = $row->getDestination();

      // Uses Symfony VarDumper.
      // @todo Explore advanced usage of CLI dumper class for nicer output.
      // https://www.drupal.org/project/migrate_devel/issues/3151276
      dump(
        '---------------------------------------------------------------------',
        '|                             $Source                               |',
        '---------------------------------------------------------------------',
        $Source,
        '---------------------------------------------------------------------',
        '|                           $Destination                            |',
        '---------------------------------------------------------------------',
        $Destination
      );
    }
  }

  /**
   * Post Row Save Function for --migrate-debug.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   Post-Row-Save Migrate Event.
   */
  public function debugRowPostSave(MigratePostRowSaveEvent $event) {
    if (PHP_SAPI !== 'cli') {
      return;
    }

    $row = $event->getRow();

    if (in_array('migrate-debug', \Drush\Drush::config()->get('runtime.options'))) {

      // Start with capital letter for variables since this is actually a label.
      $Source = $row->getSource();
      $Destination = $row->getDestination();
      $DestinationIDValues = $event->getDestinationIdValues();

      // Uses Symfony VarDumper.
      // @todo Explore advanced usage of CLI dumper class for nicer output.
      // https://www.drupal.org/project/migrate_devel/issues/3151276
      dump(
        '---------------------------------------------------------------------',
        '|                             $Source                               |',
        '---------------------------------------------------------------------',
        $Source,
        '---------------------------------------------------------------------',
        '|                           $Destination                            |',
        '---------------------------------------------------------------------',
        $Destination,
        '---------------------------------------------------------------------',
        '|                       $DestinationIdValues                        |',
        '---------------------------------------------------------------------',
        $DestinationIDValues
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PRE_ROW_SAVE][] = ['debugRowPreSave'];
    $events[MigrateEvents::POST_ROW_SAVE][] = ['debugRowPostSave'];
    return $events;
  }

}
