<?php

namespace Drupal\charts\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Charts Config Form.
 */
class ChartsConfigForm extends ConfigFormBase {

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a new ChartsConfigForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   Cache tag invalidator.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    parent::__construct($config_factory);
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'charts_form_base';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['charts.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $default_config = $this->config('charts.settings')->get('charts_default_settings') ?: [];

    $form['help'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('The settings on this page are used to set
        <strong>default</strong> settings. They do not affect existing charts.
        To make a new chart, create a new view and select the display format of
        "Chart." Or use a Charts Block and add your own data inside that block.
        You can also attach a Chart field to your content (or other entity)
        type and add your data within the Chart field.'),
    ];
    $form['settings'] = [
      '#type' => 'charts_settings',
      '#used_in' => 'config_form',
      '#required' => TRUE,
      '#default_value' => $default_config,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValue('settings');
    // The settings form element is returning an unneeded 'defaults' value.
    if (isset($settings['defaults'])) {
      unset($settings['defaults']);
    }

    // Process the default colors to remove unneeded data.
    foreach ($settings['display']['colors'] as $color_index => $color_item) {
      $settings['display']['colors'][$color_index] = $color_item['color'];
    }

    // Save the main settings.
    $config = $this->config('charts.settings');
    $config->set('charts_default_settings', $settings)
      ->save();

    // Invalidate cache tags to refresh any view relying on this.
    $this->cacheTagsInvalidator->invalidateTags($config->getCacheTags());

    parent::submitForm($form, $form_state);
  }

}
