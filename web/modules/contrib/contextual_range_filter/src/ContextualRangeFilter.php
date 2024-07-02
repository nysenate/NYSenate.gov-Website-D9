<?php

namespace Drupal\contextual_range_filter;

/**
 * Functions for the contextual_range_filter module.
 */
class ContextualRangeFilter {

  // Use the same separator as for Date fields, but allow an alternative also.
  const CONTEXTUAL_RANGE_FILTER_SEPARATOR1 = '--';
  const CONTEXTUAL_RANGE_FILTER_SEPARATOR2 = ':';

  /**
   * Split a filter range string into an array of "from" and "to" values.
   *
   * @param string $range
   *   Typically of the format "from--to", "from--" or "--to", but a single
   *   value is also allowed. A single colon is accepted instead of --.
   *
   * @return array
   *   Array of length 2, the 2nd value equals FALSE when no separator was
   *   found.
   */
  private static function split($range) {
    // A little defensive programming to make sure we have a string.
    if (is_array($range)) {
      $range = reset($range);
    }
    $range = trim($range);
    $from_to = explode(self::CONTEXTUAL_RANGE_FILTER_SEPARATOR1, $range);
    if (count($from_to) < 2) {
      $from_to = explode(self::CONTEXTUAL_RANGE_FILTER_SEPARATOR2, $range);
    }
    return count($from_to) == 1 ? [reset($from_to), FALSE] : $from_to;
  }

  /**
   * Build a search api range query based on the ranges passed in.
   *
   * @param object $views_argument_plugin
   *   The View's contextual filter plugin.
   * @param string $field
   *   The full field name as used in the SQL statement, or NULL.
   * @param object $range_converter
   *   Optional function to convert the from--to range, e.g., for relative date
   *   ranges like "-3 months--last week" to be converted to
   *   "20200120--20200413".
   */
  private static function buildRangeQuerySearchApi($views_argument_plugin, $field = NULL, $range_converter = NULL) {
    if (!isset($views_argument_plugin) || $views_argument_plugin->value === FALSE) {
      return;
    }
    $real_field = $views_argument_plugin->realField;
    if (!isset($field)) {
      // Example: "node__field_price.field_price_value".
      $field = "$real_field";
    }
    // $is_not comes from "Exclude" tickbox.
    $is_not = !empty($views_argument_plugin->options['not']);

    // All WHERE clauses are OR-ed or AND-ed together in the same group.
    // Note: NOT (a OR b OR c) == (NOT a) AND (NOT b) AND (NOT c).
    $group = $views_argument_plugin->query->setWhereGroup($is_not ? 'AND' : 'OR');

    foreach ($views_argument_plugin->value as $range) {

      list($from, $to) = self::split($range);

      if (is_callable($range_converter)) {
        list($from, $to) = call_user_func($range_converter, $from, $to);
      }

      if ($to === FALSE) {
        // Dealing with a single value, not a range.
        $operator = $is_not ? '!=' : '=';
        $views_argument_plugin->query->addWhere($group, $field, $to, $operator);
      }
      elseif ($from != '' && $to != '') {
        // from--to.
        $operator = $is_not ? 'NOT BETWEEN' : 'BETWEEN';
        $views_argument_plugin->query->addWhere($group, $field, [$from, $to], $operator);
      }
      elseif ($from != '') {
        // from--.
        $operator = $is_not ? '<' : '>=';
        $views_argument_plugin->query->addWhere($group, $field, $from, $operator );
      }
      elseif ($to != '') {
        // --to
        $operator = $is_not ? '>' : '<=';
        $views_argument_plugin->query->addWhere($group, $field, $from, $operator);
      }
    }
  }

  /**
   * Build a range query based on the ranges passed in.
   *
   * Search API and SQL storage using different query interfaces and different
   * query syntax. The specific query implementations are done in sub functions.
   * To avoid hard dependencies to search api we use the plugin id instead of an interface
   * to detect the source of the query.
   *
   * @param object $views_argument_plugin
   *   The View's contextual filter plugin.
   * @param string $field
   *   The full field name as used in the SQL statement, or NULL.
   * @param object $range_converter
   *   Optional function to convert the from--to range, e.g., for relative date
   *   ranges like "-3 months--last week" to be converted to
   *   "20200120--20200413".
   */
  public static function buildRangeQuery($views_argument_plugin, $field = NULL, $range_converter = NULL) {
    if ($views_argument_plugin->query->getBaseId() === 'search_api_query' ) {
      return self::buildRangeQuerySearchApi($views_argument_plugin, $field, $range_converter);
    } else {
      return self::buildRangeQuerySql($views_argument_plugin, $field, $range_converter);
    }
  }

  /**
   * Build sql a range query based on the ranges passed in.
   *
   * @param object $views_argument_plugin
   *   The View's contextual filter plugin.
   * @param string $field
   *   The full field name as used in the SQL statement, or NULL.
   * @param object $range_converter
   *   Optional function to convert the from--to range, e.g., for relative date
   *   ranges like "-3 months--last week" to be converted to
   *   "20200120--20200413".
   */
  private static function buildRangeQuerySql($views_argument_plugin, $field = NULL, $range_converter = NULL) {

    if (!isset($views_argument_plugin) || $views_argument_plugin->value === FALSE) {
      return;
    }
    $real_field = $views_argument_plugin->realField;
    if (!isset($field)) {
      // Example: "node__field_price.field_price_value".
      $field = "$views_argument_plugin->tableAlias.$real_field";
    }
    // $is_not comes from "Exclude" tickbox.
    $is_not = !empty($views_argument_plugin->options['not']);
    $null_check = $is_not ? "OR $field IS NULL" : '';

    // All WHERE clauses are OR-ed or AND-ed together in the same group.
    // Note: NOT (a OR b OR c) == (NOT a) AND (NOT b) AND (NOT c).
    $group = $views_argument_plugin->query->setWhereGroup($is_not ? 'AND' : 'OR');

    foreach ($views_argument_plugin->value as $range) {
      $placeholder = $views_argument_plugin->query->placeholder($real_field);

      list($from, $to) = self::split($range);

      if (is_callable($range_converter)) {
        list($from, $to) = call_user_func($range_converter, $from, $to);
      }

      if ($to === FALSE) {
        // Dealing with a single value, not a range.
        $operator = $is_not ? '!=' : '=';
        $views_argument_plugin->query->addWhereExpression($group, "$field $operator $placeholder $null_check", [$placeholder => $range]);
      }
      elseif ($from != '' && $to != '') {
        // from--to.
        $operator = $is_not ? 'NOT BETWEEN' : 'BETWEEN';
        $placeholder_from = $placeholder;
        $placeholder_to = $views_argument_plugin->query->placeholder($real_field);
        $args = [$placeholder_from => $from, $placeholder_to => $to];
        $expression = "$field $operator $placeholder_from AND $placeholder_to $null_check";
        $views_argument_plugin->query->addWhereExpression($group, $expression, $args);
      }
      elseif ($from != '') {
        // from--.
        $operator = $is_not ? '<' : '>=';
        $views_argument_plugin->query->addWhereExpression($group, "$field $operator $placeholder $null_check", [$placeholder => $from]);
      }
      elseif ($to != '') {
        // --to
        $operator = $is_not ? '>' : '<=';
        $views_argument_plugin->query->addWhereExpression($group, "$field $operator $placeholder $null_check", [$placeholder => $to]);
      }
    }
  }

}
