<?php

namespace Drupal\nys_extra_fields\Service;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

/**
 * Service for building the content type extra field.
 */
class ContentTypeBuilder {

  use StringTranslationTrait;

  /**
   * Phosphor icon (ph-fill) keyed by node bundle.
   *
   * Keyed by bundle rather than the displayed label, since e.g. an article
   * with field_category "Press release" still gets the article icon - see
   * nysenate-my-dashboard-teaser.twig in nysenate_theme, which this mirrors.
   */
  private const ICON_MAP = [
    'article' => 'article',
    'in_the_news' => 'newspaper-clipping',
    'video' => 'video-camera',
  ];

  /**
   * Builds the content type render array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The render array.
   */
  public function buildContentType(EntityInterface $entity): array {
    // Only process if the entity is a node.
    if ($entity instanceof NodeInterface) {
      // Get the node type label.
      $type = $entity->type->entity;
      $type_label = $type ? $type->label() : $entity->bundle();

      // Special handling for content types with field_category.
      if ($entity->hasField('field_category') && !$entity->get('field_category')->isEmpty()) {
        // Get the allowed values from field_category.
        $allowed_values = $entity->field_category->getSetting('allowed_values');
        // Use the allowed value as the type label if it exists.
        if (isset($allowed_values[$entity->field_category->value])) {
          $type_label = $allowed_values[$entity->field_category->value];
        }
      }

      $icon = self::ICON_MAP[$entity->bundle()] ?? NULL;
      if ($icon) {
        return [
          '#markup' => new FormattableMarkup('<i class="ph-fill ph-@icon" aria-hidden="true"></i> @label', [
            '@icon' => $icon,
            '@label' => $type_label,
          ]),
        ];
      }

      return [
        '#markup' => $type_label,
      ];
    }

    return [];
  }

}
