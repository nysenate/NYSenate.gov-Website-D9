<?php

namespace Drupal\simple_sitemap_engines\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\simple_sitemap\Logger;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Process a queue of search engines to submit sitemaps.
 *
 * @QueueWorker(
 *   id = "simple_sitemap_engine_submit",
 *   title = @Translation("Sitemap search engine submission"),
 *   cron = {"time" = 30}
 * )
 *
 * @see simple_sitemap_engines_cron()
 */
class SitemapSubmitter extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The EntityStorage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $engineStorage;

  /**
   * The HTTP Client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The sitemap generator service.
   *
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  protected $generator;

  /**
   * The Drupal logger service.
   *
   * @var \Drupal\simple_sitemap\Logger
   */
  protected $logger;

  /**
   * The Drupal state service.
   *
   * @var \Drupal\workflows\StateInterface
   */
  protected $state;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * SitemapSubmitter constructor.
   *
   * @param array $configuration
   *   The config.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $engine_storage
   *   The EntityStorageInterface.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The client used to submit to engines.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   *   The generator service.
   * @param \Drupal\simple_sitemap\Logger $logger
   *   Standard logger.
   * @param \Drupal\Core\State\StateInterface $state
   *   Drupal state service for last submitted.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              array $plugin_definition,
                              EntityStorageInterface $engine_storage,
                              ClientInterface $http_client,
                              Simplesitemap $generator,
                              Logger $logger,
                              StateInterface $state,
                              TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->engineStorage = $engine_storage;
    $this->httpClient = $http_client;
    $this->generator = $generator;
    $this->logger = $logger;
    $this->state = $state;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('simple_sitemap_engine'),
      $container->get('http_client'),
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.logger'),
      $container->get('state'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($engine_id) {
    /** @var \Drupal\simple_sitemap_engines\Entity\SearchEngine $engine */
    if ($engine = $this->engineStorage->load($engine_id)) {

      $sitemap_urls = [];
      $manager = $this->generator->getSitemapManager();

      foreach ($manager->getSitemapTypes() as $type_name => $type_definition) {
        $sitemap_generator = $manager->getSitemapGenerator($type_definition['sitemapGenerator']);

        // Submit all variants that are enabled for this search engine.
        foreach ($manager->getSitemapVariants($type_name, FALSE) as $variant_id => $variant_definition) {
          if (in_array($variant_id, $engine->sitemap_variants)
            && FALSE !== $this->generator->setVariants($variant_id)->getSitemap()) {
            $sitemap_urls[$variant_definition['label']] = $sitemap_generator->setSitemapVariant($variant_id)->getSitemapUrl();
          }
        }
      }

      // Submit all URLs.
      foreach ($sitemap_urls as $variant => $sitemap_url) {
        $submit_url = str_replace('[sitemap]', $sitemap_url, $engine->url);
        try {
          $this->httpClient->request('GET', $submit_url);
          // Log if submission was successful.
          $this->logger->m('Sitemap @variant submitted to @url', ['@variant' => $variant, '@url' => $submit_url])->log();
          // Record last submission time. This is purely informational; the
          // variable that determines when the next submission should be run is
          // stored in the global state.
          $this->state->set("simple_sitemap_engines.simple_sitemap_engine.{$engine_id}.last_submitted", $this->time->getRequestTime());
        }
        catch (RequestException $e) {
          // Catch and log exceptions so this submission gets removed from the
          // queue whether or not it succeeded.
          // If the error was caused by network failure, it's fine to just wait
          // until next time the submission is queued to try again.
          // If the error was caused by a malformed URL, keeping the submission
          // in the queue to retry is pointless since it will always fail.
          watchdog_exception('simple_sitemap', $e);
        }
      }
    }
  }

}
