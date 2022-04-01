<?php

namespace Drupal\entity_usage\Plugin\EntityUsage\Track;

use Drupal\block_content\Plugin\Block\BlockContentBlock;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_usage\EntityUsageTrackBase;

/**
 * Tracks usage of entities related in block_field fields.
 *
 * @EntityUsageTrack(
 *   id = "block_field",
 *   label = @Translation("Block Field"),
 *   description = @Translation("Tracks relationships created with 'Block Field' fields."),
 *   field_types = {"block_field"},
 * )
 */
class BlockField extends EntityUsageTrackBase {

  /**
   * {@inheritdoc}
   */
  public function getTargetEntities(FieldItemInterface $item) {
    /** @var \Drupal\block_field\BlockFieldItemInterface $item */
    $block_instance = $item->getBlock();
    if (!$block_instance) {
      return [];
    }

    $target_type = NULL;
    $target_id = NULL;

    // If there is a view inside this block, track the view entity instead.
    if ($block_instance->getBaseId() === 'views_block') {
      list($view_name, $display_id) = explode('-', $block_instance->getDerivativeId(), 2);
      // @todo worth trying to track the display id as well?
      // At this point the view is supposed to exist. Only track it if so.
      if ($this->entityTypeManager->getStorage('view')->load($view_name)) {
        $target_type = 'view';
        $target_id = $view_name;
      }
    }
    elseif ($block_instance instanceof BlockContentBlock
      && $uuid = $block_instance->getDerivativeId()) {
      $blocks = $this->entityTypeManager
        ->getStorage('block_content')
        ->loadByProperties(['uuid' => $uuid]);
      if (!empty($blocks)) {
        // Doing this here means that an initial save operation of a host entity
        // will likely not track this block, once it does not exist at this
        // point. However, it's preferable to miss that and ensure we only track
        // lodable entities.
        $block = reset($blocks);
        $target_id = $block->id();
        $target_type = 'block_content';
      }
    }
    // I'm not 100% convinced of the utility of this scenario, but technically
    // it could happen.
    elseif ($block_instance instanceof BlockPluginInterface
      && !($block_instance instanceof BlockContentBlock)) {
      $target_id = $block_instance->getPluginId();
      $target_type = 'block';
    }
    else {
      throw new \Exception('Block saved as target entity is not one of the trackable block types.');
    }

    return ($target_type && $target_id) ? [$target_type . '|' . $target_id] : [];
  }

}
