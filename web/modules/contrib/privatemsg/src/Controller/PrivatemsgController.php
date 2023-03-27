<?php

namespace Drupal\privatemsg\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\privatemsg\PrivateMsgService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Private messages routes.
 */
class PrivatemsgController extends ControllerBase {

  /**
   * The database connection.
   */
  protected Connection $connection;

  /**
   * Common functions.
   */
  protected PrivateMsgService $privateMsgService;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection, PrivateMsgService $privatemsg_service) {
    $this->connection = $connection;
    $this->privateMsgService = $privatemsg_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('privatemsg.common')
    );
  }

  /**
   * Remove message.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\Response
   *   Response.
   */
  public function removeMessage(Request $request) {
    $thread_id = $request->attributes->get('thread_id');
    $mid = $request->attributes->get('mid');

    if (!$thread_id || !$mid) {
      $response = new Response();
      $response->setContent('Incorrect request.');
      $response->setStatusCode(400);
      return $response;
    }

    $query = $this->connection->delete('pm_message');
    $query->condition('mid', $mid);
    $query->execute();

    $query = $this->connection->delete('pm_index');
    $query->condition('mid', $mid);
    $query->execute();

    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand('/messages/view/' . $thread_id));
    return $response;
  }

}
