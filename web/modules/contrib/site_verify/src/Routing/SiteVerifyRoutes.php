<?php

namespace Drupal\site_verify\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes.
 */
class SiteVerifyRoutes {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $verifications = \Drupal::database()->select('site_verify', 'site_verify')
      ->fields('site_verify', ['svid', 'file'])
      ->condition('file', '', '<>')
      ->execute()
      ->fetchAll();
    $route_collection = new RouteCollection();
    foreach ($verifications as $verification) {
      $route = new Route(
        $verification->file, [
          '_controller' => '\Drupal\site_verify\Controller\SiteVerifyController::verificationsFileContent',
          'svid' => $verification->svid,
        ],
        ['_access' => 'TRUE']
      );
      $route_collection->add('site_verify.' . $verification->file, $route);
    }
    return $route_collection;
  }

}
