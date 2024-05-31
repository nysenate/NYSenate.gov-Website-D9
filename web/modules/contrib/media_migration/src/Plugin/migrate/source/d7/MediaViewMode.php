<?php

namespace Drupal\media_migration\Plugin\migrate\source\d7;

/**
 * Drupal 7 media view mode source based on source database.
 *
 * @MigrateSource(
 *   id = "d7_media_view_mode",
 *   source_module = "file_entity"
 * )
 */
class MediaViewMode extends DummyDrupalSqlBaseWithCountCompatibility {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $rows = [
      [
        'mode' => 'full',
        'label' => $this->getMediaViewModeLabel('full'),
      ],
      [
        'mode' => 'preview',
        'label' => $this->getMediaViewModeLabel('preview'),
      ],
      [
        'mode' => 'rss',
        'label' => $this->getMediaViewModeLabel('rss'),
      ],
      [
        'mode' => 'teaser',
        'label' => $this->getMediaViewModeLabel('teaser'),
      ],
    ];

    if ($this->moduleExists('search')) {
      $rows[] = [
        'mode' => 'search_index',
        'label' => $this->getMediaViewModeLabel('search_index'),
      ];
      $rows[] = [
        'mode' => 'search_result',
        'label' => $this->getMediaViewModeLabel('search_result'),
      ];
    }

    if ($this->moduleExists('media_wysiwyg')) {
      $rows[] = [
        'mode' => 'wysiwyg',
        'label' => $this->getMediaViewModeLabel('wysiwyg'),
      ];
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'mode' => $this->t('The media view mode name.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'mode' => [
        'type' => 'string',
        'alias' => 'mvm',
      ],
    ];
  }

  /**
   * Returns the label of the specified media view mode on the destination site.
   *
   * @param string $view_mode_id
   *   The ID of the view mode.
   *
   * @return string|null
   *   The label of the view mode, or NULL if the view mode does not exist.
   */
  protected function getMediaViewModeLabel(string $view_mode_id) {
    $view_mode_storage = $this->entityTypeManager->getStorage('entity_view_mode');
    $view_mode = $view_mode_storage->load("media.$view_mode_id");
    return $view_mode ? $view_mode->label() : NULL;
  }

}
