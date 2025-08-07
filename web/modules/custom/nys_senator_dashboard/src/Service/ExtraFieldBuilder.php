<?php

namespace Drupal\nys_senator_dashboard\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\flag\FlagServiceInterface;
use Drupal\flag\FlagCountManagerInterface;

/**
 * Service for building extra field render arrays for the Senator Dashboard.
 */
class ExtraFieldBuilder {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The flag count manager.
   *
   * @var \Drupal\flag\FlagCountManagerInterface
   */
  protected $flagCount;

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $pathAliasManager;

  /**
   * The senator dashboard helper service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\SenatorDashboardHelper
   */
  protected $senatorDashboardHelper;

  /**
   * Constructs a new ExtraFieldBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   * @param \Drupal\flag\FlagCountManagerInterface $flag_count
   *   The flag count manager.
   * @param \Drupal\path_alias\AliasManagerInterface $path_alias_manager
   *   The path alias manager.
   * @param \Drupal\nys_senator_dashboard\Service\SenatorDashboardHelper $senator_dashboard_helper
   *   The senator dashboard helper service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FlagServiceInterface $flag_service,
    FlagCountManagerInterface $flag_count,
    $path_alias_manager,
    SenatorDashboardHelper $senator_dashboard_helper,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->flagService = $flag_service;
    $this->flagCount = $flag_count;
    $this->pathAliasManager = $path_alias_manager;
    $this->senatorDashboardHelper = $senator_dashboard_helper;
  }

  /**
   * Builds the flag count render array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $flag_id
   *   The flag ID.
   *
   * @return array
   *   The render array.
   */
  public function buildFlagCount(EntityInterface $entity, string $flag_id) {
    $flagging_count = $this->flagCount->getEntityFlagCounts($entity)[$flag_id] ?? 0;
    $value = match($entity->bundle()) {
      'issues' => $this->t('@count followers', ['@count' => $flagging_count]),
      'petition' => $this->t('Signed by @count constituents', ['@count' => $flagging_count]),
      default => $flagging_count,
    };
    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $value,
      '#attributes' => [
        'class' => ['senator-dashboard-flagging-count'],
      ],
    ];
  }

  /**
   * Builds the district flag count render array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $flag_id
   *   The flag ID.
   *
   * @return array
   *   The render array.
   */
  public function buildDistrictFlagCount(EntityInterface $entity, string $flag_id) {
    $flagging_count = $this->flagCount->getEntityFlagCounts($entity)[$flag_id] ?? 0;
    $in_district_flagging_count = $this->senatorDashboardHelper->getInDistrictFlaggingCount(
      $flag_id,
      $entity->id()
    );
    $out_district_flagging_count = $flagging_count - $in_district_flagging_count;
    $in_count = $this->t('@in_count in-district', ['@in_count' => $in_district_flagging_count]);
    $out_count = $this->t('@out_count out-of-district', ['@out_count' => $out_district_flagging_count]);
    return [
      '#markup' => "<p><strong>$in_count</strong></p><p><strong>$out_count</strong></p>",
    ];
  }

  /**
   * Builds the link to constituents list render array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The render array.
   */
  public function buildLinkToConstituentsList(EntityInterface $entity) {
    $url = "/senator-dashboard/constituent-activity/petitions/{$entity->id()}";
    return [
      '#type' => 'html_tag',
      '#tag' => 'a',
      '#value' => $this->t('View in-district signatories'),
      '#attributes' => [
        'href' => $url,
        'class' => ['link-to-constituents-list'],
      ],
    ];
  }

  /**
   * Builds the status render array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The render array.
   */
  public function buildStatus(EntityInterface $entity) {
    $value = $entity->isPublished() ? $this->t('Active') : $this->t('Inactive');
    $class = $entity->isPublished() ? 'active' : 'inactive';
    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $value,
      '#attributes' => [
        'class' => ["$class"],
      ],
    ];
  }

  /**
   * Builds the constituents response count render array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The render array.
   */
  public function buildConstituentsResponseCount(EntityInterface $entity) {
    $url = "/senator-dashboard/constituent-activity/questionnaires/{$entity->id()}";
    $constituent_response_count = $this->senatorDashboardHelper->getInDistrictWebformSubmissionCount($entity);
    return [
      '#type' => 'html_tag',
      '#tag' => 'a',
      '#value' => $this->t(
        '@constituent_response_count constituent(s) responded',
        ['@constituent_response_count' => $constituent_response_count]
      ),
      '#attributes' => [
        'href' => $url,
        'class' => ['c-senator-dashboard-questionnaire-teaser--link-to-constituents-list'],
      ],
    ];
  }

  /**
   * Builds the webform submissions download render array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The render array.
   */
  public function buildWebformSubmissionsDownload(EntityInterface $entity) {
    $webform_id = $entity->webform?->entity?->id();
    if ($webform_id) {
      return [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#value' => $this->t('Download all responses (CSV)'),
        '#attributes' => [
          'href' => "/admin/webform/manage/$webform_id/results/download",
          'class' => ['c-senator-dashboard--webform-submissions-download'],
        ],
      ];
    }
    return [];
  }

  /**
   * Builds the vote totals render array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The render array.
   */
  public function buildVoteTotals(EntityInterface $entity) {
    $data = $this->senatorDashboardHelper->getBillVoteCounts($entity, 'total_votes');
    $total = array_sum($data);
    $ayes_percentage = ($total > 0) ? round(($data[0] / $total) * 100) : 0;
    $nays_percentage = ($total > 0) ? round(($data[1] / $total) * 100) : 0;
    $labels = [
      $this->t('Ayes: @count (@percentage%)', ['@count' => $data[0], '@percentage' => $ayes_percentage]),
      $this->t('Nays: @count (@percentage%)', ['@count' => $data[1], '@percentage' => $nays_percentage]),
    ];
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['chart-container']],
      'total_label' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Total:'),
        '#attributes' => ['class' => 'total-label'],
      ],
      'chart' => [
        '#type' => 'chart',
        '#chart_type' => 'donut',
        '#colors' => ['#D48011', '#444444'],
        '#tooltips' => FALSE,
        'series' => [
          '#type' => 'chart_data',
          '#data' => $total ? $data : [0, 1],
        ],
        'x_axis' => [
          '#type' => 'chart_xaxis',
          '#labels' => $labels,
        ],
        '#raw_options' => [
          'chart' => [
            'height' => 150,
            'width' => 250,
          ],
        ],
      ],
      'total_value' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => "($total)",
        '#attributes' => ['class' => 'total-value'],
      ],
    ];
  }

  /**
   * Builds the vote totals district breakdown render array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The render array.
   */
  public function buildVoteTotalsDistrictBreakdown(EntityInterface $entity) {
    $in_district_votes = $this->senatorDashboardHelper->getBillVoteCounts($entity, 'in_district_votes');
    $in_district_total = array_sum($in_district_votes);
    $in_district_ayes_percentage = ($in_district_total > 0)
      ? round(($in_district_votes[0] / $in_district_total) * 100)
      : 0;
    $in_district_nays_percentage = ($in_district_total > 0)
      ? round(($in_district_votes[1] / $in_district_total) * 100)
      : 0;
    $in_district_labels = [
      $this->t('In-district Ayes: @count (@percentage%)', [
        '@count' => $in_district_votes[0],
        '@percentage' => $in_district_ayes_percentage,
      ]),
      $this->t('In-district Nays: @count (@percentage%)', [
        '@count' => $in_district_votes[1],
        '@percentage' => $in_district_nays_percentage,
      ]),
    ];

    $out_of_district_data = $this->senatorDashboardHelper->getBillVoteCounts($entity, 'out_of_district_votes');
    $out_of_district_total = array_sum($out_of_district_data);
    $out_of_district_ayes_percentage = ($out_of_district_total > 0)
      ? round(($out_of_district_data[0] / $out_of_district_total) * 100)
      : 0;
    $out_of_district_nays_percentage = ($out_of_district_total > 0)
      ? round(($out_of_district_data[1] / $out_of_district_total) * 100)
      : 0;
    $out_of_district_labels = [
      $this->t('Out-of-district Ayes: @count (@percentage%)', [
        '@count' => $out_of_district_data[0],
        '@percentage' => $out_of_district_ayes_percentage,
      ]),
      $this->t('Out-of-district Nays: @count (@percentage%)', [
        '@count' => $out_of_district_data[1],
        '@percentage' => $out_of_district_nays_percentage,
      ]),
    ];

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['district-breakdown-charts']],
    ];

    if ($in_district_total > 0) {
      $build['in_district_container'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['chart-container']],
        'in_district_label' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t('Breakdown by district:'),
          '#attributes' => ['class' => 'total-label'],
        ],
        'in_district_chart' => [
          '#type' => 'chart',
          '#chart_type' => 'donut',
          '#colors' => ['#367866', '#222'],
          '#tooltips' => FALSE,
          'series' => [
            '#type' => 'chart_data',
            '#data' => $in_district_votes,
          ],
          'x_axis' => [
            '#type' => 'chart_xaxis',
            '#labels' => $in_district_labels,
          ],
          '#raw_options' => [
            'chart' => [
              'height' => 70,
              'width' => 276,
            ],
          ],
        ],
        'in_district_total' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => "($in_district_total)",
          '#attributes' => ['class' => 'total-value'],
        ],
      ];
    }

    if ($out_of_district_total > 0) {
      $build['out_of_district_container'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['chart-container']],
        'out_of_district_label' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => ['class' => 'total-label'],
        ],
        'out_of_district_chart' => [
          '#type' => 'chart',
          '#chart_type' => 'donut',
          '#colors' => ['#367866', '#222'],
          '#tooltips' => FALSE,
          'series' => [
            '#type' => 'chart_data',
            '#data' => $out_of_district_data,
          ],
          'x_axis' => [
            '#type' => 'chart_xaxis',
            '#labels' => $out_of_district_labels,
          ],
          '#raw_options' => [
            'chart' => [
              'height' => 70,
              'width' => 300,
            ],
          ],
        ],
        'out_of_district_total' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => "($out_of_district_total)",
          '#attributes' => ['class' => 'total-value'],
        ],
      ];
    }

    return $build;
  }

  /**
   * Builds the bill action links render array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The render array.
   */
  public function buildBillActionLinks(EntityInterface $entity) {
    $comments = $this->senatorDashboardHelper->getCommentCount($entity);
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['senator-dashboard-bill-action-links'],
      ],
      'constituents_link' => [
        '#type' => 'link',
        '#title' => $this->t('View in-district constituents'),
        '#url' => Url::fromUri("internal:/senator-dashboard/constituent-activity/bills/{$entity->id()}"),
        '#attributes' => [
          'class' => ['senator-dashboard-bill-constituents-link'],
        ],
      ],
      'comments_link' => [
        '#type' => 'link',
        '#title' => $this->t('@count comment(s)', ['@count' => $comments]),
        '#url' => Url::fromUri("internal:/node/{$entity->id()}#node-bill-field-comments"),
        '#attributes' => [
          'class' => ['senator-dashboard-bill-comments-link'],
        ],
      ],
    ];
  }

  /**
   * Builds the followers link render array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The render array.
   */
  public function buildFollowersLink(EntityInterface $entity) {
    return [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => Url::fromUri("internal:/senator-dashboard/constituent-activity/issues/{$entity->id()}"),
      '#attributes' => [
        'class' => 'senator-dashboard-followers-link',
      ],
    ];
  }

  /**
   * Builds the path alias render array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The render array.
   */
  public function buildPathAlias(EntityInterface $entity) {
    $path_alias = $this->pathAliasManager->getAliasByPath('/node/' . $entity->id());
    return [
      '#markup' => $path_alias,
    ];
  }

}
