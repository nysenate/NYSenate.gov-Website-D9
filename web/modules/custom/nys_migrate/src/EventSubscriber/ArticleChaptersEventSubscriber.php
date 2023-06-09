<?php

namespace Drupal\nys_migrate\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Post Row Save Event to map Chapter Paragraph to Article Content.
 *
 * @package Drupal\nys_migrate\EventSubscriber
 */
class ArticleChaptersEventSubscriber implements EventSubscriberInterface {

  /**
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ArticleChaptersEventSubscriber constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    $events[MigrateEvents::POST_ROW_SAVE] = ['onPostRowSave'];
    return $events;
  }

  /**
   * Maps the Chapter Paragraphs to Article content.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The migrate post row save event.
   */
  public function onPostRowSave(MigratePostRowSaveEvent $event) {
    if ($event->getMigration()->getBaseId() != 'nys_content_article') {
      return;
    }

    $row = $event->getRow();
    $row_nid = $row->get('nid');

    $source = $row->getSource();

    if ($source['field_chapters']) {
      // Establish a connection to the current db.
      $db = \Drupal::database();

      // Grab Destination ID for the corresponding block.
      $dest_block = $db->select('migrate_map_nys_chapter_blocks', 'm')
        ->fields('m', ['destid1'])
        ->condition('sourceid1', $row_nid)
        ->execute()->fetchCol();

      if ($dest_block) {
        // If we have a dest id, then we need to fetch revision ID.
        $result = $db->select('block_content', 'bc')
          ->fields('bc', ['revision_id'])
          ->condition('id', $dest_block[0])
          ->execute()->fetchCol();

        if ($result) {
          $blocks = [
            'target_id' => $dest_block[0],
            'target_revision_id' => $result[0],
          ];
          // Append the paragraph to Layout components.
          $node = $this->entityTypeManager->getStorage('node')->load($row_nid);
          $node->field_layout_components[] = $blocks;
          $node->save();
        }
      }
    }
  }

}
