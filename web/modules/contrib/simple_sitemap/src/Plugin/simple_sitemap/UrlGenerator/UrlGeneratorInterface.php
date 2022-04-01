<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

/**
 * Interface UrlGeneratorInterface
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 */
interface UrlGeneratorInterface {

  public function setSettings(array $settings);

  public function setSitemapVariant($sitemap_variant);

  public function getDataSets();

  public function generate($data_set);
}
