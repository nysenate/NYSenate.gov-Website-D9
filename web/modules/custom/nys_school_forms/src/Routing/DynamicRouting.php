<?php

namespace Drupal\nys_school_forms\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class Dynamic Routing.
 *
 * @package Drupal\nys_school_forms\Routing
 */
class DynamicRouting {

  /**
   * Generates dynamic routes.
   */
  public function routes() {
    $collection = new RouteCollection();
    $school_form_types = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'school_form_type']);
    foreach ($school_form_types as $term) {
      $term_name = $term->getName();
      $alias = str_replace([' ', '-', '\''], '_', strtolower($term_name));
      $route = new Route(
            '/admin/school-forms/' . $alias,
            [
              '_title' => 'School Form Search - ' . $term_name,
              '_controller' => '\Drupal\nys_school_forms\Controller\SchoolFormsController::view',
              'form_type' => $term_name,
            ],
            [
              '_permission' => 'access all webform results',
            ]
        );
      $route->setOption('_admin_route', TRUE);
      $collection->add("nys_school_forms.school_forms.$alias", $route);
    }
    return $collection;
  }

}
