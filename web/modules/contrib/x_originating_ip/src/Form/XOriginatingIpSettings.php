<?php

namespace Drupal\x_originating_ip\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for X Originating IP administrative form.
 */
class XOriginatingIpSettings extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'x_originating_ip_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['x_originating_ip.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('x_originating_ip.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $headers = _x_originating_ip_headers();
    $config = $this->configFactory->getEditable('x_originating_ip.settings');
    $form['x_originating_ip_header'] = [
      '#type' => 'radios',
      '#title' => $this->t('Email origin header'),
      '#default_value' => $config->get('x_originating_ip_header'),
      '#options' => $headers,
      '#description' => $this->t('Though Microsoft made the X-Originating-IP header popular with Hotmail, various development how-to documents have proposed alternative headers listed here.'),
    ];

    return parent::buildForm($form, $form_state);

  }

}
