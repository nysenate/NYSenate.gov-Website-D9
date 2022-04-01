<?php

namespace Drupal\simple_sitemap;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType\SitemapTypeBase;
use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType\SitemapTypeManager;
use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapGeneratorBase;
use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapGeneratorManager;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorBase;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager;

/**
 * Class SimplesitemapManager
 * @package Drupal\simple_sitemap
 */
class SimplesitemapManager {

  const DEFAULT_SITEMAP_TYPE = 'default_hreflang';

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * @var \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType\SitemapTypeManager
   */
  protected $sitemapTypeManager;

  /**
   * @var \Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager
   */
  protected $urlGeneratorManager;

  /**
   * @var \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapGeneratorManager
   */
  protected $sitemapGeneratorManager;

  /**
   * @var \Drupal\simple_sitemap\SimplesitemapSettings
   */
  protected $settings;

  /**
   * @var SitemapTypeBase[] $sitemapTypes
   */
  protected $sitemapTypes = [];

  /**
   * @var UrlGeneratorBase[] $urlGenerators
   */
  protected $urlGenerators = [];

  /**
   * @var SitemapGeneratorBase[] $sitemapGenerators
   */
  protected $sitemapGenerators = [];

  /**
   * SimplesitemapManager constructor.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType\SitemapTypeManager $sitemap_type_manager
   * @param \Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager $url_generator_manager
   * @param \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapGeneratorManager $sitemap_generator_manager
   * @param \Drupal\simple_sitemap\SimplesitemapSettings $settings
   */
  public function __construct(
    ConfigFactory $config_factory,
    Connection $database,
    SitemapTypeManager $sitemap_type_manager,
    UrlGeneratorManager $url_generator_manager,
    SitemapGeneratorManager $sitemap_generator_manager,
    SimplesitemapSettings $settings
  ) {
    $this->configFactory = $config_factory;
    $this->db = $database;
    $this->sitemapTypeManager = $sitemap_type_manager;
    $this->urlGeneratorManager = $url_generator_manager;
    $this->sitemapGeneratorManager = $sitemap_generator_manager;
    $this->settings = $settings;
  }

  /**
   * @param string $sitemap_generator_id
   * @return \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapGeneratorBase
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getSitemapGenerator($sitemap_generator_id) {
    if (!isset($this->sitemapGenerators[$sitemap_generator_id])) {
      $this->sitemapGenerators[$sitemap_generator_id]
        = $this->sitemapGeneratorManager->createInstance($sitemap_generator_id);
    }

    return $this->sitemapGenerators[$sitemap_generator_id];
  }

  /**
   * @param string $url_generator_id
   * @return \Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorBase
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getUrlGenerator($url_generator_id) {
    if (!isset($this->urlGenerators[$url_generator_id])) {
      $this->urlGenerators[$url_generator_id]
        = $this->urlGeneratorManager->createInstance($url_generator_id);
    }

    return $this->urlGenerators[$url_generator_id];
  }

  /**
   * @return \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType\SitemapTypeBase[]
   */
  public function getSitemapTypes() {
    if (empty($this->sitemapTypes)) {
      $this->sitemapTypes = $this->sitemapTypeManager->getDefinitions();
    }

    return $this->sitemapTypes;
  }

  /**
   * @param string|null $sitemap_type
   * @param bool $attach_type_info
   * @return array
   */
  public function getSitemapVariants($sitemap_type = NULL, $attach_type_info = TRUE) {
    if (NULL === $sitemap_type) {
      $variants_by_type = [];
      foreach ($this->configFactory->listAll('simple_sitemap.variants.') as $config_name) {
        $variants = !empty($variants = $this->configFactory->get($config_name)->get('variants')) ? $variants : [];
        $variants = $attach_type_info ? $this->attachSitemapTypeToVariants($variants, explode('.', $config_name)[2]) : $variants;
        $variants_by_type[] = $variants;
      }
      $variants = array_merge([], ...$variants_by_type);
    }
    else {
      $variants = !empty($variants = $this->configFactory->get("simple_sitemap.variants.$sitemap_type")->get('variants')) ? $variants : [];
      $variants = $attach_type_info ? $this->attachSitemapTypeToVariants($variants, $sitemap_type) : $variants;
    }

    // Sort variants by weight.
    $variant_weights = array_column($variants, 'weight');
    array_multisort($variant_weights, SORT_ASC, $variants);

    return $variants;
  }

  /**
   * @param array $variants
   * @param string $type
   * @return array
   */
  protected function attachSitemapTypeToVariants(array $variants, $type) {
    return array_map(static function($variant) use ($type) { return $variant + ['type' => $type]; }, $variants);
  }

  /**
   * @param string $name
   * @param array $definition
   * @return $this
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function addSitemapVariant($name, $definition = []) {
    $all_variants = $this->getSitemapVariants();
    if (isset($all_variants[$name])) {
      $old_variant = $all_variants[$name];
      if (!empty($definition['type']) && $old_variant['type'] !== $definition['type']) {
        $this->removeSitemapVariants($name);
        unset($old_variant);
      }
      else {
        unset($old_variant['type']);
      }
    }

    if (!isset($old_variant) && empty($definition['label'])) {
      $definition['label'] = (string) $name;
    }

    if (!isset($old_variant) && empty($definition['type'])) {
      $definition['type'] = self::DEFAULT_SITEMAP_TYPE;
    }

    if (isset($definition['weight'])) {
      $definition['weight'] = (int) $definition['weight'];
    }
    elseif (!isset($old_variant)) {
      $definition['weight'] = 0;
    }

    if (isset($old_variant)) {
      $definition += $old_variant;
    }

    $variants = array_merge($this->getSitemapVariants($definition['type'], FALSE), [$name => ['label' => $definition['label'], 'weight' => $definition['weight']]]);
    $this->configFactory->getEditable('simple_sitemap.variants.' . $definition['type'])
      ->set('variants', $variants)
      ->save();

    return $this;
  }

  /**
   * @param null|array|string $variant_names
   *  Limit removal by specific variants.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function removeSitemap($variant_names = NULL) {
    if (NULL === $variant_names || !empty((array) $variant_names)) {
      $saved_variants = $this->getSitemapVariants();
      $remove_variants = NULL === $variant_names
        ? $saved_variants
        : array_intersect_key($saved_variants, array_flip((array) $variant_names));

      if (!empty($remove_variants)) {
        $type_definitions = $this->getSitemapTypes();
        foreach ($remove_variants as $variant_name => $variant_definition) {
          $this->getSitemapGenerator($type_definitions[$variant_definition['type']]['sitemapGenerator'])
            ->setSitemapVariant($variant_name)
            ->remove();
        }
      }
    }

    return $this;
  }

  /**
   * @param null|array|string $variant_names
   *  Limit removal by specific variants.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function removeSitemapVariants($variant_names = NULL) {
    if (NULL === $variant_names || !empty((array) $variant_names)) {

      // Remove sitemap instances.
      $this->removeSitemap($variant_names);

      if (NULL === $variant_names) {
        // Remove all variants and their bundle settings.
        foreach(['variants', 'bundle_settings', 'custom_links'] as $config_name_part) {
          foreach ($this->configFactory->listAll("simple_sitemap.$config_name_part.") as $config_name) {
            $this->configFactory->getEditable($config_name)->delete();
          }
        }
      }
      else {
        // Remove bundle settings for specific variants.
        foreach ((array) $variant_names as $variant_name) {
          foreach ($this->configFactory->listAll("simple_sitemap.bundle_settings.$variant_name.") as $config_name) {
            $this->configFactory->getEditable($config_name)->delete();
          }
        }

        // Remove custom links for specific variants.
        foreach ((array) $variant_names as $variant_name) {
          foreach ($this->configFactory->listAll("simple_sitemap.custom_links.$variant_name") as $config_name) {
            $this->configFactory->getEditable($config_name)->delete();
          }
        }

        // Remove specific variants from configuration.
        $remove_variants = [];
        $variants = $this->getSitemapVariants();
        foreach ((array) $variant_names as $variant_name) {
          if (isset($variants[$variant_name])) {
            $remove_variants[$variants[$variant_name]['type']][$variant_name] = $variant_name;
          }
        }
        foreach ($remove_variants as $type => $variants_per_type) {
          $this->configFactory->getEditable("simple_sitemap.variants.$type")
            ->set('variants', array_diff_key($this->getSitemapVariants($type, FALSE), $variants_per_type))
            ->save();
        }
      }

      // Remove bundle setting overrides for entities.
      $query = $this->db->delete('simple_sitemap_entity_overrides');
      if (NULL !== $variant_names) {
        $query->condition('type', (array) $variant_names, 'IN');
      }
      $query->execute();

      // Remove default variant setting.
      if (NULL === $variant_names
        || in_array($this->settings->getSetting('default_variant', ''), (array) $variant_names)) {
        $this->settings->saveSetting('default_variant', '');
      }
    }

    return $this;
  }
}
