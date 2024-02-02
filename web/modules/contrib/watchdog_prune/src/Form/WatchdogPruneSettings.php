<?php

namespace Drupal\watchdog_prune\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class WatchdogPruneSettings.
 *
 * @package Drupal\watchdog_prune\Form.
 */
class WatchdogPruneSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "watchdog_prune_settings";
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'watchdog_prune.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('watchdog_prune.settings');
    $database = \Drupal::service('database.replica');

    $form['mark_top'] = [
      '#markup' => "<p>" . $this->t("This module allows you to delete watchdog entries, on cron run, based on certain criteria (like age or watchdog entry types).In order for this module to work, Drupal's built in setting <strong>Database log messages to keep</strong>
        must be set to <strong>All</strong>. <br><br><strong>You must have a correctly configured cron task for this module to work.</strong>") . "</p>",
    ];

    $form['core_fs'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('From Drupal Core'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['core_fs']['dblog_row_limit'] = [
      '#type' => 'select',
      '#title' => $this->t('[From Drupal Core:] Database log messages to keep'),
      '#options' => ['0' => 'All'],
      '#default_value' => 0,
      '#description' => $this->t('For this module to function, we must keep this Drupal Core setting set to <strong>All</strong>.  This setting is provided here simply as a reminder of where this setting is coming from.'),
    ];

    $prune_age_options = WatchdogPruneSettings::pruneAgeOptions();

    $form['watchdog_prune_age'] = [
      '#type' => 'select',
      '#title' => $this->t('Delete watchdog entries older than:'),
      '#options' => $prune_age_options,
      '#default_value' => empty($config->get('watchdog_prune_age')) ? '-18 MONTHS' : $config->get('watchdog_prune_age'),
      '#description' => $this->t('Watchdog entries older than this time will be deleted on each cron run. This will ignore all watchdog types entered in "Delete watchdog entries by type" settings.'),
    ];

    $watchdog_types = $database->query('SELECT DISTINCT(type) FROM {watchdog}')->fetchCol();

    if (count($watchdog_types) === 0) {
      $watchdog_types = $this->t('Watchdog is empty');
    }
    else {
      $watchdog_types = implode(', ', $watchdog_types);
    }

    $phpdate_reference = Link::fromTextAndUrl($this->t('PHP Date Manual'), Url::fromUri('http://php.net/manual/en/datetime.formats.relative.php', ['attributes' => ['target' => '_blank']]))->toString();

    $form['watchdog_prune_age_type'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Delete watchdog entries by type'),
      '#description' => $this->t('Configure different prune time for each watchdog type, enter separate values on new line. Currently <em>logged</em> watchdog entry types are (<em>' . $watchdog_types . '</em>). For all available values for age check the ' . $phpdate_reference . '
        <br><br>Insert values with format
        <br><b><h4>watchdog_entry_type|age</h4></b>
        <br>Examples
        <br><b>php|-1 MONTH</b>
        <br><b>system|-1 MONTH</b>
      </br>This will delete all watchdog entries of type php and system which are older than a month on cron run.'),
      '#rows' => 10,
      '#cols' => 40,
      '#default_value' => empty($config->get('watchdog_prune_age_type')) ? '' : $config->get('watchdog_prune_age_type'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $prune_type = $form_state->getValue('watchdog_prune_age_type');

    if (!empty($prune_type)) {

      $prune_type = explode("\n", $prune_type);

      if (is_array($prune_type)) {
        $current_date = strtotime('today');
        foreach ($prune_type as $key => $value) {
          $watchdog_prune_settings = explode("|", $value);
          $user_entered_date = strtotime(trim($watchdog_prune_settings[1]));
          if ($current_date < $user_entered_date) {
            $form_state->setErrorByName('watchdog_prune_age_type', $this->t('Incorrect value for <b>' . implode("|", $watchdog_prune_settings) . '</b> Watchdog Prune age must be older than todays date'));
          }
        }
      }
    }
  }

  /**
   * Implements submitForm().
   *
   * @param array $form
   *   Processess $form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Processess $form_state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $watchdog_prune_age = $form_state->getValue('watchdog_prune_age');
    $watchdog_prune_age_type = $form_state->getValue('watchdog_prune_age_type');
    $this->config('watchdog_prune.settings')
      ->set('watchdog_prune_age', $watchdog_prune_age)
      ->set('watchdog_prune_age_type', $watchdog_prune_age_type)
      ->save();
    \Drupal::messenger()->addMessage('The configuration options have been saved.', 'status');
  }

  /**
   * Implements pruneAgeOptions().
   *
   * Gets the Prune age options.
   *
   * @return array
   *   An array of Prune age options.
   */
  protected static function pruneAgeOptions() {
    $prune_age_options = [
      '' => t('None - do not prune based on age'),
      '-1 WEEK' => t('1 week'),
      '-2 WEEKS' => t('2 weeks'),
      '-3 WEEKS' => t('3 weeks'),
      '-1 MONTH' => t('1 month'),
      '-2 MONTHS' => t('2 months'),
      '-3 MONTHS' => t('3 months'),
      '-6 MONTHS' => t('6 months'),
      '-9 MONTHS' => t('9 months'),
      '-12 MONTHS' => t('12 months (1 year)'),
      '-18 MONTHS' => t('18 months (1.5 years)'),
      '-24 MONTHS' => t('24 months (2 years)'),
      '-30 MONTHS' => t('30 months (2.5 years)'),
      '-36 MONTHS' => t('36 months (3 years)'),
    ];

    return $prune_age_options;
  }

}
