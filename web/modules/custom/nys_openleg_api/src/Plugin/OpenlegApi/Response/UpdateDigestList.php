<?php

namespace Drupal\nys_openleg_api\Plugin\OpenlegApi\Response;

/**
 * Openleg API Response generic plugin for a list of updates.
 *
 * This plugin handles the response type "update-digest list".  This functions
 * the same as UpdateList, but a digest has additional properties to account for
 * "detail=true" during the request.
 *
 * @OpenlegApiResponse(
 *   id = "update-digest list",
 *   label = @Translation("Update Digest List"),
 *   description = @Translation("Openleg API Response plugin")
 * )
 */
class UpdateDigestList extends UpdateList {

}
