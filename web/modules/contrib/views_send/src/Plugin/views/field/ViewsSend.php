<?php

namespace Drupal\views_send\Plugin\views\field;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\BulkForm;
use Drupal\Core\File\FileSystemInterface;

/**
 * Defines a simple send mass mail form element.
 *
 * @ViewsField("views_send_bulk_form")
 */
class ViewsSend extends BulkForm {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['enable_excluded_fields'] = [
      'default' => 1  ,
    ];

    return $options;
  }

  /**
   * Overrides \Drupal\system\Plugin\views\field\BulkForm::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    // Hide options that are irrelevant.
    $form['element_label_colon']['#access'] = FALSE;
    $form['exclude']['#access'] = FALSE;
    $form['alter']['#access'] = FALSE;
    $form['empty_field_behavior']['#access'] = FALSE;
    // Hide the available actions configuration as we haven't defined a bulk action to select from.
    $form['action_title']['#access'] = FALSE;
    $form['include_exclude']['#access'] = FALSE;
    $form['selected_actions']['#access'] = FALSE;
    $form['enable_excluded_fields'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use excluded fields as tokens'),
      '#default_value' => $this->options['enable_excluded_fields'],
    ];
  }

  /**
   * Overrides \Drupal\system\Plugin\views\field\BulkForm::viewsForm().
   */
  function viewsForm(&$form, FormStateInterface $form_state) {
    parent::viewsForm($form, $form_state);

    // The view is empty, abort.
    if (empty($this->view->result)) {
      return;
    }

    // Add the custom CSS for all steps of the form.
    $form['#attached']['library'][] = 'views_send/views_send.form';

    // Remove standard header which is used to select action
    unset($form['header']);

    $step = $form_state->get('step');
    if ($step == 'views_form_views_form') {
      $form['actions']['submit']['#value'] = $this->t('Next', [], ['context' => 'views_send: Go to configure mail']);
      $form['#prefix'] = '<div class="views-send-selection-form">';
      $form['#suffix'] = '</div>';
    }
    else {
      // Hide the normal output from the view
      unset($form['output']);
      $step($form, $form_state, $this->view);
    }
  }

  /**
   * Overrides \Drupal\system\Plugin\views\field\BulkForm::getBulkOptions().
   */
  protected function getBulkOptions($filtered = TRUE) {
    return [];
  }

  /**
   * Overrides \Drupal\system\Plugin\views\field\BulkForm::viewsFormSubmit().
   */
  function viewsFormSubmit(&$form, FormStateInterface $form_state) {
    switch ($form_state->get('step')) {
      case 'views_form_views_form':
        $field_name = $this->options['id'];
        $selection = array_filter($form_state->getValue($field_name));
        $form_state->set('selection', array_keys($selection));
        $form_state->set('step', 'views_send_config_form');
        // Preserve the URL as it gets lost if block display and batch API.
        if ($this->view->hasUrl()) {
          $url = $this->view->getUrl();
        } else {
          // For some reason Url::fromRoute('<current>') doesn't work.
          $url = Url::fromUserInput(\Drupal::service('path.current')->getPath());
        }
        $query = UrlHelper::filterQueryParameters($_GET, array('q'));
        $form_state->set('url', $url->setOption('query', $query));
        $form_state->setRebuild(TRUE);
        break;

      case 'views_send_config_form':
        $display = $form['display']['#value'];
        $config = \Drupal::configFactory()->getEditable('views_send.user_settings');
        $config_basekey = $display . '.uid:' . \Drupal::currentUser()->id();
        $form_state_values = $form_state->getValues();
        if ($form_state->getValue('views_send_remember')) {
          foreach ($form_state_values as $key => $value) {
            $key = ($key == 'format') ? 'views_send_message_format' : $key;
            if (substr($key, 0, 11) == 'views_send_') {
              $config->set($config_basekey . '.' . substr($key,11), $value);
            }
          }
          $config->save();
        } else {
          $config->clear($config_basekey);
          $config->save();
        }
        $form_state->set('configuration', $form_state_values);

        // If a file was uploaded, process it.
        if (VIEWS_SEND_MIMEMAIL && \Drupal::currentUser()->hasPermission('attachments with views_send') &&
            isset($_FILES['files']) && is_uploaded_file($_FILES['files']['tmp_name']['views_send_attachments'])) {
          // attempt to save the uploaded file
          $dir = \Drupal::config('system.file')->get('default_scheme') . '://views_send_attachments';
          \Drupal::service('file_system')->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
          $files = file_save_upload('views_send_attachments', [], $dir);
          // set error if file was not uploaded
          if (!$files) {
            $form_state->setErrorByName('views_send_attachment', $this->t('Error uploading file.'));
          }
          else {
            // set files to form_state, to process when form is submitted
            // @todo: when we add a multifile formfield then loop through to add each file to attachments array
            $form_state->set(array('configuration', 'views_send_attachments'), $files);
          }
        }

        $form_state->set('step', 'views_send_confirm_form');
        $form_state->setRebuild(TRUE);
        break;

      case 'views_send_confirm_form':

        // Queue the email for sending.
        views_send_queue_mail($form_state->get('configuration'), $form_state->get('selection'), $this->view);

        $form_state->setRedirectUrl($form_state->get('url'));
        break;
    }
  }

  /**
   * Overrides \Drupal\system\Plugin\views\field\BulkForm::::viewsFormValidate().
   */
  function viewsFormValidate(&$form, FormStateInterface $form_state) {
    if ($form_state->get('step') != 'views_form_views_form') {
      return;
    }
    // Only the first initial form is handled here.
    $field_name = $this->options['id'];
    $selection = array_filter($form_state->getValue($field_name));

    if (empty($selection)) {
      $form_state->setErrorByName($field_name, $this->t('Please select at least one item.'));
    }
  }
}
