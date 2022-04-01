<?php

namespace Drupal\site_verify\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete a forum term.
 */
class SiteVerifyDeleteForm extends ConfirmFormBase {

  protected $siteVerify = NULL;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['site_verify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_verify_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if (!empty($this->siteVerify)) {
      $record = \Drupal::service('site_verify_service')->siteVerifyLoad($this->siteVerify);
      return $this->t('Are you sure you want to delete the site verification %label?', ['%label' => $record['engine']['name']]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('site_verify.verifications_list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $site_verify = NULL) {

    $this->siteVerify = $site_verify;
    $record = \Drupal::service('site_verify_service')->siteVerifyLoad($this->siteVerify);

    $form = parent::buildForm($form, $form_state);

    $form['record'] = [
      '#type' => 'value',
      '#value' => $record,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $record = $form_state->getValue('record');
    \Drupal::database()->delete('site_verify')
      ->condition('svid', $record['svid'])
      ->execute();
    $this->messenger()->addStatus(t('Verification for %engine has been deleted.', [
      '%engine' => $record['engine']['name'],
    ]));
    \Drupal::logger('site_verify')->notice(t('Verification for %engine deleted.', [
      '%engine' => $record['engine']['name'],
    ]));
    $form_state->setRedirect('site_verify.verifications_list');

    // Set the menu to be rebuilt.
    \Drupal::service('router.builder')->setRebuildNeeded();
  }

}
