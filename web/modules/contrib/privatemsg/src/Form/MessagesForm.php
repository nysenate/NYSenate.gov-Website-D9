<?php

namespace Drupal\privatemsg\Form;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\privatemsg\PrivateMsgService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Private messages form.
 */
class MessagesForm extends FormBase {

  /**
   * Common functions.
   */
  protected PrivateMsgService $privateMsgService;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The Date Formatter.
   */
  protected DateFormatter $dateFormatter;

  /**
   * The pager manager.
   */
  protected PagerManagerInterface $pagerManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(PrivateMsgService $privatemsg_service, EntityTypeManagerInterface $entity_type_manager, DateFormatter $date_formatter, PagerManagerInterface $pager_manager) {
    $this->privateMsgService = $privatemsg_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->pagerManager = $pager_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('privatemsg.common'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('pager.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'privatemsg_messages';
  }

  /**
   * Returns pager array.
   */
  public function pagerArray(array $items, int $itemsPerPage): ?array {
    $total = \count($items);
    $currentPage = $this->pagerManager->createPager($total, $itemsPerPage)->getCurrentPage();
    $chunks = \array_chunk($items, $itemsPerPage);
    $currentPageItemsRaw = $chunks[$currentPage];

    foreach ($currentPageItemsRaw as $currentPageItem) {
      $currentPageItems[$currentPageItem['thread_id']] = $currentPageItem;
    }

    return $currentPageItems;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = $this->getRequest();
    $uid = $request->attributes->get('user');

    if ($uid) {
      $current_user = $this->entityTypeManager->getStorage('user')->load($uid);
    }
    else {
      $current_user = $this->currentUser();
    }

    $header = [
      'subject' => $this->t('Subject'),
      'count' => $this->t('Messages count'),
      'participants' => $this->t('Participants'),
      'last_updated' => $this->t('Last Updated'),
    ];

    $options = [];

    $threads = $this->privateMsgService->getThreadsByUserId($current_user->id());

    if ($threads) {
      $threads = $this->pagerArray($threads, 10);
    }

    foreach ($threads as $thread) {
      $participant_id = $this->privateMsgService->getThreadParticipantByThreadId($thread['thread_id'], $current_user->id());
      $participant = $this->entityTypeManager->getStorage('user')->load($participant_id);
      $participant_name = '';
      if (!empty($participant->name)) {
        $participant_name = $participant->name->value;
      }
      $mids = $this->privateMsgService->getMessagesIdsByThreadId($thread['thread_id']);
      $count = \count($mids);

      $subject = '<a href="/messages/view/' . $thread['thread_id'] . '">' . $thread['subject'] . '</a>';

      if (isset($thread['is_new'])) {
        $is_new = $thread['is_new'];

        if ($is_new) {
          $subject .= ' <span class="marker">new</span>';
        }
      }

      $participants = '<a href="/user/' . $participant_id . '">' . $participant_name . '</a>';

      $options[$thread['thread_id']] = [
        'thread_id' => $thread['thread_id'],
        'subject' => Markup::create($subject),
        'count' => $count,
        'participants' => Markup::create($participants),
        'last_updated' => $this->dateFormatter->format($thread['timestamp'], 'medium'),
      ];
    }

    $form['new_message'] = [
      '#type' => 'item',
      '#markup' => '<a href="/messages/new">' . $this->t('Write new message') . '</a>',
    ];

    if ($current_user->hasPermission('privatemsg use messages actions')) {
      $form['select_actions'] = [
        '#type' => 'select',
        '#options' => [
          0 => $this->t('Actions...'),
          1 => $this->t('Delete'),
          2 => $this->t('Mark as read'),
          3 => $this->t('Mark as unread'),
        ],
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Perform an action'),
      ];
    }

    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No messages available.'),
      '#attached' => [
        'library' => [
          'privatemsg/privatemsg',
        ],
      ],
    ];

    $form['pager'] = [
      '#type' => 'pager',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $table = \array_filter($form_state->getValue('table'));

    if (!$table) {
      $form_state->setErrorByName('table', $this->t('You must first select one (or more) messages before you can take that action.'));
    }

    if ($form_state->getValue('select_actions') == 0) {
      $form_state->setErrorByName('select_actions', $this->t('You must first select an action.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = $this->currentUser();
    $table = \array_filter($form_state->getValue('table'));
    $action = $form_state->getValue('select_actions');

    // Delete.
    if ($action == 1) {
      foreach ($table as $thread_id) {
        $this->privateMsgService->deleteThread($thread_id);
      }
    }

    // Mark as read.
    if ($action == 2) {
      foreach ($table as $thread_id) {
        $this->privateMsgService->markThreadAsReadForUser($thread_id, $current_user->id());
      }
    }

    // Mark as unread.
    if ($action == 3) {
      foreach ($table as $thread_id) {
        $this->privateMsgService->markThreadAsUnreadForUser($thread_id, $current_user->id());
      }
    }
  }

}
