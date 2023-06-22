<?php

namespace Drupal\nys_sendgrid\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\nys_sendgrid\TemplatesManager;

/**
 * Configuration form for nys_sendgrid module.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * A shortcut to the nys_sendgrid.settings config collection.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $localConfig;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);

    $this->localConfig = $this->config('nys_sendgrid.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'nys_sendgrid_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Get the existing API key.  This will not be rendered, but it is used for
    // specific warning text.  We need an immutable config instance to see any
    // overrides.
    $apikey = $this->configFactory->get('nys_sendgrid.settings')
      ->get('api_key');
    $apikey_text = $apikey
        ? "An API key is already saved.  Leave the box blank to keep it, or input a new one to change it."
        : "<h2><b>No API key has been configured.  The API key is required before attempting to send mail.</b></h2>";
    $apikey_required = !((boolean) $apikey);

    $base_select = $this->constructTemplateSelect();

    $form = [
      'nys_sendgrid_api_frame' => [
        '#type' => 'fieldset',
        '#title' => 'API Settings',
        '#description' => $this->t('Basic requirements for API calls.'),
        '#description_display' => 'before',
        'nys_sendgrid_apikey' => [
          '#type' => 'password',
          '#required' => $apikey_required,
          '#title' => 'SendGrid API Key',
          '#description' => $apikey_text,
          '#default_value' => '',
        ],
      ],

      'nys_sendgrid_behavior_frame' => [
        '#type' => 'fieldset',
        '#title' => 'Behavioral Options',
        '#description' => $this->t('Manage options controlling how the module will construct Mail objects.'),
        '#description_display' => 'before',
        'suppress_categories' => [
          '#type' => 'checkbox',
          '#title' => 'Suppress category auto-assignment',
          '#description' => $this->t('By default, NYS SendGrid will add the originating module name and email key as categories for SendGrid.  Checking this box will suppress this behavior.  This feature can be reproduced on an ad-hoc basis by setting params["suppress_categories"] to TRUE.'),
          '#default_value' => $this->localConfig->get('suppress_categories') ?? FALSE,
        ],
      ],

      'nys_sendgrid_template_frame' => [
        '#type' => 'fieldset',
        '#title' => 'Templates',
        '#description' => $this->t('Manage how the module behaves with template-related options.'),
        '#description_display' => 'before',
        'default_template' => array_merge(
          $base_select, [
            '#title' => 'Default Template',
            '#description' => $this->t('The template ID to attach to SendGrid requests when one is not specified by the caller or matched to an assignment.'),
            '#default_value' => $this->localConfig->get('default_template') ?? 0,
          ]
        ),

        'nys_sendgrid_add_assignment' => [
          '#prefix' => '<div class="nys_sendgrid_template_wrapper"><div>Add a template assignment to a specific mail key/ID.  The mail ID is generated as &quot;<i>&lt;module&gt;_&lt;mail_name&gt;</i>&quot;. <br />Wildcards using just &quot;<i>&lt;module&gt;</i>&quot; are acceptable as well.</div>',
          '#suffix' => '</div>',
          'add_assign_name' => [
            '#type' => 'textfield',
            '#title' => 'Key/ID',
            '#default_value' => '',
            '#size' => 20,
          ],
          'add_assign_id' => ['#title' => 'Template'] + $base_select,
        ],

        'nys_sendgrid_assigns_frame' => [
          '#type' => 'fieldset',
          '#title' => 'Current Assignments',
          '#description' => $this->t('Lists any existing template assignments by mail key.  To remove an entry, set the template to "None".'),
          '#description_display' => 'before',
        ],

        'suppress_template' => [
          '#type' => 'checkbox',
          '#title' => 'Suppress Templates',
          '#description' => $this->t('Prevents automatic template assignment, for both default and mail-specific templates.  This feature can be reproduced on an ad-hoc basis by setting params["suppress_template"] to TRUE.'),
          '#default_value' => $this->localConfig->get('suppress_template') ?? FALSE,
        ],

        'content_substitution' => [
          '#type' => 'checkbox',
          '#title' => 'Auto-populate Content Tokens',
          '#description' => $this->t("Enables the auto-population of the {{{body}}} and {{{subject}}} tokens for dynamic templates.  The values for those tokens will be derived from mail object's respective values."),
          '#default_value' => $this->localConfig->get('content_substitution') ?? FALSE,
        ],
        'content_token_body' => [
          '#type' => 'textfield',
          '#default_value' => $this->localConfig->get('content_token_body') ?? 'body',
          '#title' => 'Token Name for Body',
          '#description' => $this->t('When auto-populating the body content into a dynamic token, use this token name.'),
        ],
        'content_token_subject' => [
          '#type' => 'textfield',
          '#default_value' => $this->localConfig->get('content_token_subject') ?? 'subject',
          '#title' => 'Token Name for Subject',
          '#description' => $this->t('When auto-populating the subject into a dynamic token, use this token name.'),
        ],
      ],
    ];

    // Create the assignment lists.
    $assigns = [];
    $assigned = $this->localConfig->get('template_assignments') ?: [];
    foreach ($assigned as $t_name => $t_id) {
      $ctrl = array_merge(
            $base_select, [
              '#default_value' => $t_id,
              '#title' => 'Template',
            ]
        );
      $input = [
        '#type' => 'textfield',
        '#default_value' => $t_name,
        '#title' => 'Key/ID',
        '#size' => 20,
      ];
      $assigns[] = [
        'mail_key' => $input,
        'template_id' => $ctrl,
        '#prefix' => '<div class="nys_sendgrid_template_wrapper">',
        '#suffix' => '</div>',
      ];
    }
    if (count($assigns)) {
      $assigns['#tree'] = TRUE;
      $assigns['#parents'] = ['templates'];
    }
    else {
      $assigns['#markup'] = '<p>No mail key assignments have been made.</p>';
    }
    $form['nys_sendgrid_template_frame']['nys_sendgrid_assigns_frame']['template_assigns'] = $assigns;

    return parent::buildForm($form, $form_state);
  }

  /**
   * Helper function to create a Drupal Form API element for SendGrid templates.
   *
   * @param string|int $selected
   *   The template ID to be marked as selected.
   * @param string $title
   *   The title of the form element.
   * @param string $description
   *   The description of the form element.
   *
   * @return array
   *   The element, as a Drupal Forms API array.
   */
  protected function constructTemplateSelect($selected = 0, string $title = '', string $description = ''): array {
    $templates = TemplatesManager::getTemplates();
    $options = [];
    foreach ($templates as $key => $val) {
      $options[$key] = $val->getName();
    }

    return [
      '#type' => 'select',
      '#title' => $title,
      '#description' => $description,
      '#options' => array_merge([0 => 'None'], $options),
      '#default_value' => $selected,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!empty($values['api_key'])) {
      $this->localConfig->set('api_key', Html::escape($values['api_key']));
    }
    if (isset($values['suppress_categories'])) {
      $this->localConfig->set('suppress_categories', (boolean) $values['suppress_categories']);
    }
    $this->localConfig
      ->set('default_template', $values['default_template'])
      ->set('suppress_template', (boolean) $values['suppress_template'])
      ->set('content_substitution', (boolean) $values['content_substitution'])
      ->set('content_token_body', $values['content_token_body'])
      ->set('content_token_subject', $values['content_token_subject']);

    $assigns = [];
    if (!empty($values['templates'] ?? [])) {
      foreach ($values['templates'] as $val) {
        if ($val['template_id']) {
          $assigns[$val['mail_key']] = $val['template_id'];
        }
      }
    }
    if (!(empty($values['add_assign_id']) && empty($values['add_assign_name']))) {
      $assigns[$values['add_assign_name']] = $values['add_assign_id'];
    }
    $this->localConfig
      ->set('template_assignments', $assigns)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['nys_sendgrid.settings'];
  }

}
