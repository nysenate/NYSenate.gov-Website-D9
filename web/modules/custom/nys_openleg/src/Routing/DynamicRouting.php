<?php

namespace Drupal\nys_openleg\Routing;

use Drupal\Core\Config\Config;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\nys_openleg\StatuteHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides for dynamic route generation for Openleg.
 */
class DynamicRouting implements ContainerInjectionInterface {

  /**
   * Drupal config factory.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * Constructor.
   */
  public function __construct(Config $config) {
    $this->config = $config;
  }

  /**
   * Injects the request and config objects into construction.
   *
   * @return static
   */
  public static function create(ContainerInterface $container): DynamicRouting {
    return new static(
      $container->get('config.factory')->getEditable('nys_openleg_api.settings')
    );
  }

  /**
   * Generates dynamic routes.
   */
  public function routes(): RouteCollection {
    $controller = 'Drupal\\nys_openleg\\Controller\\MainController::';
    $title = 'NYS Open Legislation';
    $path = $this->resolvePath();
    $permit = ['_permission' => 'access content'];

    $routes = [
      'top' => [],
      'book' => ['vars' => ['book' => 'all']],
      'location' => ['vars' => ['book' => 'all', 'location' => '']],
      'search' => [
        'path' => '/search',
        'title' => 'Search',
        'method' => 'search',
        'vars' => ['search_term' => ''],
      ],
    ];

    $route_collection = new RouteCollection();

    foreach ($routes as $key => $val) {
      $this_path = $path . ($val['path'] ?? '');
      $this_title = $val['title'] ?? '';
      $this_title = $title . ($this_title ? ': ' . $this_title : '');
      $this_method = $val['method'] ?? 'browse';
      $defaults = [
        '_controller' => $controller . $this_method,
        '_title' => $this_title,
      ];
      foreach (($val['vars'] ?? []) as $var_key => $var_val) {
        $defaults[$var_key] = $var_val;
        $this_path .= '/{' . $var_key . '}';
      }

      $route = new Route($this_path, $defaults, $permit, ['no_cache' => 'TRUE']);
      $route_collection->add('nys_openleg.' . $key, $route);
    }

    return $route_collection;
  }

  /**
   * Ensures a base path is always available in config.
   *
   * @return string
   *   The path to the Openleg base page.
   */
  protected function resolvePath(): string {
    $ret = $this->config->get('base_path') ?: StatuteHelper::DEFAULT_LANDING_URL;
    $this->config->set('base_path', $ret)->save();
    return $ret;
  }

}
