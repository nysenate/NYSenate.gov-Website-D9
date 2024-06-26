<?php

namespace Drupal\nys_dashboard\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\flag\FlagService;
use Drupal\nys_bills\BillsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to manage a user's subscribed issues, bills, or committees.
 */
class ManageDashboardForm extends FormBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  public EntityTypeManager $entityTypeManager;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  public AccountProxy $currentUser;

  /**
   * Flag service.
   *
   * @var \Drupal\flag\FlagService
   */
  public FlagService $flagService;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  public Renderer $renderer;

  /**
   * Mapping of field names to flag types.
   *
   * @var array
   */
  public array $fieldNamesToFlagTypes = [
    'bills' => 'follow_this_bill',
    'issues' => 'follow_issue',
    'committees' => 'follow_committee',
  ];

  /**
   * Mapping of field names to browse more URLs.
   *
   * @var array
   */
  public array $fieldNamesToBrowseMoreLink = [
    'bills' => '/legislation',
    'issues' => '/explore-issues',
    'committees' => '/senators-committees',
  ];

  /**
   * NYS Bills Helper service.
   *
   * @var \Drupal\nys_bills\BillsHelper
   */
  protected BillsHelper $billsHelper;

  /**
   * Constructs a ManageDashboardForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   Current user service.
   * @param \Drupal\flag\FlagService $flagService
   *   Flag service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer service.
   * @param \Drupal\nys_bills\BillsHelper $billsHelper
   *   NYS Bills Helper service.
   */
  public function __construct(
    EntityTypeManager $entityTypeManager,
    AccountProxy $currentUser,
    FlagService $flagService,
    Renderer $renderer,
    BillsHelper $billsHelper,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->flagService = $flagService;
    $this->renderer = $renderer;
    $this->billsHelper = $billsHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ManageDashboardForm|static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('flag'),
      $container->get('renderer'),
      $container->get('nys_bill.bills_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'nys_dashboard_manage_dashboard';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Confirmation form.
    if (
      $form_state->has('is_confirmation_step')
      && $form_state->get('is_confirmation_step') === TRUE
    ) {
      return $this->buildConfirmationForm($form, $form_state);
    }

    // Main form.
    $form_state->set('is_confirmation_step', FALSE);
    $form['#attached']['library'][] = 'core/jquery';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'core/jquery.form';
    $form['#attached']['library'][] = 'nysenate_theme/manage-dashboard';
    $form['type_filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter type'),
      '#options' => [
        'All' => $this->t('- Any -'),
        'bills' => $this->t('Bills'),
        'issues' => $this->t('Issues'),
        'committees' => $this->t('Committees'),
      ],
      '#ajax' => [
        'callback' => [$this, 'applyFormFilter'],
        'wrapper' => 'followed-types-fieldset-wrapper',
      ],
    ];
    $form['followed_types_fieldset'] = [
      '#type' => 'fieldset',
      '#prefix' => '<div id="followed-types-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    foreach ($this->fieldNamesToFlagTypes as $field_name => $flag_type) {
      $options = $this->getUsersFlaggedEntitiesByFlagName($flag_type);
      $browse_more_link = "<a href='{$this->fieldNamesToBrowseMoreLink[$field_name]}'>Browse $field_name</a>";
      $help_text = empty($options)
        ? "You're not following any $field_name. $browse_more_link."
        : "To stop following, uncheck $field_name and click Update My Preferences.";
      $help_text_class = empty($options)
        ? 'manage-dashboard--not-following'
        : '';
      $uncheck_all_button_label = !empty($options)
        ? "Uncheck all $field_name"
        : '';
      $form['followed_types_fieldset'][$field_name] = [
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => ucfirst($field_name) . " You're Following",
          '#weight' => -3,
        ],
        'help_text' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $help_text,
          '#weight' => -2,
          '#attributes' => ['class' => $help_text_class],
        ],
        'uncheck_all_button' => [
          '#type' => 'html_tag',
          '#tag' => 'button',
          '#attributes' => [
            'class' => 'uncheck-all-button',
            'type' => 'button',
          ],
          '#value' => $uncheck_all_button_label,
          '#weight' => -1,
        ],
        '#type' => 'checkboxes',
        '#options' => $options,
        '#default_value' => array_keys($options),
        '#weight' => 0,
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update my preferences'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::confirmFormModal',
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Populate $form_state 'content_to_unfollow'.
    $content_to_unfollow = [];
    $user_input = $form_state->getUserInput();
    if (!empty($user_input)) {
      foreach ($this->fieldNamesToFlagTypes as $field_name => $flag_type) {
        // Break loop if user input empty, or if input doesn't match
        // type_filter.
        if (
          empty($user_input[$field_name])
          || $user_input['type_filter'] !== 'All' && $user_input['type_filter'] !== $field_name
        ) {
          continue;
        }

        $user_unchecked_entity_ids = array_diff(
          array_keys($form['followed_types_fieldset'][$field_name]['#options']),
          array_values($user_input[$field_name])
        );
        foreach ($user_unchecked_entity_ids as $flagged_entity_id) {
          $content_to_unfollow[] = [
            'flag_type' => $flag_type,
            'flagged_entity_id' => $flagged_entity_id,
            'field_name' => $field_name,
          ];
        }
      }
    }
    $form_state->set('content_to_unfollow', $content_to_unfollow);

    // Populate $form_state '$unfollow_content_list'.
    $unfollow_content_list = [];
    foreach ($content_to_unfollow as $unfollow_content) {
      $flagged_entity_type_id = ($unfollow_content['flag_type'] !== 'follow_this_bill') ? 'taxonomy_term' : 'node';
      try {
        $flagged_entity = $this->entityTypeManager
          ->getStorage($flagged_entity_type_id)
          ->load($unfollow_content['flagged_entity_id']);
        $content_type = substr($unfollow_content['field_name'], 0, -1);
        $unfollow_content_list[] = $content_type . ': ' . $flagged_entity->label();
      }
      catch (\Throwable) {
      }
    }
    $form_state->set('unfollow_content_list', $unfollow_content_list);

    // Put form into confirmation step and trigger rebuild.
    $form_state->set('is_confirmation_step', TRUE);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback to inject re-rendered fieldset form element.
   */
  public function applyFormFilter(array &$form, FormStateInterface $form_state): array {
    $type_filter = $form_state->getValue('type_filter');

    foreach ($this->fieldNamesToFlagTypes as $field_name => $flag_type) {
      // Ensure ajax re-rendered fields default to checked.
      foreach ($form['followed_types_fieldset'][$field_name]['#default_value'] as $flagged_entity_id) {
        $form['followed_types_fieldset'][$field_name][$flagged_entity_id]['#checked'] = TRUE;
      }
      // Disable access to all but selected follow types.
      if (!empty($type_filter) && $type_filter !== 'All') {
        if ($field_name == $type_filter) {
          continue;
        }
        $form['followed_types_fieldset'][$field_name]['#access'] = FALSE;
      }
    }

    return $form['followed_types_fieldset'];
  }

  /**
   * Ajax callback to render form in confirm step as pop-up modal.
   */
  public function confirmFormModal(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $url = new Url('nys_dashboard.manage_dashboard');
    if (empty($form_state->get('content_to_unfollow'))) {
      $response->addCommand(new RedirectCommand($url->toString()));
      $this->messenger()->addWarning('Nothing chosen to unfollow.');
      return $response;
    }
    $form = $this->buildForm($form, $form_state);
    // See http://api.jqueryui.com/dialog.
    $dialog_options = [
      'width' => 726,
    ];
    $response->addCommand(new OpenModalDialogCommand('', $form, $dialog_options));
    return $response;
  }

  /**
   * Gets the current user's flagged entities by the flag machine name.
   *
   * @param string $flag_machine_name
   *   The flag's machine name.
   *
   * @return array
   *   An array of entity ID keys and entity label values.
   */
  public function getUsersFlaggedEntitiesByFlagName(string $flag_machine_name): array {
    try {
      $flag_ids = $this->entityTypeManager
        ->getStorage('flagging')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('flag_id', $flag_machine_name)
        ->condition('uid', $this->currentUser->id())
        ->execute();
    }
    catch (\Throwable) {
      $flag_ids = [];
    }

    $entity_ids_and_labels = [];
    if (!empty($flag_ids)) {
      try {
        $flags = $this->entityTypeManager
          ->getStorage('flagging')
          ->loadMultiple($flag_ids);
      }
      catch (\Throwable) {
        $flags = [];
      }
      foreach ($flags as $flag) {
        $entity_id = $flag->flagged_entity?->referencedEntities()[0]?->id();
        $entity_label = $flag->flagged_entity?->referencedEntities()[0]?->label();
        if (!empty($entity_id) && !empty($entity_label)) {
          $entity_ids_and_labels[$entity_id] = $entity_label;
        }
      }
    }

    return $entity_ids_and_labels;
  }

  /**
   * Builds confirmation form.
   */
  public function buildConfirmationForm(array &$form, FormStateInterface $form_state): array {
    $title = $this->t('Are you sure you want to unfollow these topics?');
    $form['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $title,
    ];
    $help_text = <<<TEXT
      Clicking “Yes” will unfollow and remove all posts under the selected
      topic(s) from your dashboard feed.
      TEXT;
    $form['help_text'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $help_text,
    ];
    $form['unfollow_list'] = [
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#items' => $form_state->get('unfollow_content_list'),
    ];
    $form['actions']['confirm'] = [
      '#type' => 'submit',
      '#value' => $this->t('Yes, unfollow my selection'),
      '#submit' => ['::unfollowTopics'],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('No, cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => new Url('nys_dashboard.manage_dashboard'),
    ];
    return $form;
  }

  /**
   * Confirmation form submit handler.
   */
  public function unfollowTopics(array &$form, FormStateInterface $form_state): void {
    $content_to_unfollow = $form_state->get('content_to_unfollow');
    foreach ($content_to_unfollow as $unfollow_content) {
      $flag = $this->flagService->getFlagById($unfollow_content['flag_type']);
      $flagged_entity_type_id = ($unfollow_content['flag_type'] !== 'follow_this_bill') ? 'taxonomy_term' : 'node';

      try {
        // Load the flagged entity.
        /** @var \Drupal\Core\Entity\ContentEntityBase $flagged_entity */
        $flagged_entity = $this->entityTypeManager
          ->getStorage($flagged_entity_type_id)
          ->load($unfollow_content['flagged_entity_id']);

        // Remove the entity's flag.
        $this->flagService->unflag($flag, $flagged_entity, $this->currentUser);

        // If this item is a bill, cancel the nys_subscriptions record.
        if ($unfollow_content['flag_type'] === 'follow_this_bill') {
          $this->billsHelper->findSubscription($flagged_entity, $this->currentUser)
            ?->cancel()
            ?->save();
        }
      }
      catch (\Throwable) {
        $this->messenger()->addError('There was an error unfollowing '
          . $flagged_entity->label() . '. Please try again later.');
        continue;
      }
    }

    $unfollow_content_render_array = [
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#items' => $form_state->get('unfollow_content_list'),
    ];
    try {
      $unfollow_content_ul = $this->renderer->render($unfollow_content_render_array);
      $message = 'Successfully unfollowed the followed content:' . $unfollow_content_ul;
    }
    catch (\Throwable) {
      $message = 'An error occurred while unsubscribing.  Please try again later.';
    }
    $rendered_message = Markup::create($message);
    $this->messenger()
      ->addStatus($this->t('@rendered_message', ['@rendered_message' => $rendered_message]));
  }

}
