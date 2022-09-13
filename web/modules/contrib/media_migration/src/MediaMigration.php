<?php

namespace Drupal\media_migration;

use Drupal\Core\Site\Settings;

/**
 * Media Migration configuration and helpers.
 */
final class MediaMigration {

  /**
   * Migration tag for every media related migrations.
   *
   * @const string
   */
  const MIGRATION_TAG_MAIN = 'Media Migration';

  /**
   * Migration tag for media configuration migrations.
   *
   * @const string
   */
  const MIGRATION_TAG_CONFIG = 'Media Configuration';

  /**
   * Migration tag for media entity migrations.
   *
   * @const string
   */
  const MIGRATION_TAG_CONTENT = 'Media Entity';

  /**
   * The name of the media UUID prophecy table.
   *
   * @const string
   */
  const MEDIA_UUID_PROPHECY_TABLE = 'media_migration_media_entity_uuid_prophecy';

  /**
   * The name of the media source ID column.
   *
   * @const string
   */
  const MEDIA_UUID_PROPHECY_SOURCEID_COL = 'source_id';

  /**
   * The name of the column that contains the destination UUID.
   *
   * @const string
   */
  const MEDIA_UUID_PROPHECY_UUID_COL = 'destination_uuid';

  /**
   * The name of the setting of how embedded media should be referred.
   *
   * @const string
   */
  const MEDIA_REFERENCE_METHOD_SETTINGS = 'media_migration_embed_media_reference_method';

  /**
   * The ID embedded media reference method.
   *
   * @const string
   */
  const EMBED_MEDIA_REFERENCE_METHOD_ID = 'id';

  /**
   * The UUID embedded media reference method.
   *
   * @const string
   */
  const EMBED_MEDIA_REFERENCE_METHOD_UUID = 'uuid';

  /**
   * Default embedded media reference method.
   *
   * @const string
   */
  const EMBED_MEDIA_REFERENCE_METHOD_DEFAULT = self::EMBED_MEDIA_REFERENCE_METHOD_ID;

  /**
   * Valid embedded media reference methods.
   *
   * @const string[]
   */
  const VALID_EMBED_MEDIA_REFERENCE_METHODS = [
    self::EMBED_MEDIA_REFERENCE_METHOD_ID,
    self::EMBED_MEDIA_REFERENCE_METHOD_UUID,
  ];

  /**
   * The name of embed code transformation destination filter plugin setting.
   *
   * @const string
   */
  const MEDIA_TOKEN_DESTINATION_FILTER_SETTINGS = 'media_migration_embed_token_transform_destination_filter_plugin';

  /**
   * Entity embed destination filter.
   *
   * @const string
   */
  const MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED = 'entity_embed';

  /**
   * Media embed destination filter.
   *
   * @const string
   */
  const MEDIA_TOKEN_DESTINATION_FILTER_MEDIA_EMBED = 'media_embed';

  /**
   * Default embed token destination filter plugin ID.
   *
   * Actually, MEDIA_TOKEN_DESTINATION_FILTER_MEDIA_EMBED would be the correct
   * default, but doing that would cause a BC break in this module.
   *
   * @const string
   */
  const MEDIA_TOKEN_DESTINATION_FILTER_DEFAULT = self::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED;

  /**
   * The required modules of the valid destination filter plugins.
   *
   * @const array[]
   */
  const MEDIA_TOKEN_DESTINATION_FILTER_REQUIREMENTS = [
    self::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED => [
      'entity_embed',
    ],
    self::MEDIA_TOKEN_DESTINATION_FILTER_MEDIA_EMBED => [
      'media',
    ],
  ];

  /**
   * SQL pattern of Linkit file links in (formatted) text fields.
   *
   * @const string
   */
  const SQL_PATTERN_LINKIT_FILE_LINK = '%<a %href=_/file/%';

  /**
   * PCRE pattern of Linkit file links in (formatted) text fields.
   *
   * @const string
   */
  const PCRE_PATTERN_LINKIT_FILE_LINK = '/.*<a(?=\s).*(?<=\s)href=(?<quote>[\'|"])\/file\/\d+(?P=quote)/';

  /**
   * Sets the method of the embedded media reference.
   *
   * @return string
   *   The reference method. This might be 'id', or 'uuid'.
   */
  public static function getEmbedMediaReferenceMethod() {
    $value_from_settings = Settings::get(self::MEDIA_REFERENCE_METHOD_SETTINGS, self::EMBED_MEDIA_REFERENCE_METHOD_DEFAULT);

    if (self::getEmbedTokenDestinationFilterPlugin() === self::MEDIA_TOKEN_DESTINATION_FILTER_MEDIA_EMBED) {
      return self::EMBED_MEDIA_REFERENCE_METHOD_UUID;
    }

    return in_array($value_from_settings, self::VALID_EMBED_MEDIA_REFERENCE_METHODS, TRUE) ?
      $value_from_settings :
      self::EMBED_MEDIA_REFERENCE_METHOD_DEFAULT;
  }

  /**
   * Returns the embed media token transform's destination filter plugin.
   *
   * @return string
   *   The embed media token transform's destination filter_plugin from
   *   settings.php.
   */
  public static function getEmbedTokenDestinationFilterPlugin() {
    return Settings::get(self::MEDIA_TOKEN_DESTINATION_FILTER_SETTINGS, self::MEDIA_TOKEN_DESTINATION_FILTER_DEFAULT);
  }

  /**
   * Whether the transform's destination filter_plugin is valid or not.
   *
   * @param string|null $filter_plugin_id
   *   The filter plugin ID to check.
   *
   * @return bool
   *   TRUE if the plugin is valid, FALSE if not.
   */
  public static function embedTokenDestinationFilterPluginIsValid($filter_plugin_id = NULL) {
    $valid_filter_plugin_ids = array_keys(self::MEDIA_TOKEN_DESTINATION_FILTER_REQUIREMENTS);
    $filter_plugin_id = $filter_plugin_id ?? self::getEmbedTokenDestinationFilterPlugin();
    return in_array($filter_plugin_id, $valid_filter_plugin_ids, TRUE);
  }

}
