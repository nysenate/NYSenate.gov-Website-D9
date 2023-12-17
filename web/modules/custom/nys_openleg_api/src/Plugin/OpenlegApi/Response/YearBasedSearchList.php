<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

/**
 * Represents the search results of a year-based search.
 *
 * This is a template, and does not match a known OpenLeg response type.  Year
 * based search use URLs like "/api/3/bills/2023/", without a search term.
 *
 * @OpenlegApiResponse(
 *   id = "year_based_search_list",
 *   label = @Translation("Year Based Search Response Template"),
 *   description = @Translation("Openleg API Response plugin template")
 * )
 */
abstract class YearBasedSearchList extends ResponseSearch {

  /**
   * Resolves an "id" from the OpenLeg representation of an object.
   *
   * @param object $item
   *   An OpenLeg representation of a single item.  In year-based searches, the
   *   response's result->items property holds an array of single items.
   *
   * @return string
   *   The calculated ID of the passed item.
   */
  abstract public function id(object $item): string;

  /**
   * Generates an array of IDs from the search result items.
   */
  public function getIdFromYearList(): array {
    return array_unique(
      array_filter(
        array_map(
          [$this, 'id'],
          $this->items()
        )
      )
    );
  }

}
