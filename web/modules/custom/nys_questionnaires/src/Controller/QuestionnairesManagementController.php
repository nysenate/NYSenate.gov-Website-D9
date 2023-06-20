<?php

namespace Drupal\nys_questionnaires\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\nys_senators\SenatorsHelper;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manages AJAX callbacks for senator management questionnaires tab.
 */
class QuestionnairesManagementController extends ControllerBase {

  /**
   * NYS Senators Helper service.
   *
   * @var \Drupal\nys_senators\SenatorsHelper
   */
  protected SenatorsHelper $senatorsHelper;

  /**
   * Drupal's Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * Drupal's Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected Renderer $renderer;

  /**
   * Constructor.
   */
  public function __construct(Connection $connection, SenatorsHelper $senatorsHelper, Renderer $renderer) {
    $this->senatorsHelper = $senatorsHelper;
    $this->connection = $connection;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
          $container->get('database'),
          $container->get('nys_senators.senators_helper'),
          $container->get('renderer')
      );
  }

  /**
   * Gets a list of submissions for a senator's questionnaire.
   *
   * The context is always in the current user viewing the senator term passed
   * in $taxonomy_term.  The return will be all submissions, if the requested
   * questionnaire is owned by the senator.  Otherwise, only submissions from
   * the senator's district are returned.
   *
   * @return \Drupal\Core\Render\HtmlResponse
   *   A pre-rendered table of submissions.
   */
  public function ajaxGetSubmissions(Term $taxonomy_term, Node $qid): HtmlResponse {

    // Get the questionnaire owner.  If not, stop here.
    $owner = $qid->field_senator_multiref->entity ?? NULL;
    if (!$owner) {
      return $this->errorResponse("An error occurred while loading the owner.");
    }

    // If the senator is not the owner, set a filter for the senator's district.
    $district = $owner->id() == $taxonomy_term->id()
        ? NULL
        : $this->senatorsHelper->loadDistrict($taxonomy_term);

    // Build each row based on the query return.
    $rows = array_map(
          [$this, 'buildSubmissionRow'],
          $this->getSubmissions($qid, $district)
      );

    // Finish the render array, and render it.
    if (count($rows)) {
      $ret = [
        '#theme' => 'table',
        '#header' => ['User Name', 'Date of Submission', 'District'],
        '#rows' => $rows,
      ];
    }
    else {
      $ret = ['#markup' => 'Could not find any submissions'];
    }
    $ret = $this->renderer->renderPlain($ret);

    return new HtmlResponse($ret);
  }

  /**
   * Builds a row meant for a table's render array for submissions.
   *
   * @param array $row
   *   A row as returned from getSubmissions()
   */
  protected function buildSubmissionRow(array $row): array {
    // Get the link for the user.
    $url = Url::fromRoute('entity.user.edit_form', ['user' => $row['uid']]);
    $name = $row['first_name'] . ' ' . $row['last_name'];
    $name_link = Link::fromTextAndUrl($name, $url)->toString();

    // Get the link for the submission.
    $url = Url::fromRoute(
          'entity.webform.user.submission', [
            'webform' => $row['qid'],
            'webform_submission' => $row['sid'],
          ]
      );
    $sub_link = Link::fromTextAndUrl(date("Y-m-d H:i:s", $row['submitted']), $url)
      ->toString();

    // Build the row.
    return [
      ['data' => $name_link, 'class' => 'questionnaire-username'],
      ['data' => $sub_link, 'class' => 'questionnaire-submit-date'],
      ['data' => $row['district'], 'class' => 'questionnaire-district'],
    ];
  }

  /**
   * Returns an array of submissions for a questionnaire.
   *
   * @param \Drupal\node\Entity\Node $qid
   *   A node of type 'webform' (questionnaire).
   * @param \Drupal\taxonomy\Entity\Term|null $district
   *   The district to use as a filter on the return.  If populated, only
   *   submissions from this district will be included.
   *
   * @return array
   *   Each element is an array with these keys:
   *     qid: questionnaire's nid
   *     sid: submission's sid
   *     created: timestamp of submission
   *     uid: user's uid
   *     mail: user's email
   *     first_name: user's first name
   *     last_name: user's last name
   *     district: user's district number
   *     district_tid: term id of the user's district
   */
  protected function getSubmissions(Node $qid, ?Term $district): array {
    $webform = $qid->webform->target_id;
    $query = $this->connection->select('webform_submission', 'ws');
    $query->join('users_field_data', 'u', 'u.uid=ws.uid');
    $query->join('user__field_first_name', 'ufn', 'u.uid=ufn.entity_id');
    $query->join('user__field_last_name', 'uln', 'u.uid=uln.entity_id');
    $query->join('user__field_district', 'ud', 'u.uid=ud.entity_id');
    $query->join(
          'taxonomy_term__field_district_number', 'fdn',
          'ud.field_district_target_id=fdn.entity_id and fdn.bundle=:district',
          [':district' => 'districts']
      );

    // Fields to be returned.
    $query->addExpression($qid->id(), 'qid');
    $query->addField('ws', 'sid');
    $query->addField('ws', 'created', 'submitted');
    $query->addField('ws', 'uid');
    $query->addFIeld('u', 'mail');
    $query->addField('ufn', 'field_first_name_value', 'first_name');
    $query->addField('uln', 'field_last_name_value', 'last_name');
    $query->addField('fdn', 'field_district_number_value', 'district');
    $query->addField('ud', 'field_district_target_id', 'district_tid');

    // Use the district filter, if it was passed.
    if ($district) {
      $query->condition('fdn.entity_id', $district->id());
    }
    $query->condition('ws.webform_id', $webform)
      ->orderBy('ws.created');

    // Fetch and return.
    try {
      $ret = $query->execute()->fetchAllAssoc('sid', \PDO::FETCH_ASSOC);
    }
    catch (\Throwable $e) {
      $this->getLogger('nys_questionnaires')->error(
            "Failed to query for questionnaire submissions",
            [
              '@query' => (string) $query,
              '@qid' => $qid->id(),
              '@district' => $district ? $district->id() : 'none',
              '@excp' => $e->getMessage(),
            ]
        );
      $ret = [];
    }

    return $ret;
  }

  /**
   * Generates an error response for a failed AJAX operation.
   */
  protected function errorResponse(string $msg = ''): HtmlResponse {
    if (!$msg) {
      $msg = 'An error occurred while loading this questionnaire.';
    }
    return new HtmlResponse($msg, 500);
  }

}
