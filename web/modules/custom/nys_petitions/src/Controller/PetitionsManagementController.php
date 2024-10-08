<?php

namespace Drupal\nys_petitions\Controller;

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
 * Manages AJAX callbacks for senator management petitions tab.
 */
class PetitionsManagementController extends ControllerBase {

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
   * Gets a list of signatures for a senator's questionnaire.
   *
   * The context is always in the current user viewing the senator term passed
   * in $taxonomy_term.  The return will be all signatures, if the requested
   * petition is owned by the senator.  Otherwise, only signatures from the
   * senator's district are returned.
   *
   * @return \Drupal\Core\Render\HtmlResponse
   *   A pre-rendered table of submissions.
   */
  public function ajaxGetSignatories(Term $taxonomy_term, Node $node): HtmlResponse {

    // Get the questionnaire owner.  If not, stop here.
    $owner = $node->field_senator_multiref->entity ?? NULL;
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
          $this->getSignatories($node, $district)
      );

    // Finish the render array, and render it.
    if (count($rows)) {
      $ret = [
        '#theme' => 'table',
        '#header' => ['User Name', 'Signing Date', 'District'],
        '#rows' => $rows,
      ];
    }
    else {
      $ret = ['#markup' => 'Could not find any signatures'];
    }
    $ret = $this->renderer->renderInIsolation($ret);

    return new HtmlResponse($ret);
  }

  /**
   * Builds a row meant for a table's render array for petitions.
   *
   * @param array $row
   *   A row as returned from getSubmissions()
   */
  protected function buildSubmissionRow(array $row): array {
    // Get the link for the user.
    $url = Url::fromRoute('entity.user.edit_form', ['user' => $row['uid']]);
    $name = $row['first_name'] . ' ' . $row['last_name'];
    $name_link = Link::fromTextAndUrl($name, $url)->toString();

    // Build the row.
    return [
      ['data' => $name_link, 'class' => 'signature-username'],
      ['data' => date("Y-m-d", $row['created']), 'class' => 'signature-date'],
      ['data' => $row['district'], 'class' => 'signature-district'],
    ];
  }

  /**
   * Returns an array of signatures for a petition.
   *
   * @param \Drupal\node\Entity\Node $node
   *   A node of type 'petition'.
   * @param \Drupal\taxonomy\Entity\Term|null $district
   *   The district to use as a filter on the return.  If populated, only
   *   submissions from this district will be included.
   *
   * @return array
   *   Each element is an array with these keys:
   *     nid: petition's nid
   *     created: timestamp of signature
   *     uid: user's uid
   *     mail: user's email
   *     first_name: user's first name
   *     last_name: user's last name
   *     district: user's district number
   *     district_tid: term id of the user's district
   */
  protected function getSignatories(Node $node, ?Term $district): array {
    $query = $this->connection->select('flagging', 'f');
    $query->join('users_field_data', 'u', 'u.uid=f.uid');
    $query->join('user__field_first_name', 'ufn', 'u.uid=ufn.entity_id');
    $query->join('user__field_last_name', 'uln', 'u.uid=uln.entity_id');
    $query->join('user__field_district', 'ud', 'u.uid=ud.entity_id');
    $query->join(
          'taxonomy_term__field_district_number', 'fdn',
          'ud.field_district_target_id=fdn.entity_id and fdn.bundle=:district',
          [':district' => 'districts']
      );

    // Fields to be returned.
    $query->addExpression($node->id(), 'nid');
    $query->addField('f', 'id');
    $query->addField('f', 'created');
    $query->addField('f', 'uid');
    $query->addField('u', 'mail');
    $query->addField('ufn', 'field_first_name_value', 'first_name');
    $query->addField('uln', 'field_last_name_value', 'last_name');
    $query->addField('fdn', 'field_district_number_value', 'district');
    $query->addField('ud', 'field_district_target_id', 'district_tid');

    // Use the district filter, if it was passed.
    if ($district) {
      $query->condition('fdn.entity_id', $district->id());
    }
    $query->condition('f.flag_id', 'sign_petition')
      ->condition('f.entity_type', 'node')
      ->condition('f.entity_id', $node->id())
      ->orderBy('f.created');

    // Fetch and return.
    try {
      $ret = $query->execute()->fetchAllAssoc('id', \PDO::FETCH_ASSOC);
      $this->getLogger('nys_petitions')
        ->debug(
                'Query for petition signatures', [
                  '@query' => (string) $query,
                  '@nid' => $node->id(),
                  '@district' => $district ? $district->id() : 'none',
                ]
            );
    }
    catch (\Throwable $e) {
      $this->getLogger('nys_petitions')->error(
            "Failed to query for petition signatures",
            [
              '@query' => (string) $query,
              '@nid' => $node->id(),
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
      $msg = 'An error occurred while loading this petition.';
    }
    return new HtmlResponse($msg, 500);
  }

}
