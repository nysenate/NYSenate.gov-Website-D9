<?php

namespace Drupal\config_filter;

use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConfigFilterEventSubscriber.
 */
class ConfigFilterEventSubscriber implements EventSubscriberInterface {

  use StorageCopyTrait;

  /**
   * The filter storage factory.
   *
   * @var \Drupal\config_filter\ConfigFilterStorageFactory
   */
  protected $filterStorageFactory;

  /**
   * The sync storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $sync;

  /**
   * ConfigFilterEventSubscriber constructor.
   *
   * @param \Drupal\config_filter\ConfigFilterStorageFactory $filterStorageFactory
   *   The filter storage factory.
   * @param \Drupal\Core\Config\StorageInterface $sync
   *   The sync storage.
   */
  public function __construct(ConfigFilterStorageFactory $filterStorageFactory, StorageInterface $sync) {
    $this->filterStorageFactory = $filterStorageFactory;
    $this->sync = $sync;
  }

  /**
   * The storage is transformed for importing.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The event for altering configuration of the storage.
   */
  public function onImportTransform(StorageTransformEvent $event) {
    $storage = $event->getStorage();
    // The temporary storage representing the active storage.
    $temp = new MemoryStorage();
    // Get the filtered storage based on the event storage.
    $filtered = $this->filterStorageFactory->getFilteredStorage($storage, ['config.storage.sync']);
    // Simulate the importing of configuration.
    self::replaceStorageContents($filtered, $temp);
    // Set the event storage to the one of the simulated import.
    self::replaceStorageContents($temp, $storage);
  }

  /**
   * The storage is transformed for exporting.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The event for altering configuration of the storage.
   */
  public function onExportTransform(StorageTransformEvent $event) {
    $storage = $event->getStorage();
    // The temporary storage representing the sync storage.
    $temp = new MemoryStorage();
    // Copy the contents of the sync storage to the temporary one.
    self::replaceStorageContents($this->sync, $temp);
    // Get the simulated filtered sync storage.
    $filtered = $this->filterStorageFactory->getFilteredStorage($temp, ['config.storage.sync']);
    // Simulate the exporting of the configuration.
    self::replaceStorageContents($storage, $filtered);
    // Set the event storage to the inner storage of the simulated sync storage.
    self::replaceStorageContents($temp, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // @todo: use class constants when they get added in #2991683
    $events['config.transform.import'][] = ['onImportTransform'];
    $events['config.transform.export'][] = ['onExportTransform'];
    return $events;
  }

}
