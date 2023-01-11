<?php

namespace Drupal\webform_views_extras\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Webform submission relationships entities.
 */
interface WebformSubmissionRelationshipsInterface extends ConfigEntityInterface {

  /**
   * Returns the content entity type ID.
   *
   * @return string
   *   The content entity ID.
   */
  function getContentEntityTypeId();
}
