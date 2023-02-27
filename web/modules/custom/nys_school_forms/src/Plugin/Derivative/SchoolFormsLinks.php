<?php

namespace Drupal\nys_school_forms\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derivative class that provides the menu links for the Products.
 */
class SchoolFormsLinks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $instance = new static($base_plugin_id);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];

    $school_form_types = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['vid' => 'school_form_type']);
    foreach ($school_form_types as $form_type) {
      $term_name = $form_type->getName();
      $alias = str_replace([' ', '-', '\''], '_', strtolower($term_name));

      $links[$form_type->id()] = [
        'title' => 'School Form Submission - ' . $term_name,
        'description' => 'List School Form Submissions',
        'route_name' => 'nys_school_forms.school_forms.' . $alias,
        'parent' => 'entity.webform.collection',
        'route_parameters' => ['form_type' => $term_name],
      ] + $base_plugin_definition;
    }

    return $links;
  }

}
