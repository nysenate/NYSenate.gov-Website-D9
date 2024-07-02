<?php

namespace Drupal\media_migration\Plugin\migrate\process;

use Drupal\media_migration\MediaMigration;
use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Adds media_plugin or entity_embed tokens to filter_html's allowed html.
 *
 * @code
 * process:
 *   destination_property:
 *     plugin: filter_settings_embed_media
 *     source: source_property
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "filter_settings_embed_media",
 *   handle_multiples = TRUE
 * )
 */
class FilterSettingsEmbedMedia extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $destination_filter_plugin_id = MediaMigration::getEmbedTokenDestinationFilterPlugin();
    if (!MediaMigration::embedTokenDestinationFilterPluginIsValid($destination_filter_plugin_id)) {
      throw new MigrateException("The embed token's destination filter plugin ID is invalid.");
    }
    if ($row->getDestinationProperty('id') === 'filter_html') {
      switch ($destination_filter_plugin_id) {
        case MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED:
          $tag_to_add = '<drupal-entity data-*>';
          break;

        case MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_MEDIA_EMBED:
          $tag_to_add = '<drupal-media data-* alt title>';
          break;
      }

      if (empty($tag_to_add)) {
        return $value;
      }

      $value['allowed_html'] = ltrim("{$value['allowed_html']} $tag_to_add");
    }

    // The Drupal 7 "media_filter" filter (Media WYSIWYG module) did not have
    // any settings, but the Drupal 8|9 "media_embed" filter does have (and it
    // also specifies defaults). The D8|9 "media_embed" filter implements
    // calculateDependencies() to ensure any text format that uses it also has
    // the appropriate configuration dependencies. In this logic, it inspects
    // the "media_embed" filter settings. When those settings are not specified
    // (which they should be given they're in the default settings in the filter
    // plugin annotation), then the dependency calculation triggers a fatal
    // error.
    // To prevent that, we apply the defaults manually here.
    // @todo Remove this when https://drupal.org/i/3166930 gets fixed.
    // @see https://drupal.org/i/3167525
    if ($row->getDestinationProperty('id') === 'media_embed') {
      // @see \Drupal\media\Plugin\Filter\MediaEmbed
      $value += [
        'default_view_mode' => 'default',
        'allowed_view_modes' => [],
        'allowed_media_types' => [],
      ];
    }

    return $value;
  }

}
