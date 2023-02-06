<?php

namespace Drupal\simple_sitemap\Commands;

use Drupal\simple_sitemap\Entity\SimpleSitemap;
use Drupal\simple_sitemap\Queue\QueueWorker;
use Drupal\simple_sitemap\Manager\Generator;
use Drush\Commands\DrushCommands;

/**
 * Provides Drush commands for managing sitemaps.
 */
class SimpleSitemapCommands extends DrushCommands {

  /**
   * The simple_sitemap.generator service.
   *
   * @var \Drupal\simple_sitemap\Manager\Generator
   */
  protected $generator;

  /**
   * SimplesitemapCommands constructor.
   *
   * @param \Drupal\simple_sitemap\Manager\Generator $generator
   *   The simple_sitemap.generator service.
   */
  public function __construct(Generator $generator) {
    $this->generator = $generator;

    parent::__construct();
  }

  /**
   * Regenerate all sitemaps or continue generation.
   *
   * @command simple-sitemap:generate
   *
   * @usage drush simple-sitemap:generate
   *   Regenerate all sitemaps or continue generation.
   *
   * @validate-module-enabled simple_sitemap
   *
   * @aliases ssg, simple-sitemap-generate
   */
  public function generate(): void {
    $this->generator->generate(QueueWorker::GENERATE_TYPE_DRUSH);
  }

  /**
   * Queue all or specific sitemaps for regeneration.
   *
   * @param array $options
   *   The command options.
   *
   * @command simple-sitemap:rebuild-queue
   *
   * @option variants
   *   Queue all or specific sitemaps for regeneration.
   *
   * @usage drush simple-sitemap:rebuild-queue
   *   Rebuild the sitemap queue for all sitemaps.
   * @usage drush simple-sitemap:rebuild-queue --variants=default,test
   *   Rebuild the sitemap queue queuing only sitemaps 'default' and 'test'.
   *
   * @validate-module-enabled simple_sitemap
   *
   * @aliases ssr, simple-sitemap-rebuild-queue
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function rebuildQueue(array $options = ['variants' => '']): void {
    // @todo No need to load all sitemaps here.
    $variants = array_keys(SimpleSitemap::loadMultiple());
    if (isset($options['variants']) && (string) $options['variants'] !== '') {
      $chosen_variants = array_map('trim', array_filter(explode(',', (string) $options['variants'])));
      if (!empty($erroneous_variants = array_diff($chosen_variants, $variants))) {
        $message = 'The following variants do not exist: ' . implode(', ', $erroneous_variants)
          . '. Available variants are: ' . implode(', ', $variants) . '.';
        $this->logger()->log('error', $message);
        return;
      }
      $variants = $chosen_variants;
    }

    $this->generator->setVariants($variants)->rebuildQueue();

    $this->logger()->log('notice', 'The following variants have been queued for regeneration: ' . implode(', ', $variants) . '.');
  }

}
