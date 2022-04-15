<?php

namespace Drupal\facets\Event;

/**
 * Defines events for the Facets module.
 */
final class FacetsEvents {

  /**
   * This event allows modules to change the facet's query string if needed.
   *
   * @Event
   *
   * @see \Drupal\facets\Event\QueryStringCreated
   */
  public const QUERY_STRING_CREATED = QueryStringCreated::class;

  /**
   * This event allows modules to change the active filters after parsing them.
   *
   * @Event
   *
   * @see \Drupal\facets\Event\ActiveFiltersParsed
   */
  public const ACTIVE_FILTERS_PARSED = ActiveFiltersParsed::class;

}
