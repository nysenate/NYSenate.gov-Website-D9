<?php

namespace Drupal\entityqueue\Controller;

use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\entityqueue\EntityQueueInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\entityqueue\EntitySubqueueInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\entityqueue\EntityQueueRepositoryInterface;

/**
 * Returns responses for Entityqueue UI routes.
 */
class EntityQueueUIController extends ControllerBase {

  use AjaxHelperTrait;

  /**
   * The Entityqueue repository service.
   *
   * @var EntityQueueRepositoryInterface
   */
  protected $entityQueueRepository;

  /**
   * Constructs a EntityQueueUIController object
   *
   * @param EntityQueueRepositoryInterface $entityqueue_respository
   *   The Entityqueue repository service.
   */
  public function __construct(EntityQueueRepositoryInterface $entityqueue_respository) {
    $this->entityQueueRepository = $entityqueue_respository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entityqueue.repository')
    );
  }


  /**
   * Provides a list of all the subqueues of an entity queue.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $entity_queue
   *   The entity queue.
   *
   * @return array
   *   A render array.
   */
  public function subqueueList(EntityQueueInterface $entity_queue) {
    $list_builder = $this->entityTypeManager()->getListBuilder('entity_subqueue');
    $list_builder->setQueueId($entity_queue->id());

    return $list_builder->render();
  }

  /**
   * Provides a list of subqueues where an entity can be added.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param string $entity_type_id
   *   (optional) The entity type ID.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   (optional) An entity object.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function subqueueListForEntity(RouteMatchInterface $route_match, $entity_type_id = NULL, EntityInterface $entity = NULL) {
    if (!$entity) {
      $entity = $route_match->getParameter($entity_type_id);
    }

    $queues = $this->entityQueueRepository->getAvailableQueuesForEntity($entity);
    $subqueues = $this->entityTypeManager()->getStorage('entity_subqueue')->loadByProperties(['queue' => array_keys($queues)]);
    $list_builder = $this->entityTypeManager()->getListBuilder('entity_subqueue');

    $build['#title'] = $this->t('Entityqueues for %title', ['%title' => $entity->label()]);
    $build['#type'] = 'container';
    $build['#attributes']['id'] = 'entity-subqueue-list';
    $build['#attached']['library'][] = 'core/drupal.ajax';
    $build['table'] = [
      '#type' => 'table',
      '#header' => $list_builder->buildHeader(),
      '#rows' => [],
      '#cache' => [],
      '#empty' => $this->t('There are no queues available.'),
    ];

    /** @var \Drupal\entityqueue\EntitySubqueueInterface $subqueue */
    foreach ($subqueues as $subqueue_id => $subqueue) {
      $row = $list_builder->buildRow($subqueue);

      // Check if entity is in queue.
      $subqueue_items = $subqueue->get('items')->getValue();
      if (in_array($entity->id(), array_column($subqueue_items, 'target_id'), TRUE)) {
        $row['operations']['data']['#links'] = [
          'remove-item' => [
            'title' => $this->t('Remove from queue'),
            'url' => Url::fromRoute('entity.entity_subqueue.remove_item', ['entity_queue' => $queues[$subqueue->bundle()]->id(), 'entity_subqueue' => $subqueue_id, 'entity' => $entity->id()]),
            'attributes' => [
              'class' => ['use-ajax'],
            ],
          ],
        ];
      }
      else {
        $row['operations']['data']['#links'] = [
          'add-item' => [
            'title' => $this->t('Add to queue'),
            'url' => Url::fromRoute('entity.entity_subqueue.add_item', ['entity_queue' => $queues[$subqueue->bundle()]->id(), 'entity_subqueue' => $subqueue_id, 'entity' => $entity->id()]),
            'attributes' => [
              'class' => ['use-ajax'],
            ],
          ],
        ];
      }

      // Add an operation for editing the subqueue items.
      // First, compute the destination to send the user back to the
      // entityqueue tab they're currently on. We can't rely on <current>
      // since if any of the AJAX links are used and the page is rebuilt,
      // <current> will point to the most recent AJAX callback, not the
      // original entityqueue tab.
      $destination = Url::fromRoute("entity.$entity_type_id.entityqueue", [$entity_type_id => $entity->id()])->toString();
      $row['operations']['data']['#links']['edit-subqueue-items'] = [
        'title' => $this->t('Edit subqueue items'),
        'url' => $subqueue->toUrl('edit-form', ['query' => ['destination' => $destination]]),
      ];

      $build['table']['#rows'][$subqueue->id()] = $row;
    }

    return $build;
  }

  /**
   * Returns a form to add a new subqeue.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $entity_queue
   *   The queue this subqueue will be added to.
   *
   * @return array
   *   The entity subqueue add form.
   */
  public function addForm(EntityQueueInterface $entity_queue) {
    $subqueue = $this->entityTypeManager()->getStorage('entity_subqueue')->create(['queue' => $entity_queue->id()]);
    return $this->entityFormBuilder()->getForm($subqueue, 'add');
  }

  /**
   * Calls a method on an entity queue and reloads the listing page.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $entity_queue
   *   The view being acted upon.
   * @param string $op
   *   The operation to perform, e.g., 'enable' or 'disable'.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Either returns a rebuilt listing page as an AJAX response, or redirects
   *   back to the listing page.
   */
  public function ajaxOperation(EntityQueueInterface $entity_queue, $op, Request $request) {
    // Perform the operation.
    $entity_queue->$op()->save();

    // If the request is via AJAX, return the rendered list as JSON.
    if ($request->request->get('js')) {
      $list = $this->entityTypeManager()->getListBuilder('entity_queue')->render();
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('#entity-queue-list', $list));
      return $response;
    }

    // Otherwise, redirect back to the page.
    return $this->redirect('entity.entity_queue.collection');
  }

  /**
   * Calls a method on an entity subqueue page and reloads the page.
   *
   * @param \Drupal\entityqueue\EntitySubqueueInterface $entity_subqueue
   *   The subqueue being acted upon.
   * @param string $op
   *   The operation to perform, e.g., 'addItem' or 'removeItem'.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Either returns a rebuilt listing page as an AJAX response, or redirects
   *   back to the current page.
   */
  public function subqueueAjaxOperation(EntitySubqueueInterface $entity_subqueue, $op, Request $request) {
    $entity_id = $request->get('entity');
    $entity = $this->entityTypeManager()->getStorage($entity_subqueue->getQueue()->getTargetEntityTypeId())->load($entity_id);

    // Perform the operation.
    $entity_subqueue->$op($entity);

    // Run validation.
    $violations = $entity_subqueue->validate();

    // Save subqueue.
    if (count($violations) === 0) {
      $entity_subqueue->save();
    }

    // If the request is via AJAX, return the rendered list as JSON.
    if ($this->isAjax()) {
      $route_match = RouteMatch::createFromRequest($request);
      $content = $this->subqueueListForEntity($route_match, $entity->getEntityTypeId(), $entity);

      // Also display the validation errors if there are any.
      if (count($violations)) {
        $content['errors'] = [
          '#theme' => 'status_messages',
          '#message_list' => [
            'error' => [$this->t('The operation could not be performed for the following reasons:')]
          ],
          '#status_headings' => [
            'error' => $this->t('Error message'),
          ],
          '#weight' => -10,
        ];
        foreach ($violations as $violation) {
          $content['errors']['#message_list']['error'][] = $violation->getMessage();
        }
      }

      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('#entity-subqueue-list', $content));
      return $response;
    }

    // Otherwise, redirect back to the page.
    return $this->redirect('<current>');
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param string $entity_type_id
   *   (optional) The entity type ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, $entity_type_id = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $route_match->getParameter($entity_type_id);

    if ($entity && $this->entityQueueRepository->getAvailableQueuesForEntity($entity)) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
