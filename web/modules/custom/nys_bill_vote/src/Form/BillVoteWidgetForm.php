<?php

use Drupal\nys_bill_vote\BillVoteHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Url;

/**
 * The Bill Vote Widget form class.
 */
class BillVoteWidgetForm extends FormBase {

  /**
   * The BillVoteHelper class variable.
   *
   * @var \Drupal\nys_bill_vote\BillVoteHelper
   */
  protected $billVoteHelper;

  /**
   * Default object for current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(BillVoteHelper $bill_vote_helper, AccountProxy $current_user) {
    $this->billVoteHelper = $bill_vote_helper;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nys_bill_vote.bill_vote'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nys_bill_vote_widget';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $parameter = []) {
    // In this special case, just leave.
    // @todo This method comes from nys_utils.
    if (senator_viewing_constituent_dashboard()) {
      return $form;
    }

    // Detect the build settings.
    $form_state->setBuildInfo($this->billVoteHelper->widgetBuildSettings($form_state));

    // Copy the build settings into $form_state['settings'].
    // Anything detected in build_info should take precedence.
    $settings = $form_state['settings'] ?? [];
    $form_state['settings'] = array_merge($settings, $form_state->getBuildInfo());

    // Now get the canonical information.
    $node = $form_state->getFormObject();
    $node_type = $node->getType();
    $node_id = $node->id();

    // Discover if a vote already exists.
    // @todo Port this method.
    $default_vote = nys_bill_vote_get_default($node_type, $node_id);
    $default_value = nys_bill_vote_get_val($default_vote);

    // Discover if a vote has been submitted.
    if (!empty($form_state->getValue('nys_bill_vote'))) {
      $default_value = $form_state->getValue('nys_bill_vote');
    }

    // Add the distinct class.
    $form['#attributes'] = [
      'class' => [
        'nys-bill-vote-form',
      ],
    ];

    // @todo Port this D9 style.
    $form['#id'] = 'nys-bill-vote-vote-widget-' . $node_id;

    $label = $this->billVoteHelper->getVotedLabel($default_value);

    // The main form.
    $form['nys_bill_vote_container'] = [
      // Main form attributes.
      '#type' => 'container',
      '#attributes' => [
        'class' => ['nys-bill-vote'],
      ],
      '#id' => 'edit-nys-bill-vote-container-' . $node_id,

      // Primary label.
      'nys_bill_vote_label' => [
        '#markup' => '<p class="c-bill-polling--cta">' . $label . '</p>',
      ],

      // The "Aye" button.
      'nys_bill_vote_yes' => [
        '#type' => 'button',
        '#attributes' => [
          'class' => [
            'c-block--btn',
            'c-half-btn',
            'c-half-btn--left',
            'nys-bill-vote-yes',
          ],
          'value' => 'yes',
          'type' => 'submit',
        ],
        '#id' => 'edit-nys-bill-vote-yes-' . $node_id,
        '#value' => 'Aye',
        '#ajax' => [
          'callback' => [$this, 'voteAjaxCallback'],
          'event' => 'click',
        ],
      ],

      // The "Nay" button.
      'nys_bill_vote_no' => [
        '#type' => 'button',
        '#attributes' => [
          'class' => [
            'c-block--btn',
            'c-half-btn',
            'c-half-btn--right',
            'nys-bill-vote-no',
          ],
          'value' => 'no',
          'type' => 'submit',
        ],
        '#id' => 'edit-nys-bill-vote-no-' . $node_id,
        '#value' => 'Nay',
        '#ajax' => [
          'callback' => [$this, 'voteAjaxCallback'],
          'event' => 'click',
        ],
      ],
      '#attached' => [
        'library' => [
          'nys_bill_vote/nys_bill_vote',
        ],
      ],
    ];

    return $form;
  }

  /**
   * AJAX Callback function for the buttons.
   */
  public function voteAjaxCallback(&$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $node = $form_state->getFormObject();
    $value = $form_state->getTriggeringElement()['#value'];

    // We want to process the vote if the user is logged in.
    if ($this->currentUser->isAuthenticated()) {
      $this->billVoteHelper->processVote($node->getType(), $node->id(), $value);
      // @todo Port these methods.
      $form['nys_bill_vote']['#default_value'] = $this->billVoteHelper->getVal(nys_bill_vote_get_default($node->getType(), $node->id(), TRUE));
      $form['nys_bill_vote']['#options'] = $this->billVoteHelper->getOptions();
    }

    // If the user is on a page that isn't the bill node, send them there.
    $test_action = trim(parse_url($form['#action'])['path'], '/');
    // @todo Change this to D9 method.
    $node_match = drupal_get_normal_path($test_action);
    $bill_path = 'node/' . $node->id();

    if ($node_match != $bill_path) {
      $options = [];
      // Only attach the value if the user is anonymous, otherwise it is
      // processed above.
      if (!$this->currentUser->isAuthenticated()) {
        $options['query'] = [
          'intent' => $this->billVoteHelper->getIntentFromVote($value),
        ];
      }
      $url = Url::fromUserInput($bill_path, $options);
      $command = new RedirectCommand($url->toString());
      $response->addCommand($command);

      return $response;
    }

    return [
      '#type' => 'ajax',
      '#commands' => [
        [
          'command' => 'nysBillVoteUpdate',
          'vote_label' => $this->billVoteHelper->getVotedLabel($value),
          'vote_value' => $value,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo This method comes from nys_accumulator custom module.
    nyslog();
    $node = $form_state->getFormObject();
    $element = $form_state->getTriggeringElement();
    $this->billVoteHelper->processVote($node->getType(), $node->id(), $element['value']);
  }

}
