<?php

namespace Drupal\webform_views\Plugin\Derivative;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions webform submission views.
 *
 * With this derivative it's possible to set up views on paths like:
 * - /webform/webform-id/my-submission-view
 * - /webform/webform-another-id/my-submission-view
 *
 * Therefore you can have 2 different views for 2 different webforms on the same
 * route. Standard views local task derivative would allow you to set up only
 * one view on the path /webform/%webform/my-submission-view.
 */
class ViewsLocalTask extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The view storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $viewStorage;

  /**
   * Local task manager service.
   *
   * @var \Drupal\Core\Menu\LocalTaskManagerInterface
   */
  protected $localTaskManager;

  /**
   * Constructs a \Drupal\views\Plugin\Derivative\ViewsLocalTask instance.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param \Drupal\Core\Entity\EntityStorageInterface $view_storage
   *   The view storage.
   * @param \Drupal\Core\Menu\LocalTaskManagerInterface $local_task_manager
   *   Local task manager service.
   */
  public function __construct(RouteProviderInterface $route_provider, StateInterface $state, EntityStorageInterface $view_storage, LocalTaskManagerInterface $local_task_manager) {
    $this->routeProvider = $route_provider;
    $this->state = $state;
    $this->viewStorage = $view_storage;
    $this->localTaskManager = $local_task_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('state'),
      $container->get('entity_type.manager')->getStorage('view'),
      $container->get('plugin.manager.menu.local_task')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    static $recursion = 0;

    $this->derivatives = [];

    $view_route_names = $this->state->get('views.view_route_names');
    foreach (webform_views_applicable_views() as $trio) {
      list($view_id, $display_id, $path) = $trio;

      /** @var $executable \Drupal\views\ViewExecutable */
      $executable = $this->viewStorage->load($view_id)->getExecutable();

      $executable->setDisplay($display_id);
      $menu = $executable->display_handler->getOption('menu');

      $plugin_id = 'view.' . $executable->storage->id() . '.' . $display_id;
      $route_name = $view_route_names[$executable->storage->id() . '.' . $display_id];

      $parent_path = explode('/', $path);
      array_pop($parent_path);
      $parent_path = implode('/', $parent_path);
      $pattern = '/' . $parent_path;

      if ($routes = $this->routeProvider->getRoutesByPattern($pattern)) {
        foreach ($routes->all() as $name => $route) {
          // Array reverse is here because we prefer the lower level tasks over
          // higher level ones.
          foreach (array_reverse($this->localTaskManager->getLocalTasksForRoute($name)) as $leveled_local_tasks) {
            foreach ($leveled_local_tasks as $local_task) {
              /** @var \Drupal\Core\Menu\LocalTaskInterface $local_task */
              if ($local_task->getRouteName() == $name) {
                $definition = [
                    'route_name' => $route_name,
                    'weight' => $menu['weight'],
                    'title' => $menu['title'],
                  ] + $base_plugin_definition;
                if ($local_task->getPluginDefinition()['parent_id']) {
                  $definition['parent_id'] = $local_task->getPluginDefinition()['parent_id'];
                }
                else {
                  $definition['base_route'] = $local_task->getPluginDefinition()['base_route'];
                }

                $this->derivatives['webform_views:' . $plugin_id] = $definition;

                // Skip after the first found route.
                break(3);
              }
            }
          }
        }

        $recursion++;

        if ($recursion == 1 && isset($name) && !isset($this->derivatives['webform_views:' . $plugin_id])) {
          // As a last resort try to look up a local task whose ID equals the
          // route name because most of local tasks copy-paste their IDs after
          // the route they represent.
          $parent_task = $this->localTaskManager->getDefinition($name, FALSE);
          if ($parent_task) {
            $this->derivatives['webform_views:' . $plugin_id] = [
                'route_name' => $route_name,
                'weight' => $menu['weight'],
                'title' => $menu['title'],
                'parent_id' => $parent_task['parent_id'] ?: $parent_task['id'],
              ] + $base_plugin_definition;
          }
          else {
            $this->derivatives['webform_views:' . $plugin_id] = [
                'route_name' => $route_name,
                'weight' => $menu['weight'],
                'title' => $menu['title'],
                'base_route' => $name,
              ] + $base_plugin_definition;
          }
        }
      }
    }
    return $this->derivatives;
  }

  /**
   * Alters the webform views local tasks.
   */
  public function alterLocalTasks(&$local_tasks) {
    // We want to unset all the "standard" tabs - those generated by
    // Drupal\views\Plugin\Derivative\ViewsLocalTask since we manage menu tabs
    // of webform submission views on our own.
    foreach (webform_views_applicable_views() as $trio) {
      list($view_id, $display_id, $path) = $trio;

      $plugin_id = 'view.' . $view_id . '.' . $display_id;
      unset($local_tasks['views_view:' . $plugin_id]);
    }
  }

}
