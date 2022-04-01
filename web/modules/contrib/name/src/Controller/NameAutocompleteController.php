<?php

namespace Drupal\name\Controller;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\name\NameAutocomplete;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for name autocompletion routes.
 */
class NameAutocompleteController implements ContainerInjectionInterface {

  /**
   * The name autocomplete helper class to find matching name values.
   *
   * @var \Drupal\name\NameAutocomplete
   */
  protected $nameAutocomplete;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an NameAutocompleteController object.
   *
   * @param \Drupal\name\NameAutocomplete $name_autocomplete
   *   The name autocomplete helper class to find matching name values.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity field manager.
   */
  public function __construct(NameAutocomplete $name_autocomplete, EntityFieldManager $entityFieldManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->nameAutocomplete = $name_autocomplete;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('name.autocomplete'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns response for the name autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param string $field_name
   *   The field name.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $component
   *   The name component.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   *
   * @see \Drupal\name\NameAutocomplete::getMatches()
   */
  public function autocomplete(Request $request, $field_name, $entity_type, $bundle, $component) {
    $definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);

    if (!isset($definitions[$field_name])) {
      throw new AccessDeniedHttpException();
    }

    $field_definition = $definitions[$field_name];
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler($entity_type);
    if ($field_definition->getType() != 'name' || !$access_control_handler->fieldAccess('edit', $field_definition)) {
      throw new AccessDeniedHttpException();
    }

    $matches = $this->nameAutocomplete->getMatches($field_definition, $component, $request->query->get('q'));
    return new JsonResponse($matches);
  }

}
