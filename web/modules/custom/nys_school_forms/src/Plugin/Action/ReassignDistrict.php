<?php

namespace Drupal\nys_school_forms\Plugin\Action;

use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\nys_school_forms\SchoolFormsService;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Action to reassign a school's district via SAGE.
 *
 * @Action(
 *   id = "nys_school_forms_reassign_district",
 *   label = @Translation("Reassign District"),
 *   type = "node",
 *   category = @Translation("Custom")
 * )
 */
class ReassignDistrict extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface {

  /**
   * NYS School Forms service.
   *
   * @var \Drupal\nys_school_forms\SchoolFormsService
   */
  protected SchoolFormsService $schoolService;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, SchoolFormsService $school_forms_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->schoolService = $school_forms_service;
  }

  /**
   * {@inheritDoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE): bool|AccessResultInterface {
    $result = new AccessResultForbidden();
    if (($object instanceof Node) && ($object->getType() == 'school')) {
      $result = $object->access('update', $account, TRUE)
        ->orIf($object->access('edit', $account, TRUE));
    }

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritDoc}
   */
  public function execute($entity = NULL): string {
    $result = $this->schoolService->reassignDistrict($entity);
    return $result == $this->schoolService::ASSIGN_DISTRICT_SUCCESS
      ? "District reassigned successfully."
      : "District reassignment failed.";
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('nys_school_forms.school_forms'),
    );
  }

}
