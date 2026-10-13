<?php

namespace Drupal\nys_opendata\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\nys_opendata\NysOpenDataCsv;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class HomepageHeroController.
 *
 * Handles routing for nys_homepage_hero module.
 */
class OpenDataController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(protected Connection $database, protected RequestStack $stack) {}

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('database'), $container->get('request_stack'));
  }

  /**
   * The AJAX handler for front-end datatables requests.
   *
   * @param int|null $fid
   *   The Drupal file ID.
   */
  public function nysOpenDataFetchDatasource(?int $fid = NULL): JsonResponse {
    $fid = (int) $fid;
    $ret = ['data' => ['An unknown error occurred while attempting to fetch data']];
    if ($this->verifyOpenDataAssignment($fid)) {
      $get = $this->stack->getCurrentRequest()->query->all();
      $order_column = (int) ($get['order'][0]['column'] ?? 0);
      $descend = ($get['order'][0]['dir'] ?? 'asc') === 'desc';
      $file = new NysOpenDataCsv($fid);
      $file->sortData($order_column, $descend);
      $content = $file->getDataSlice($get['start'] ?? 0, $get['length'] ?? 100);
      $ret = [
        'data' => $content,
        'draw' => (int) $get['draw'],
        'recordsTotal' => $file->countRows(),
        'recordsFiltered' => $file->countRows(),
      ];
    }
    return new JsonResponse($ret);
  }

  /**
   * Checks if a file is associated with any open_data node.
   */
  protected function verifyOpenDataAssignment(int $fid): bool {
    $ret = FALSE;
    if ($fid) {
      $query = $this->database->select('file_usage', 'fu');
      $query->join('node_field_data', 'nfd', 'fu.id = nfd.nid');
      $query->fields('fu', ['fid'])
        ->condition('fu.fid', $fid)
        ->condition('fu.type', 'node')
        ->condition('nfd.type', 'open_data')
        ->range(0, 1);
      $ret = ($query->execute()->fetchField() == $fid);
    }
    return $ret;
  }

}
