<?php

namespace Drupal\nys_school_forms\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a route controller for watches autocomplete form elements.
 */
class TeacherAutocompleteController extends ControllerBase {

  /**
   * Entity Type Mananger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The database.
   *
   * @var \Drupal\mysql\Driver\Database\mysql\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->database = $container->get('database');
    return $instance;
  }

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request, $form_type) {
    $data_results = [];
    $input = $request->query->get('q');

    // Get the typed string from the URL, if it exists.
    if (!$input) {
      return new JsonResponse($data_results);
    }

    $webform_id = '';
    switch ($form_type) {
      // Earth Day.
      case 'Earth Day':
        $webform_id = 'school_form_earth_day';
        break;

      // Thanksgiving.
      case 'Thanksgiving':
        $webform_id = 'school_form_thanksgiving';
        break;

      default:
        $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(
              [
                'vid' => 'school_form_type',
                'name' => $form_type,
              ]
          );
        if ($terms !== NULL) {
          $term = reset($terms);
          $webform_id = $term->field_school_form->target_id;
        }
        break;
    }

    $input = '%' . Xss::filter($input) . '%';
    $select = $this->database->select('webform_submission_data', 'wsd')
      ->fields('wsd', ['value'])
      ->condition('wsd.value', $input, 'LIKE')
      ->condition('wsd.webform_id', $webform_id, '=')
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
