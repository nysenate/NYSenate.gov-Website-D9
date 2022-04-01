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

}
