<?php

namespace Drupal\nys_senator_dashboard\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
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
  protected Connection $database;

  /**
   * The Route Match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The managed senators handler service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler
   */
  protected ManagedSenatorsHandler $managedSenatorsHandler;

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
   * NYS Bill Vote configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

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
    $this->config = $this->configFactory->get('nys_bill_vote.config');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
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
   */
  public function build(): array {
    // Get all the necessary settings.
    $settings = $this->getConfiguration();

    $ret = [
      '#type' => 'component',
      '#component' => 'nys_bill_vote:bill-vote-count-block',
      '#props' => ['no_results' => TRUE],
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

    // Signal success up to this point.
    $ret['#props'] = ['no_results' => FALSE, 'detail_link_nid' => (int) $nid];

    // Calculate the percentages.
    $stats['aye_percent'] = $this->renderPercent($stats['aye'] / max($stats['total'], 1));
    $stats['nay_percent'] = $this->renderPercent($stats['nay'] / max($stats['total'], 1));
    $stats['aye_in_district_percent'] = $this->renderPercent($stats['aye_in_district'] / max($stats['total_in_district'], 1));
    $stats['nay_in_district_percent'] = $this->renderPercent($stats['nay_in_district'] / max($stats['total_in_district'], 1));
    $stats['aye_out_district_percent'] = $this->renderPercent($stats['aye_out_district'] / max($stats['total_out_district'], 1));
    $stats['nay_out_district_percent'] = $this->renderPercent($stats['nay_out_district'] / max($stats['total_out_district'], 1));

    $ret['#slots'] = [];
    foreach ($this->defaultConfiguration() as $key => $value) {
      $this_value = $settings[$key] ?? $value;
      if ($this_value) {
        $type = str_replace('show_', '', $key);
        $callback = 'build' . ucfirst(str_replace('_', '', $type));
        if (method_exists($this, $callback)) {
          // "summary" must go in "#props".  All others in "#slots".
          $ret[$type == 'summary' ? '#props' : '#slots'][$type] = $this->$callback($stats);
        }
      }
    }

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

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'show_summary' => TRUE,
      'show_total' => TRUE,
      'show_in_district' => TRUE,
      'show_out_district' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form += [
      'show_summary' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Text Summary'),
        '#description' => $this->t('Display the text version of voting statistics.'),
        '#default_value' => $this->configuration['show_summary'],
      ],
      'show_total' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Total Votes Graph'),
        '#description' => $this->t('Display the total votes graph.'),
        '#default_value' => $this->configuration['show_total'],
      ],
      'show_in_district' => [
        '#type' => 'checkbox',
        '#title' => $this->t('In-District Graph'),
        '#description' => $this->t('Display the in-district voting graph.'),
        '#default_value' => $this->configuration['show_in_district'],
      ],
      'show_out_district' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Out-of-District Graph'),
        '#description' => $this->t('Display the out-of-district voting graph.'),
        '#default_value' => $this->configuration['show_out_district'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    foreach ([
      'show_summary',
      'show_total',
      'show_in_district',
      'show_out_district',
    ] as $x) {
      $this->configuration[$x] = $values[$x] ?? FALSE;
    }
  }

  /**
   * Callback to create the "summary" twig variable.
   */
  protected function buildSummary(array $props): array {
    return $props;
  }

  /**
   * Common template for all voting charts.
   *
   * Creates a donut chart, 300px by 200px.  Consumers must populate '#title',
   * '#colors', 'series.#title', 'series.#data', and 'x_axis.#labels'.
   */
  protected function buildGraphTemplate(): array {
    return [
      '#type' => 'chart',
      '#tooltips' => TRUE,
      '#title' => '',
      '#chart_type' => 'donut',
      '#colors' => [],
      'series' => [
        '#type' => 'chart_data',
        '#title' => '',
        '#data' => [],
      ],
      'x_axis' => [
        '#type' => 'chart_xaxis',
        '#title' => $this->t('Vote Location'),
        '#labels' => [],
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
        'legend' => [
          'position' => 'top',
        ],
        'noData' => [
          'text' => 'No votes saved',
          'align' => 'center',
          'verticalAlign' => 'middle',
        ],
      ],
    ];
  }

  /**
   * Callback to create the "total" twig variable.
   */
  protected function buildTotal(array $props): array {
    $template = $this->buildGraphTemplate();
    $template['#title'] = 'Bill Vote Totals';
    $template['#colors'] = [
      $this->config->get('aye_in'),
      $this->config->get('nay_in'),
      $this->config->get('aye_out'),
      $this->config->get('nay_out'),
    ];
    $template['series']['#title'] = $this->t('Total Votes');
    $template['x_axis']['#labels'] = [
      $this->t('Aye In District'),
      $this->t('Nay In District'),
      $this->t('Aye Out District'),
      $this->t('Nay Out District'),
    ];
    $template['#raw_options']['legend']['show'] = TRUE;
    if ($props['total'] > 0) {
      $template['series']['#data'] = [
        (int) $props['aye_in_district'],
        (int) $props['nay_in_district'],
        (int) $props['aye_out_district'],
        (int) $props['nay_out_district'],
      ];
    }

    return $template;
  }

  /**
   * Callback to create the "in_district" twig variable.
   */
  protected function buildIndistrict(array $props): array {
    $template = $this->buildGraphTemplate();

    $template['#title'] = 'Voting In District';
    $template['#colors'] = [
      $this->config->get('aye_in'),
      $this->config->get('nay_in'),
    ];
    $template['series']['#title'] = $this->t('In-District Votes');
    $template['x_axis']['#labels'] = [
      $this->t('Aye In District'),
      $this->t('Nay In District'),
    ];
    $template['#raw_options']['legend']['show'] = FALSE;
    if ($props['total_in_district'] > 0) {
      $template['series']['#data'] = $props['total_in_district'] > 0
        ? [
          (int) $props['aye_in_district'],
          (int) $props['nay_in_district'],
        ]
        : [];
    }

    return $template;
  }

  /**
   * Callback to create the "out_district" twig variable.
   */
  protected function buildOutdistrict(array $props): array {
    $template = $this->buildGraphTemplate();
    $template['#title'] = 'Voting Outside District';
    $template['#colors'] = [
      $this->config->get('aye_out'),
      $this->config->get('nay_out'),
    ];
    $template['series']['#title'] = $this->t('Out-of-District Votes');
    $template['x_axis']['#labels'] = [
      $this->t('Aye Out District'),
      $this->t('Nay Out District'),
    ];
    $template['#raw_options']['legend']['show'] = FALSE;
    if ($props['total_out_district'] > 0) {
      $template['series']['#data'] = [
        (int) $props['aye_out_district'],
        (int) $props['nay_out_district'],
      ];
    }

    return $template;
  }

}
