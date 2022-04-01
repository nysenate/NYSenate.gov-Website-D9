<?php

namespace Drupal\image_style_quality\Plugin\ImageEffect;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\ConfigurableImageEffectBase;
use Drupal\Core\Form\FormStateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allows you to change the quality of an image, per image style.
 *
 * @ImageEffect(
 *   id = "image_style_quality",
 *   label = @Translation("Image Style Quality"),
 *   description = @Translation("Allows you to change the quality of an image, per image style.")
 * )
 */
class ImageStyleQualityImageEffect extends ConfigurableImageEffectBase {

  /**
   * A mutable quality toolkit.
   *
   * @var array
   */
  protected $mutableQualityToolkit;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    $this->configFactory->get($this->mutableQualityToolkit['config_object'])->setModuleOverride([
      $this->mutableQualityToolkit['config_key'] => $this->configuration['quality'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['quality'] = [
      '#type' => 'number',
      '#title' => $this->t('Quality'),
      '#description' => $this->t('Define the image quality for JPEG manipulations. Ranges from 0 to 100. Higher values mean better image quality but bigger files.'),
      '#min' => 0,
      '#max' => 100,
      '#default_value' => $this->configuration['quality'],
      '#field_suffix' => $this->t('%'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['quality'] = $form_state->getValue('quality');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#markup' => $this->t('(@quality% Quality)', ['@quality' => $this->configuration['quality']]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'quality' => 75,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, $toolkit, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
    $this->mutableQualityToolkit = $toolkit;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('image'),
      $container->get('image_style_quality.mutable_quality_toolkit_manager')->getActiveToolkit(),
      $container->get('config.factory')
    );
  }

}
