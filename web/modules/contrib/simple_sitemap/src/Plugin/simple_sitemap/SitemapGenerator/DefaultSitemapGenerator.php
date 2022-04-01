<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator;

/**
 * Class DefaultSitemapGenerator
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator
 *
 * @SitemapGenerator(
 *   id = "default",
 *   label = @Translation("Default sitemap generator"),
 *   description = @Translation("Generates a standard conform hreflang sitemap of your content."),
 * )
 */
class DefaultSitemapGenerator extends SitemapGeneratorBase {

  const XMLNS_XHTML = 'http://www.w3.org/1999/xhtml';
  const XMLNS_IMAGE = 'http://www.google.com/schemas/sitemap-image/1.1';

  /**
   * @var bool
   */
  protected $isHreflangSitemap;

  /**
   * @var array
   */
  protected static $attributes = [
    'xmlns' => self::XMLNS,
    'xmlns:xhtml' => self::XMLNS_XHTML,
    'xmlns:image' => self::XMLNS_IMAGE,
  ];

  /**
   * Generates and returns a sitemap chunk.
   *
   * @param array $links
   *   All links with their multilingual versions and settings.
   *
   * @return string
   *   Sitemap chunk
   */
  protected function getXml(array $links) {
    $this->writer->openMemory();
    $this->writer->setIndent(TRUE);
    $this->writer->startSitemapDocument();

    // Add the XML stylesheet to document if enabled.
    if ($this->settings['xsl']) {
      $this->writer->writeXsl();
    }

    $this->writer->writeGeneratedBy();
    $this->writer->startElement('urlset');
    $this->addSitemapAttributes();
    $this->addLinks($links);
    $this->writer->endElement();
    $this->writer->endDocument();

    return $this->writer->outputMemory();
  }

  /**
   * Adds attributes to the sitemap.
   */
  protected function addSitemapAttributes() {
    $attributes = self::$attributes;
    if (!$this->isHreflangSitemap()) {
      unset($attributes['xmlns:xhtml']);
    }
    $sitemap_variant = $this->sitemapVariant;
    $this->moduleHandler->alter('simple_sitemap_attributes', $attributes, $sitemap_variant);
    foreach ($attributes as $name => $value) {
      $this->writer->writeAttribute($name, $value);
    }
  }

  /**
   * Adds URL elements to the sitemap.
   *
   * @param array $links
   */
  protected function addLinks(array $links) {
    foreach ($links as $url_data) {
      $this->writer->startElement('url');
      $this->addUrl($url_data);
      $this->writer->endElement();
    }
  }

  /**
   * Adds a URL element to the sitemap.
   *
   * @param array $url_data
   *   The array of properties for this URL.
   */
  protected function addUrl(array $url_data) {
    $this->writer->writeElement('loc', $url_data['url']);

    // If more than one language is enabled, add all translation variant URLs
    // as alternate links to this link turning the sitemap into a hreflang
    // sitemap.
    if (isset($url_data['alternate_urls']) && $this->isHreflangSitemap()) {
      $this->addAlternateUrls($url_data['alternate_urls']);
    }

    // Add lastmod if any.
    if (isset($url_data['lastmod'])) {
      $this->writer->writeElement('lastmod', $url_data['lastmod']);
    }

    // Add changefreq if any.
    if (isset($url_data['changefreq'])) {
      $this->writer->writeElement('changefreq', $url_data['changefreq']);
    }

    // Add priority if any.
    if (isset($url_data['priority'])) {
      $this->writer->writeElement('priority', $url_data['priority']);
    }

    // Add images if any.
    if (!empty($url_data['images'])) {
      foreach ($url_data['images'] as $image) {
        $this->writer->startElement('image:image');
        $this->writer->writeElement('image:loc', $image['path']);
        if (strlen($image['title']) > 0) {
          $this->writer->writeElement('image:title', $image['title']);
        }
        if (strlen($image['alt']) > 0) {
          $this->writer->writeElement('image:caption', $image['alt']);
        }
        $this->writer->endElement();
      }
    }
  }

  /**
   * Adds all translation variant URLs as alternate URLs to a URL.
   *
   * @param array $alternate_urls
   */
  protected function addAlternateUrls(array $alternate_urls) {
    foreach ($alternate_urls as $language_id => $alternate_url) {
      $this->writer->startElement('xhtml:link');
      $this->addAlternateUrl($language_id, $alternate_url);
      $this->writer->endElement();
    }
  }

  /**
   * Adds a translation variant URL as alternate URL to a URL.
   *
   * @param $language_id
   * @param $alternate_url
   */
  protected function addAlternateUrl($language_id, $alternate_url) {
    $this->writer->writeAttribute('rel', 'alternate');
    $this->writer->writeAttribute('hreflang', $language_id);
    $this->writer->writeAttribute('href', $alternate_url);
  }

  /**
   * Checks if sitemap is hreflang compliant.
   *
   * @return bool
   */
  protected function isHreflangSitemap() {
    return NULL !== $this->isHreflangSitemap
      ? $this->isHreflangSitemap
      : self::isMultilingualSitemap();
  }
}
