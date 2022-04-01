<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType;

/**
 * Class DefaultHreflangSitemapType
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType
 *
 * @SitemapType(
 *   id = "default_hreflang",
 *   label = @Translation("Default hreflang"),
 *   description = @Translation("The default hreflang sitemap type."),
 *   sitemapGenerator = "default",
 *   urlGenerators = {
 *     "custom",
 *     "entity",
 *     "entity_menu_link_content",
 *     "arbitrary",
 *   },
 * )
 */
class DefaultHreflangSitemapType extends SitemapTypeBase {
}
