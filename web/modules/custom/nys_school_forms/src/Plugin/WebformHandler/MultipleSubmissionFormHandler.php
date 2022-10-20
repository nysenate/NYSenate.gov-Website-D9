<?php
namespace Drupal\nys_school_forms\Plugin\WebformHandler;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\webformSubmissionInterface;
use Drupal\webform\WebformSubmissionForm;
use Drupal\user\Entity\User;

/**
 * Form submission handler.
 *
 * @WebformHandler(
 *   id = "multiple_submissions",
 *   label = @Translation("Multiple Submission Handler"),
 *   category = @Translation("Multiple Submission Handler"),
 *   description = @Translation("Create multiple submissions per every file"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class MultipleSubmissionFormHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
   public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $values = $webform_submission->getData();
    if (count($values['attach_your_submission']) > 0) {
      foreach ($values['attach_your_submission'] as $key => $submission) {
         if ($key === 0) {
           continue;
         }
         $new_submission = $values;
         unset($new_submission['attach_your_submission']);
         $new_submission['attach_your_submission'] = [];
         $new_submission['attach_your_submission'][0] = $values['attach_your_submission'][$key];
         $uid = User::load(\Drupal::currentUser()->id());
         $new_submission_values = [
          'webform_id' => 'school_form',
          'in_draft' => FALSE,
          'uid' => $uid,
          'langcode' => 'en',
          'uri' => 'form/school-form',
          'remote_addr' => '',
          'data' => [
            $new_submission
          ],
        ];
         $webform_submission = WebformSubmissionForm::submitFormValues($new_submission_values);
      }
      $new_first_submission = $values;
      unset($new_first_submission['attach_your_submission']);
      $new_first_submission['attach_your_submission'] = [];
      $new_first_submission['attach_your_submission'][0] = $values['attach_your_submission'][0];
      //$webform_submission->setData($new_first_submission);
      //$webform_submission->resave();
    }
  }
}
