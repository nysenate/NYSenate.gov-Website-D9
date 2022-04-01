<?php

namespace Drupal\views_autocomplete_filters\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns autocomplete responses for taxonomy terms.
 */
class ViewsAutocompleteFiltersController implements ContainerInjectionInterface {

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;


  /**
   * ViewsAutocompleteFiltersController constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')->get('views_autocomplete_filters')
    );
  }

  /**
   * Access for the autocomplete filters path.
   *
   * Determine if the given user has access to the view. Note that
   * this sets the display handler if it hasn't been.
   *
   * @param string $view_name
   *   The View name.
   * @param string $view_display
   *   The View display.
   *
   * @return bool.
   */
  public function access($view_name, $view_display) {
    // Determine if the given user has access to the view. Note that
    // this sets the display handler if it hasn't been.
    $view = Views::getView($view_name);
    if ($view->access($view_display)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }
    /**
   * Retrieves suggestions for taxonomy term autocompletion.
   *
   * This function outputs text suggestions in response to Ajax requests
   * made by the String filter with autocomplete.
   * The output is a JSON object of suggestions found.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $view_name
   *   The View name.
   * @param string $view_display
   *   The View display.
   * @param string $filter_name
   *   The string filter identifier field.
   * @param string $view_args
   *   The View arguments, contextual filters.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
   *   When valid field name is specified, a JSON response containing the
   *   autocomplete suggestions for searched strings. Otherwise a normal response
   *   containing a failure message.
   */
  public function autocomplete(Request $request, $view_name, $view_display, $filter_name, $view_args) {
    $matches = $field_names = [];
    $string = $request->query->get('q');
    // Get view and execute.
    $view = Views::getView($view_name);
    $view->setDisplay($view_display);
    if (!empty($view_args)) {
      $view->setArguments(explode('||', $view_args));
    }
    // Set display and display handler vars for quick access.
    $display_handler = $view->display_handler;
  
    // Force "Display all values" for arguments set,
    // to get results no matter of Not Contextual filter present settings.
    $arguments = $display_handler->getOption('arguments');
    if (!empty($arguments)) {
      foreach ($arguments as $k => $argument) {
        $arguments[$k]['default_action'] = 'ignore';
      }
      $display_handler->setOption('arguments', $arguments);
    }

    // Get exposed filter options for our field.
    // Also, check if filter is exposed and autocomplete is enabled for this
    // filter (and if filter exists/exposed at all).
    $filters = $display_handler->getOption('filters');
    if (empty($filters[$filter_name]['exposed']) || empty($filters[$filter_name]['expose']['autocomplete_filter'])) {
      throw new NotFoundHttpException();
    }
    $current_filter = $filters[$filter_name];
    $expose_options = $current_filter['expose'];

    // Do not filter if the string length is less that minimum characters setting.
    if (strlen(trim($string)) < $expose_options['autocomplete_min_chars']) {
      return new JsonResponse($matches);
    }

    // Determine fields which will be used for output.
    if (empty($expose_options['autocomplete_field']) && !empty($current_filter['name']) ) {
      if ($view->getHandler($view->current_display, 'field', $filters[$filter_name]['id'])) {
        $field_names = [[$filter_name]['id']];
        // Force raw data for no autocomplete field defined.
        $expose_options['autocomplete_raw_suggestion'] = 1;
        $expose_options['autocomplete_raw_dropdown'] = 1;
      }
      else {
        // Field is not set, report about it to watchdog and return empty array.
        $this->logger->error('Field for autocomplete filter %label is not set in view %view, display %display', [
          '%label' => $expose_options['label'],
          '%view' => $view->id(),
          '%display' => $view->current_display,
        ]);
        return new JsonResponse($matches);
      }
    }
    // Text field autocomplete filter.
    elseif (!empty($expose_options['autocomplete_field'])) {
      $field_names =[$expose_options['autocomplete_field']];
    }
    // For combine fields autocomplete filter.
    elseif (!empty($current_filter['fields'])) {
      $field_names = array_keys($current_filter['fields']);
    }

    // Get fields options and check field exists in this display.
    foreach ($field_names as $field_name) {
      $field_options = $view->getHandler($view->current_display, 'field', $field_name);
      if (empty($field_options)) {
        // Field not exists, report about it to watchdog and return empty array.
        $this->logger->error('Field for autocomplete filter %label not exists in view %view, display %display', [
          '%label' => $expose_options['label'],
          '%view' => $view->id(),
          '%display' => $view->current_display,
        ]);
        return new JsonResponse($matches);
      }
    }

    // Collect exposed filter values and set them to the view.
    $view->setExposedInput($this->getExposedInput($view, $request, $expose_options));

    // Disable cache for view, because caching autocomplete is a waste of time and memory.
    $display_handler->setOption('cache', ['type' => 'none']);

    // Force limit for results.
    if (empty($expose_options['autocomplete_items'])) {
      $pager_type = 'none';
    }
    else {
      $pager_type = 'some';
    }
    $pager = [
      'type' => $pager_type,
      'options' => [
        'items_per_page' => $expose_options['autocomplete_items'],
        'offset' => 0,
      ],
    ];
    $display_handler->setOption('pager', $pager);

    // Execute view query.
    $view->preExecute();
    $view->execute();
    $view->postExecute();
    $display_handler = $view->display_handler;
  
    // Render field on each row and fill matches array.
    $use_raw_suggestion = !empty($expose_options['autocomplete_raw_suggestion']);
    $use_raw_dropdown = !empty($expose_options['autocomplete_raw_dropdown']);

    $view->row_index = 0;
    foreach ($view->result as $index => $row) {
      $view->row_index = $index;
      /** @var \Drupal\views\Plugin\views\style\StylePluginBase $style_plugin */
      $style_plugin = $display_handler->getPlugin('style');
  
      foreach ($field_names as $field_name) {
        $rendered_field = $raw_field = '';
        // Render field only if suggestion or dropdown item not in RAW format.
        if (!$use_raw_suggestion || !$use_raw_dropdown) {
          $rendered_field = $style_plugin->getField($index, $field_name);
        }
        // Get the raw field value only if suggestion or dropdown item is in RAW format.
        if ($use_raw_suggestion || $use_raw_dropdown) {
          $raw_field = $style_plugin->getFieldValue($index, $field_name);
          if (!is_array($raw_field)) {
            $raw_field = [['value' => $raw_field]];
          }
          else {
            $raw_field_items = $raw_field;
            $raw_field = [];
            foreach ($raw_field_items as $raw_field_item) {
              $raw_field[] = ['value' => $raw_field_item];
            }
          }
        }
  
        if (empty($raw_field) && !empty($rendered_field)) {
          $raw_field = [['value' => $rendered_field]];
        }
        if (is_array($raw_field)) {
          foreach ($raw_field as $delta => $item) {
            if (isset($item['value']) && strstr(mb_strtolower($item['value']), mb_strtolower($string))) {
              $dropdown = $use_raw_dropdown ? Html::escape($item['value']) : $rendered_field;
              if ($dropdown != '') {
                if ($use_raw_suggestion) {
                  $suggestion = Unicode::truncate(Html::escape($item['value']), 128);
                }
                else {
                  $suggestion = Unicode::truncate(Xss::filter($rendered_field, []), 128);
                }
                $suggestion = Html::decodeEntities($suggestion);

                // Add a class wrapper for a few required CSS overrides.
                $matches[] = [
                  'value' => $suggestion,
                  'label' => $dropdown,
                ];
              }
            }
          }
        }
      }
    }
    unset($view->row_index);

    // @ToDo: No results message
    // Follow https://www.drupal.org/node/2346973 issue when Drupal core will
    // provide a solution for such messages.

    if (!empty($matches)) {
      $matches = array_values(array_unique($matches, SORT_REGULAR));
    }

    return new JsonResponse($matches);
  }

  /**
   * Collect exposed filter values for setting them to the view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param array $expose_options
   *   The options for the exposed filter.
   *
   * @return array|string[]
   *   The exposed input.
   */
  protected function getExposedInput(ViewExecutable $view, Request $request, array $expose_options) {
    $display_handler = $view->display_handler;
    $filters = $display_handler->getOption('filters');

    if (!empty($expose_options['autocomplete_dependent'])) {
      $exposed_input = $view->getExposedInput();
    }
    else {
      $exposed_input = [];
      // Need to reset the default values for exposed filters.
      foreach ($display_handler->getOption('filters') as $name => $filter) {
        if (!empty($filters[$name]['exposed'])) {
          if (!empty($filter['is_grouped'])) {
            $filters[$name]['group_info']['default_group'] = 'All';
          }
          $filters[$name]['value'] = [];
        }
      }
      $display_handler->setOption('filters', $filters);
    }
    $exposed_input[$expose_options['identifier']] = $request->query->get('q');
    return $exposed_input;
  }

}
