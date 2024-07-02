<?php

namespace Drupal\views_combine;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Query\Select;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Provides views query combiner.
 */
class ViewsCombiner {

  /**
   * The base view.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * An array of combined views filters.
   *
   * @var array
   */
  protected $filters = [];

  /**
   * An array of view queries to combine.
   *
   * @var \Drupal\Core\Database\Query\Select[]
   */
  protected $queries = [];

  /**
   * An array of query fields and expressions.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * An array of query order fields and keys.
   *
   * @var array
   */
  protected $orders = [];

  /**
   * An array of query order key directions.
   *
   * @var array
   */
  protected $orderDirections = [];

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ViewsCombiner constructor.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The base view.
   */
  public function __construct(ViewExecutable $view) {
    $this->view = $view;
    $this->connection = \Drupal::service('database');
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
  }

  /**
   * Check if view combines one or more additional views.
   *
   * @return bool
   *   Returns TRUE or FALSE.
   */
  public function hasViews() {
    foreach ($this->view->display_handler->getHandlers('field') as $field_handler) {
      if ($field_handler->getPluginId() === 'views_combine') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Combine view queries.
   *
   * @see views_combine_views_post_build()
   */
  public function combine() {
    if (!$this->hasViews()) {
      return $this;
    }
    if ($this->setFilters()->setViews()->queries) {
      // Establish collection of query fields and orders.
      foreach ($this->queries as $query) {
        $this->fields += $query->getFields() + $query->getExpressions();
        $order_key = 0;
        foreach (array_keys($query->getOrderBy()) as $order_field) {
          $this->orders[$order_field] = $order_key;
          $order_key++;
        }
      }
      $this->setFields()->setCombineSorts()->setUnions();
    }
    return $this;
  }

  /**
   * Get view query identifier.
   *
   * @var \Drupal\views\ViewExecutable $view
   *   The base view.
   */
  public function getId(ViewExecutable $view) {
    return $view->id() . ':' . $view->current_display;
  }

  /**
   * Get the unique query identifier.
   *
   * @param \Drupal\Core\Database\Query\Select $query
   *   The query.
   *
   * @return string
   *   Returns the unique query identifier.
   */
  public function getQueryId(Select $query) {
    return str_replace(':', '_', $query->getMetaData('view_id'));
  }

  /**
   * Get combined views recursively.
   *
   * @var string $view_query_id
   *   The view query identifier.
   *
   * @return array
   *   Returns array of view identifiers.
   */
  public function getViewIds(string $view_query_id) {
    if (!isset($ids)) {
      $ids = [$view_query_id];
    }
    [$view_id, $display_id] = explode(':', $view_query_id);
    if ($view = $this->getView($view_id, $display_id)) {
      foreach ($view->display_handler->getHandlers('field') as $field_handler) {
        if ($field_handler->getPluginId() === 'views_combine') {
          $ids[] = $field_handler->options['view_id'];
          $ids = array_merge($ids, $this->getViewIds($field_handler->options['view_id']));
        }
      }
    }
    return $ids;
  }

  /**
   * Load view display executable.
   *
   * @param string $view_id
   *   The view identifier.
   * @param string $display_id
   *   The display identifier.
   *
   * @return \Drupal\views\ViewExecutable|null
   *   Returns the view executable.
   */
  public function getView(string $view_id, string $display_id) {
    $_view = NULL;
    try {
      /** @var \Drupal\views\Entity\View $view */
      $view = $this->entityTypeManager->getStorage('view')
        ->load($view_id);

      if ($view && isset($view->get('display')[$display_id])) {
        $_view = $view->getExecutable();
        $_view->setDisplay($display_id);
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // Unable to load view storage.
    }
    return $_view;
  }

  /**
   * Build view with inheritance from base view.
   *
   * @param string $view_id
   *   The view identifier.
   * @param string $display_id
   *   The display identifier.
   * @param \Drupal\views\ViewExecutable $base_view
   *   The base view.
   * @param \Drupal\views\Plugin\views\field\FieldPluginBase|null $field_handler
   *   The views field handler.
   *
   * @return \Drupal\views\ViewExecutable|null
   *   Returns the built view executable.
   */
  public function buildView(string $view_id, string $display_id, ViewExecutable $base_view, FieldPluginBase $field_handler = NULL) {
    if ($view = $this->getView($view_id, $display_id)) {
      $input = $base_view->exposed_raw_input;

      if ($field_handler) {
        foreach ($input as $id => $value) {
          if (isset($field_handler->options['filter_map'][$id]) && $field_handler->options['filter_map'][$id]) {
            // Use the mapped filter identifier.
            $input[$field_handler->options['filter_map'][$id]] = $value;
          }
        }
      }
      if (isset($input['sort_by']) && !empty($input['sort_by'])) {
        $id = $input['sort_by'];
        $sort_handlers = $view->getHandlers('sort');
        if (!array_key_exists($id, $sort_handlers)) {
          $input['sort_order'] = $input['sort_order'] ?? 'ASC';
          if (isset($field_handler->options['sort_map'][$id]) && $field_handler->options['sort_map'][$id]) {
            // Use the mapped sort identifier.
            $input['sort_by'] = $field_handler->options['sort_map'][$id];
          }
          elseif (!empty($sort_handlers)) {
            // Use the first sort identifier.
            $input['sort_by'] = key($sort_handlers);
          }
          else {
            unset($input['sort_by'], $input['sort_order'], $input['sort_bef_combine']);
          }
          if (isset($input['sort_bef_combine'])) {
            $input['sort_bef_combine'] = $input['sort_by'] . '_' . $input['sort_order'];
          }
        }
      }

      $view->setExposedInput($input);
      $view->preExecute($base_view->args);
      $view->build();
      return $view;
    }
    return NULL;
  }

  /**
   * Set combined views filters.
   *
   * @todo Support grouped filters and sleep for a week.
   */
  protected function setFilters() {
    foreach ($this->view->filter as $filter) {
      if ($filter->realField === 'views_combine' && $filter->value) {
        if (in_array('all', $filter->value)) {
          $filter->value = ['all' => 'all'];
        }
        $values = [];
        foreach ($filter->value as $value) {
          if ($value === 'all' && $filter->options['expose']['all_views']) {
            $values += $filter->options['expose']['all_views'];
          }
          elseif ($value === 'current_view') {
            $values += [$this->getId($this->view)];
          }
          else {
            $values += [$value];
            $values += $this->getViewIds($value);
          }
        }
        $this->filters[$filter->operator] = array_unique($values);
      }
    }
    return $this;
  }

  /**
   * Check view against combined views filters.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   *
   * @return bool
   *   Returns TRUE or FALSE.
   */
  protected function filter(ViewExecutable $view) {
    if ($this->filters) {
      $view_id = $this->getId($view);
      foreach ($this->filters as $operator => $value) {
        $in_value = in_array($view_id, $value);
        if (($operator === 'in' && !$in_value) || ($operator === 'not in' && $in_value)) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  /**
   * Set views queries to combine.
   *
   * @var \Drupal\views\ViewExecutable $view
   *   The base view.
   */
  protected function setViews() {
    if ($this->filter($this->view)) {
      /** @var \Drupal\Core\Database\Query\Select $query */
      $query = $this->view->build_info['query'];
      $query->addMetaData('view_id', $this->getId($this->view));
      $this->queries[$this->getQueryId($query)] = $query;
    }
    foreach ($this->view->field as $field_handler) {
      if ($field_handler->realField === 'views_combine' && $field_handler->options['view_id']) {
        [$view_id, $display_id] = explode(':', $field_handler->options['view_id']);
        if (($_view = $this->buildView($view_id, $display_id, $this->view, $field_handler)) && $this->filter($_view)) {
          if ($_view->build_info['query'] instanceof Select) {
            /** @var \Drupal\Core\Database\Query\Select $query */
            $query = $_view->build_info['query'];
            $query->addMetaData('view_id', $this->getId($_view));
            $unions =& $query->getUnion();
            foreach ($unions as $union) {
              $this->queries[$this->getQueryId($union['query'])] = $union['query'];
            }
            $unions = [];
            $this->queries[$this->getQueryId($query)] = $query;
          }
        }
      }
    }
    $this->view->views_combine_queries = $this->queries;
    return $this;
  }

  /**
   * Set views query fields.
   *
   * Each UNION query must have matching column names and positions. Otherwise,
   * the query execution throws a fatal or may have incorrect data results. Plus
   * normalizing order field names presents the capability for robust sorting.
   */
  protected function setFields() {
    foreach ($this->queries as $key => $query) {
      $tables =& $query->getTables();
      $fields =& $query->getFields();
      $expressions =& $query->getExpressions();
      $orders =& $query->getOrderBy();

      // Convert fields and normalize expression values and column positions.
      foreach ($fields as $schema) {
        $query->addExpression($schema['table'] . '.' . $schema['field'], $schema['alias']);
      }
      $fields = [];
      if (!isset($expressions['_view_id'])) {
        $query->addExpression(":view_$key", '_view_id', [
          ":view_$key" => $query->getMetaData('view_id'),
        ]);
      }
      foreach (array_diff_key($this->fields, $expressions) as $schema) {
        $query->addExpression('NULL', $schema['alias']);
      }

      // Set order fields. Order direction prefers the topmost view.
      foreach ($orders as $field => $direction) {
        if (!isset($this->orderDirections[$this->orders[$field]])) {
          $this->orderDirections[$this->orders[$field]] = $direction;
        }
        if (!preg_match('/_order_\d+/', $field)) {
          unset($expressions["_order_{$this->orders[$field]}"]);
          $query->addExpression($expressions[$field]['expression'], "_order_{$this->orders[$field]}");
        }
      }
      for ($i = count($orders); $i < count(array_unique($this->orders)); $i++) {
        if (!isset($expressions["_order_$i"])) {
          $query->addExpression('NULL', "_order_$i");
        }
      }
      $orders = [];

      // Append union query key to SQL argument placeholders.
      $this
        ->setArguments($tables, $key)
        ->setArguments($expressions, $key)
        ->setArguments($orders, $key);

      // Normalize sequence of expressions (fields).
      $expressions = array_merge(array_flip(array_keys($this->fields)), $expressions);
    }
    return $this;
  }

  /**
   * Set combined views sort, normal sorts are handled by fields.
   *
   * @todo this is always first sort, should go by field order.
   */
  protected function setCombineSorts() {
    $combine = $this->view->sort['views_combine'] ?? FALSE;
    if ($combine) {
      $view_count = 0;
      foreach ($this->queries as $query) {
        // First query is base query.
        if (!$view_count) {
          $query->orderBy('_combine_sort', $combine->options['order']);
        }
        // Sort in order of added combine.
        $query->addExpression($view_count, '_combine_sort');
        $view_count++;
      }
    }
    return $this;
  }

  /**
   * Set unique argument placeholders.
   *
   * @param array $definitions
   *   The SQL definition.
   * @param string $key
   *   The query key.
   */
  protected function setArguments(array &$definitions, string $key) {
    foreach ($definitions as &$definition) {
      if (isset($definition['arguments'])) {
        foreach ($definition['arguments'] as $argument => $argument_value) {
          $definition['arguments']["{$argument}_$key"] = $argument_value;
          array_walk($definition, function (&$value) use ($argument, $key) {
            if (is_scalar($value)) {
              $value = str_replace($argument, "{$argument}_$key", $value);
            }
          });
          unset($definition['arguments'][$argument]);
        }
      }
    }
    return $this;
  }

  /**
   * Set views query unions.
   *
   * First query is presumed to be the base view query. All proceeding queries
   * are unionized under the base query.
   */
  protected function setUnions() {
    // Set base query unions and order.
    $base_query = array_shift($this->queries);
    foreach ($this->queries as $query) {
      $base_query->union($query);
    }
    foreach ($this->orders as $order_key) {
      $base_query->orderBy("_order_$order_key", $this->orderDirections[$order_key]);
    }
    $this->view->build_info['query'] = $base_query;

    // Set count query with distinct to prevent optimizations.
    if ($this->view->display_handler->usesPager()) {
      $count_query = clone $base_query;
      $count_query->distinct();
      $unions =& $count_query->getUnion();
      foreach ($unions as $union_query) {
        $union_query['query']->distinct();
      }
      $this->view->build_info['count_query'] = $count_query;
    }
  }

  /**
   * Prepare results from combined views.
   */
  public function results() {
    if ($this->hasViews()) {
      $cache = &drupal_static('views_combine');
      foreach ($this->view->result as $key => $row) {
        if (isset($row->_view_id) && !$row->_entity) {
          // Get appropriate view for the result row. Necessary to load missing
          // entities and set proper display style handlers.
          if (!isset($cache[$row->_view_id])) {
            [$view_id, $display_id] = explode(':', $row->_view_id);
            $cache[$row->_view_id] = $this->buildView($view_id, $display_id, $this->view);
          }

          // Get the row entities.
          if ($cache[$row->_view_id]) {
            $results = [$row];
            $cache[$row->_view_id]->getQuery()->loadEntities($results);
          }
          else {
            unset($this->view->result[$key]);
          }
        }
      }
    }
  }

}
