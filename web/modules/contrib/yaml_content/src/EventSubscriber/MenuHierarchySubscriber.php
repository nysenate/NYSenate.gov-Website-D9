<?php

namespace Drupal\yaml_content\EventSubscriber;

use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\yaml_content\Event\EntityPreSaveEvent;
use Drupal\yaml_content\Event\YamlContentEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber to correct link parent reference formatting.
 *
 * Using entity references and processing for parent menu links within YAML
 * results in the entity being loaded and assigned to the parent value, but
 * the schema for menu content links expects this value to be a string instead.
 * This event subscriber replaces the entity object with the appropriate
 * reference string instead prior to saving.
 */
class MenuHierarchySubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[YamlContentEvents::ENTITY_PRE_SAVE][] = ['serializeMenuParent'];

    return $events;
  }

  /**
   * Convert loaded parent entities for menu items to an expected value format.
   *
   * @param \Drupal\yaml_content\Event\EntityPreSaveEvent $event
   *   The entity pre-save event containing data being processed.
   */
  public function serializeMenuParent(EntityPreSaveEvent $event) {
    $import_content = $event->getContentData();

    // Stop here if we're not working with a menu link.
    if (empty($import_content['entity'])
      || $import_content['entity'] != 'menu_link_content') {
      return;
    }

    // Stop here if there is no parent link indicated in content.
    if (empty($import_content['parent'])) {
      return;
    }

    /** @var \Drupal\Core\Menu\MenuLinkInterface $entity */
    $entity = $event->getEntity();
    $parent_value = $entity->parent->first()->value;

    if ($parent_value instanceof MenuLinkContentInterface) {
      $entity->set('parent', $parent_value->getPluginId(), FALSE);
    }
  }

}
