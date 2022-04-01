<?php

namespace Drupal\simple_sitemap_engines\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the the search engine entity class.
 *
 * @ConfigEntityType(
 *   id = "simple_sitemap_engine",
 *   label = @Translation("Search engine"),
 *   admin_permission = "administer sitemap settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\simple_sitemap_engines\Controller\SearchEngineListBuilder",
 *   },
 *   links = {
 *     "collection" = "/admin/config/search/simplesitemap/engines/list",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "url",
 *     "sitemap_variants",
 *   }
 * )
 */
class SearchEngine extends ConfigEntityBase {

  /**
   * The search engine ID.
   *
   * @var string
   */
  public $id;

  /**
   * The search engine label.
   *
   * @var string
   */
  public $label;

  /**
   * The search engine submission URL.
   *
   * When submitting to search engines, '[sitemap]' will be replaced with the
   * full URL to the sitemap.xml.
   *
   * @var string
   */
  public $url;

  /**
   * List of sitemap variants to be submitted to this search engine.
   *
   * @var array
   */
  public $sitemap_variants;

  /**
   * Implements magic __toString() to simplify checkbox list building.
   *
   * @return string
   *   The search engine label.
   */
  public function __toString() {
    return $this->label();
  }

}
