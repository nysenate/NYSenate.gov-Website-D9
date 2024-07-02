<?php

namespace Drupal\views_combine\Plugin\views\cache;

use Drupal\Core\Cache\Cache;
use Drupal\views\Plugin\views\cache\Tag;
use Drupal\views_combine\ViewsCombiner;

/**
 * A handler to merge cache tags of combined views.
 *
 * @ingroup views_cache_plugins
 *
 * @ViewsCache(
 *   id = "combine_tags",
 *   title = @Translation("Tag based (views combine)"),
 *   help = @Translation("Merge cache tags from current view and combined views.")
 * )
 */
class CombineTags extends Tag {

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();

    if (isset($this->view->views_combine_queries)) {
      $views_combiner = new ViewsCombiner($this->view);

      foreach ($this->view->views_combine_queries as $query) {
        [$view_id, $display_id] = explode(':', $query->getMetaData('view_id'));
        if ($view = $views_combiner->getView($view_id, $display_id)) {
          // Merge the view storage cache tags.
          $tags = Cache::mergeTags($tags, $view->storage->getCacheTags());

          if ($entity_information = $view->getQuery()->getEntityTableInfo()) {
            foreach ($entity_information as $metadata) {
              // Merge the view executable query cache tags.
              $tags = Cache::mergeTags(
                $tags,
                \Drupal::entityTypeManager()->getDefinition($metadata['entity_type'])->getListCacheTags()
              );
            }
          }
        }
      }
    }

    return $tags;
  }

}
