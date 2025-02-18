<?php

namespace Drupal\nys_senator_dashboard\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler;
use Drupal\nys_senators\SenatorsHelper;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Senator Dashboard dynamic menu block.
 */
#[Block(
  id: 'senator_dashboard_constituent_engagement_block',
  admin_label: new TranslatableMarkup('Constituent Engagement block')
)]
class ConstituentEngagementBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The managed senators handler service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler
   */
  protected ManagedSenatorsHandler $managedSenatorsHandler;

  /**
   * The senators helper service.
   *
   * @var \Drupal\nys_senators\SenatorsHelper
   */
  protected SenatorsHelper $senatorsHelper;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Constructs the SenatorDashboardMenuBlock object.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user's account proxy.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler $managed_senators_handler
   *   The managed senators handler service.
   * @param \Drupal\nys_senators\SenatorsHelper $senators_helper
   *   The senators helper service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    ManagedSenatorsHandler $managed_senators_handler,
    SenatorsHelper $senators_helper,
    Connection $database,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->managedSenatorsHandler = $managed_senators_handler;
    $this->senatorsHelper = $senators_helper;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('nys_senator_dashboard.managed_senators_handler'),
      $container->get('nys_senators.senators_helper'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $senator = $this->managedSenatorsHandler->getActiveSenator($this->currentUser->id(), FALSE);
    $district = $this->senatorsHelper->loadDistrict($senator);
    $start_of_year = mktime(0, 0, 0, 1, 1, date('Y'));
    return [
      '#theme' => 'nys_senator_dashboard__constituent_engagement_block',
      '#constituent_engagement_data' => [
        [
          'data' => $this->getNewConstituentsCount($district, $start_of_year),
          'label' => $this->t('New Constituents'),
          'class' => 'c-constituent-engagement-block__new-constituents',
          // @todo update link once route implemented.
          'url' => '',
        ],
        [
          'data' => $this->responsesToBillsCounts($senator, $district, $start_of_year),
          'label' => $this->t('Responses to Bills'),
          'class' => 'c-constituent-engagement-block__responses-to-bills',
          // @todo update link once route implemented.
          'url' => '',
        ],
        [
          'data' => $this->getPetitionsSignedCount($senator, $start_of_year),
          'label' => $this->t('Responses to Petitions'),
          'class' => 'c-constituent-engagement-block__responses-to-petitions',
          // @todo update link once route implemented.
          'url' => '',
        ],
        [
          'data' => $this->getQuestionnaireResponseCount($senator, $start_of_year),
          'label' => $this->t('Responses to Questionnaires'),
          'class' => 'c-constituent-engagement-block__responses-to-questionnaires',
          // @todo update link once route implemented.
          'url' => '',
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => [
          'user:' . $this->currentUser->id(),
          'tempstore_user:' . $this->currentUser->id(),
        ],
      ],
    ];
  }

  /**
   * Gets a count of new constituent users this year for a given district.
   *
   * @param \Drupal\taxonomy\Entity\Term $district
   *   A district term.
   * @param int $start_of_year
   *   A unix timestamp.
   *
   * @return int|null
   *   A count of user accounts for whom:
   *     - the creation date is after the beginning of the year
   *     - the assigned district is the passed senator's district
   */
  protected function getNewConstituentsCount(Term $district, int $start_of_year): ?int {
    try {
      $count = $this->entityTypeManager->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('created', $start_of_year, '>=')
        ->condition('status', 1)
        ->condition('field_district.entity.tid', $district->id())
        ->count()
        ->execute() ?? 0;
    }
    catch (\Throwable) {
      $count = 0;
    }
    return $count;
  }

  /**
   * Gets a count of votes on bills this year for a given district.
   *
   * @param \Drupal\taxonomy\Entity\Term $senator
   *   A senator term.
   * @param \Drupal\taxonomy\Entity\Term $district
   *   A district term.
   * @param int $start_of_year
   *   A unix timestamp.
   *
   * @return int|null
   *   A count of voting entries, for which:
   *     - the vote type is 'nys_bill_vote'
   *     - the bill voted upon is sponsored by the passed senator
   *     - the voting user is assigned to the passed senator's district
   *     - the vote was submitted after the beginning of the year
   */
  protected function responsesToBillsCounts(Term $senator, Term $district, int $start_of_year): ?int {
    try {
      $query = $this->database->select('votingapi_vote', 'v');
      $query->join('node__field_ol_sponsor', 'ols', 'v.entity_id=ols.entity_id');
      $query->join('user__field_district', 'fd', 'v.user_id=fd.entity_id');
      $count = $query
        ->condition('ols.bundle', 'bill')
        ->condition('ols.field_ol_sponsor_target_id', $senator->id())
        ->condition('fd.field_district_target_id', $district->id())
        ->condition('v.type', 'nys_bill_vote')
        ->condition('v.timestamp', $start_of_year, '>=')
        ->countQuery()
        ->execute()
        ->fetchField() ?? 0;
    }
    catch (\Throwable) {
      $count = 0;
    }
    return $count;
  }

  /**
   * Gets count of petitions signed this year for the given senator.
   *
   * @param \Drupal\taxonomy\Entity\Term $senator
   *   A senator term.
   * @param int $start_of_year
   *   A unix timestamp.
   *
   * @return int|null
   *   A count of flagging entries, for which:
   *     - the flag type is 'sign_petition'
   *     - the creation date is after the beginning of the year
   *     - the flagged entity is owned by the passed senator
   */
  protected function getPetitionsSignedCount(Term $senator, int $start_of_year): ?int {
    try {
      $query = $this->database->select('flagging', 'f');
      $query->join('node__field_senator_multiref', 'sm', 'f.entity_id=sm.entity_id');
      $count = $query
        ->condition('sm.bundle', 'petition')
        ->condition('sm.field_senator_multiref_target_id', $senator->id())
        ->condition('f.flag_id', 'sign_petition')
        ->condition('f.created', $start_of_year, '>=')
        ->countQuery()
        ->execute()
        ->fetchField() ?? 0;
    }
    catch (\Throwable) {
      $count = 0;
    }
    return $count;
  }

  /**
   * Gets count of questionnaire responses this year for the given senator.
   *
   * @param \Drupal\taxonomy\Entity\Term $senator
   *   A senator term.
   * @param int $start_of_year
   *   A unix timestamp.
   *
   * @return int|null
   *   A count of all webform submissions which:
   *     - are owned by a node of type "webform"
   *     - are owned by a node assigned to the passed senator
   *     - were submitted since the start of the calendar year
   */
  protected function getQuestionnaireResponseCount(Term $senator, int $start_of_year): ?int {
    try {
      $query = $this->database->select('node_field_data', 'n');
      $query->join('node__field_senator_multiref', 'smr', 'smr.entity_id=n.nid AND smr.bundle=n.type');
      $query->join('node__webform', 'nw', 'nw.entity_id=n.nid AND nw.bundle=n.type');
      $query->join('webform_submission', 'ws', 'ws.webform_id=nw.webform_target_id');
      $count = $query
        ->fields('ws', ['sid'])
        ->condition('n.type', 'webform')
        ->condition('smr.field_senator_multiref_target_id', $senator->id())
        ->condition('ws.created', $start_of_year, '>=')
        ->countQuery()
        ->execute()
        ->fetchField() ?? 0;
    }
    catch (\Throwable) {
      $count = 0;
    }
    return $count;
  }

}
