<?php

namespace Drupal\simple_sitemap\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\simple_sitemap\Entity\SimpleSitemap;
use Drupal\simple_sitemap\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_sitemap\Manager\Generator;
use Drupal\Core\Path\PathValidatorInterface;

/**
 * Provides form to manage custom links.
 */
class CustomLinksForm extends SimpleSitemapFormBase {

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * CustomLinksForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\simple_sitemap\Manager\Generator $generator
   *   The sitemap generator service.
   * @param \Drupal\simple_sitemap\Settings $settings
   *   The simple_sitemap.settings service.
   * @param \Drupal\simple_sitemap\Form\FormHelper $form_helper
   *   Helper class for working with forms.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    Generator $generator,
    Settings $settings,
    FormHelper $form_helper,
    PathValidatorInterface $path_validator
  ) {
    parent::__construct(
      $config_factory,
      $generator,
      $settings,
      $form_helper
    );
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.settings'),
      $container->get('simple_sitemap.form_helper'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'simple_sitemap_custom_links_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $custom_link_settings = $this->generator->setVariants()->customLinkManager()->get();

    $form['custom_links'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Relative Drupal paths'),
      '#default_value' => $custom_link_settings ? $this->customLinksToString(reset($custom_link_settings)) : '',
      '#description' => $this->t("Please specify drupal internal (relative) paths, one per line. Do not forget to prepend the paths with a '/'.<br>Optionally link priority <em>(0.0 - 1.0)</em> can be added by appending it after a space.<br> Optionally link change frequency <em>(always / hourly / daily / weekly / monthly / yearly / never)</em> can be added by appending it after a space.<br/<br><strong>Examples:</strong><br><em>/ 1.0 daily</em> -> home page with the highest priority and daily change frequency<br><em>/contact</em> -> contact page with the default priority and no change frequency information"),
    ];

    $form['variants'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Sitemaps'),
      '#description' => $this->t('The sitemaps to include the above links in.<br>Sitemaps can be configured <a href="@url">here</a>.', ['@url' => $GLOBALS['base_url'] . '/admin/config/search/simplesitemap']),
      '#options' => array_map(
        function ($variant) {
          return $variant->label();
        },
        SimpleSitemap::loadMultiple()
      ),
      '#default_value' => array_keys(array_filter(
          $this->generator->setVariants()->customLinkManager()->get(),
          function ($e) {
            return !empty($e);
          })
      ),
    ];

    $form['include_images'] = [
      '#type' => 'select',
      '#title' => $this->t('Include images'),
      '#description' => $this->t('If a custom link points to an entity, include its referenced images in the sitemap.'),
      '#default_value' => $this->settings->get('custom_links_include_images', FALSE),
      '#options' => [0 => $this->t('No'), 1 => $this->t('Yes')],
    ];

    $form = $this->formHelper->regenerateNowForm($form);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('custom_links')) && empty($form_state->getValue('variants'))) {
      $form_state->setErrorByName('variants', $this->t('Custom links must be assigned to at least one sitemap.'));
    }

    foreach ($this->stringToCustomLinks($form_state->getValue('custom_links')) as $i => $link_config) {
      $placeholders = [
        '@line' => ++$i,
        '@path' => $link_config['path'],
        '@priority' => $link_config['priority'] ?? '',
        '@changefreq' => $link_config['changefreq'] ?? '',
        '@changefreq_options' => implode(', ', array_keys(FormHelper::getChangefreqOptions())),
      ];

      // Checking if internal path exists.
      if (!(bool) $this->pathValidator->getUrlIfValidWithoutAccessCheck($link_config['path'])
      // Path validator does not see a double slash as an error. Catching this
      // to prevent breaking path generation.
       || strpos($link_config['path'], '//') !== FALSE) {
        $form_state->setErrorByName('', $this->t('<strong>Line @line</strong>: The path <em>@path</em> does not exist.', $placeholders));
      }

      // Making sure the paths start with a slash.
      if ($link_config['path'][0] !== '/') {
        $form_state->setErrorByName('', $this->t("<strong>Line @line</strong>: The path <em>@path</em> needs to start with a '/'.", $placeholders));
      }

      // Making sure the priority is formatted correctly.
      if (isset($link_config['priority']) && !FormHelper::isValidPriority($link_config['priority'])) {
        $form_state->setErrorByName('', $this->t('<strong>Line @line</strong>: The priority setting <em>@priority</em> for path <em>@path</em> is incorrect. Set the priority from 0.0 to 1.0.', $placeholders));
      }

      // Making sure changefreq is formatted correctly.
      if (isset($link_config['changefreq']) && !FormHelper::isValidChangefreq($link_config['changefreq'])) {
        $form_state->setErrorByName('', $this->t('<strong>Line @line</strong>: The changefreq setting <em>@changefreq</em> for path <em>@path</em> is incorrect. The following are the correct values: <em>@changefreq_options</em>.', $placeholders));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->generator->setVariants()->customLinkManager()->remove();
    if (!empty($variants = $form_state->getValue('variants')) && !empty($links = $form_state->getValue('custom_links'))) {
      $this->generator->setVariants(array_values($variants));
      foreach ($this->stringToCustomLinks($links) as $link_config) {
        $this->generator->customLinkManager()->add($link_config['path'], $link_config);
      }
    }

    $this->settings->save('custom_links_include_images', (bool) $form_state->getValue('include_images'));
    parent::submitForm($form, $form_state);
  }

  /**
   * Converts a string with custom links to an array.
   *
   * @param string $custom_links_string
   *   A string representation of the custom links to convert.
   *
   * @return array
   *   Array of custom links.
   */
  protected function stringToCustomLinks(string $custom_links_string): array {

    // Unify newline characters and explode into array.
    $custom_links_string_lines = explode("\n", str_replace("\r\n", "\n", $custom_links_string));

    // Remove empty values and whitespaces from array.
    $custom_links_string_lines = array_filter(array_map('trim', $custom_links_string_lines));

    $custom_links = [];
    foreach ($custom_links_string_lines as $i => $line) {
      $link_settings = explode(' ', $line);
      $custom_links[$i]['path'] = $link_settings[0];

      // If two arguments are provided for a link, assume the first to be
      // priority, the second to be changefreq.
      if (!empty($link_settings[1]) && !empty($link_settings[2])) {
        $custom_links[$i]['priority'] = $link_settings[1];
        $custom_links[$i]['changefreq'] = $link_settings[2];
      }
      else {
        // If one argument is provided for a link, guess if it is priority or
        // changefreq.
        if (!empty($link_settings[1])) {
          if (is_numeric($link_settings[1])) {
            $custom_links[$i]['priority'] = $link_settings[1];
          }
          else {
            $custom_links[$i]['changefreq'] = $link_settings[1];
          }
        }
      }
    }

    return $custom_links;
  }

  /**
   * Converts an array of custom links to a string.
   *
   * @param array $links
   *   Array of custom links to convert.
   *
   * @return string
   *   A string representation of the custom links.
   */
  protected function customLinksToString(array $links): string {
    $setting_string = '';
    foreach ($links as $custom_link) {
      $setting_string .= $custom_link['path'];
      $setting_string .= isset($custom_link['priority'])
        ? ' ' . FormHelper::formatPriority($custom_link['priority'])
        : '';
      $setting_string .= isset($custom_link['changefreq'])
        ? ' ' . $custom_link['changefreq']
        : '';
      $setting_string .= "\r\n";
    }

    return $setting_string;
  }

}
