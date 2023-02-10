<?php

namespace Drupal\nys_school_forms;

use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Pager\PagerParametersInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\RouteMatchInterface;

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
   */
  public function __construct(
    PagerParametersInterface $pager_param,
    PagerManagerInterface $pager_manager,
    EntityTypeManager $entityTypeManager,
    RouteMatchInterface $current_route_match) {
    $this->pagerParam = $pager_param;
    $this->pagerManager = $pager_manager;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * Creates a render array for the school forms page.
   *
   * @return array
   *   The search form and search results build array.
   */
  public function getResults($senator = '', $school = '', $teacher_name = '', $from_date = '', $to_date = '', $sort_by = '', $order = '') {
    $results = [];
    $admin_type = $this->currentRouteMatch->getRouteName();
    $query = $this->entityTypeManager->getStorage('webform_submission')->getQuery();
    if ($admin_type == 'nys_school_forms.school_forms_earth_day') {
      $query->condition('webform_id', 'school_form_earth_day');
    }
    if ($admin_type == 'nys_school_forms.school_forms_thanksgiving') {
      $query->condition('webform_id', 'school_form_thanksgiving');
    }
    if ($from_date) {
      $query->condition('completed', strtotime($from_date), '>');
    }
    if ($to_date) {
      // Make to date filter inclusive of the day.
      $query->condition('completed', strtotime($to_date) + 86399, '<');
    }
    if ($sort_by == 'date' || empty($sort_by)) {
      if ($order) {
        $query->sort('completed', $order);
      }
      else {
        $query->sort('completed', 'DESC');
      }
    }
    $query_results = $query->execute();
    foreach ($query_results as $query_result) {
      $submission = $this->entityTypeManager->getStorage('webform_submission')->load($query_result);
      $submission_data = $submission->getData();
      /** @var \Drupal\node\NodeInterface $school_node */
      $school_node = $this->entityTypeManager->getStorage('node')->load($submission_data['school_name']);
      if ($school && $school != $school_node->label()) {
        continue;
      }
      /** @var \Drupal\taxonomy\TermInterface $district */
      $district = $school_node->get('field_district')->entity;
      $school_senator = $district->get('field_senator')->entity;
      if ($senator && $senator != $school_senator->id()) {
        continue;
      }
      if ($teacher_name && $teacher_name != $submission_data['contact_name']) {
        continue;
      }

      foreach ($submission_data['attach_your_submission'] as $student) {
        $file = $this->entityTypeManager->getStorage('file')->load($student['student_submission']);
        if (empty($file)) {
          continue;
        }
        $results[strtoupper($student['student_name'])] = [
          'school_node' => $school_node,
          'parent_node' => $parent_node,
          'senator' => $school_senator,
          'submission' => $submission,
          'student' => $student,
        ];
      }
    }
    if ($sort_by == 'student') {
      ksort($results, SORT_NATURAL);
      $results = array_values($results);
      if ($order == 'desc') {
        // Reverse the array if sort is descending.
        $results = array_reverse($results);
      }
    }
    else {
      $results = array_values($results);
    }
    return $results;
  }

}
