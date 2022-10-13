<?php

namespace Drupal\simple_sitemap\Manager;

use Drupal\Core\Lock\LockBackendInterface;
use Drupal\simple_sitemap\Entity\SimpleSitemap;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Queue\QueueWorker;
use Drupal\simple_sitemap\Settings;

/**
 * Main managing service.
 *
 * Capable of setting/loading module settings, queuing elements and generating
 * the sitemap. Services for custom link and entity link generation can be
 * fetched from this service as well.
 */
class Generator {

  use VariantSetterTrait;

  /**
   * The simple_sitemap.settings service.
   *
   * @var \Drupal\simple_sitemap\Settings
   */
  protected $settings;

  /**
   * The simple_sitemap.queue_worker service.
   *
   * @var \Drupal\simple_sitemap\Queue\QueueWorker
   */
  protected $queueWorker;

  /**
   * The lock backend that should be used.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * Simple XML Sitemap logger.
   *
   * @var \Drupal\simple_sitemap\Logger
   */
  protected $logger;

  /**
   * Simplesitemap constructor.
   *
   * @param \Drupal\simple_sitemap\Settings $settings
   *   The simple_sitemap.settings service.
   * @param \Drupal\simple_sitemap\Queue\QueueWorker $queue_worker
   *   The simple_sitemap.queue_worker service.
   * @param \Drupal\Core\Lock\LockBackendInterface|null $lock
   *   The lock backend that should be used.
   * @param \Drupal\simple_sitemap\Logger|null $logger
   *   Simple XML Sitemap logger.
   */
  public function __construct(
    Settings $settings,
    QueueWorker $queue_worker,
    LockBackendInterface $lock = NULL,
    Logger $logger = NULL
  ) {
    $this->settings = $settings;
    $this->queueWorker = $queue_worker;
    $this->lock = $lock;
    $this->logger = $logger;
  }

  /**
   * Returns a specific setting or a default value if setting does not exist.
   *
   * @param string $name
   *   Name of the setting, like 'max_links'.
   * @param mixed $default
   *   Value to be returned if the setting does not exist in the configuration.
   *
   * @return mixed
   *   The current setting from configuration or a default value.
   */
  public function getSetting(string $name, $default = NULL) {
    return $this->settings->get($name, $default);
  }

  /**
   * Stores a specific sitemap setting in configuration.
   *
   * @param string $name
   *   Setting name, like 'max_links'.
   * @param mixed $setting
   *   The setting to be saved.
   *
   * @return $this
   */
  public function saveSetting(string $name, $setting): Generator {
    $this->settings->save($name, $setting);

    return $this;
  }

  /**
   * Gets the default variant from the currently set variants.
   *
   * @return string|null
   *   The default variant or NULL if there are no variants.
   */
  public function getDefaultVariant(): ?string {
    if (empty($variants = $this->getVariants())) {
      return NULL;
    }

    if (count($variants) > 1) {
      $variant = $this->getSetting('default_variant');

      if ($variant && in_array($variant, $variants)) {
        return $variant;
      }
    }

    return reset($variants);
  }

  /**
   * Returns a sitemap variant, its index, or its requested chunk.
   *
   * @param int|null $delta
   *   Optional delta of the chunk.
   *
   * @return string|null
   *   If no chunk delta is provided, either the sitemap string is returned,
   *   or its index string in case of a chunked sitemap.
   *   If a chunk delta is provided, the relevant chunk string is returned.
   *   Returns null if the content is not retrievable from the database.
   */
  public function getContent(?int $delta = NULL): ?string {
    $variant = $this->getDefaultVariant();

    /** @var \Drupal\simple_sitemap\Entity\SimpleSitemapInterface $sitemap */
    if ($variant && ($sitemap = SimpleSitemap::load($variant)) && $sitemap->isEnabled()
      && ($sitemap_string = $sitemap->fromPublished()->toString($delta))) {
      return $sitemap_string;
    }

    return NULL;
  }

  /**
   * Generates all sitemaps.
   *
   * @param string $from
   *   Can be 'form', 'drush', 'cron' and 'backend'.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function generate(string $from = QueueWorker::GENERATE_TYPE_FORM): Generator {
    if (!$this->lock->lockMayBeAvailable(QueueWorker::LOCK_ID)) {
      $this->logger->m('Unable to acquire a lock for sitemap generation.')->log('error')->display('error');
      return $this;
    }
    switch ($from) {
      case QueueWorker::GENERATE_TYPE_FORM:
      case QueueWorker::GENERATE_TYPE_DRUSH;
        $this->queueWorker->batchGenerate($from);
        break;

      case QueueWorker::GENERATE_TYPE_CRON:
      case QueueWorker::GENERATE_TYPE_BACKEND:
        $this->queueWorker->generate($from);
        break;
    }

    return $this;
  }

  /**
   * Queues links from currently set variants.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function queue(): Generator {
    $this->queueWorker->queue($this->getVariants());

    return $this;
  }

  /**
   * Deletes the queue and queues links from currently set variants.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function rebuildQueue(): Generator {
    if (!$this->lock->lockMayBeAvailable(QueueWorker::LOCK_ID)) {
      $this->logger->m('Unable to acquire a lock for sitemap generation.')->log('error')->display('error');
      return $this;
    }
    $this->queueWorker->rebuildQueue($this->getVariants());

    return $this;
  }

  /**
   * Gets the simple_sitemap.entity_manager service.
   *
   * @return \Drupal\simple_sitemap\Manager\EntityManager
   *   The simple_sitemap.entity_manager service.
   */
  public function entityManager(): EntityManager {
    /** @var \Drupal\simple_sitemap\Manager\EntityManager $entities */
    $entities = \Drupal::service('simple_sitemap.entity_manager');

    return $entities->setVariants($this->getVariants());
  }

  /**
   * Gets the simple_sitemap.custom_link_manager service.
   *
   * @return \Drupal\simple_sitemap\Manager\CustomLinkManager
   *   The simple_sitemap.custom_link_manager service.
   */
  public function customLinkManager(): CustomLinkManager {
    /** @var \Drupal\simple_sitemap\Manager\CustomLinkManager $custom_links */
    $custom_links = \Drupal::service('simple_sitemap.custom_link_manager');

    return $custom_links->setVariants($this->getVariants());
  }

}
