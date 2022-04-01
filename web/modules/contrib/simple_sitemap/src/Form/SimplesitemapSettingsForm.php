<?php

namespace Drupal\simple_sitemap\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Class SimplesitemapSettingsForm
 * @package Drupal\simple_sitemap\Form
 */
class SimplesitemapSettingsForm extends SimplesitemapFormBase {

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * SimplesitemapSettingsForm constructor.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   * @param \Drupal\simple_sitemap\Form\FormHelper $form_helper
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  public function __construct(
    Simplesitemap $generator,
    FormHelper $form_helper,
    LanguageManagerInterface $language_manager
  ) {
    parent::__construct(
      $generator,
      $form_helper
    );
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.form_helper'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_sitemap_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['simple_sitemap_settings']['#prefix'] = FormHelper::getDonationText();

    $form['simple_sitemap_settings']['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
    ];

    $form['simple_sitemap_settings']['settings']['cron_generate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Regenerate the sitemaps during cron runs'),
      '#description' => $this->t('Uncheck this if you intend to only regenerate the sitemaps manually or via drush.'),
      '#default_value' => $this->generator->getSetting('cron_generate', TRUE),
    ];

    $form['simple_sitemap_settings']['settings']['cron_generate_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Sitemap generation interval'),
      '#description' => $this->t('The sitemap will be generated according to this interval.'),
      '#default_value' => $this->generator->getSetting('cron_generate_interval', 0),
      '#options' => FormHelper::getCronIntervalOptions(),
      '#states' => [
        'visible' => [':input[name="cron_generate"]' => ['checked' => TRUE]],
      ],
    ];

    $form['simple_sitemap_settings']['settings']['xsl'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add styling and sorting to sitemaps'),
      '#description' => $this->t('If checked, sitemaps will be displayed as tables with sortable entries and thus become much friendlier towards human visitors. Search engines will not care.'),
      '#default_value' => $this->generator->getSetting('xsl', TRUE),
    ];

    $form['simple_sitemap_settings']['settings']['languages'] = [
      '#type' => 'details',
      '#title' => $this->t('Language settings'),
      '#open' => FALSE,
    ];

    $form['simple_sitemap_settings']['settings']['languages']['disable_language_hreflang'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove hreflang markup in HTML'),
      '#description' => $this->t('Google recommends displaying hreflang definitions either in the HTML markup or in the sitemap, but not in both places.<br>If checked, hreflang definitions created by the language module will be removed from the markup reducing its size.'),
      '#default_value' => $this->generator->getSetting('disable_language_hreflang', FALSE),
    ];

    $form['simple_sitemap_settings']['settings']['languages']['skip_untranslated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip non-existent translations'),
      '#description' => $this->t('If checked, entity links are generated exclusively for languages the entity has been translated to as long as the language is not excluded below.<br>Otherwise entity links are generated for every language installed on the site apart from languages excluded below.<br>Bear in mind that non-entity paths like homepage will always be generated for every non-excluded language.'),
      '#default_value' => $this->generator->getSetting('skip_untranslated', FALSE),
    ];

    $language_options = [];
    foreach ($this->languageManager->getLanguages() as $language) {
      if (!$language->isDefault()) {
        $language_options[$language->getId()] = $language->getName();
      }
    }
    $form['simple_sitemap_settings']['settings']['languages']['excluded_languages'] = [
      '#title' => $this->t('Exclude languages'),
      '#type' => 'checkboxes',
      '#options' => $language_options,
      '#description' => !empty($language_options)
        ? $this->t('There will be no links generated for languages checked here.')
        : $this->t('There are no languages other than the default language <a href="@url">available</a>.', ['@url' => $GLOBALS['base_url'] . '/admin/config/regional/language']),
      '#default_value' => $this->generator->getSetting('excluded_languages', []),
    ];

    $form['simple_sitemap_settings']['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => TRUE,
    ];

    $variants = $this->generator->getSitemapManager()->getSitemapVariants(NULL, FALSE);
    $default_variant = $this->generator->getSetting('default_variant');
    $form['simple_sitemap_settings']['advanced']['default_variant'] = [
      '#type' => 'select',
      '#title' => $this->t('Default sitemap variant'),
      '#description' => $this->t('This sitemap variant will be available under <em>/sitemap.xml</em> in addition to its default path <em>/variant-name/sitemap.xml</em>.<br>Variants can be configured <a href="@url">here</a>.', ['@url' => $GLOBALS['base_url'] . '/admin/config/search/simplesitemap/variants']),
      '#default_value' => isset($variants[$default_variant]) ? $default_variant : '',
      '#options' => ['' => $this->t('- None -')] + array_map(function($variant) { return $this->t($variant['label']); }, $variants),
      ];

    $form['simple_sitemap_settings']['advanced']['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default base URL'),
      '#default_value' => $this->generator->getSetting('base_url', ''),
      '#size' => 30,
      '#description' => $this->t('On some hosting providers it is impossible to pass parameters to cron to tell Drupal which URL to bootstrap with. In this case the base URL of sitemap links can be overridden here.<br>Example: <em>@url</em>', ['@url' => $GLOBALS['base_url']]),
    ];

    $form['simple_sitemap_settings']['advanced']['remove_duplicates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude duplicate links'),
      '#description' => $this->t('Prevent per-sitemap variant duplicate links.<br>Unchecking this may help avoiding PHP memory errors on huge sites.'),
      '#default_value' => $this->generator->getSetting('remove_duplicates', TRUE),
    ];

    $form['simple_sitemap_settings']['advanced']['max_links'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum links in a sitemap'),
      '#min' => 1,
      '#description' => $this->t('The maximum number of links one sitemap can hold. If more links are generated than set here, a sitemap index will be created and the links split into several sub-sitemaps.<br>50 000 links is the maximum Google will parse per sitemap, but choosing a lower value may be needed to avoid PHP memory errors on huge sites.<br>If left blank, all links will be shown on a single sitemap.'),
      '#default_value' => $this->generator->getSetting('max_links'),
    ];

    $form['simple_sitemap_settings']['advanced']['generate_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Sitemap generation max duration'),
      '#min' => 1,
      '#description' => $this->t('The maximum duration <strong>in seconds</strong> the generation task can run during a single cron run or during one batch process iteration.<br>The higher the number, the quicker the generation process, but higher the risk of PHP timeout errors.'),
      '#default_value' => $this->generator->getSetting('generate_duration', 10000) / 1000,
      '#required' => TRUE,
    ];

    $form['simple_sitemap_settings']['advanced']['entities_per_queue_item'] = [
      '#type' => 'number',
      '#title' => $this->t('Entities per queue item'),
      '#min' => 1,
      '#description' => $this->t('The number of entities to process in each queue item.<br>Increasing this number will use more memory but will result in less queries improving generation speed.'),
      '#default_value' => $this->generator->getSetting('entities_per_queue_item', 50),
    ];

    $this->formHelper->displayRegenerateNow($form['simple_sitemap_settings']);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $base_url = $form_state->getValue('base_url');
    $form_state->setValue('base_url', rtrim($base_url, '/'));
    if ($base_url !== '' && !UrlHelper::isValid($base_url, TRUE)) {
      $form_state->setErrorByName('base_url', $this->t('The base URL is invalid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach (['max_links',
               'cron_generate',
               'cron_generate_interval',
               'remove_duplicates',
               'skip_untranslated',
               'xsl',
               'base_url',
               'default_variant',
               'disable_language_hreflang',
               'entities_per_queue_item'] as $setting_name) {
      $this->generator->saveSetting($setting_name, $form_state->getValue($setting_name));
    }
    $this->generator->saveSetting('excluded_languages', array_filter($form_state->getValue('excluded_languages')));
    $this->generator->saveSetting('generate_duration', $form_state->getValue('generate_duration') * 1000);

    parent::submitForm($form, $form_state);

    // Regenerate sitemaps according to user setting.
    if ($form_state->getValue('simple_sitemap_regenerate_now')) {
      $this->generator->setVariants(TRUE)
        ->rebuildQueue()
        ->generateSitemap();
    }
  }
}
