<?php

namespace Drupal\htmlpurifier\Plugin\Filter;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "htmlpurifier",
 *   title = @Translation("HTML Purifier"),
 *   description = @Translation("Removes malicious HTML code and ensures that the output is standards compliant."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR
 * )
 */
class HtmlPurifierFilter extends FilterBase {

  /**
   * Array of error messages from HTMLPurifier configuration assignments.
   *
   * @var array
   */
  protected $configErrors = [];

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if (!empty($this->settings['htmlpurifier_configuration'])) {
      $purifier_config = $this->applyPurifierConfig($this->settings['htmlpurifier_configuration']);
    }
    else {
      $purifier_config = \HTMLPurifier_Config::createDefault();
    }
    $purifier = new \HTMLPurifier($purifier_config);
    $purified_text = $purifier->purify($text);
    return new FilterProcessResult($purified_text);
  }

  /**
   * Applies the configuration to a HTMLPurifier_Config object.
   *
   * @param string $configuration
   *
   * @return \HTMLPurifier_Config
   */
  protected function applyPurifierConfig($configuration) {
    /* @var $purifier_config \HTMLPurifier_Config */
    $purifier_config = \HTMLPurifier_Config::createDefault();

    $settings = Yaml::decode($configuration);
    foreach ($settings as $namespace => $directives) {
      if (is_array($directives)) {
        foreach ($directives as $key => $value) {
          $purifier_config->set("$namespace.$key", $value);
        }
      }
      else {
        $this->configErrors[] = 'Invalid value for namespace $namespace, must be an array of directives.';
      }
    }

    return $purifier_config;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    if (empty($this->settings['htmlpurifier_configuration'])) {
      /* @var $purifier_config \HTMLPurifier_Config */
      $purifier_config = \HTMLPurifier_Config::createDefault();
      $default_value = Yaml::encode($purifier_config->getAll());
    }
    else {
      $default_value = $this->settings['htmlpurifier_configuration'];
    }

    $form['htmlpurifier_configuration'] = [
      '#type' => 'textarea',
      '#rows' => 50,
      '#title' => t('HTML Purifier Configuration'),
      '#description' => t('These are the config directives in YAML format, according to the <a href="@url">HTML Purifier documentation</a>', ['@url' => 'http://htmlpurifier.org/live/configdoc/plain.html']),
      '#default_value' => $default_value,
      '#element_validate' => [
        [$this, 'settingsFormConfigurationValidate'],
      ],
    ];

    return $form;
  }

  /**
   * Settings form validation callback for htmlpurifier_configuration element.
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function settingsFormConfigurationValidate($element, FormStateInterface $form_state) {
    $values = $form_state->getValue('filters');
    if (isset($values['htmlpurifier']['settings']['htmlpurifier_configuration'])) {
      $this->configErrors = [];

      // HTMLPurifier library uses triger_error() for not valid settings.
      set_error_handler([$this, 'configErrorHandler']);
      try {
        $this->applyPurifierConfig($values['htmlpurifier']['settings']['htmlpurifier_configuration']);
      }
      catch (\Exception $ex) {
        // This could be a malformed YAML or any other exception.
        $form_state->setError($element, $ex->getMessage());
      }
      restore_error_handler();

      if (!empty($this->configErrors)) {
        foreach ($this->configErrors as $error) {
          $form_state->setError($element, $error);
        }
        $this->configErrors = [];
      }
    }
  }

  /**
   * Custom error handler to manage invalid purifier configuration assignments.
   *
   * @param $errno
   * @param $errstr
   */
  public function configErrorHandler($errno, $errstr) {
    // Do not set a validation error if the error is about a deprecated use.
    if ($errno < E_DEPRECATED) {
      // \HTMLPurifier_Config::triggerError() adds ' invoked on line ...' to the
      // error message. Remove that part from our validation error message.
      $needle = 'invoked on line';
      $pos = strpos($errstr, $needle);
      if ($pos !== FALSE) {
        $message = substr($errstr, 0, $pos - 1);
        $this->configErrors[] = $message;
      }
      else {
        $this->configErrors[] = 'HTMLPurifier configuration is not valid. Error: ' . $errstr;
      }
    }
  }

}
