<?php

namespace Drupal\nys_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\TermInterface;

/**
 * Block for Senator About Text.
 *
 * @Block(
 *   id = "nys_blocks_about_text",
 *   admin_label = @Translation("About Text Block"),
 * )
 */
class AboutTextBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build['#theme'] = 'nys_blocks_about_text';
    $node = \Drupal::routeMatch()->getParameter('node');

    if (!empty($node) && $node->bundle() === 'microsite_page') {
      if ($node->hasField('field_microsite_page_type') && !$node->get('field_microsite_page_type')->isEmpty()) {
        $term = $node->field_microsite_page_type->entity ?? [];
        if ($term instanceof TermInterface) {
          $name = $term->name->value ?? '';
          if ($name === 'About') {
            if ($node->hasField('field_senator_multiref') && !$node->get('field_senator_multiref')->isEmpty()) {
              $senator = $node->field_senator_multiref->first()->entity;

              if ($senator->hasField('field_about') && !$senator->get('field_about')->isEmpty()) {
                $about_text[]['text'] = $senator->field_about->value;
                $build['#about_text'] = $about_text;
              }
            }
          }
        }
      }
    }

    return $build;
  }

}
