<?php

namespace Drupal\og_prepopulate;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\og\Og;
use Drupal\prepopulate\Populate as BasePopulate;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service to populate og audience fields from URL.
 *
 * @package Drupal\og_prepopulate
 */
class Populate extends BasePopulate {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Populate constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(RequestStack $request, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, AccountProxyInterface $current_user) {
    $populator = parent::__construct($request, $entity_type_manager, $module_handler);
    $this->currentUser = $current_user;
    return $populator;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatEntityAutocomplete($value, array &$element) {
    $entity = $this->entityTypeManager
      ->getStorage($element['#target_type'])
      ->load($value);
    if ($entity && Og::isMember($entity, $this->currentUser->getAccount())) {
      $element['#value'] = "{$entity->label()} ($value)";
      $element['#access'] = FALSE;
    }
  }

}
