<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator;

/**
 * Interface SitemapGeneratorInterface
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator
 */
interface SitemapGeneratorInterface {

  public function setSitemapVariant($sitemap_variant);

  public function setSettings(array $settings);

  public function generate(array $links);

  public function generateIndex();

  public function publish();

  public function remove();

  public function getSitemapUrl($delta = NULL);
}
