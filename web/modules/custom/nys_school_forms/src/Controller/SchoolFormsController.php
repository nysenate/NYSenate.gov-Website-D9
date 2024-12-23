<?php

namespace Drupal\nys_school_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\nys_school_forms\SchoolFormsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Route controller for school administration.
 */
class SchoolFormsController extends ControllerBase {

  protected const DEFAULT_REDIRECT_DESTINATION = '/admin/content/schools';

  /**
   * The current request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $request;

  /**
   * The school forms service.
   *
   * @var \Drupal\nys_school_forms\SchoolFormsService
   */
  protected SchoolFormsService $schoolFormsService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->request = $container->get('request_stack');
    $instance->schoolFormsService = $container->get('nys_school_forms.school_forms');
    return $instance;
  }

  /**
   * Reassigns a school's district and redirects back to the caller.
   */
  public function reassignDistrict(NodeInterface $node): RedirectResponse {
    $result = $this->schoolFormsService->reassignDistrict($node);
    $message = SchoolFormsService::ASSIGN_DISTRICT_MESSAGES[$result] ?? 'Unknown Error';
    $messenger = $this->messenger();
    $type = $result === SchoolFormsService::ASSIGN_DISTRICT_SUCCESS
      ? $messenger::TYPE_STATUS
      : $messenger::TYPE_ERROR;
    $messenger->addMessage($message, $type);

    $destination = $this->request->getCurrentRequest()->headers->get('referer')
      ?: static::DEFAULT_REDIRECT_DESTINATION;
    return new RedirectResponse($destination, 307);
  }

}
