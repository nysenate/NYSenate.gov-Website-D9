<?php

namespace Drupal\nys_openleg\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\nys_openleg_api\Statute;

/**
 * Class HistoryForm.
 *
 * Form-handling class for NYS Openleg history selector.
 */
class HistoryForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'nys_openleg_history_form';
  }

  /**
   * {@inheritdoc}
   *
   * Requires an instance of Drupal\nys_openleg\Api\Request\Statute as the
   * first buildInfo argument.
   *
   * Note that this form is meant to embed into a page with its own controller.
   * The caller must deal with any submissions.
   *
   * The 'history' control could be empty, or a selected milestone as "Y-m-d",
   * or the string "current" (indicating "most recent revision").
   *
   * @throws \InvalidArgumentException
   *   If no Statute is received.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Validate the required buildInfo argument.
    $statute = $form_state->getBuildInfo()['args'][0] ?? NULL;
    if (!($statute instanceof Statute)) {
      throw new \InvalidArgumentException('HistoryForm requires a Statute object as an argument');
    }

    // Ensure this form is not cached.
    $form_state->disableCache();

    // Find the publish dates (most recent, and for this history marker)
    $current = $statute->getActiveDate() ?: '--ERROR--';
    $latest = $statute->getLatestActiveDate();

    // Get the relevant publish dates.  Reverse the sort order.
    $dates = $statute->getPublishDates();
    rsort($dates);

    // Build the milestone banner with conditional link or text.
    $is_most_recent = ($current === $latest);
    if ($is_most_recent) {
      $milestone_html = '<div class="nys-openleg-history-published">'
        . 'Viewing most recent revision (from ' . $current . ')'
        . '</div>';
    }
    else {
      $milestone_html = '<div class="nys-openleg-history-published">'
        . 'Viewing historical revision (from ' . $current . ')<br />'
        . '<a href="javascript:void(0);" class="nys-openleg-history-view-latest" data-latest="' . htmlspecialchars($latest) . '">Click here to view most recent revision (' . $latest . ')</a>'
        . '</div>';
    }

    // Render the history form.
    return [
      '#attached' => [
        'library' => ['nys_openleg/history-form'],
      ],
      'milestone' => [
        '#markup' => $milestone_html,
      ],
      'history' => [
        '#type' => 'select',
        '#empty_option' => '-- Choose a Date --',
        '#options' => array_combine($dates, $dates),
        '#default_value' => $current,
        '#title' => 'View historical revision as of: ',
        '#attributes' => [
          'onChange' => 'this.form.submit();',
        ],
      ],
      'history_note' => [
        '#markup' => '<div class="nys-openleg-history-note">NOTE: The above date options correlate to ALL revision records for the entire volume, not just the section being viewed. The retrieved revision will likely be from an earlier date than the chosen date.</div>',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Submissions are handled by MainController::browse().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
