<?php

namespace Drupal\security_review\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Link;
use Drupal\security_review\Checklist;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityReview;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The class of the Help pages' controller.
 */
class HelpController extends ControllerBase {

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
   * The date.formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private $dateFormatter;

  /**
   * Constructs a HelpController.
   *
   * @param \Drupal\security_review\SecurityReview $security_review
   *   The security_review service.
   * @param \Drupal\security_review\Checklist $checklist
   *   The security_review.checklist service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date.formatter service.
   */
  public function __construct(SecurityReview $security_review, Checklist $checklist, DateFormatterInterface $dateFormatter) {
    // Store the dependencies.
    $this->checklist = $checklist;
    $this->securityReview = $security_review;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('security_review'),
      $container->get('security_review.checklist'),
      $container->get('date.formatter')
    );
  }

  /**
   * Serves as an entry point for the help pages.
   *
   * @param string|NULL $namespace
   *   The namespace of the check (null if general page).
   * @param string $title
   *   The name of the check.
   *
   * @return array
   *   The requested help page.
   */
  public function index($namespace, $title) {
    // If no namespace is set, print the general help page.
    if ($namespace === NULL) {
      return $this->generalHelp();
    }

    // Print check-specific help.
    return $this->checkHelp($namespace, $title);
  }

  /**
   * Returns the general help page.
   *
   * @return array
   *   The general help page.
   */
  private function generalHelp() {
    $paragraphs = [];

    // Print the general help.
    $paragraphs[] = $this->t('You should take the security of your site very seriously. Fortunately, Drupal is fairly secure by default. The Security Review module automates many of the easy-to-make mistakes that render your site insecure, however it does not automatically make your site impenetrable. You should give care to what modules you install and how you configure your site and server. Be mindful of who visits your site and what features you expose for their use.');
    $paragraphs[] = $this->t('You can read more about securing your site in the <a href="http://drupal.org/security/secure-configuration">drupal.org handbooks</a> and on <a href="http://crackingdrupal.com">CrackingDrupal.com</a>. There are also additional modules you can install to secure or protect your site. Be aware though that the more modules you have running on your site the greater (usually) attack area you expose.');
    $paragraphs[] = $this->t('<a href="http://drupal.org/node/382752">Drupal.org Handbook: Introduction to security-related contrib modules</a>');

    // Print the list of security checks with links to their help pages.
    $checks = [];
    foreach ($this->checklist->getChecks() as $check) {
      // Get the namespace array's reference.
      $check_namespace = &$checks[$check->getMachineNamespace()];

      // Set up the namespace array if not set.
      if (!isset($check_namespace)) {
        $check_namespace['namespace'] = $check->getNamespace();
        $check_namespace['check_links'] = [];
      }

      // Add the link pointing to the check-specific help.
      $check_namespace['check_links'][] = Link::createFromRoute(
        $this->t('@title', ['@title' => $check->getTitle()]),
        'security_review.help',
        [
          'namespace' => $check->getMachineNamespace(),
          'title' => $check->getMachineTitle(),
        ]
      );
    }

    return [
      '#theme' => 'general_help',
      '#paragraphs' => $paragraphs,
      '#checks' => $checks,
    ];
  }

  /**
   * Returns a check-specific help page.
   *
   * @param string $namespace
   *   The namespace of the check.
   * @param string $title
   *   The name of the check.
   *
   * @return array
   *   The check's help page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the check is not found.
   */
  private function checkHelp($namespace, $title) {
    // Get the requested check.
    $check = $this->checklist->getCheck($namespace, $title);

    // If the check doesn't exist, throw 404.
    if ($check == NULL) {
      throw new NotFoundHttpException();
    }

    // Print the help page.
    $output = [];
    $output[] = $check->help();

    // If the check is skipped print the skip message, else print the
    // evaluation.
    if ($check->isSkipped()) {

      if ($check->skippedBy() != NULL) {
        $user_object = $check->skippedBy();
        $user = $user_object->toLink()->toString();
      }
      else {
        $user = 'Anonymous';
      }

      $skip_message = $this->t(
        'Check marked for skipping on @date by @user',
        [
          '@date' => $this->dateFormatter->format($check->skippedOn()),
          '@user' => $user,
        ]
      );

      $output[] = [
        '#type' => 'markup',
        '#markup' => "<p>$skip_message</p>",
      ];
    }
    else {
      // Evaluate last result, if any.
      $last_result = $check->lastResult(TRUE);
      if ($last_result instanceof CheckResult) {
        // Separator.
        $output[] = [
          '#type' => 'markup',
          '#markup' => '<div />',
        ];

        // Evaluation page.
        $output[] = $check->evaluate($last_result);
      }
    }

    // Return the completed page.
    return $output;
  }

}
