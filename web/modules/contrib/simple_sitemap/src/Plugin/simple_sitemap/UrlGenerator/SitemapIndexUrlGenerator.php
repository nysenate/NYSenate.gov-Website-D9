<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\simple_sitemap\Entity\SimpleSitemap;
use Drupal\simple_sitemap\Exception\SkipElementException;

/**
 * Class VariantIndexUrlGenerator.
 *
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 *
 * @UrlGenerator(
 *   id = "index",
 *   label = @Translation("Sitemap URL generator"),
 *   description = @Translation("Generates sitemap URLs for a sitemap index."),
 * )
 */
class SitemapIndexUrlGenerator extends UrlGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function getDataSets(): array {
    return \Drupal::entityTypeManager()
      ->getStorage('simple_sitemap')
      ->getQuery()
      ->sort('weight')
      ->accessCheck(FALSE)
      ->execute();
  }

  /**
   * {@inheritdoc}
   *
   * @todo May need to implement a way of saving which sitemaps to index with
   * each sitemap index. Right now all sitemaps that are not of a type that
   * implements the sitemap index generator are indexed.
   */
  protected function processDataSet($data_set): array {
    if (($sitemap = SimpleSitemap::load($data_set))
      && $sitemap->status()
      && $sitemap->getType()->getSitemapGenerator()->getPluginId() !== 'index') {
      $url_object = $sitemap->toUrl()->setAbsolute();

      return [
        'url' => $url_object->toString(),
        'lastmod' => date('c', $sitemap->fromPublished()->getCreated()),

        // Additional info useful in hooks.
        'meta' => [
          'path' => $url_object->getInternalPath(),
          'entity_info' => [
            'entity_type' => $sitemap->getEntityTypeId(),
            'id' => $sitemap->id(),
          ],
        ],
      ];
    }

    throw new SkipElementException();
  }

}
