<?php

namespace Drupal\nys_school_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;

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
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $results = [];
    $input = $request->query->get('q');

    // Get the typed string from the URL, if it exists.
    if (!$input) {
      return new JsonResponse($results);
    }

    $input = Xss::filter($input);

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('title', $input, 'CONTAINS')
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
