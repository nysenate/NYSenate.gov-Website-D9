<?php

namespace Drupal\media_migration;

use Drupal\migrate\Row;

/**
 * Interface for Media WYSIWYG plugins.
 *
 * MediaWysiwygInterface plugin instances are used for adding Media Migration's
 * migrate process plugins to the value process pipeline of formatted text
 * fields.
 *
 * These migrate process plugins are transforming Drupal 7 tokens and tags to an
 * equivalent Drupal 9 tag:
 * - Drupal 7 Media WYSIWYG JSON embed tokens found in field values are
 *   transformed to Drupal 9 Entity Embed or Drupal 9 Media embed tags
 *   (process plugin ID: 'media_wysiwyg_filter').
 * - <img> tags are also transformed to Drupal 9 Entity Embed or Drupal 9 Media
 *   embed tags (process plugin ID: 'img_tag_to_embed').
 * - Drupal 7 'ckeditor_link_file' links are transformed to Drupal 9 Linkit
 *   links (process plugin ID: 'ckeditor_link_file_to_linkit'),
 *
 * Every Media WYSIWYG plugin must define a unique plugin ID, a plugin label and
 * an entity_type_map. The entity_type_map should map the source (content)
 * entity type IDs to destination (content) entity type IDs. For content entity
 * types provided by contrib modules, it is also advisable to explicitly specify
 * a provider module: that will ensure that the plugin won't be discovered by
 * the plugin manager unless the provider module is installed.
 *
 * An example annotation:
 * @code
 * @MediaWysiwyg(
 *   id = "custom_plugin_id",
 *   label = @Translation("A custom Media WYSIWYG plugin"),
 *   entity_type_map = {
 *     "entity_type_id_on_source" = "dest_entity_type_id",
 *     "another_entity_type_id_on_source" = "dest_entity_type_id",
 *     "yet_another_entity_type_id_on_source" = "dest_entity_type_id_2",
 *   },
 *   provider = "module_providing_dest_entity_type_id_and_dest_entity_type_id_2"
 * )
 * @endcode
 *
 * @see \Drupal\media_migration\Plugin\migrate\process\MediaWysiwygFilter
 * @see \Drupal\media_migration\Plugin\migrate\process\ImgTagToEmbedFilter
 * @see \Drupal\media_migration\Plugin\migrate\process\CKEditorLinkFileToLinkitFilter
 * @see \Drupal\media_migration\Annotation\MediaWysiwyg
 * @see \Drupal\media_migration\MediaWysiwygPluginManager
 * @see \Drupal\media_migration\MigratePluginAlterer::addMediaWysiwygProcessor
 */
interface MediaWysiwygInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

  /**
   * Processes the migrations affected by the given field instance row.
   *
   * This method is responsible for altering those field value process pipelines
   * (by adding the needed migration process plugins) which are migrating the
   * values of the given field instance migration row.
   *
   * @param array $migrations
   *   The available migration plugin definitions as an array, keyed by their
   *   plugin ID.
   * @param \Drupal\migrate\Row $row
   *   A field instance migration row.
   *
   * @return array[]
   *   The migration definitions, including the required changes.
   *
   * @see \Drupal\media_migration\MigratePluginAlterer::addMediaWysiwygProcessor
   */
  public function process(array $migrations, Row $row);

}
