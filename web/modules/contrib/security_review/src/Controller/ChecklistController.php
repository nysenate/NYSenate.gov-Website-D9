<?php

namespace Drupal\security_review\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\security_review\Checklist;
use Drupal\security_review\SecurityReview;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The class of the 'Run & Review' page's controller.
 */
class ChecklistController extends ControllerBase {

  /**
   * The CSRF Token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   */
  protected $csrfToken;

  /**
   * The security_review.checklist service.
   *
   * @var \Drupal\security_review\Checklist
   */
  protected $checklist;

  /**
   * The security_review service.
   *
   * @var \Drupal\security_review\SecurityReview
   */
  protected $securityReview;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a ChecklistController.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token_generator
   *   The CSRF Token generator.
   * @param \Drupal\security_review\SecurityReview $security_review
   *   The security_review service.
   * @param \Drupal\security_review\Checklist $checklist
   *   The security_review.checklist service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(CsrfTokenGenerator $csrf_token_generator, SecurityReview $security_review, Checklist $checklist, MessengerInterface $messenger) {
    $this->csrfToken = $csrf_token_generator;
    $this->checklist = $checklist;
    $this->securityReview = $security_review;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('csrf_token'),
      $container->get('security_review'),
      $container->get('security_review.checklist'),
      $container->get('messenger')
    );
  }

  /**
   * Creates the Run & Review page.
   *
   * @return array
   *   The 'Run & Review' page's render array.
   */
  public function index() {
    $run_form = [];

    // If the user has the required permissions, show the RunForm.
    if ($this->currentUser()->hasPermission('run security checks')) {
      // Get the Run form.
      $run_form = $this->formBuilder()
        ->getForm('Drupal\security_review\Form\RunForm');

      // Close the Run form if there are results.
      if ($this->securityReview->getLastRun() > 0) {
        $run_form['run_form']['#open'] = FALSE;
      }
    }

    // Print the results if any.
    if ($this->securityReview->getLastRun() <= 0) {
      // If they haven't configured the site, prompt them to do so.
      if (!$this->securityReview->isConfigured()) {
        $this->messenger->addWarning($this->t('It appears this is your first time using the Security Review checklist. Before running the checklist please review the settings page at <a href=":url">admin/reports/security-review/settings</a> to set which roles are untrusted.',
          [':url' => Url::fromRoute('security_review.settings')->toString()]
        ), 'warning');
      }
    }

    return [$run_form, $this->results()];
  }

  /**
   * Creates the results' table.
   *
   * @return array
   *   The render array for the result table.
   */
  public function results() {
    // If there are no results return.
    if ($this->securityReview->getLastRun() <= 0) {
      return [];
    }

    $checks = [];
    foreach ($this->checklist->getChecks() as $check) {
      // Initialize with defaults.
      $check_info = [
        'message' => $this->t(
          'The check "@name" hasn\'t been run yet.',
          ['@name' => $check->getTitle()]
        ),
        'skipped' => $check->isSkipped(),
      ];

      // Get last result.
      $last_result = $check->lastResult();
      if ($last_result != NULL) {
        if (!$last_result->isVisible()) {
          continue;
        }
        $check_info['result'] = $last_result->result();
        $check_info['message'] = $last_result->resultMessage();
      }

      // Determine help link.
      $check_info['help_link'] = Link::createFromRoute(
        'Details',
        'security_review.help',
        [
          'namespace' => $check->getMachineNamespace(),
          'title' => $check->getMachineTitle(),
        ]
      );

      // Add toggle button.
      $toggle_text = $check->isSkipped() ? 'Enable' : 'Skip';
      $check_info['toggle_link'] = Link::createFromRoute($toggle_text,
        'security_review.toggle',
        ['check_id' => $check->id()],
        ['query' => ['token' => $this->csrfToken->get($check->id())]]
      );

      // Add to array of completed checks.
      $checks[] = $check_info;
    }

    return [
      '#theme' => 'run_and_review',
      '#date' => $this->securityReview->getLastRun(),
      '#checks' => $checks,
      '#attached' => [
        'library' => ['security_review/run_and_review'],
      ],
    ];
  }

}
