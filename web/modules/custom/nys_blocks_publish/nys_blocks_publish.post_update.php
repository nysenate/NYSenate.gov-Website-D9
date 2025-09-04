// my_block_publish.post_update.php
<?php

/**
 * @file
 * Adds status to manage display forms for custom block types.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityDisplayInterface;

/**
 * Add 'status' widget to all block_content form displays.
 */
function nys_blocks_publish_post_update_add_status_widget(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_form_display', function (EntityDisplayInterface $display) {
    if ($display->getTargetEntityTypeId() === 'block_content' && !$display->getComponent('status')) {
      $display->setComponent('status', [
        'type' => 'boolean_checkbox',
        'settings' => ['display_label' => TRUE],
        'region' => 'content',
        'weight' => -99,
      ]);
      return TRUE;
    }
    return FALSE;
  });
}
