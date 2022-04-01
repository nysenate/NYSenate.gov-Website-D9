<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Url;
use Drupal\simple_sitemap\Annotation\UrlGenerator;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CustomUrlGenerator
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 *
 * @UrlGenerator(
 *   id = "custom",
 *   label = @Translation("Custom URL generator"),
 *   description = @Translation("Generates URLs set in admin/config/search/simplesitemap/custom."),
 * )
 *
 */
class CustomUrlGenerator extends EntityUrlGeneratorBase {

  const PATH_DOES_NOT_EXIST_MESSAGE = 'The custom path @path has been omitted from the XML sitemaps as it does not exist. You can review custom paths <a href="@custom_paths_url">here</a>.';


  /**
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * @var bool
   */
  protected $includeImages;

  /**
   * CustomUrlGenerator constructor.
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   * @param \Drupal\simple_sitemap\Logger $logger
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\simple_sitemap\EntityHelper $entityHelper
   * @param \Drupal\Core\Path\PathValidator $path_validator
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Simplesitemap $generator,
    Logger $logger,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    EntityHelper $entityHelper,
    PathValidator $path_validator) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $generator,
      $logger,
      $language_manager,
      $entity_type_manager,
      $entityHelper
    );
    $this->pathValidator = $path_validator;
  }

  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.logger'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('simple_sitemap.entity_helper'),
      $container->get('path.validator')
    );
  }

  /**
   * @inheritdoc
   */
  public function getDataSets() {
    $this->includeImages = $this->generator->getSetting('custom_links_include_images', FALSE);

    return array_values($this->generator->setVariants($this->sitemapVariant)->getCustomLinks());
  }

  /**
   * @inheritdoc
   */
  protected function processDataSet($data_set) {
    if (!(bool) $this->pathValidator->getUrlIfValidWithoutAccessCheck($data_set['path'])) {
      $this->logger->m(self::PATH_DOES_NOT_EXIST_MESSAGE,
        ['@path' => $data_set['path'], '@custom_paths_url' => $GLOBALS['base_url'] . '/admin/config/search/simplesitemap/custom'])
        ->display('warning', 'administer sitemap settings')
        ->log('warning');

      return FALSE;
    }

    $url_object = Url::fromUserInput($data_set['path'])->setAbsolute();
    $path = $url_object->getInternalPath();

    $entity = $this->entityHelper->getEntityFromUrlObject($url_object);

    $path_data = [
      'url' => $url_object,
      'lastmod' => !empty($entity) && method_exists($entity, 'getChangedTime')
        ? date('c', $entity->getChangedTime())
        : NULL,
      'priority' => isset($data_set['priority']) ? $data_set['priority'] : NULL,
      'changefreq' => !empty($data_set['changefreq']) ? $data_set['changefreq'] : NULL,
      'images' => $this->includeImages && !empty($entity)
        ? $this->getEntityImageData($entity)
        : [],
      'meta' => [
        'path' => $path,
      ]
    ];

    // Additional info useful in hooks.
    if (!empty($entity)) {
      $path_data['meta']['entity_info'] = [
        'entity_type' => $entity->getEntityTypeId(),
        'id' => $entity->id(),
      ];
    }

    return $path_data;
  }
}
