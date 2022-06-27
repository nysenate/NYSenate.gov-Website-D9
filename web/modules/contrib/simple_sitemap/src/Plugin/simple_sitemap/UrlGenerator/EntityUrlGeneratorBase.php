<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Language\LanguageInterface;
use Drupal\simple_sitemap\Entity\EntityHelper;
use Drupal\simple_sitemap\Exception\SkipElementException;
use Drupal\simple_sitemap\Plugin\simple_sitemap\SimpleSitemapPluginBase;
use Drupal\simple_sitemap\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\simple_sitemap\Logger;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Provides a base class for entity UrlGenerator plugins.
 */
abstract class EntityUrlGeneratorBase extends UrlGeneratorBase {

  /**
   * Local cache for the available language objects.
   *
   * @var \Drupal\Core\Language\LanguageInterface[]
   */
  protected $languages;

  /**
   * Default language ID.
   *
   * @var string
   */
  protected $defaultLanguageId;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * An account implementation representing an anonymous user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $anonUser;

  /**
   * Helper class for working with entities.
   *
   * @var \Drupal\simple_sitemap\Entity\EntityHelper
   */
  protected $entityHelper;

  /**
   * EntityUrlGeneratorBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\simple_sitemap\Logger $logger
   *   Simple XML Sitemap logger.
   * @param \Drupal\simple_sitemap\Settings $settings
   *   The simple_sitemap.settings service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\simple_sitemap\Entity\EntityHelper $entity_helper
   *   Helper class for working with entities.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Logger $logger,
    Settings $settings,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    EntityHelper $entity_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $settings);
    $this->languages = $language_manager->getLanguages();
    $this->defaultLanguageId = $language_manager->getDefaultLanguage()->getId();
    $this->entityTypeManager = $entity_type_manager;
    $this->anonUser = new AnonymousUserSession();
    $this->entityHelper = $entity_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): SimpleSitemapPluginBase {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap.logger'),
      $container->get('simple_sitemap.settings'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('simple_sitemap.entity_helper')
    );
  }

  /**
   * Gets the URL variants.
   *
   * @param array $path_data
   *   The path data.
   * @param \Drupal\Core\Url $url_object
   *   The URL object.
   *
   * @return array
   *   The URL variants.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getUrlVariants(array $path_data, Url $url_object): array {
    $url_variants = [];

    if (!$this->sitemap->isMultilingual() || !$url_object->isRouted()) {

      // Not a routed URL or URL language negotiation disabled: Including only
      // default variant.
      $alternate_urls = $this->getAlternateUrlsForDefaultLanguage($url_object);
    }
    elseif ($this->settings->get('skip_untranslated')
      && ($entity = $this->entityHelper->getEntityFromUrlObject($url_object)) instanceof ContentEntityInterface) {

      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $translation_languages = $entity->getTranslationLanguages();
      if (isset($translation_languages[LanguageInterface::LANGCODE_NOT_SPECIFIED])
        || isset($translation_languages[LanguageInterface::LANGCODE_NOT_APPLICABLE])) {

        // Content entity's language is unknown: Including only default variant.
        $alternate_urls = $this->getAlternateUrlsForDefaultLanguage($url_object);
      }
      else {
        // Including only translated variants of content entity.
        $alternate_urls = $this->getAlternateUrlsForTranslatedLanguages($entity, $url_object);
      }
    }
    else {
      // Not a content entity or including all untranslated variants.
      $alternate_urls = $this->getAlternateUrlsForAllLanguages($url_object);
    }

    foreach ($alternate_urls as $langcode => $url) {
      $url_variants[] = $path_data + [
        'langcode' => $langcode,
        'url' => $url,
        'alternate_urls' => $alternate_urls,
      ];
    }

    return $url_variants;
  }

  /**
   * Gets the alternate URLs for default language.
   *
   * @param \Drupal\Core\Url $url_object
   *   The URL object.
   *
   * @return array
   *   An array of alternate URLs.
   */
  protected function getAlternateUrlsForDefaultLanguage(Url $url_object): array {
    $alternate_urls = [];
    if ($url_object->access($this->anonUser)) {
      $alternate_urls[$this->defaultLanguageId] = $this->replaceBaseUrlWithCustom($url_object
        ->setAbsolute()->setOption('language', $this->languages[$this->defaultLanguageId])->toString()
      );
    }

    return $alternate_urls;
  }

  /**
   * Gets the alternate URLs for translated languages.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to process.
   * @param \Drupal\Core\Url $url_object
   *   The URL object.
   *
   * @return array
   *   An array of alternate URLs.
   */
  protected function getAlternateUrlsForTranslatedLanguages(ContentEntityInterface $entity, Url $url_object): array {
    $alternate_urls = [];

    foreach ($entity->getTranslationLanguages() as $language) {
      if (!isset($this->settings->get('excluded_languages')[$language->getId()]) || $language->isDefault()) {
        if ($entity->getTranslation($language->getId())->access('view', $this->anonUser)) {
          $alternate_urls[$language->getId()] = $this->replaceBaseUrlWithCustom($url_object
            ->setAbsolute()->setOption('language', $language)->toString()
          );
        }
      }
    }

    return $alternate_urls;
  }

  /**
   * Gets the alternate URLs for all languages.
   *
   * @param \Drupal\Core\Url $url_object
   *   The URL object.
   *
   * @return array
   *   An array of alternate URLs.
   */
  protected function getAlternateUrlsForAllLanguages(Url $url_object): array {
    $alternate_urls = [];
    if ($url_object->access($this->anonUser)) {
      foreach ($this->languages as $language) {
        if (!isset($this->settings->get('excluded_languages')[$language->getId()]) || $language->isDefault()) {
          $alternate_urls[$language->getId()] = $this->replaceBaseUrlWithCustom($url_object
            ->setAbsolute()->setOption('language', $language)->toString()
          );
        }
      }
    }

    return $alternate_urls;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function generate($data_set): array {
    try {
      $path_data = $this->processDataSet($data_set);
      if (isset($path_data['url']) && $path_data['url'] instanceof Url) {
        $url_object = $path_data['url'];
        unset($path_data['url']);
        return $this->getUrlVariants($path_data, $url_object);
      }
      return [$path_data];
    }
    catch (SkipElementException $e) {
      return [];
    }
  }

  /**
   * Gets the image data for specified entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to process.
   *
   * @return array
   *   The image data.
   */
  protected function getEntityImageData(ContentEntityInterface $entity): array {
    $image_data = [];

    /** @var FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');

    foreach ($entity->getFieldDefinitions() as $field) {
      if ($field->getType() === 'image') {
        foreach ($entity->get($field->getName())->getValue() as $value) {
          if (NULL !== ($file = File::load($value['target_id']))) {
            $image_data[] = [
              'path' => $this->replaceBaseUrlWithCustom(
                $file_url_generator->generateAbsoluteString($file->getFileUri())
              ),
              'alt' => $value['alt'],
              'title' => $value['title'],
            ];
          }
        }
      }
    }

    return $image_data;
  }

}
