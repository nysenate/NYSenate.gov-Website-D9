<?php

namespace Drupal\components\Template\Loader;

use Drupal\components\Template\ComponentsRegistry;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

/**
 * Loads namespaced templates from the filesystem.
 *
 * This loader adds module and theme defined namespaces to the Twig filesystem
 * loader so that templates can be referenced by namespace, like
 * \@mycomponents/box.html.twig or \@mythemeComponents/page.html.twig.
 */
class ComponentsLoader extends FilesystemLoader {

  /**
   * The components registry service.
   *
   * @var \Drupal\components\Template\ComponentsRegistry
   */
  protected $componentsRegistry;

  /**
   * Constructs a new ComponentsLoader object.
   *
   * @param \Drupal\components\Template\ComponentsRegistry $componentsRegistry
   *   The components registry service.
   */
  public function __construct(ComponentsRegistry $componentsRegistry) {
    parent::__construct();

    $this->componentsRegistry = $componentsRegistry;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Twig\Error\LoaderError
   *   Thrown if a template matching $name cannot be found.
   */
  protected function findTemplate($name, $throw = TRUE) {
    // Validate the given template.
    $extension = substr($name, strrpos($name, '.', -1));
    if ($name[0] !== '@' || !str_contains(substr($name, 2), '/') || $extension !== '.twig' && $extension !== '.html' && $extension !== '.svg') {
      if (!$throw) {
        return NULL;
      }

      throw new LoaderError(sprintf('Malformed namespaced template name "%s" (expecting "@namespace/template_name.twig").', $name));
    }
    else {
      // componentsRegistry::getTemplate() returns a string or NULL, exactly
      // what componentsLoader::findTemplate() should return.
      $path = $this->componentsRegistry->getTemplate($name);

      if ($path || !$throw) {
        return $path;
      }

      throw new LoaderError(sprintf('Unable to find template "%s" in the components registry.', $name));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name): bool {
    return (bool) $this->componentsRegistry->getTemplate($name);
  }

}
