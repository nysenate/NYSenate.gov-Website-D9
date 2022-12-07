<?php

namespace Drupal\nys_school_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;
use Drupal\mysql\Driver\Database\mysql\Connection;

/**
 * Defines a route controller for watches autocomplete form elements.
 */
class TeacherAutocompleteController extends ControllerBase {

  /**
   * The database.
   *
   * @var \Drupal\mysql\Driver\Database\mysql\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('database')
    );
  }

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $data_results = [];
    $input = $request->query->get('q');

    // Get the typed string from the URL, if it exists.
    if (!$input) {
      return new JsonResponse($data_results);
    }

    $input = '%' . Xss::filter($input) . '%';
    $select = $this->database->select('webform_submission_data', 'wsd')
      ->fields('wsd', ['value'])
      ->condition('wsd.value', $input, 'LIKE')
      ->condition('wsd.webform_id', 'school_form', '=')
      ->condition('wsd.name', 'contact_name', '=')
      ->orderBy('wsd.value')
      ->range(0, 10);
    $executed = $select->execute();
    // Get all the results.
    $results = $executed->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($results as $row) {
      $data_results[] = [
        'value' => $row['value'],
        'label' => $row['value'],
      ];
    }

    return new JsonResponse($data_results);
  }

}
