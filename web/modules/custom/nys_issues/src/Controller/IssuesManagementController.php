<?php

namespace Drupal\nys_issues\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manages AJAX callbacks for senator management issues tab.
 */
class IssuesManagementController extends ControllerBase {

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
  public function __construct(Connection $connection, Renderer $renderer) {
    $this->connection = $connection;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('database'),
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
   * @param \Drupal\taxonomy\Entity\Term $taxonomy_term
   *   A Senator taxonomy term.
   * @param \Drupal\taxonomy\Entity\Term $tid
   *   An Issue taxonomy term.
   *
   * @return \Drupal\Core\Render\HtmlResponse
   *   A pre-rendered table of constituents following the issue.
   */
  public function ajaxGetFollows(Term $taxonomy_term, Term $tid): HtmlResponse {

    // Build each row based on the query return.
    $rows = array_map(
      [$this, 'buildRow'],
      $this->getIssueFollowers($taxonomy_term, $tid)
    );

    $link = Link::fromTextAndUrl($tid->getName(), $tid->toUrl())->toString();

    $ret = $this->renderer->renderPlain($ret);

    return new HtmlResponse($ret);
  }

  /**
   * Builds a row meant for the "users following issues" table.
   *
   * @param array $row
   *   A row as returned from getIssueFollowers()
   */
  protected function buildRow(array $row): array {
    // Get the link for the user.
    $url = Url::fromRoute('entity.user.edit_form', ['user' => $row['uid']]);
    $name = $row['first_name'] . ' ' . $row['last_name'];
    $name_link = Link::fromTextAndUrl($name, $url)->toString();

    $address = implode(', ', [
      $row['addr_1'],
      $row['addr_2'],
      $row['city'],
      $row['zip'],
    ]);

    // Build the row.
    return [
      ['data' => $name_link, 'class' => 'issue-follower-username'],
      ['data' => $address, 'class' => 'issue-follower-address'],
    ];
  }

  /**
   * Returns an array of constituents following an issue.
   *
   * @return array
   *   Each element is an array representing a constituent.  Available keys are
   *   uid, mail, first_name, last_name, addr_1, addr_2, city, and zip.
   */
  protected function getIssueFollowers(Term $senator, Term $issue): array {
    // Start with the user.
    $query = $this->connection->select('users_field_data', 'u');

    // Join to all the user field tables.
    $query->join('user__field_first_name', 'ufn', 'u.uid=ufn.entity_id');
    $query->join('user__field_last_name', 'uln', 'u.uid=uln.entity_id');
    $query->join('user__field_address', 'ufa', 'u.uid=ufa.entity_id');
    $query->join('user__field_district', 'ufd', 'ufd.entity_id=u.uid');

    // Join to the district's field_senator table.
    $query->join('taxonomy_term__field_senator', 'ttfs',
      'ttfs.entity_id=ufd.field_district_target_id and ttfs.bundle=:districts',
      [':districts' => 'districts']);

    // Join the flagging table.
    $query->join('flagging', 'f',
      'f.uid=u.uid and f.flag_id=:follow and f.entity_type=:term',
      [':follow' => 'follow_issue', ':term' => 'taxonomy_term']);

    // Fields to be returned.
    $query->addField('u', 'uid');
    $query->addField('u', 'mail');
    $query->addField('ufn', 'field_first_name_value', 'first_name');
    $query->addField('uln', 'field_last_name_value', 'last_name');
    $query->addField('ufa', 'field_address_address_line1', 'addr_1');
    $query->addField('ufa', 'field_address_address_line2', 'addr_2');
    $query->addField('ufa', 'field_address_locality', 'city');
    $query->addField('ufa', 'field_address_postal_code', 'zip');

    $query->condition('ttfs.field_senator_target_id', $senator->id())
      ->condition('f.entity_id', $issue->id())
      ->orderBy('last_name')
      ->orderBy('first_name')
      ->orderBy('uid');

    // Fetch and return.
    try {
      $ret = $query->execute()->fetchAllAssoc('uid', \PDO::FETCH_ASSOC);
    }
    catch (\Throwable $e) {
      $this->getLogger('nys_issues')->error(
        "Failed to query for issue followers",
        [
          '@query' => (string) $query,
          '@issue_tid' => $issue->id(),
          '@senator_tid' => $senator->id(),
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
      $msg = 'An error occurred while loading.';
    }
    return new HtmlResponse($msg, 500);
  }

}
