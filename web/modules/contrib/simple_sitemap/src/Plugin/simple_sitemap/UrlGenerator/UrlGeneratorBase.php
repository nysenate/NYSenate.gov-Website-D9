<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\simple_sitemap\Plugin\simple_sitemap\SimplesitemapPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Simplesitemap;

/**
 * Class UrlGeneratorBase
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 */
abstract class UrlGeneratorBase extends SimplesitemapPluginBase implements UrlGeneratorInterface {

  /**
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  protected $generator;

  /**
   * @var \Drupal\simple_sitemap\Logger
   */
  protected $logger;

  /**
   * @var array
   */
  protected $settings;

  /**
   * @var string
   */
  protected $sitemapVariant;

  /**
   * UrlGeneratorBase constructor.
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   * @param \Drupal\simple_sitemap\Logger $logger
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Simplesitemap $generator,
    Logger $logger
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->generator = $generator;
    $this->logger = $logger;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.logger')
    );
  }

  /**
   * @param array $settings
   * @return $this
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;

    return $this;
  }

  /**
   * @param string $sitemap_variant
   * @return $this
   */
  public function setSitemapVariant($sitemap_variant) {
    $this->sitemapVariant = $sitemap_variant;

    return $this;
  }

  /**
   * @param string $url
   * @return string
   */
  protected function replaceBaseUrlWithCustom($url) {
    return !empty($this->settings['base_url'])
      ? str_replace($GLOBALS['base_url'], $this->settings['base_url'], $url)
      : $url;
  }

  /**
   * @return mixed
   */
  abstract public function getDataSets();

  /**
   * @param $data_set
   * @return mixed
   */
  abstract protected function processDataSet($data_set);

  /**
   * @param $data_set
   * @return array
   */
  public function generate($data_set) {
    $path_data = $this->processDataSet($data_set);

    return FALSE !== $path_data ? [$path_data] : [];
  }
}
