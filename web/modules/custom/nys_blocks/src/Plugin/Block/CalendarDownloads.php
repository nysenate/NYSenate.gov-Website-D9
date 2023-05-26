<?php

namespace Drupal\nys_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\file\Entity\File;

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
    $session_calendar_pdf = '/sites/default/files/pdfs/legislative-session-calendar.pdf';
    $session_calendar_pdf_fid = \Drupal::configFactory()->get('nys_config.settings')->get('session_calendar_pdf');
    if (!empty($session_calendar_pdf_fid)) {
      $file = File::load($session_calendar_pdf_fid[0]);
      if ($file) {
        $session_calendar_pdf = \Drupal::service('file_url_generator')
          ->generateAbsoluteString($file->getFileUri());
      }
    }

    $public_hearing_schedule = '/sites/default/files/pdfs/23-phc-5-5.pdf';
    $public_hearing_schedule_pdf_fid = \Drupal::configFactory()->get('nys_config.settings')->get('public_hearing_schedule');
    if (!empty($public_hearing_schedule_pdf_fid)) {
      $file = File::load($public_hearing_schedule_pdf_fid[0]);
      if ($file) {
        $public_hearing_schedule = \Drupal::service('file_url_generator')
          ->generateAbsoluteString($file->getFileUri());
      }
    }

    return [
      '#theme' => 'nys_blocks_calendar_downloads',
      '#session_calendar_pdf_href' => $session_calendar_pdf,
      '#public_hearing_schedule_href' => $public_hearing_schedule,
    ];
  }

}
