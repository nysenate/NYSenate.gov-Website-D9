<?php

namespace Drupal\charts\Services;

/**
 * Defines an interface for Chart attachment service classes.
 */
interface ChartAttachmentServiceInterface {

  /**
   * Get Attachment Views.
   *
   * @return array
   *   Different views.
   */
  public function getAttachmentViews();

  /**
   * Set Attachment Views.
   *
   * @param array $attachmentViews
   *   Attach an array of views.
   */
  public function setAttachmentViews(array $attachmentViews = []);

}
