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
class SchoolAutocompleteController extends ControllerBase {

  /**
   * The entityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
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
    $results = [];
    $input = $request->query->get('q');

    // Get the typed string from the URL, if it exists.
    if (!$input) {
      return new JsonResponse($results);
    }

    // Narrow the options based on form_type.
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

    $select = $this->database->select('webform_submission_data', 'wsd')
      ->fields('wsd', ['value'])
      ->condition('wsd.webform_id', $webform_id, '=')
      ->condition('wsd.name', 'school_name', '=')
      ->distinct()
      ->orderBy('wsd.value');

    $webform_results = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);
    $school_ids = array_column($webform_results, 'value');

    $input = Xss::filter($input);

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('title', $input, 'CONTAINS')
      ->condition('nid', $school_ids, 'IN')
      ->condition('type', 'school', '=')
      ->sort('title')
      ->range(0, 10);

    $ids = $query->execute();
    $nodes = $ids ? $this->entityTypeManager->getStorage('node')->loadMultiple($ids) : [];

    foreach ($nodes as $node) {
      $results[] = [
        'value' => $node->label(),
        'label' => $node->label(),
      ];
    }

    return new JsonResponse($results);
  }

}
