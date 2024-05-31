<?php

namespace Drupal\fontawesome\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a form that configures fontawesome settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Drupal LibraryDiscovery service container.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, LibraryDiscoveryInterface $library_discovery) {
    parent::__construct($config_factory);

    $this->libraryDiscovery = $library_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fontawesome_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'fontawesome.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get current settings.
    $fontawesome_config = $this->config('fontawesome.settings');

    // Load the fontawesome libraries so we can use its definitions here.
    $fontawesome_library = $this->libraryDiscovery->getLibraryByName('fontawesome', 'fontawesome.svg');

    $form['tag'] = [
      '#type' => 'select',
      '#title' => $this->t('Font Awesome Tag'),
      '#options' => [
        'i' => $this->t('&lt;i&gt;'),
        'span' => $this->t('&lt;span&gt;'),
      ],
      '#default_value' => empty($fontawesome_config->get('tag')) ? 'i' : $fontawesome_config->get('tag'),
      '#description' => $this->t('Font Awesome works with any consistent HTML element. By default, Font Awesome uses the &lt;i&gt; tag for its icons. However, in some cases you may want to use a different tag for your Font Awesome icons, such as a &lt;span&gt; tag. Changing the value here will change the way the tags are inserted into your site. Manually created Font Awesome tags can use any HTML element you like. Note that changing this setting will require clearing the site cache.'),
    ];

    $form['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Font Awesome Method'),
      '#options' => [
        'svg' => $this->t('SVG with JS'),
        'webfonts' => $this->t('Web Fonts with CSS'),
      ],
      '#default_value' => $fontawesome_config->get('method'),
      '#description' => $this->t('This setting controls the way Font Awesome works. SVG with JS is the modern, easy, and powerful version with the most backwards compatibility. Web Fonts with CSS is the classic Font Awesome icon method that you have seen in earlier versions of Font Awesome. We recommend SVG with JS. Please note that the Webfonts with CSS version does not allow backwards compatibility with Font Awesome 4. That means you will need to check your code base to be certain that the icons are all updated to work with version 5. See @gettingStartedLink for more information.', [
        '@gettingStartedLink' => Link::fromTextAndUrl($this->t('the Font Awesome guide'), Url::fromUri('https://fontawesome.com/start'))->toString(),
      ]),
    ];

    $form['allow_pseudo_elements'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow CSS pseudo-elements?'),
      '#description' => $this->t('If you do not want to add icons directly in code, you can add them through CSS pseudo-elements. Font Awesome has leveraged the ::before pseudo-element to add icons to a page since the very beginning. For more information on how to use pseudo-elements, see the @pseudoElementsLink. Note that this feature is always available with the Webfonts version of Font Awesome. If you turn this feature on for SVG with JS, it will slow your site down noticeably.', [
        '@pseudoElementsLink' => Link::fromTextAndUrl($this->t('Font Awesome guide to pseudo-elements'), Url::fromUri('https://fontawesome.com/how-to-use/on-the-web/advanced/css-pseudo-elements'))->toString(),
      ]),
      '#default_value' => $fontawesome_config->get('allow_pseudo_elements'),
    ];

    $form['load_assets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load Font Awesome library?'),
      '#default_value' => $fontawesome_config->get('load_assets'),
      '#description' => $this->t("If enabled, this module will attempt to load the Font Awesome library for you. To prevent loading twice, leave this option disabled if you're including the assets manually or through another module or theme."),
    ];

    $form['external'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('External file configuration'),
      '#description' => $this->t('These settings control the method by which the Font Awesome library is loaded. You can choose to use an external (full URL) or local (relative path) library by selecting a URL / path below, or you can use a local version of the file by leaving the box unchecked and downloading the library <a href=":remoteurl">:remoteurl</a> and installing locally at %installpath. See the README for more information.', [
        ':remoteurl' => $fontawesome_library['remote'],
        '%installpath' => '/libraries',
      ]),
      '#states' => [
        'visible' => [
          ':input[name="load_assets"]' => ['checked' => TRUE],
        ],
      ],
      'use_cdn' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Use external file (CDN) / local file?'),
        '#description' => $this->t('Checking this box will cause the Font Awesome library to be loaded from the given source rather than from the local library file.'),
        '#default_value' => $fontawesome_config->get('use_cdn'),
      ],
      'external_svg_location' => [
        '#type' => 'textfield',
        '#title' => $this->t('External File Location'),
        '#default_value' => $fontawesome_config->get('external_svg_location'),
        '#size' => 80,
        '#description' => $this->t('Enter a source URL for the external Font Awesome library file you wish to use. Note that this is designed for use with the <strong>SVG with JS</strong> method. Use for the Webfonts method at your own risk. This URL should point to the Font Awesome JS svg file when using <strong>SVG with JS</strong> or it should point to the Font Awesome CSS file when using <strong>Web Fonts with CSS</strong>. Leave blank to use the default Font Awesome CDN.'),
        '#states' => [
          'disabled' => [
            ':input[name="use_cdn"]' => ['checked' => FALSE],
          ],
          'visible' => [
            ':input[name="use_cdn"]' => ['checked' => TRUE],
          ],
        ],
      ],
      'external_svg_integrity' => [
        '#type' => 'textfield',
        '#title' => $this->t('External File Integrity Value'),
        '#default_value' => $fontawesome_config->get('external_svg_integrity'),
        '#size' => 80,
        '#description' => $this->t('Enter an (optional) integrity value for checking the CDN. This value should be provided by Font Awesome alongside the CDN source. This will force the browser to confirm the integrity of the CDN to prevent unexpected content from being loaded.'),
        '#states' => [
          'disabled' => [
            ':input[name="use_cdn"]' => ['checked' => FALSE],
          ],
          'visible' => [
            ':input[name="use_cdn"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];

    $form['partial'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Partial file configuration'),
      '#description' => $this->t('By default, Font Awesome loads all of the icons. However, you can choose to load only some of the icon files if you only want a subset of the available icons. This method can result in reduced file size. These files will be assumed to exist in the same directory as the parent <i>all.js/all.css</i> file.'),
      'use_solid_file' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Load solid icons'),
        '#description' => $this->t('Checking this box will cause the Font Awesome library to load the file containing the solid icon declarations (<i>solid.js/solid.css</i>)'),
        '#default_value' => is_null($fontawesome_config->get('use_solid_file')) === TRUE ? TRUE : $fontawesome_config->get('use_solid_file'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="load_assets"]' => ['checked' => TRUE],
        ],
      ],
      'use_regular_file' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Load regular icons'),
        '#description' => $this->t('Checking this box will cause the Font Awesome library to load the file containing the regular icon declarations (<i>regular.js/regular.css</i>)'),
        '#default_value' => is_null($fontawesome_config->get('use_regular_file')) === TRUE ? TRUE : $fontawesome_config->get('use_regular_file'),
      ],
      'use_light_file' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Load light icons'),
        '#description' => $this->t('Checking this box will cause the Font Awesome library to load the file containing the light icon declarations (<i>light.js/light.css</i>). Note that this a Pro-only feature.'),
        '#default_value' => is_null($fontawesome_config->get('use_light_file')) === TRUE ? TRUE : $fontawesome_config->get('use_light_file'),
      ],
      'use_brands_file' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Load brand icons'),
        '#description' => $this->t('Checking this box will cause the Font Awesome library to load the file containing the brands icon declarations (<i>brands.js/brands.css</i>)'),
        '#default_value' => is_null($fontawesome_config->get('use_brands_file')) === TRUE ? TRUE : $fontawesome_config->get('use_brands_file'),
      ],
      'use_duotone_file' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Load duotone icons'),
        '#description' => $this->t('Checking this box will cause the Font Awesome library to load the file containing the duotone icon declarations (<i>duotone.js/duotone.css</i>)'),
        '#default_value' => is_null($fontawesome_config->get('use_duotone_file')) === TRUE ? TRUE : $fontawesome_config->get('use_duotone_file'),
      ],
      'use_thin_file' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Load thin icons'),
        '#description' => $this->t('Checking this box will cause the Font Awesome library to load the file containing the thin icon declarations (<i>thin.js/thin.css</i>)'),
        '#default_value' => is_null($fontawesome_config->get('use_thin_file')) === TRUE ? TRUE : $fontawesome_config->get('use_thin_file'),
      ],
      'use_sharpregular_file' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Load sharp regular icons'),
        '#description' => $this->t('Checking this box will cause the Font Awesome library to load the file containing the sharp regular icon declarations (<i>sharp-regular.js/sharp-regular.css</i>)'),
        '#default_value' => is_null($fontawesome_config->get('use_sharpregular_file')) === TRUE ? TRUE : $fontawesome_config->get('use_sharpregular_file'),
      ],
      'use_sharplight_file' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Load sharp light icons'),
        '#description' => $this->t('Checking this box will cause the Font Awesome library to load the file containing the sharp light icon declarations (<i>sharp-light.js/sharp-light.css</i>)'),
        '#default_value' => is_null($fontawesome_config->get('use_sharplight_file')) === TRUE ? TRUE : $fontawesome_config->get('use_sharplight_file'),
      ],
      'use_sharpsolid_file' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Load sharp solid icons'),
        '#description' => $this->t('Checking this box will cause the Font Awesome library to load the file containing the sharp solid icon declarations (<i>sharp-solid.js/sharp-solid.css</i>)'),
        '#default_value' => is_null($fontawesome_config->get('use_sharpsolid_file')) === TRUE ? TRUE : $fontawesome_config->get('use_sharpsolid_file'),
      ],
      'use_custom_file' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Load custom icons'),
        '#description' => $this->t('Checking this box will cause the Font Awesome library to load the file containing the custom icon declarations (<i>custom-icons.js/custom-icons.css</i>)'),
        '#default_value' => is_null($fontawesome_config->get('use_custom_file')) === TRUE ? TRUE : $fontawesome_config->get('use_custom_file'),
      ],
    ];

    $form['shim'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Version 4 Backwards Compatibility'),
      '#description' => $this->t('Version 5 of Font Awesome has some changes which require modifications to the way you declare many of your icons. The settings below are designed to ease that transition. See @upgradingLink for more information.', [
        '@upgradingLink' => Link::fromTextAndUrl($this->t('the Font Awesome guide to upgrading version 4 to version 5'), Url::fromUri('https://fontawesome.com/how-to-use/on-the-web/setup/upgrading-from-version-4'))->toString(),
      ]),
      '#states' => [
        'visible' => [
          ':input[name="load_assets"]' => ['checked' => TRUE],
        ],
      ],
      'use_shim' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Use version 4 shim file?'),
        '#description' => $this->t('Rather than editing all of your Font Awesome declarations to use the new Font Awesome syntax, you can choose to include a shim file above. This file will allow you to use Font Awesome version 5 with Font Awesome version 4 syntax. This prevents you from needing to modify your existing code and syntax.'),
        '#default_value' => $fontawesome_config->get('use_shim'),
      ],
      'external_shim_location' => [
        '#type' => 'textfield',
        '#title' => $this->t('External / local Library Location'),
        '#default_value' => $fontawesome_config->get('external_shim_location'),
        '#size' => 80,
        '#description' => $this->t('Enter a source URL for the external / local (relative path) Font Awesome v4 shim file you wish to use. This URL should point to the Font Awesome JS shim file. Leave blank to use the default Font Awesome CDN.'),
        '#states' => [
          'disabled' => [
            ':input[name="use_cdn"]' => ['checked' => FALSE],
            ':input[name="use_shim"]' => ['checked' => FALSE],
          ],
          'visible' => [
            ':input[name="use_cdn"]' => ['checked' => TRUE],
            ':input[name="use_shim"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];

    $form['bypass_validation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bypass Font Awesome icon validation?'),
      '#default_value' => $fontawesome_config->get('bypass_validation'),
      '#description' => $this->t("If enabled, icon name validation will not take place. This is useful when using custom icons from Font Awesome's hosted kits."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Validate URL.
    if (!empty($values['fontawesome_external_location']) && !UrlHelper::isValid($values['fontawesome_external_location'])) {
      $form_state->setErrorByName('fontawesome_external_location', $this->t('Invalid external library location.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Load the fontawesome libraries so we can use its definitions here.
    $fontawesome_library = $this->libraryDiscovery->getLibraryByName('fontawesome', 'fontawesome.svg');

    // Clear the library cache so we use the updated information.
    $this->libraryDiscovery->clearCachedDefinitions();

    // Set external file defaults.
    $default_location = 'https://use.fontawesome.com/releases/v' . $fontawesome_library['version'] . '/';
    $default_svg_location = $default_location . 'js/all.js';
    $default_webfonts_location = $default_location . 'css/all.css';
    $default_svg_shimfile_location = $default_location . 'js/v4-shims.js';
    $default_webfonts_shimfile_location = $default_location . 'css/v4-shims.css';

    // Use default values if CDN is checked and the locations are blank.
    if ($values['use_cdn']) {
      if (empty($values['external_svg_location']) || $values['external_svg_location'] == $default_webfonts_location || $values['external_svg_location'] == $default_svg_location) {
        // Choose the default depending on method.
        $values['external_svg_location'] = ($values['method'] == 'webfonts') ? $default_webfonts_location : $default_svg_location;
      }
      if ($values['use_shim'] && (empty($values['external_shim_location']) || $values['external_shim_location'] == $default_webfonts_shimfile_location || $values['external_shim_location'] == $default_svg_shimfile_location)) {
        // Choose the default depending on method.
        $values['external_shim_location'] = ($values['method'] == 'webfonts') ? $default_webfonts_shimfile_location : $default_svg_shimfile_location;
      }
    }

    // Save the updated settings.
    $this->config('fontawesome.settings')
      ->set('tag', $values['tag'])
      ->set('method', $values['method'])
      ->set('load_assets', $values['load_assets'])
      ->set('use_cdn', $values['use_cdn'])
      ->set('external_svg_location', (string) $values['external_svg_location'])
      ->set('external_svg_integrity', (string) $values['external_svg_integrity'])
      ->set('use_shim', $values['use_shim'])
      ->set('external_shim_location', (string) $values['external_shim_location'])
      ->set('allow_pseudo_elements', $values['allow_pseudo_elements'])
      ->set('use_solid_file', $values['use_solid_file'])
      ->set('use_regular_file', $values['use_regular_file'])
      ->set('use_light_file', $values['use_light_file'])
      ->set('use_brands_file', $values['use_brands_file'])
      ->set('use_duotone_file', $values['use_duotone_file'])
      ->set('use_thin_file', $values['use_thin_file'])
      ->set('bypass_validation', $values['bypass_validation'])
      ->set('use_sharpregular_file', $values['use_sharpregular_file'])
      ->set('use_sharplight_file', $values['use_sharplight_file'])
      ->set('use_sharpsolid_file', $values['use_sharpsolid_file'])
      ->set('use_custom_file', $values['use_custom_file'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
