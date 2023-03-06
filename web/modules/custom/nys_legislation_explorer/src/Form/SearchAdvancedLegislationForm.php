<?php

namespace Drupal\nys_legislation_explorer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The Search advanced legislation form class.
 */
class SearchAdvancedLegislationForm extends FormBase {

  /**
   * Returns a list of session years in descending order.
   *
   * Restricts years from the current session year to the earliest session that
   * there is legislation data for (2009).
   *
   * @param int $min_year
   *   Minimum year restriction to fetch session years from.
   *
   * @return array
   *   Sessions years.
   */
  public function getSessionYearList(int $min_year = 2009): array {
    $year = date("Y");
    $session_years = [];
    while ($year >= $min_year) {
      if ($year % 2 != 0) {
        $session_years[] = $year;
      }
      $year--;
    }
    return $session_years;
  }

  /**
   * Returns a list of Senators.
   *
   * @return array
   *   Senators array.
   */
  public function getSenatorsList(): array {
    $query = \Drupal::database()->select('taxonomy_term__field_senator_name', 's');
    $query->fields('s', ['entity_id']);
    $query->addExpression("CONCAT(s.field_senator_name_given,' ',s.field_senator_name_family)", 'senator_full_name');
    $query->orderBy('s.field_senator_name_family');

    $result = [];
    $result[0] = 'Any';
    return $query->execute()->fetchAllKeyed(0, 1);
  }

  /**
   * Returns a list of committees.
   *
   * @return array
   *   Committee array.
   */
  public function getCommitteeList(): array {
    $committees = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'committees']);

    $result = [];
    $result[NULL] = 'Any';
    foreach ($committees as $committe) {
      $result[$committe->label()] = $committe->label();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nys_search_legislation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['my_block'] = [
      '#type' => 'block',
      '#attributes' => [
        'class' => ['my-custom-class'],
      ],
    ];

    $form['my_block']['my_block_text'] = [
      '#type' => 'item',
      '#markup' => $this->t('This is my custom block.'),
    ];

    $form['my_block']['my_block_title'] = [
      '#type' => 'item',
      '#markup' => $this->t('My Block Title'),
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => ('FILTER BY CONTENT TYPE:'),
      '#options' => [
        'bill' => t('Bills'),
        'resolution' => t('Resolutions'),
        'agenda' => t('Committee Meeting Agendas'),
        'calendar' => t('Session Calendars'),
        'transcript' => t('Session Transcripts'),
        'public_hearing' => t('Public Hearing Transcripts'),
      ],
    ];

    // Bill search form.
    $form['bill_printno'] = [
      '#type' => 'textfield',
      '#title' => t('PRINT NO'),
      '#size' => 10,
    ];

    $bill_years = [];
    $bill_years[NULL] = 'Any';
    foreach ($this->getSessionYearList() as $year) {
      $bill_years[$year] = $year . '-' . ($year + 1);
    }
    $form['bill_session_year'] = [
      '#type' => 'select',
      '#title' => ('SESSION YEAR'),
      '#options' => $bill_years,
    ];

    $form['bill_text'] = [
      '#type' => 'textfield',
      '#title' => t('TITLE / SPONSOR MEMO / FULL TEXT'),
    ];

    $form['bill_sponsor'] = [
      '#type' => 'select',
      '#title' => ('SENATE SPONSOR'),
      '#options' => $this->getSenatorsList(),
    ];

    $form['bill_status'] = [
      '#type' => 'select',
      '#title' => t('BILL STATUS'),
      '#options' => [
        '' => t('Any'),
        'INTRODUCED' => t('Introduced'),
        'IN_ASSEMBLY_COMM' => t('In Assembly Committee'),
        'IN_SENATE_COMM' => t('In Senate Committee'),
        'ASSEMBLY_FLOOR' => t('Assembly Floor Calendar'),
        'SENATE_FLOOR' => t('Senate Floor Calendar'),
        'PASSED_ASSEMBLY' => t('Passed Assembly'),
        'PASSED_SENATE' => t('Passed Senate'),
        'DELIVERED_TO_GOV' => t('Delivered to Governor'),
        'SIGNED_BY_GOV' => t('Signed by Governor'),
        'VETOED' => t('Vetoed'),
        'STRICKEN' => t('Stricken'),
        'LOST' => t('Lost'),
        'SUBSTITUTED' => t('Substituted'),
        'ADOPTED' => t('Adopted'),
      ],
    ];

    $form['bill_committee'] = [
      '#type' => 'select',
      '#title' => ('IN COMMITTEE'),
      '#options' => $this->getCommitteeList(),
    ];

    $form['bill_issue'] = [
      '#type' => 'entity_autocomplete',
      '#title' => ('ISSUE'),
      '#target_type' => 'taxonomy_term',
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['issues'],
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('SEARCH'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\nys_openleg\Service\ApiManager $openApiService */
    $openApiService = \Drupal::service('manager.openleg_api');

    $values = $form_state->getValues();

    $type = $values['type'];
    $bill_print_no = $values['bill_printno'];
    $bill_session_year = $values['bill_session_year'];
    $bill_text = $values['bill_text'];
    $bill_sponsor = $values['bill_sponsor'];
    $bill_status = $values['bill_status'];
    $bill_committee = $values['bill_committee'];
    $bill_issue = $values['bill_issue'];

    $search_term = '';
    !empty($bill_print_no) ? $search_term .= 'printNo:' . $bill_print_no . ' @ ' : $search_term .= '';
    !empty($bill_session_year) ? $search_term .= 'session:' . $bill_session_year . ' @ ' : $search_term .= '';
    !empty($bill_text) ? $search_term .= 'title:' . $bill_text . ' @ ' : $search_term .= ' ';
    !empty($bill_sponsor) ? $search_term .= 'sponsor.member.sessionMemberId:' . $bill_sponsor . ' @ ' :
      $search_term .= '';
    !empty($bill_status) ? $search_term .= 'status.statusType:' . $bill_status . ' @ ' : $search_term .= '';
    !empty($bill_committee) ? $search_term .= 'status.committeeName:' . $bill_committee . ' @ ' : $search_term .= '';
    !empty($bill_issue) ?? $search_term = 'status.statusType:' . $bill_issue;

    $search_term_replaced = substr(str_replace('@', 'AND', $search_term), 0, -4);

    // Search information will search in service.
    $api_result = $openApiService->getSearch('bill', $search_term_replaced);
    $results = $api_result->result()->items;

    \Drupal::messenger()->addMessage(t('Form submitted successfully.'));

    return $form;

  }

}
