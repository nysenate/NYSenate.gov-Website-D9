<?php

namespace Drupal\simple_sitemap\Manager;

/**
 * Provides an interface to setting/getting sitemaps.
 */
interface SitemapGetterInterface {

  /**
   * Gets the currently set sitemaps.
   *
   * @return \Drupal\simple_sitemap\Entity\SimpleSitemap[]
   *   The currently set sitemaps, or all compatible sitemaps if none are set.
   */
  public function getSitemaps(): array;

  /**
   * Sets the sitemaps.
   *
   * @param \Drupal\simple_sitemap\Entity\SimpleSitemap[]|\Drupal\simple_sitemap\Entity\SimpleSitemap|string[]|string|null $sitemaps
   *   SimpleSitemap[]: Array of sitemap objects to be set.
   *   string[]: Array of sitemap IDs to be set.
   *   SimpleSitemap: A particular sitemap object to be set.
   *   string: A particular sitemap ID to be set.
   *   null: All compatible sitemaps will be set.
   *
   * @return $this
   */
  public function setSitemaps($sitemaps = NULL): self;

}
