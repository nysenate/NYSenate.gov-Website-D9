<?php

namespace Drupal\nys_opendata\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\nys_opendata\NysOpenDataCsv;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class HomepageHeroController.
 *
 * Handles routing for nys_homepage_hero module.
 */
class OpenDataController extends ControllerBase {

  /**
   * The AJAX handler for front-end datatables requests.
   *
   * @param int $fid
   *   The Drupal file ID.
   */
  public function nysOpenDataFetchDatasource($fid = NULL) {
    if ((int) $fid) {
      $order_column = (int) ($_GET['order'][0]['column'] ?? 0);
      $descend = ($_GET['order'][0]['dir'] ?? 'asc') === 'desc';
      $file = new NysOpenDataCsv($fid);
      $file->sortData($order_column, $descend);
      $content = $file->getDataSlice($_GET['start'] ?? 0, $_GET['length'] ?? 100);
      $ret = [
        'data' => $content,
        'draw' => (int) $_GET['draw'],
        'recordsTotal' => $file->countRows(),
        'recordsFiltered' => $file->countRows(),
      ];

      return new JsonResponse($ret);
    }
  }

}
