<?php

namespace Drupal\nys_senator_dashboard\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\nys_bill_vote\Service\BillVoteStatistics;
use Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Bill Vote Count Block.
 */
#[Block(
  id: 'nys_senator_dashboard_bill_vote_count_block',
  admin_label: new TranslatableMarkup('NYS Senator Dashboard: Bill Vote Count Block'),
  context_definitions: [
    'node' => new EntityContextDefinition(
      'entity:node',
      label: new TranslatableMarkup('Node'),
      required: FALSE
    ),
  ]
)]
class BillVoteCountBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Route Match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The managed senators handler service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler
   */
  protected $managedSenatorsHandler;

  /**
   * The Bill Voting Statistics service.
   *
   * @var \Drupal\nys_bill_vote\Service\BillVoteStatistics
   */
  protected BillVoteStatistics $voteStats;

  /**
   * Drupal Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a new VoteCountBlock instance.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The Route Match service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory service.
   * @param \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler $managed_senators_handler
   *   The managed senators handler service.
   * @param \Drupal\nys_bill_vote\Service\BillVoteStatistics $vote_stats
   *   Bill voting statistics service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $database,
    RouteMatchInterface $route_match,
    AccountProxyInterface $current_user,
    ConfigFactoryInterface $config_factory,
    ManagedSenatorsHandler $managed_senators_handler,
    BillVoteStatistics $vote_stats,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
    $this->managedSenatorsHandler = $managed_senators_handler;
    $this->voteStats = $vote_stats;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('nys_senator_dashboard.managed_senators_handler'),
      $container->get('nys_bill_vote.bill_vote_statistics'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return ['route'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return [
      'user:' . $this->currentUser->id(),
      'tempstore_user:' . $this->currentUser->id(),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @todo there could be a use for text summary.  do we need a separate block
   *   for that, or can we switch with config?
   *
   * @todo Make graph colors configurable at least at the global level,
   *   consider at the user level.
   */
  public function build(): array {
    $chart_settings = $this->configFactory->get('charts.settings');

    $style = 'block';
    $ret = [
      '#type' => 'component',
      '#component' => 'nys_bill_vote:bill-vote-count-' . $style,
      '#props' => ['no_results' => TRUE, 'using_graph' => FALSE],
    ];

    // Get bill NID from either node context or views arg_0.
    try {
      $nid = (int) ($this->getContextValue('node')?->id()
        ?? $this->routeMatch->getParameter('arg_0')
      );
    }
    catch (\Exception) {
      $nid = 0;
    }
    if (empty($nid)) {
      return $ret;
    }

    // Get active managed senator district ID.
    $active_senator_district_id = $this->managedSenatorsHandler
      ->getActiveSenatorDistrictId();

    // Get stats array.
    $stats = $this->voteStats->getStats($nid, $active_senator_district_id);

    // Setup and calculate component props.
    $props = $stats +
      [
        'no_results' => FALSE,
        'detail_link_nid' => (int) $nid,
      ];
    $props['aye_percent'] = $this->renderPercent($props['aye'] / max($props['total'], 1));
    $props['nay_percent'] = $this->renderPercent($props['nay'] / max($props['total'], 1));
    $props['aye_in_district_percent'] = $this->renderPercent($props['aye_in_district'] / max($props['total_in_district'], 1));
    $props['nay_in_district_percent'] = $this->renderPercent($props['nay_in_district'] / max($props['total_in_district'], 1));
    $props['aye_out_district_percent'] = $this->renderPercent($props['aye_out_district'] / max($props['total_out_district'], 1));
    $props['nay_out_district_percent'] = $this->renderPercent($props['nay_out_district'] / max($props['total_out_district'], 1));

    $ret['#props'] = $props;

    $ret['#slots'] = [
      'graph' => [
        '#type' => 'chart',
        '#tooltips' => $chart_settings->get('charts_default_settings.display.tooltips'),
        '#title' => $this->t('Bill Vote Summary'),
        '#chart_type' => 'donut',
        '#colors' => [
          '#1d84c3',
          '#ff0000',
          '#00ff00',
          '#0000ff',
        ],
        'series' => [
          '#type' => 'chart_data',
          '#title' => $this->t('Vote Counts'),
          '#data' => [
            (int) $props['aye_in_district'],
            (int) $props['nay_in_district'],
            (int) $props['aye_out_district'],
            (int) $props['nay_out_district'],
          ],
        ],
        'x_axis' => [
          '#type' => 'chart_xaxis',
          '#title' => $this->t('Vote Location'),
          '#labels' => [
            $this->t('Aye In District'),
            $this->t('Nay In District'),
            $this->t('Aye Out District'),
            $this->t('Nay Out District'),
          ],
        ],
        'y_axis' => [
          '#type' => 'chart_yaxis',
          '#title' => $this->t('Number of Votes'),
        ],
        '#raw_options' => [
          'chart' => [
            'height' => '200px',
            'width' => '300px',
          ],
        ],
      ],
    ];

    return $ret;
  }

  /**
   * Pretty rendering for percentage values.
   *
   * @param mixed $value
   *   Integer or float value.
   * @param int $len
   *   Maximum significant digits after the new decimal position.
   *   E.g.:
   *     - .7348 becomes "73.48" with $len=2
   *     - .0625 becomes "6" with $len=0.
   */
  protected function renderPercent(mixed $value, int $len = 2): string {
    return sprintf('%.' . $len . 'f', $value * 100);
  }

}
