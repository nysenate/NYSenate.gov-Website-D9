<?php

namespace Drupal\Tests\media_migration\Traits;

/**
 * Source database table values for Media Migration's tests.
 */
trait MediaMigrationSourceDatabaseTrait {

  /**
   * Returns the values for the "field_config" database table.
   *
   * @param bool $with_image_field
   *   Whether the returned data should also contain a record for an image
   *   field storage with name "field_image". Defaults to TRUE.
   *
   * @return array[]
   *   An array of database table records with values, keyed by the column name.
   */
  public static function getFieldConfigTableData(bool $with_image_field = TRUE) {
    $data = [
      [
        'id' => 2,
        'field_name' => 'body',
        'type' => 'text_with_summary',
        'module' => 'text',
        'active' => 1,
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => 1,
        'locked' => 0,
        'data' => serialize([
          'entity_types' => ['node'],
          'translatable' => FALSE,
          'settings' => [],
          'storage' => [
            'type' => 'field_sql_storage',
            'settings' => [],
            'module' => 'field_sql_storage',
            'active' => 1,
          ],
          'foreign keys' => [
            'format' => [
              'table' => 'filter_format',
              'columns' => ['format' => 'format'],
            ],
          ],
          'indexes' => ['format' => ['format']],
        ]),
        'cardinality' => 1,
        'translatable' => 0,
        'deleted' => 0,
      ],
    ];

    if ($with_image_field) {
      $data[] = [
        'id' => 4,
        'field_name' => 'field_image',
        'type' => 'image',
        'module' => 'image',
        'active' => 1,
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => 1,
        'locked' => 0,
        'data' => serialize([
          'indexes' => ['fid' => ['fid']],
          'settings' => [
            'uri_scheme' => 'public',
            'default_image' => 0,
          ],
          'storage' => [
            'type' => 'field_sql_storage',
            'settings' => [],
            'module' => 'field_sql_storage',
            'active' => 1,
            'details' => [
              'sql' => [
                'FIELD_LOAD_CURRENT' => [
                  'field_data_field_image' => [
                    'fid' => 'field_image_fid',
                    'alt' => 'field_image_alt',
                    'title' => 'field_image_title',
                    'width' => 'field_image_width',
                    'height' => 'field_image_height',
                  ],
                ],
                'FIELD_LOAD_REVISION' => [
                  'field_revision_field_image' => [
                    'fid' => 'field_image_fid',
                    'alt' => 'field_image_alt',
                    'title' => 'field_image_title',
                    'width' => 'field_image_width',
                    'height' => 'field_image_height',
                  ],
                ],
              ],
            ],
          ],
          'entity_types' => [],
          'translatable' => FALSE,
          'foreign keys' => [
            'fid' => [
              'table' => 'file_managed',
              'columns' => ['fid' => 'fid'],
            ],
          ],
        ]),
        'cardinality' => 1,
        'translatable' => 0,
        'deleted' => 0,
      ];
    }

    return $data;
  }

  /**
   * Returns the values for the "field_config_instance" database table.
   *
   * @param bool $with_node_article_image_field
   *   Whether the returned data should also contain a record for an image field
   *   instance "field_image" used on node article. Defaults to FALSE.
   * @param bool $node_article_image_alt_allowed
   *   Whether the returned image field instance "field_image" of article nodes
   *   (if any) should allow editing the "alt" property of the images. Defaults
   *   to FALSE.
   * @param bool $node_article_image_title_allowed
   *   Whether the returned image field instance "field_image" of article nodes
   *   (if any) should allow editing the "title" property of the images.
   *   Defaults to FALSE.
   * @param bool $media_image_alt_required
   *   Whether image media on the source is configured to require the "alt"
   *   property for images. Defaults to FALSE.
   * @param bool $media_image_title_required
   *   Whether image media on the source is configured to require the "title"
   *   property for images. Defaults to FALSE.
   *
   * @return array[]
   *   An array of database table records with values, keyed by the column name.
   */
  public static function getFieldConfigInstanceTableData(bool $with_node_article_image_field = FALSE, bool $node_article_image_alt_allowed = FALSE, bool $node_article_image_title_allowed = FALSE, bool $media_image_alt_required = FALSE, bool $media_image_title_required = FALSE) {
    $data = [
      [
        'id' => 7,
        'field_id' => 5,
        'field_name' => 'field_file_image_alt_text',
        'entity_type' => 'file',
        'bundle' => 'image',
        'data' => serialize([
          'default_value' => NULL,
          'description' => 'Help text for alt field',
          'display' => [
            'default' => [
              'label' => 'above',
              'settings' => [],
              'type' => 'hidden',
              'weight' => 0,
            ],
            'full' => [
              'label' => 'above',
              'settings' => [],
              'type' => 'hidden',
              'weight' => 0,
            ],
            'preview' => [
              'label' => 'above',
              'settings' => [],
              'type' => 'hidden',
              'weight' => 0,
            ],
            'teaser' => [
              'label' => 'above',
              'settings' => [],
              'type' => 'hidden',
              'weight' => 0,
            ],
          ],
          'label' => 'Alt Text',
          'required' => (int) $media_image_alt_required,
          'settings' => [
            'text_processing' => '0',
            'user_register_form' => FALSE,
            'wysiwyg_override' => 1,
          ],
          'widget' => [
            'active' => 1,
            'module' => 'text',
            'settings' => ['size' => '60'],
            'type' => 'text_textfield',
            'weight' => '-4',
          ],
        ]),
        'deleted' => 0,
      ],
      [
        'id' => 8,
        'field_id' => 6,
        'field_name' => 'field_file_image_title_text',
        'entity_type' => 'file',
        'bundle' => 'image',
        'data' => serialize([
          'default_value' => NULL,
          'description' => 'Help text for title field',
          'display' => [
            'default' => [
              'label' => 'above',
              'settings' => [],
              'type' => 'hidden',
              'weight' => 1,
            ],
            'full' => [
              'label' => 'above',
              'settings' => [],
              'type' => 'hidden',
              'weight' => 0,
            ],
            'preview' => [
              'label' => 'above',
              'settings' => [],
              'type' => 'hidden',
              'weight' => 0,
            ],
            'teaser' => [
              'label' => 'above',
              'settings' => [],
              'type' => 'hidden',
              'weight' => 0,
            ],
          ],
          'label' => 'Title Text',
          'required' => (int) $media_image_title_required,
          'settings' => [
            'text_processing' => '0',
            'user_register_form' => FALSE,
            'wysiwyg_override' => 1,
          ],
          'widget' => [
            'active' => 1,
            'module' => 'text',
            'settings' => ['size' => '60'],
            'type' => 'text_textfield',
            'weight' => '-3',
          ],
        ]),
        'deleted' => 0,
      ],
    ];

    if ($with_node_article_image_field) {
      $data[] = [
        'id' => 6,
        'field_id' => 4,
        'field_name' => 'field_image',
        'entity_type' => 'node',
        'bundle' => 'article',
        'data' => serialize([
          'label' => 'Image',
          'description' => 'Upload an image to go with this article.',
          'required' => 0,
          'settings' => [
            'file_directory' => 'field/image',
            'file_extensions' => 'png gif jpg jpeg',
            'max_filesize' => '2 MB',
            'max_resolution' => '',
            'min_resolution' => '',
            'alt_field' => (int) $node_article_image_alt_allowed,
            'title_field' => (int) $node_article_image_title_allowed,
            'default_image' => 0,
            'user_register_form' => FALSE,
          ],
          'widget' => [
            'weight' => '-1',
            'type' => 'image_image',
            'module' => 'image',
            'active' => 1,
            'settings' => [
              'progress_indicator' => 'throbber',
              'preview_image_style' => 'thumbnail',
            ],
          ],
          'display' => [
            'default' => [
              'label' => 'above',
              'type' => 'image',
              'weight' => '-1',
              'settings' => [
                'image_style' => 'large',
                'image_link' => '',
              ],
              'module' => 'image',
            ],
            'teaser' => [
              'label' => 'hidden',
              'type' => 'image',
              'settings' => [
                'image_style' => 'medium',
                'image_link' => 'content',
              ],
              'weight' => -1,
              'module' => 'image',
            ],
          ],
        ]),
        'deleted' => 0,
      ];
    }

    return $data;
  }

  /**
   * Returns values for the "field_data_field_file_image_title_text" DB table.
   *
   * @param bool $with_title_data
   *   Whether the returned data should should contain a record for some image
   *   "title" properties. Defaults to FALSE.
   *
   * @return array[]
   *   An array of database table records with values, keyed by the column name.
   */
  public static function getFieldDataFieldFileImageTitleTextTableData(bool $with_title_data = TRUE) {
    $data = [
      [
        'entity_type' => 'file',
        'bundle' => 'image',
        'deleted' => 0,
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'und',
        'delta' => 0,
        'field_file_image_title_text_value' => $with_title_data ? 'Title copy for blue.png' : NULL,
        'field_file_image_title_text_format' => NULL,
      ],
    ];

    if ($with_title_data) {
      $data[] = [
        'entity_type' => 'file',
        'bundle' => 'image',
        'deleted' => 0,
        'entity_id' => 3,
        'revision_id' => 3,
        'language' => 'und',
        'delta' => 0,
        'field_file_image_title_text_value' => 'Title copy for red.jpeg',
        'field_file_image_title_text_format' => NULL,
      ];
    }

    return $data;
  }

  /**
   * Returns the values for the "field_data_field_file_image_alt_text" DB table.
   *
   * @param bool $with_alt_data
   *   Whether the returned data should should contain a record for some image
   *   "alt" properties. Defaults to FALSE.
   *
   * @return array[]
   *   An array of database table records with values, keyed by the column name.
   */
  public static function getFieldDataFieldFileImageAltTextTableData(bool $with_alt_data = TRUE) {
    $data = [
      [
        'entity_type' => 'file',
        'bundle' => 'image',
        'deleted' => 0,
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'und',
        'delta' => 0,
        'field_file_image_alt_text_value' => $with_alt_data ? 'Alternative text about blue.png' : NULL,
        'field_file_image_alt_text_format' => NULL,
      ],
    ];

    if ($with_alt_data) {
      $data[] = [
        'entity_type' => 'file',
        'bundle' => 'image',
        'deleted' => 0,
        'entity_id' => 3,
        'revision_id' => 3,
        'language' => 'und',
        'delta' => 0,
        'field_file_image_alt_text_value' => 'Alternative text about red.jpeg',
        'field_file_image_alt_text_format' => NULL,
      ];
    }

    return $data;
  }

  /**
   * Returns the values for the "field_managed" database table.
   *
   * @return array[]
   *   An array of database table records with values, keyed by the column name.
   */
  public static function getFileManagedTableData() {
    return [
      [
        'fid' => 1,
        'uid' => 1,
        'filename' => 'Blue PNG',
        'uri' => 'public://blue.png',
        'filemime' => 'image/png',
        'filesize' => 9061,
        'status' => 1,
        'timestamp' => 1587725909,
        'type' => 'image',
      ],
      [
        'fid' => 2,
        'uid' => 1,
        'filename' => 'green.jpg',
        'uri' => 'public://field/image/green.jpg',
        'filemime' => 'image/jpeg',
        'filesize' => 11050,
        'status' => 1,
        'timestamp' => 1587730322,
        'type' => 'image',
      ],
      [
        'fid' => 3,
        'uid' => 1,
        'filename' => 'reg.jpeg',
        'uri' => 'public://red.jpeg',
        'filemime' => 'image/jpeg',
        'filesize' => 19098,
        'status' => 1,
        'timestamp' => 1587726037,
        'type' => 'image',
      ],
      [
        'fid' => 4,
        'uid' => 1,
        'filename' => 'DrupalCon Amsterdam 2019: Keynote - Driesnote',
        'uri' => 'youtube://v/Apqd4ff0NRI',
        'filemime' => 'video/youtube',
        'filesize' => 0,
        'status' => 1,
        'timestamp' => 1587726087,
        'type' => 'video',
      ],
      [
        'fid' => 5,
        'uid' => 1,
        'filename' => 'Responsive Images in Drupal 8',
        'uri' => 'vimeo://v/204517230',
        'filemime' => 'video/vimeo',
        'filesize' => 0,
        'status' => 1,
        'timestamp' => 1587730964,
        'type' => 'video',
      ],
      [
        'fid' => 6,
        'uid' => 1,
        'filename' => 'LICENSE.txt',
        'uri' => 'public://LICENSE.txt',
        'filemime' => 'text/plain',
        'filesize' => 18002,
        'status' => 1,
        'timestamp' => 1587731111,
        'type' => 'document',
      ],
      [
        'fid' => 7,
        'uid' => 2,
        'filename' => 'yellow.jpg',
        'uri' => 'public://field/image/yellow.jpg',
        'filemime' => 'image/jpeg',
        'filesize' => 5363,
        'status' => 1,
        'timestamp' => 1588600435,
        'type' => 'image',
      ],
      [
        'fid' => 8,
        'uid' => 2,
        'filename' => 'video.webm',
        'uri' => 'public://video.webm',
        'filemime' => 'video/webm',
        'filesize' => 18123,
        'status' => 1,
        'timestamp' => 1594037784,
        'type' => 'video',
      ],
      [
        'fid' => 9,
        'uid' => 2,
        'filename' => 'video.mp4',
        'uri' => 'public://video.mp4',
        'filemime' => 'video/mp4',
        'filesize' => 18011,
        'status' => 1,
        'timestamp' => 1594117700,
        'type' => 'video',
      ],
      [
        'fid' => 10,
        'uid' => 2,
        'filename' => 'yellow.webp',
        'uri' => 'public://yellow.webp',
        'filemime' => 'image/webp',
        'filesize' => 3238,
        'status' => 1,
        'timestamp' => 1594191582,
        'type' => 'image',
      ],
      [
        'fid' => 11,
        'uid' => 1,
        'filename' => 'audio.m4a',
        'uri' => 'public://audio.m4a',
        'filemime' => 'audio/mpeg',
        'filesize' => 10711,
        'status' => 1,
        'timestamp' => 1594191582,
        'type' => 'audio',
      ],
      [
        'fid' => 12,
        'uid' => 2,
        'filename' => 'document.odt',
        'uri' => 'public://document.odt',
        'filemime' => 'application/vnd.oasis.opendocument.text',
        'filesize' => 8089,
        'status' => 1,
        'timestamp' => 1594201103,
        'type' => 'document',
      ],
    ];
  }

  /**
   * Returns the values for the "field_usage" database table.
   *
   * @return array[]
   *   An array of database table records with values, keyed by the column name.
   */
  public static function getFileUsageTableData() {
    return [
      [
        'fid' => 1,
        'module' => 'media',
        'type' => 'node',
        'id' => 1,
        'count' => 1,
      ],
      [
        'fid' => 2,
        'module' => 'file',
        'type' => 'node',
        'id' => 1,
        'count' => 1,
      ],
      [
        'fid' => 3,
        'module' => 'file',
        'type' => 'node',
        'id' => 1,
        'count' => 1,
      ],
      [
        'fid' => 4,
        'module' => 'file',
        'type' => 'node',
        'id' => 1,
        'count' => 1,
      ],
      [
        'fid' => 7,
        'module' => 'file',
        'type' => 'node',
        'id' => 2,
        'count' => 1,
      ],
    ];
  }

  /**
   * Returns the values for the "users" database table.
   *
   * @return array[]
   *   An array of database table records with values, keyed by the column name.
   */
  public static function getUsersTableData() {
    return [
      [
        'uid' => 0,
        'name' => '',
        'pass' => '',
        'mail' => NULL,
        'theme' => '',
        'signature' => '',
        'signature_format' => NULL,
        'created' => 0,
        'access' => 0,
        'login' => 0,
        'status' => 0,
        'timezone' => NULL,
        'language' => '',
        'picture' => 0,
        'init' => NULL,
        'data' => NULL,
      ],
    ];
  }

}
