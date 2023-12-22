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

    // Find the date of the current history marker.
    $current = $statute->detail->result->activeDate ?? '--ERROR--';

    // Render the history form.
    return [
      'milestone' => [
        '#markup' => '<div class="nys-openleg-history-published">This entry was published on ' . $current . '<div class="nys-openleg-history-note"><div class="nys-openleg-history-note-text">The selection dates indicate all change milestones for the entire volume, not just the location being viewed.  Specifying a milestone date will retrieve the most recent version of the location before that date.</div></div></div>',
      ],
      'history' => [
        '#type' => 'select',
        '#options' => array_combine($statute->publishDates(), $statute->publishDates()),
        '#default_value' => $current,
        '#title' => 'See most recent version before or on: ',
        '#attributes' => [
          'onChange' => 'this.form.submit();',
        ],
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
