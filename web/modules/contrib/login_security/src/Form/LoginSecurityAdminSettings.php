<?php

namespace Drupal\login_security\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Providing a class for LoginSecurityAdminSettings form.
 *
 * @package Drupal\login_security\Form
 */
class LoginSecurityAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_destination_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $config = $this->config('login_security.settings');

    $form['general_settings'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('General settings'),
    ];
    $form['general_settings']['track_time'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Track time'),
      '#default_value' => $config->get('track_time'),
      '#size' => 3,
      '#description' => $this->t('The time window to check for security violations: the time in minutes the login information is kept to compute the login attempts count. A common example could be 1440 minutes (24 hours). After that time, the attempt is deleted from the list, and will never be considered again.'),
      '#field_suffix' => $this->t('minute(s)'),
    ];
    $form['general_settings']['user_wrong_count'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('User'),
      '#default_value' => $config->get('user_wrong_count'),
      '#size' => 3,
      '#description' => $this->t('Enter the number of login failures a user is allowed. <br>
        After this amount is reached, the user will be blocked, no matter the host attempting to log in. Use this option carefully on public sites, as an attacker may block your site users. <br>
        The user blocking protection will not disappear and should be removed manually from the <a href=":user">user management</a> interface.', [':user' => $base_url . '/admin/people']),
      '#field_suffix' => $this->t('failed attempts'),
    ];
    $form['general_settings']['host_wrong_count'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Soft host'),
      '#default_value' => $config->get('host_wrong_count'),
      '#size' => 3,
      '#description' => $this->t('Enter the number of login failures a host is allowed. <br>
        After this amount is reached, the host will not be able to submit the log in form again, but can still browse the site contents as an anonymous user. <br>
        This protection is effective during the time indicated at tracking time option.'),
      '#field_suffix' => $this->t('failed attempts'),
    ];
    $form['general_settings']['host_wrong_count_hard'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Host'),
      '#default_value' => $config->get('host_wrong_count_hard'),
      '#size' => 3,
      '#description' => $this->t('Enter the number of login failures a host is allowed. <br>
        After this number is reached, the host will be blocked, no matter the username attempting to log in. <br>
        The host blocking protection will not disappear automatically and should be removed manually from the <a href=":access">access rules</a> administration interface.', [':access' => $base_url . '/admin/config/people/ban']),
      '#field_suffix' => $this->t('failed attempts'),
    ];
    $form['general_settings']['activity_threshold'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Attack detection'),
      '#default_value' => $config->get('activity_threshold'),
      '#size' => 3,
      '#description' => $this->t('Enter the number of login failures before creating a warning log entry about this suspicious activity. <br>
        If the number of invalid login events currently being tracked reach this number, and ongoing attack is detected.'),
      '#field_suffix' => $this->t('failed attempts'),
    ];
    $form['notification'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Notification'),
    ];
    $form['notification']['disable_core_login_error'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable login failure error message'),
      '#description' => $this->t('Prevents the display of login error messages. <br>
        A user attempting to login will not be aware if the account exists, an invalid user name or password has been submitted, or if the account is blocked. The core messages are also hidden.'),
      '#default_value' => $config->get('disable_core_login_error'),
    ];
    $form['notification']['notice_attempts_available'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify the user about the number of remaining login attempts'),
      '#default_value' => $config->get('notice_attempts_available'),
      '#description' => $this->t('The user is notified about the number of remaining login attempts before the account gets blocked. <br>
        Security tip: If you enable this option, try to not disclose as much of your login policies as possible in the message shown on any failed login attempt.'),
    ];
    $form['notification']['last_login_timestamp'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display last login timestamp'),
      '#description' => $this->t('When a user successfully logs in, a message will display the last time he logged into the site.'),
      '#default_value' => $config->get('last_login_timestamp'),
    ];
    $form['notification']['last_access_timestamp'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display last access timestamp'),
      '#description' => $this->t('When a user successfully logs in, a message will display the last site access with this account.'),
      '#default_value' => $config->get('last_access_timestamp'),
    ];
    $form['notification']['login_activity_email'] = [
      '#type' => 'details',
      '#title' => $this->t('Email for ongoing attack detection'),
    ];
    $form['notification']['login_activity_email']['login_activity_notification_emails'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#description' => $this->t('Provide a comma-separated list of emails for who should receive an email message when an ongoing attack is detected. No notification will be sent if the field is blank.'),
      '#default_value' => $config->get('login_activity_notification_emails'),
    ];
    $form['notification']['login_activity_email']['login_activity_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('login_activity_email_subject'),
    ];
    $form['notification']['login_activity_email']['login_activity_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('login_activity_email_body'),
    ];
    $form['notification']['user_block_email'] = [
      '#type' => 'details',
      '#title' => $this->t('Email for blocked account'),
    ];
    $form['notification']['user_block_email']['user_blocked_notification_emails'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#description' => $this->t('Provide a comma-separated list of emails for who should receive an email message when a user is blocked. No notification will be sent if the field is blank.'),
      '#default_value' => $config->get('user_blocked_notification_emails'),
    ];
    $form['notification']['user_block_email']['user_blocked_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('user_blocked_email_subject'),
    ];
    $form['notification']['user_block_email']['user_blocked_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('user_blocked_email_body'),
    ];
    $form['notification']['message']['notice_attempts_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Failed login attempt'),
      '#rows' => 2,
      '#default_value' => $config->get('notice_attempts_message'),
      '#description' => $this->t('Enter the message string to be shown if the login fails after the form is submitted.'),
    ];
    $form['notification']['message']['host_soft_banned'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Banned host (soft IP ban)'),
      '#rows' => 2,
      '#default_value' => $config->get('host_soft_banned'),
      '#description' => $this->t('Enter the soft IP ban message to be shown when a host attempts to log in too many times.'),
    ];
    $form['notification']['message']['host_hard_banned'] = [
      '#type' => 'textarea',
      '#rows' => 2,
      '#title' => $this->t('Banned host (hard IP ban)'),
      '#default_value' => $config->get('host_hard_banned'),
      '#description' => $this->t('Enter the hard IP ban message to be shown when a host attempts to log in too many times.'),
    ];
    $form['notification']['message']['user_blocked'] = [
      '#type' => 'textarea',
      '#rows' => 2,
      '#title' => $this->t('Blocked user by uid'),
      '#default_value' => $config->get('user_blocked'),
      '#description' => $this->t('Enter the message to be shown when a user gets blocked due to enough failed login attempts.'),
    ];
    $form['notification']['tokens'] = [
      '#type' => 'item',
      '#title' => $this->t('Tokens'),
      '#description' => $this->t("<ul><li>%date: The (formatted) date and time of the event.</li><li>%ip: The IP address tracked for this event.</li><li>%username: The username entered in the login form (sanitized).</li><li>%email: If the user exists, this will be the email address.</li><li>%uid: If the user exists, this will be the user uid.</li><li>%site: The name of the site as configured in the administration.</li><li>%uri: The base url of this Drupal site.</li><li>%edit_uri: Direct link to the user (based on the name entered) edit page.</li><li>%hard_block_attempts: Configured maximum attempts before hard blocking the IP address.</li><li>%soft_block_attempts: Configured maximum attempts before soft blocking the IP address.</li><li>%user_block_attempts: Configured maximum login attempts before blocking the user.</li><li>%user_ip_current_count: The total attempts for this user name tracked from this IP address.</li><li>%ip_current_count: The total login attempts tracked from from this IP address.</li><li>%user_current_count: The total login attempts tracked for this user name .</li><li>%tracking_time: The tracking time value: in hours.</li><li>%tracking_current_count: Total tracked events</li><li>%activity_threshold: Value of attempts to detect ongoing attack.</li></ul>"),
    ];

    // Clean event tracking list.
    $form['actions']['clean_tracked_events'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear event tracking information'),
      '#weight' => 20,
      '#submit' => ['::cleanTrackedEvents'],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()->getEditable('login_security.settings')
      ->set('track_time', $form_state->getValue('track_time'))
      ->set('user_wrong_count', $form_state->getValue('user_wrong_count'))
      ->set('host_wrong_count', $form_state->getValue('host_wrong_count'))
      ->set('host_wrong_count_hard', $form_state->getValue('host_wrong_count_hard'))
      ->set('activity_threshold', $form_state->getValue('activity_threshold'))
      ->set('disable_core_login_error', $form_state->getValue('disable_core_login_error'))
      ->set('notice_attempts_available', $form_state->getValue('notice_attempts_available'))
      ->set('last_login_timestamp', $form_state->getValue('last_login_timestamp'))
      ->set('last_login_timestamp', $form_state->getValue('last_login_timestamp'))
      ->set('last_access_timestamp', $form_state->getValue('last_access_timestamp'))
      ->set('login_activity_notification_emails', $form_state->getValue('login_activity_notification_emails'))
      ->set('user_blocked_notification_emails', $form_state->getValue('user_blocked_notification_emails'))
      ->set('notice_attempts_message', $form_state->getValue('notice_attempts_message'))
      ->set('host_soft_banned', $form_state->getValue('host_soft_banned'))
      ->set('host_hard_banned', $form_state->getValue('host_hard_banned'))
      ->set('user_blocked', $form_state->getValue('user_blocked'))
      ->set('user_blocked_email_subject', $form_state->getValue('user_blocked_email_subject'))
      ->set('user_blocked_email_body', $form_state->getValue('user_blocked_email_body'))
      ->set('login_activity_email_subject', $form_state->getValue('login_activity_email_subject'))
      ->set('login_activity_email_body', $form_state->getValue('login_activity_email_body'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Submit handler to clean the login_security_track table.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function cleanTrackedEvents(array &$form, FormStateInterface $form_state) {
    $count = _login_security_remove_all_events();
    $this->messenger()->addMessage($this->t('Login Security event track list is now empty. @count item(s) deleted.', ['@count' => $count]));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['login_security.settings'];
  }

}
