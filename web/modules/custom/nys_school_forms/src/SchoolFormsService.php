<?php

namespace Drupal\nys_school_forms;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Pager\PagerParametersInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;

/**
 * Elastic Search API Integration.
 */
class SchoolFormsService {

  /**
   * Drupal pager parameters interface.
   *
   * @var \Drupal\Core\Pager\PagerParametersInterface
   */
  protected $pagerParam;

  /**
   * Drupal pager manager interface.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * Entity Type Mananger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * StreamWrapperManager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManager
   */
  protected $streamWrapperManager;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Pager\PagerParametersInterface $pager_param
   *   Pager.
   * @param \Drupal\Core\Pager\PagerManagerInterface $pager_manager
   *   Pager manager.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   Current route match.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManager $streamWrapperManager
   *   The StreamWrapperManager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(
        PagerParametersInterface $pager_param,
        PagerManagerInterface $pager_manager,
        EntityTypeManager $entityTypeManager,
        RouteMatchInterface $current_route_match,
        StreamWrapperManager $streamWrapperManager,
        FileUrlGeneratorInterface $file_url_generator
    ) {
    $this->pagerParam = $pager_param;
    $this->pagerManager = $pager_manager;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentRouteMatch = $current_route_match;
    $this->streamWrapperManager = $streamWrapperManager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * Creates a render array for the school forms page.
   *
   * @return array
   *   The search form and search results build array.
   */
  public function getResults($params, $admin_view = TRUE) {
    $results = [];
    $query = $this->entityTypeManager->getStorage('webform_submission')->getQuery();
    $webform_id = '';
    if (!empty($params['form_type'])) {
      switch ($params['form_type']) {
        // Earth Day.
        case 'Earth Day':
          $webform_id = 'school_form_earth_day';
          break;

        // Thanksgiving.
        case 'Thanksgiving':
        case 'Thankful':
          $webform_id = 'school_form_thanksgiving';
          break;

        default:
          $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(
                [
                  'vid' => 'school_form_type',
                  'name' => $params['form_type'],
                ]
            );
          if ($terms !== NULL) {
            $term = reset($terms);
            if ($term) {
              $webform_id = $term->field_school_form->target_id;
            }
          }
          break;
      }
    }
    $query->condition('webform_id', $webform_id);
    if (!empty($params['from_date'])) {
      $query->condition('created', $params['from_date'], '>');
    }
    if (!empty($params['to_date'])) {
      // Make to date filter inclusive of the day.
      $query->condition('created', (int) $params['to_date'] + 86399, '<');
    }
    if ($params['sort_by']) {
      if ($params['sort_by'] === 'date') {
        if ($params['sort_order']) {
          $query->sort('created', $params['sort_order']);
        }
        else {
          $query->sort('created', 'DESC');
        }
      }
    }
    else {
      $query->sort('created', 'DESC');
    }

    $query_results = $query
      ->accessCheck(FALSE)
      ->execute();
    foreach ($query_results as $query_result) {
      $submission = $this->entityTypeManager->getStorage('webform_submission')->load($query_result);
      /**
       * @var \Drupal\node\NodeInterface $parent_node
       */
      $parent_node = $submission->getSourceEntity();
      $submission_data = $submission->getData();
      /**
       * @var \Drupal\node\NodeInterface $school_node
       */
      $school_node = $this->entityTypeManager->getStorage('node')->load($submission_data['school_name']);
      if ($params['school'] && $params['school'] != $school_node->label()) {
        continue;
      }
      /**
       * @var \Drupal\taxonomy\TermInterface $district
       */
      $district = $school_node->get('field_district')->entity;
      $school_senator = $district->get('field_senator')->entity;
      if ($params['senator'] && $params['senator'] != $school_senator->id()) {
        continue;
      }
      if ($params['teacher_name'] && $params['teacher_name'] != $submission_data['contact_name']) {
        continue;
      }
      foreach ($submission_data['attach_your_submission'] as $student) {
        $file = $this->entityTypeManager->getStorage('file')->load($student['student_submission']);
        if (empty($file)) {
          continue;
        }

        $file_uri = $file->getFileUri();
        $scheme = $this->streamWrapperManager->getScheme($file_uri);

        if ($admin_view) {
          $results[strtoupper($student['student_name'])] = [
            'school_node' => $school_node,
            'parent_node' => $parent_node,
            'senator' => $school_senator,
            'submission' => $submission,
            'student' => $student,
          ];
        }
        else {
          if ($scheme === 'public') {
            $grade = $this->mapGrades($submission_data['grade']);

            $created_time = $submission->getCreatedTime();
            $year = date('Y', $created_time);
            $results[$year][$school_node->id()]['grade_levels'][$grade['weight']]['submissions'][] = [
              'url' => $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()),
              'title' => [
                '#markup' => $student['student_name'] . ' <sub>(' . explode('/', $file->getMimeType())[1] . ')</sub>',
              ],
            ];
            $results[$year][$school_node->id()]['grade_levels'][$grade['weight']]['title'] = $grade['value'];
            $results[$year][$school_node->id()]['title'] = $school_node->label();
          }
        }
      }
    }
    if (!$admin_view) {
      $results = $this->orderGrades($results);
    }

    if ($params['sort_by'] == 'student' && $admin_view) {
      ksort($results, SORT_NATURAL);
      if ($params['sort_order'] == 'desc') {
        // Reverse the array if sort is descending.
        $results = array_reverse($results);
      }
    }

    return $results;
  }

  /**
   * Maps the grade number to the display grade value.
   *
   * @return array
   *   The pair value of grade level and its weight.
   */
  public function mapGrades($grade) {
    $map = [
      'K' => 'Kindergarten',
      '1' => '1st Grade',
      '2' => '2nd Grade',
      '3' => '3rd Grade',
      '4' => '4th Grade',
      '5' => '5th Grade',
      '6' => '6th Grade',
      '7' => '7th Grade',
      '8' => '8th Grade',
      '9' => '9th Grade',
      '10' => '10th Grade',
      '11' => '11th Grade',
      '12' => '12th Grade',
    ];

    return [
      'value' => $map[$grade],
      'weight' => array_search($grade, array_keys($map)),
    ];
  }

  /**
   * Maps the grade number to the display grade value.
   *
   * @return array
   *   Re-order the results so that they are in the right grade level order.
   */
  public function orderGrades(array $year_schools): array {
    foreach ($year_schools as &$schools) {
      foreach ($schools as &$school) {
        $grade_levels = $school['grade_levels'];
        // Sort by grade level in ascending order.
        ksort($grade_levels);
        $school['grade_levels'] = $grade_levels;

        // Sort submissions within each grade level.
        foreach ($grade_levels as &$grade_level) {
          $submissions = $grade_level['submissions'];
          usort($submissions, fn($a, $b) => $a['title']['#markup'] <=> $b['title']['#markup']);
          $grade_level['submissions'] = $submissions;
        }
      }

      if (!empty($schools)) {
        // Sort schools by title in ascending order.
        usort($schools, fn($a, $b) => $a['title'] <=> $b['title']);
      }
    }
    return $year_schools;
  }

}
