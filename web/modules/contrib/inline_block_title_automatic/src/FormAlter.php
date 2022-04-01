<?php

namespace Drupal\inline_block_title_automatic;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Form\FormStateInterface;

/**
 * A bridge for the alter hooks.
 *
 * Block content entities in layout builder suffer from a minor UI headache with
 * regards to titles. All block content entities require the "info" field to
 * have a value. In the reusable library world, this is labelled "Block
 * description". This is surfaced in the admin UIs when searching through the
 * block library as the administrative label of the entity, thus it is far
 * useful to have a descriptive label such as "winter recycling campaign 2018"
 * than something editorial such as "Recycle now!". These block descriptions
 * are also required for any component that may not have a clear "title" element
 * to display.
 *
 * For inline blocks and reusable blocks, a "placement" label is also always
 * required. For reusable blocks, this placement handily defaults to the "info"
 * field described above, but can be changed! For inline blocks, this is
 * mandatory and is used to populate the info field when a block is created,
 * thus the placement label always equals the "info" label.
 *
 * Allowing the placement label of the block to be overridden for reusable
 * blocks and having it required for inline blocks is confusing. For reusable
 * blocks, it breaks the model that content is authored in the block library,
 * for inline blocks it's not clear if that content is actually designed to
 * surface for end users (content admins would have to be aware of this and make
 * that decision for every block they place).
 *
 * This module fixes it by simply hiding all "placement" titles when inserting
 * a layout builder block_content block and removing the "Title display"
 * checkbox. As a result, a confusing decision is taken away from content
 * authors.
 *
 * The primary implication for site builders is: any primary user-facing title
 * based component requires a new field and the "Block description" field should
 * be purely used as an admin-facing description.
 */
class FormAlter {

  /**
   * Invoked for implementations of hook form alter for layout builder.
   */
  public function blockAddConfigureAlter(&$form, FormStateInterface $form_state, $form_id) {
    // Only alter the block configuration forms for inline or reusable
    // block_content entity blocks, since they are primarily the ones afflicted
    // with the conflict described above.
    $is_reusable_block_content = $form['settings']['provider']['#value'] === 'block_content';
    $is_inline_block = isset($form['settings']['block_form']['#block']) && $form['settings']['block_form']['#block'] instanceof BlockContent;
    if (!$is_reusable_block_content && !$is_inline_block) {
      return;
    }

    // Hide the label of the block placement and provide a default value if it
    // is empty.
    $form['settings']['label']['#type'] = 'value';
    if (empty($form['settings']['label']['#default_value'])) {
      $form['settings']['label']['#default_value'] = 'Inline block';
    }

    // Default to hiding the label of the block.
    $form['settings']['label_display']['#default_value'] = FALSE;
    $form['settings']['label_display']['#type'] = 'value';
  }

}
