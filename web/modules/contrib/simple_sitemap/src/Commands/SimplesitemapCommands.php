<?php

namespace Drupal\simple_sitemap\Commands;

use Drupal\simple_sitemap\Queue\QueueWorker;
use Drupal\simple_sitemap\Simplesitemap;
use Drush\Commands\DrushCommands;

/**
 * Class SimplesitemapCommands
 * @package Drupal\simple_sitemap\Commands
 */
class SimplesitemapCommands extends DrushCommands {

  /**
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  protected $generator;

  /**
   * SimplesitemapCommands constructor.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   */
  public function __construct(Simplesitemap $generator) {
    $this->generator = $generator;
  }

  /**
   * Regenerate all XML sitemap variants or continue generation.
   *
   * @command simple-sitemap:generate
   *
   * @usage drush simple-sitemap:generate
   *   Regenerate all XML sitemap variants or continue generation.
   *
   * @validate-module-enabled simple_sitemap
   *
   * @aliases ssg, simple-sitemap-generate
   */
  public function generate() {
    $this->generator->generateSitemap(QueueWorker::GENERATE_TYPE_DRUSH);
  }

  /**
   * Queue all or specific sitemap variants for regeneration.
   *
   * @command simple-sitemap:rebuild-queue
   *
   * @option variants
   *   Queue all or specific sitemap variants for regeneration.
   *
   * @usage drush simple-sitemap:rebuild-queue
   *   Rebuild the sitemap queue for all sitemap variants.
   * @usage drush simple-sitemap:rebuild-queue --variants=default,test
   *   Rebuild the sitemap queue queuing only variants 'default' and 'test'.
   *
   * @validate-module-enabled simple_sitemap
   *
   * @aliases ssr, simple-sitemap-rebuild-queue
   *
   * @param array $options
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function rebuildQueue(array $options = ['variants' => '']) {
    $variants = array_keys($this->generator->getSitemapManager()->getSitemapVariants(NULL, FALSE));
    if (strlen($options['variants']) > 0) {
      $chosen_variants = array_map('trim', array_filter(explode(',', $options['variants'])));
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
