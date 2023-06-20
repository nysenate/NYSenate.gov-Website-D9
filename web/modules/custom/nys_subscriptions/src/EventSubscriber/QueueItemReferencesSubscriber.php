<?php

namespace Drupal\nys_subscriptions\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\nys_subscriptions\Event\QueueItemReferences;
use Drupal\nys_subscriptions\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for nys_sendgrid after.format event.
 */
class QueueItemReferencesSubscriber implements EventSubscriberInterface {

  /**
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $manager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritDoc}
   *
   * This subscriber should fire before any others.
   */
  public static function getSubscribedEvents(): array {
    return [
      Events::QUEUEITEM_REFERENCES => ['populateReferences', 1000],
    ];
  }

  /**
   * Populates the target entity reference for all subscription queue items.
   *
   * By this point, we've done as much checking as we can to validate the
   * entity information.  To paraphrase Elsa, LET IT THROW!
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function populateReferences(QueueItemReferences $event) {
    $refs = &$event->item->references;
    $data = $event->item->data ?? [];

    // Set the target.
    $refs['target_entity'] = $this->manager
      ->getStorage($data['target_type'] ?? '')
      ->load($data['target_id'] ?? 0);

    // If a source exists, set it.
    $type = $data['source_type'] ?? '';
    $id = $data['source_id'] ?? '';
    $refs['source_entity'] = ($type && $id)
        ? $this->manager->getStorage($type)->load($id) : NULL;
  }

}
