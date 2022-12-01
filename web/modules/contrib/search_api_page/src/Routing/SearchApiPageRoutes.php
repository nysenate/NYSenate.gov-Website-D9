<?php

namespace Drupal\search_api_page\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\search_api_page\Controller\SearchApiPageController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a route subscriber to register a url for serving search pages.
 */
class SearchApiPageRoutes implements ContainerInjectionInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new SearchApiRoutes object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function routes() {
    $routes = [];

    $is_multilingual = $this->languageManager->isMultilingual();

    /** @var \Drupal\search_api_page\SearchApiPageInterface $search_api_page */
    foreach ($this->entityTypeManager->getStorage('search_api_page')->loadMultiple() as $search_api_page) {

      // Default path.
      $default_path = $search_api_page->getPath();

      // Loop over all languages so we can get the translated path (if any).
      foreach ($this->languageManager->getLanguages() as $language) {

        // Check if we are multilingual or not.
        $path = $default_path;
        if ($is_multilingual) {
          $pathOverride = $this->languageManager
            ->getLanguageConfigOverride($language->getId(), 'search_api_page.search_api_page.' . $search_api_page->id())
            ->get('path');

          if (!empty($pathOverride)) {
            $path = $pathOverride;
          }
        }

        $defaultArgs = [
          '_controller' => SearchApiPageController::class . '::page',
          '_title_callback' => SearchApiPageController::class . '::title',
          'search_api_page_name' => $search_api_page->id(),
        ];

        // Use clean urls or not.
        if ($search_api_page->getCleanUrl()) {
          $path .= '/{keys}';
          $defaultArgs['keys'] = '';
        }

        $routeName = 'search_api_page.' . $language->getId() . '.' . $search_api_page->id();
        $routeRequirements = ['_permission' => 'view search api pages', 'keys' => '.*'];
        $routes[$routeName] = new Route($path, $defaultArgs, $routeRequirements);
      }
    }

    return $routes;
  }

}
