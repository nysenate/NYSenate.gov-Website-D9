<?php

namespace Drupal\charts\Services;

/**
 * Class ChartAttachmentService.
 *
 * @package Drupal\charts\Services.
 */
class ChartAttachmentService implements ChartAttachmentServiceInterface {

  private $attachmentViews;

  /**
   * {@inheritdoc}
   */
  public function getAttachmentViews() {
    return $this->attachmentViews;
  }

  /**
   * {@inheritdoc}
   */
  public function setAttachmentViews(array $attachmentViews = []) {
    $this->attachmentViews = $attachmentViews;
  }

}
