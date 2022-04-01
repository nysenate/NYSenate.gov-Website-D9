<?php

namespace Drupal\oembed_providers\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the oEmbed provider bucket entity.
 *
 * @ConfigEntityType(
 *   id = "oembed_provider_bucket",
 *   label = @Translation("oEmbed provider bucket"),
 *   label_collection = @Translation("oEmbed provider buckets"),
 *   label_singular = @Translation("oembed provider bucket"),
 *   label_plural = @Translation("oembed provider buckets"),
 *   label_count = @PluralTranslation(
 *     singular = "@count oembed provider bucket",
 *     plural = "@count oembed provider buckets",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\oembed_providers\OembedProviderBucketListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\oembed_providers\OembedProviderBucketForm",
 *       "add" = "Drupal\oembed_providers\OembedProviderBucketForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     }
 *   },
 *   admin_permission = "administer oembed providers",
 *   config_prefix = "bucket",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "providers",
 *     "description"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/media/oembed-providers/buckets/{oembed_provider_bucket}/edit",
 *     "delete-form" = "/admin/config/media/oembed-providers/buckets/{oembed_provider_bucket}/delete",
 *     "collection" = "/admin/config/media/oembed-providers/buckets",
 *   }
 * )
 */
class ProviderBucket extends ConfigEntityBase {

  /**
   * The oEmbed provider bucket ID (machine name).
   *
   * @var string
   */
  protected $id;

  /**
   * The oEmbed provider bucket label.
   *
   * @var string
   */
  protected $label;

  /**
   * Description of this bucket.
   *
   * @var string
   */
  protected $description;

  /**
   * Providers allowed by this bucket.
   *
   * @var array
   */
  protected $providers;

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Dependency injection is impossible because
    // \Drupal\Core\Entity\EntityBase defines an incompatible create() method.
    \Drupal::service('plugin.manager.media.source')->clearCachedDefinitions();
  }

}
