<?php

namespace Drupal\nys_entity_print\EventSubscriber;

use Drupal\entity_print\Event\PrintEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Alters Entity Print configuration (e.g., headers).
 */
final class EntityPrintSubscriber implements EventSubscriberInterface {

  /**
   * Registers the events this subscriber listens to.
   *
   * @return array<string, string[]|string>
   *   An array keyed by event name mapping to the handler method.
   */
  public static function getSubscribedEvents(): array {
    // Subscribe to the configuration alter event.
    return [
      PrintEvents::CONFIGURATION_ALTER => 'onEntityPrintConfigurationAlter',
    ];
  }

  /**
   * React to the event fired when retrieving a Print engine configuration.
   */
  public function onEntityPrintConfigurationAlter(GenericEvent $event): void {
    $configuration = $event->getArgument('configuration');
    $configuration['headers']['X-Robots-Tag'] = 'noindex,nofollow,noarchive';
    $event->setArgument('configuration', $configuration);
  }

}
