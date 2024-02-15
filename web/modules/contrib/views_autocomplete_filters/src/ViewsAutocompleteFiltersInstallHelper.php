<?php

namespace Drupal\views_autocomplete_filters;

use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class ViewsAutocompleteFiltersInstallHelper.
 */
class ViewsAutocompleteFiltersInstallHelper {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Config\CachedStorage definition.
   *
   * @var \Drupal\Core\Config\CachedStorage
   */
  protected CachedStorage $configStorage;

  /**
   * Constructs a new ViewUnpublishedInstallHelper object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Config\CachedStorage $config_storage
   *   The config storage service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CachedStorage $config_storage) {
    $this->configFactory = $config_factory;
    $this->configStorage = $config_storage;
  }

  /**
   * Remove the errant views_autocomplete_filters dependency from Views.
   */
  public function removeDependency() {

    $view_names = $this->configStorage->listAll('views.view');
    foreach ($view_names as $name) {
      $dependencies = $this->configFactory->get($name)->get('dependencies.module');
      if (!empty($dependencies) && array_key_exists('views_autocomplete_filters', array_flip($dependencies))) {
        $dependencies = array_diff($dependencies, ['views_autocomplete_filters']);
        $this->configFactory
          ->getEditable($name)
          ->set('dependencies.module', array_values($dependencies))
          ->save(TRUE);
      }
    }
  }

}
