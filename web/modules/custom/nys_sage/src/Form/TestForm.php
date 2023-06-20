<?php

namespace Drupal\nys_sage\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\nys_sage\Service\SageApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allow for calling the SAGE API and viewing the return.
 */
class TestForm extends FormBase {

  /**
   * Local config for nys_sage.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $localConfig;

  /**
   * SAGE API service object.
   *
   * @var \Drupal\nys_sage\Service\SageApi
   */
  protected SageApi $sageApi;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $config, SageApi $sage_api) {
    $this->localConfig = $config->get('nys_sage.settings');
    $this->sageApi = $sage_api;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): TestForm {
    return new static(
          $container->get('config.factory'),
          $container->get('sage_api')
      );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'nys_sage.test_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // @todo Build the options by scanning the namespace for classes.
    // Perhaps see how the plugin machinery does it.
    $form['test_call_method'] = [
      '#type' => 'select',
      '#title' => 'Group/Method',
      '#options' => ['district:assign' => $this->t('District: Assign')],
      '#description' => $this->t('The group and method to call.'),
      '#default_value' => $form_state->getValue('test_call_method'),
      '#required' => TRUE,
    ];

    $form['test_call_params'] = [
      '#type' => 'textarea',
      '#title' => "Parameters",
      '#cols' => 20,
      '#description' => $this->t("Parameters for the call.  Each line should be a single 'key=value' pair."),
      '#default_value' => $form_state->getValue('test_call_params'),
    ];

    $logging = (bool) $this->localConfig->get('logging');
    $log_desc = $logging
        ? $this->t("Logging is already on.")
        : $this->t("Force the call to be logged even though logging is turned off.");
    $form['test_call_log'] = [
      '#type' => 'checkbox',
      '#title' => 'Log this call?',
      '#disabled' => $logging,
      '#description' => $log_desc,
      '#default_value' => $logging || $form_state->getValue('test_call_log'),
    ];

    $form['test_call_go'] = [
      '#type' => 'submit',
      '#value' => 'Call SAGE',
    ];

    // Only add the result box if a call has been made.
    if ($result = $form_state->getValue('test_call_results')) {
      $form['test_call_results'] = [
        '#type' => 'textarea',
        '#title' => 'Call Results',
        '#description' => $this->t('An export of the JSON-decoded response.'),
        '#disabled' => TRUE,
        '#value' => $result,
        '#weight' => 100,
      ];
    }

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Collect the group and method.
    if (!($family = $form_state->getValue('test_call_method'))) {
      $form_state->setErrorByName('test_call_method', "Invalid method/group.  Please make a selection.");
      $family = '';
    }
    [$group, $method] = explode(':', (string) $family);

    if ($group && $method && !$form_state->getErrors()) {
      // Prep the parameters.
      $params = [];
      parse_str(str_replace("\n", '&', $form_state->getValue('test_call_params')), $params);
      $params += [
        'key' => $this->localConfig->get('api_key'),
      ];

      // Generate a request and execute it.  We avoid SageApi::call() because
      // we want to be able to force refresh and logging.
      $request = $this->sageApi->createRequest($group, $method, $params);
      $response = $request->execute(TRUE, $form_state->getValue('test_call_log'));

      // Set the value on the form, and set the rebuild state.
      $form_state->setValue('test_call_results', var_export($response, 1));
      $form_state->setRebuild(TRUE);
    }
  }

}
