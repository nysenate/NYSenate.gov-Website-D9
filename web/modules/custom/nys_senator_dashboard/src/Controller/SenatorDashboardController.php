<?php

namespace Drupal\nys_senator_dashboard\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a controller for actions related to the senator dashboard.
 */
class SenatorDashboardController extends ControllerBase {

  /**
   * The private temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the controller with required dependencies.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The private temp store factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(PrivateTempStoreFactory $tempStoreFactory, MessengerInterface $messenger, EntityTypeManagerInterface $entityTypeManager) {
    $this->tempStoreFactory = $tempStoreFactory;
    $this->messenger = $messenger;
    $this->entityTypeManager = $entityTypeManager;
    $this->tempStoreFactory = $tempStoreFactory;
    $this->messenger = $messenger;
  }

  /**
   * Dependency injection for controller.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('messenger'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Sets the active managed senator for the current user.
   *
   * @param int $senator_id
   *   The senator ID passed in the route.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response back to the referring page or the homepage.
   */
  public function setActiveSenator($senator_id, Request $request) {
    $tempstore = $this->tempStoreFactory->get('nys_senator_dashboard');

    // Get allowed senator IDs.
    $allowed_senator_ids = [];
    try {
      $current_user = $this->entityTypeManager
        ->getStorage('user')
        ->load($this->currentUser()->id());
      $field_senator_multiref = $current_user->field_senator_multiref?->getValue();
      if (is_array($field_senator_multiref)) {
        $allowed_senator_ids = array_column($field_senator_multiref, 'target_id');
      }
    }
    catch (\Throwable) {
      $this->messenger->addError($this->t('There was an error updating your active managed senator.'));
    }

    // Set the active_managed_senator via tempstore, otherwise set an error.
    if (in_array($senator_id, $allowed_senator_ids)) {
      $success = TRUE;
      try {
        $tempstore->set('active_managed_senator', $senator_id);
      }
      catch (\Exception $e) {
        $success = FALSE;
        $this->messenger->addError($this->t('There was an error updating your active managed senator.'));
      }
      if ($success) {
        $this->messenger->addMessage($this->t('Your active managed senator has been updated successfully.'));
      }
    }
    else {
      $this->messenger->addError($this->t('The specified senator ID is invalid or you do not have access to manage this senator.'));
    }

    // Redirect the user to the referring page if internal, home otherwise.
    $referer = $request->headers->get('referer');
    if ($referer) {
      $parsed_url = parse_url($referer);
      $path = $parsed_url['path'] ?? '/';
      if (Url::fromUserInput($path)->isRouted()) {
        return new RedirectResponse($path);
      }
      else {
        return $this->redirect('<front>');
      }
    }
    else {
      return $this->redirect('<front>');
    }
  }

}
