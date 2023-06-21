<?php

namespace Drupal\nys_comment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller class for nys_comment module.
 */
class CommentController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Default object for entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The constructor method.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager class.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('entity_type.manager'),
      );
  }

  /**
   * Publishes the specified comment.
   *
   * @param int $comment
   *   A comment entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function banUser(int $comment) {
    $comment = $this->entityTypeManager->getStorage('comment')
      ->load($comment);

    $uid = $comment->uid->target_id;
    $user = $this->entityTypeManager->getStorage('user')
      ->load($uid);

    $user->set('field_user_banned_comments', 1);
    $user->save();

    $this->messenger()->addStatus($this->t('User banned.'));
    $permalink_uri = $comment->permalink();
    $permalink_uri->setAbsolute();
    return new RedirectResponse($permalink_uri->toString());
  }

  /**
   * Rejects the specified comment.
   *
   * @param int $comment
   *   A comment entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function reject(int $comment) {
    $comment = $this->entityTypeManager->getStorage('comment')
      ->load($comment);
    $comment->field_rejected = TRUE;
    $comment->save();

    $this->messenger()->addStatus($this->t('Comment rejected.'));
    $permalink_uri = $comment->permalink();
    $permalink_uri->setAbsolute();
    return new RedirectResponse($permalink_uri->toString());
  }

}
