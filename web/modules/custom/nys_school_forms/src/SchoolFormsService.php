<?php

namespace Drupal\nys_school_forms;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Pager\PagerParametersInterface;
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
   */
  public function __construct(
    PagerParametersInterface $pager_param,
    PagerManagerInterface $pager_manager,
    EntityTypeManager $entityTypeManager,
    RouteMatchInterface $current_route_match,
    StreamWrapperManager $streamWrapperManager) {
    $this->pagerParam = $pager_param;
    $this->pagerManager = $pager_manager;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentRouteMatch = $current_route_match;
    $this->streamWrapperManager = $streamWrapperManager;
  }

  /**
   * Creates a render array for the school forms page.
   *
   * @return array
   *   The search form and search results build array.
   */
  public function getResults($params) {
    $results = [];
    $query = $this->entityTypeManager->getStorage('webform_submission')->getQuery();
    $webform_id = '';
    switch ($params['form_type']) {
      // Earth Day.
      case 'Earth Day':
        $webform_id = 'school_form_earth_day';
        break;

      // Thanksgiving.
      case 'Thanksgiving':
        $webform_id = 'school_form_thanksgiving';
        break;

      default:
        $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(
          [
            'vid' => 'school_form_type',
            'name' => $params['form_type'],
          ]);
        if ($terms !== NULL) {
          $term = reset($terms);
          $webform_id = $term->field_school_form->target_id;
        }
        break;
    }

    $query->condition('webform_id', $webform_id);
    if ($params['from_date']) {
      $query->condition('completed', strtotime($params['from_date']), '>');
    }
    if ($params['to_date']) {
      // Make to date filter inclusive of the day.
      $query->condition('completed', strtotime($params['to_date']) + 86399, '<');
    }
    if ($params['sort_by'] === 'date') {
      if ($params['sort_order']) {
        $query->sort('completed', $params['sort_order']);
      }
      else {
        $query->sort('completed', 'DESC');
      }
    }
    $query_results = $query->execute();
    foreach ($query_results as $query_result) {
      $submission = $this->entityTypeManager->getStorage('webform_submission')->load($query_result);
      /** @var \Drupal\node\NodeInterface $parent_node */
      $parent_node = $submission->getSourceEntity();
      $submission_data = $submission->getData();
      /** @var \Drupal\node\NodeInterface $school_node */
      $school_node = $this->entityTypeManager->getStorage('node')->load($submission_data['school_name']);
      if ($params['school'] && $params['school'] != $school_node->label()) {
        continue;
      }
      /** @var \Drupal\taxonomy\TermInterface $district */
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

        $results[strtoupper($student['student_name'])] = [
          'school_node' => $school_node,
          'parent_node' => $parent_node,
          'senator' => $school_senator,
          'submission' => $submission,
          'student' => $student,
        ];
      }
    }
    if ($params['sort_by'] == 'student') {
      ksort($results, SORT_NATURAL);
      if ($params['sort_order'] == 'desc') {
        // Reverse the array if sort is descending.
        $results = array_reverse($results);
      }
    }
    return $results;
  }

}
