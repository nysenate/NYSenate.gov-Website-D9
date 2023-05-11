<?php

namespace Drupal\nys_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Block for Calendar Events Downloads.
 *
 * @Block(
 *   id = "nys_blocks_calendar_downloads",
 *   admin_label = @Translation("Calendar Events Downloads"),
 * )
 */
class CalendarDownloads extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'nys_blocks_calendar_downloads',
      '#session_calendar_pdf_href' => '/sites/default/files/pdfs/legislative-session-calendar.pdf',
      '#public_hearing_schedule_href' => '/sites/default/files/pdfs/23-phc-5-5.pdf',
    ];
  }

}
