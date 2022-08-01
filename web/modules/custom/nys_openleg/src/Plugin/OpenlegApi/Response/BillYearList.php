<?php

namespace Drupal\nys_openleg\Plugin\OpenlegApi\Response;

/**
 * Openleg API Response plugin for a list of bills in a session year.
 *
 * @OpenlegApiResponse(
 *   id = "bill-info list",
 *   label = @Translation("Bill Year List"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class BillYearList extends ResponseSearch {

}
