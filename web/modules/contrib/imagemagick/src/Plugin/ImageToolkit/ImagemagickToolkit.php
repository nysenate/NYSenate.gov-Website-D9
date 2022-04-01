<?php

namespace Drupal\imagemagick\Plugin\ImageToolkit;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ImageToolkit\ImageToolkitBase;
use Drupal\Core\ImageToolkit\ImageToolkitOperationManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file_mdm\FileMetadataManagerInterface;
use Drupal\imagemagick\Event\ImagemagickExecutionEvent;
use Drupal\imagemagick\ImagemagickExecArguments;
use Drupal\imagemagick\ImagemagickExecManagerInterface;
use Drupal\imagemagick\ImagemagickFormatMapperInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides ImageMagick integration toolkit for image manipulation.
 *
 * @ImageToolkit(
 *   id = "imagemagick",
 *   title = @Translation("ImageMagick image toolkit")
 * )
 */
class ImagemagickToolkit extends ImageToolkitBase {

  /**
   * The id of the file_mdm plugin managing image metadata.
   */
  const FILE_METADATA_PLUGIN_ID = 'imagemagick_identify';

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The format mapper service.
   *
   * @var \Drupal\imagemagick\ImagemagickFormatMapperInterface
   */
  protected $formatMapper;

  /**
   * The file metadata manager service.
   *
   * @var \Drupal\file_mdm\FileMetadataManagerInterface
   */
  protected $fileMetadataManager;

  /**
   * The ImageMagick execution manager service.
   *
   * @var \Drupal\imagemagick\ImagemagickExecManagerInterface
   */
  protected $execManager;

  /**
   * The execution arguments object.
   *
   * @var \Drupal\imagemagick\ImagemagickExecArguments
   */
  protected $arguments;

  /**
   * The width of the image.
   *
   * @var int
   */
  protected $width;

  /**
   * The height of the image.
   *
   * @var int
   */
  protected $height;

  /**
   * The number of frames of the source image, for multi-frame images.
   *
   * @var int
   */
  protected $frames;

  /**
   * Image orientation retrieved from EXIF information.
   *
   * @var int
   */
  protected $exifOrientation;

  /**
   * The source image colorspace.
   *
   * @var string
   */
  protected $colorspace;

  /**
   * The source image profiles.
   *
   * @var string[]
   */
  protected $profiles = [];

  /**
   * Constructs an ImagemagickToolkit object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\ImageToolkit\ImageToolkitOperationManagerInterface $operation_manager
   *   The toolkit operation manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\imagemagick\ImagemagickFormatMapperInterface $format_mapper
   *   The format mapper service.
   * @param \Drupal\file_mdm\FileMetadataManagerInterface $file_metadata_manager
   *   The file metadata manager service.
   * @param \Drupal\imagemagick\ImagemagickExecManagerInterface $exec_manager
   *   The ImageMagick execution manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ImageToolkitOperationManagerInterface $operation_manager, LoggerInterface $logger, ConfigFactoryInterface $config_factory, ImagemagickFormatMapperInterface $format_mapper, FileMetadataManagerInterface $file_metadata_manager, ImagemagickExecManagerInterface $exec_manager, EventDispatcherInterface $dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $operation_manager, $logger, $config_factory);
    $this->formatMapper = $format_mapper;
    $this->fileMetadataManager = $file_metadata_manager;
    $this->execManager = $exec_manager;
    $this->arguments = new ImagemagickExecArguments($this->execManager);
    $this->eventDispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('image.toolkit.operation.manager'),
      $container->get('logger.channel.image'),
      $container->get('config.factory'),
      $container->get('imagemagick.format_mapper'),
      $container->get('file_metadata_manager'),
      $container->get('imagemagick.exec_manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('imagemagick.settings');

    $form['imagemagick'] = [
      '#markup' => $this->t("<a href=':im-url'>ImageMagick</a> and <a href=':gm-url'>GraphicsMagick</a> are stand-alone packages for image manipulation. At least one of them must be installed on the server, and you need to know where it is located. Consult your server administrator or hosting provider for details.", [
        ':im-url' => 'http://www.imagemagick.org',
        ':gm-url' => 'http://www.graphicsmagick.org',
      ]),
    ];
    $form['quality'] = [
      '#type' => 'number',
      '#title' => $this->t('Image quality'),
      '#size' => 10,
      '#min' => 0,
      '#max' => 100,
      '#maxlength' => 3,
      '#default_value' => $config->get('quality'),
      '#field_suffix' => '%',
      '#description' => $this->t('Define the image quality of processed images. Ranges from 0 to 100. Higher values mean better image quality but bigger files.'),
    ];

    // Settings tabs.
    $form['imagemagick_settings'] = [
      '#type' => 'vertical_tabs',
      '#tree' => FALSE,
    ];

    // Graphics suite to use.
    $form['suite'] = [
      '#type' => 'details',
      '#title' => $this->t('Graphics package'),
      '#group' => 'imagemagick_settings',
    ];
    $options = [
      'imagemagick' => $this->getExecManager()->getPackageLabel('imagemagick'),
      'graphicsmagick' => $this->getExecManager()->getPackageLabel('graphicsmagick'),
    ];
    $form['suite']['binaries'] = [
      '#type' => 'radios',
      '#title' => $this->t('Suite'),
      '#default_value' => $this->getExecManager()->getPackage(),
      '#options' => $options,
      '#required' => TRUE,
      '#description' => $this->t("Select the graphics package to use."),
    ];
    // Path to binaries.
    $form['suite']['path_to_binaries'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to the package executables'),
      '#default_value' => $config->get('path_to_binaries'),
      '#required' => FALSE,
      '#description' => $this->t('If needed, the path to the package executables (<kbd>convert</kbd>, <kbd>identify</kbd>, <kbd>gm</kbd>, etc.), <b>including</b> the trailing slash/backslash. For example: <kbd>/usr/bin/</kbd> or <kbd>C:\Program Files\ImageMagick-6.3.4-Q16\</kbd>.'),
    ];
    // Version information.
    $status = $this->getExecManager()->checkPath($this->configFactory->get('imagemagick.settings')->get('path_to_binaries'));
    if (empty($status['errors'])) {
      $version_info = explode("\n", preg_replace('/\r/', '', Html::escape($status['output'])));
    }
    else {
      $version_info = $status['errors'];
    }
    $form['suite']['version'] = [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => TRUE,
      '#title' => $this->t('Version information'),
      '#description' => '<pre>' . implode('<br />', $version_info) . '</pre>',
    ];

    // Image formats.
    $form['formats'] = [
      '#type' => 'details',
      '#title' => $this->t('Image formats'),
      '#group' => 'imagemagick_settings',
    ];
    // Image formats enabled in the toolkit.
    $form['formats']['enabled'] = [
      '#type' => 'item',
      '#title' => $this->t('Currently enabled images'),
      '#description' => $this->t("@suite formats: %formats<br />Image file extensions: %extensions", [
        '%formats' => implode(', ', $this->formatMapper->getEnabledFormats()),
        '%extensions' => mb_strtolower(implode(', ', static::getSupportedExtensions())),
        '@suite' => $this->getExecManager()->getPackageLabel(),
      ]),
    ];
    // Image formats map.
    $form['formats']['mapping'] = [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => TRUE,
      '#title' => $this->t('Enable/disable image formats'),
      '#description' => $this->t("Edit the map below to enable/disable image formats. Enabled image file extensions will be determined by the enabled formats, through their MIME types. More information in the module's README.txt"),
    ];
    $form['formats']['mapping']['image_formats'] = [
      '#type' => 'textarea',
      '#rows' => 15,
      '#default_value' => Yaml::encode($config->get('image_formats')),
    ];
    // Image formats supported by the package.
    if (empty($status['errors'])) {
      $this->arguments()->add('-list format', ImagemagickExecArguments::PRE_SOURCE);
      $output = NULL;
      $this->getExecManager()->execute('convert', $this->arguments(), $output);
      $this->arguments()->reset();
      $formats_info = implode('<br />', explode("\n", preg_replace('/\r/', '', Html::escape($output))));
      $form['formats']['list'] = [
        '#type' => 'details',
        '#collapsible' => TRUE,
        '#open' => FALSE,
        '#title' => $this->t('Format list'),
        '#description' => $this->t("Supported image formats returned by executing <kbd>'convert -list format'</kbd>. <b>Note:</b> these are the formats supported by the installed @suite executable, <b>not</b> by the toolkit.<br /><br />", ['@suite' => $this->getExecManager()->getPackageLabel()]),
      ];
      $form['formats']['list']['list'] = [
        '#markup' => "<pre>" . $formats_info . "</pre>",
      ];
    }

    // Execution options.
    $form['exec'] = [
      '#type' => 'details',
      '#title' => $this->t('Execution options'),
      '#group' => 'imagemagick_settings',
    ];

    // Cache metadata.
    $configure_link = Link::fromTextAndUrl(
      $this->t('Configure File Metadata Manager'),
      Url::fromRoute('file_mdm.settings')
    );
    $form['exec']['metadata_caching'] = [
      '#type' => 'item',
      '#title' => $this->t("Cache image metadata"),
      '#description' => $this->t("The File Metadata Manager module allows to cache image metadata. This reduces file I/O and <kbd>shell</kbd> calls. @configure.", [
        '@configure' => $configure_link->toString(),
      ]),
    ];
    // Prepend arguments.
    $form['exec']['prepend'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prepend arguments'),
      '#default_value' => $config->get('prepend'),
      '#required' => FALSE,
      '#description' => $this->t("Use this to add e.g. <kbd><a href=':limit-url'>-limit</a></kbd> or <kbd><a href=':debug-url'>-debug</a></kbd> arguments in front of the others when executing the <kbd>identify</kbd> and <kbd>convert</kbd> commands. The arguments specified will be added before the source image file name.", [
        ':limit-url' => 'https://www.imagemagick.org/script/command-line-options.php#limit',
        ':debug-url' => 'https://www.imagemagick.org/script/command-line-options.php#debug',
      ]),
    ];

    // Locale.
    $form['exec']['locale'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Locale'),
      '#default_value' => $config->get('locale'),
      '#required' => FALSE,
      '#description' => $this->t("The locale to be used to prepare the command passed to executables. The default, <kbd>'en_US.UTF-8'</kbd>, should work in most cases. If that is not available on the server, enter another locale. 'Installed Locales' below provides a list of locales installed on the server."),
    ];
    // Installed locales.
    $locales = $this->getExecManager()->getInstalledLocales();
    $locales_info = implode('<br />', explode("\n", preg_replace('/\r/', '', Html::escape($locales))));
    $form['exec']['installed_locales'] = [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => FALSE,
      '#title' => $this->t('Installed locales'),
      '#description' => $this->t("This is the list of all locales available on this server. It is the output of executing <kbd>'locale -a'</kbd> on the operating system."),
    ];
    $form['exec']['installed_locales']['list'] = [
      '#markup' => "<pre>" . $locales_info . "</pre>",
    ];
    // Log warnings.
    $form['exec']['log_warnings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log warnings'),
      '#default_value' => $config->get('log_warnings'),
      '#description' => $this->t('Log a warning entry in the watchdog when the execution of a command returns with a non-zero code, but no error message.'),
    ];
    // Debugging.
    $form['exec']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display debugging information'),
      '#default_value' => $config->get('debug'),
      '#description' => $this->t('Shows commands and their output to users with the %permission permission.', [
        '%permission' => $this->t('Administer site configuration'),
      ]),
    ];

    // Advanced image settings.
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced image settings'),
      '#group' => 'imagemagick_settings',
    ];
    $form['advanced']['density'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Change image resolution to 72 ppi'),
      '#default_value' => $config->get('advanced.density'),
      '#return_value' => 72,
      '#description' => $this->t("Resamples the image <a href=':help-url'>density</a> to a resolution of 72 pixels per inch, the default for web images. Does not affect the pixel size or quality.", [
        ':help-url' => 'http://www.imagemagick.org/script/command-line-options.php#density',
      ]),
    ];
    $form['advanced']['colorspace'] = [
      '#type' => 'select',
      '#title' => $this->t('Convert colorspace'),
      '#default_value' => $config->get('advanced.colorspace'),
      '#options' => [
        'RGB' => $this->t('RGB'),
        'sRGB' => $this->t('sRGB'),
        'GRAY' => $this->t('Gray'),
      ],
      '#empty_value' => 0,
      '#empty_option' => $this->t('- Original -'),
      '#description' => $this->t("Converts processed images to the specified <a href=':help-url'>colorspace</a>. The color profile option overrides this setting.", [
        ':help-url' => 'http://www.imagemagick.org/script/command-line-options.php#colorspace',
      ]),
      '#states' => [
        'enabled' => [
          ':input[name="imagemagick[advanced][profile]"]' => ['value' => ''],
        ],
      ],
    ];
    $form['advanced']['profile'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Color profile path'),
      '#default_value' => $config->get('advanced.profile'),
      '#description' => $this->t("The path to a <a href=':help-url'>color profile</a> file that all processed images will be converted to. Leave blank to disable. Use a <a href=':color-url'>sRGB profile</a> to correct the display of professional images and photography.", [
        ':help-url' => 'http://www.imagemagick.org/script/command-line-options.php#profile',
        ':color-url' => 'http://www.color.org/profiles.html',
      ]),
    ];
    $form['advanced']['coalesce'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Coalesce Animated GIF images'),
      '#default_value' => $config->get('advanced.coalesce'),
      '#description' => $this->t("<a href=':help-url'>Fully define</a> the look of each frame of a GIF animation sequence, to form a 'film strip' animation, before any operation is performed on the image.", [
        ':help-url' => 'https://imagemagick.org/script/command-line-options.php#coalesce',
      ]),
    ];

    return $form;
  }

  /**
   * Returns the ImageMagick execution manager service.
   *
   * @return \Drupal\imagemagick\ImagemagickExecManagerInterface
   *   The ImageMagick execution manager service.
   */
  public function getExecManager(): ImagemagickExecManagerInterface {
    return $this->execManager;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    try {
      // Check that the format map contains valid YAML.
      $image_formats = Yaml::decode($form_state->getValue([
        'imagemagick', 'formats', 'mapping', 'image_formats',
      ]));
      // Validate the enabled image formats.
      $errors = $this->formatMapper->validateMap($image_formats);
      if ($errors) {
        $form_state->setErrorByName('imagemagick][formats][mapping][image_formats', new FormattableMarkup("<pre>Image format errors:<br/>@errors</pre>", ['@errors' => Yaml::encode($errors)]));
      }
    }
    catch (InvalidDataTypeException $e) {
      // Invalid YAML detected, show details.
      $form_state->setErrorByName('imagemagick][formats][mapping][image_formats', $this->t("YAML syntax error: @error", ['@error' => $e->getMessage()]));
    }
    // Validate the binaries path only if this toolkit is selected, otherwise
    // it will prevent the entire image toolkit selection form from being
    // submitted.
    if ($form_state->getValue(['image_toolkit']) === 'imagemagick') {
      $status = $this->getExecManager()->checkPath($form_state->getValue([
        'imagemagick', 'suite', 'path_to_binaries',
      ]), $form_state->getValue(['imagemagick', 'suite', 'binaries']));
      if ($status['errors']) {
        $form_state->setErrorByName('imagemagick][suite][path_to_binaries', new FormattableMarkup(implode('<br />', $status['errors']), []));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('imagemagick.settings');
    $config
      ->set('quality', (int) $form_state->getValue([
        'imagemagick', 'quality',
      ]))
      ->set('binaries', (string) $form_state->getValue([
        'imagemagick', 'suite', 'binaries',
      ]))
      ->set('path_to_binaries', (string) $form_state->getValue([
        'imagemagick', 'suite', 'path_to_binaries',
      ]))
      ->set('image_formats', Yaml::decode($form_state->getValue([
        'imagemagick', 'formats', 'mapping', 'image_formats',
      ])))
      ->set('prepend', (string) $form_state->getValue([
        'imagemagick', 'exec', 'prepend',
      ]))
      ->set('locale', (string) $form_state->getValue([
        'imagemagick', 'exec', 'locale',
      ]))
      ->set('log_warnings', (bool) $form_state->getValue([
        'imagemagick', 'exec', 'log_warnings',
      ]))
      ->set('debug', (bool) $form_state->getValue([
        'imagemagick', 'exec', 'debug',
      ]))
      ->set('advanced.density', (int) $form_state->getValue([
        'imagemagick', 'advanced', 'density',
      ]))
      ->set('advanced.colorspace', (string) $form_state->getValue([
        'imagemagick', 'advanced', 'colorspace',
      ]))
      ->set('advanced.profile', (string) $form_state->getValue([
        'imagemagick', 'advanced', 'profile',
      ]))
      ->set('advanced.coalesce', (bool) $form_state->getValue([
        'imagemagick', 'advanced', 'coalesce',
      ]));
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    return ((bool) $this->getMimeType());
  }

  /**
   * Resets all image properties and any processing argument.
   *
   * This is an helper in case an image needs to be scratched or replaced.
   *
   * @param int $width
   *   The image width.
   * @param int $height
   *   The image height.
   * @param string $format
   *   The image Imagemagick format.
   *
   * @see \Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick\CreateNew
   */
  public function reset(int $width, int $height, string $format): ImagemagickToolkit {
    $this
      ->setWidth($width)
      ->setHeight($height)
      ->setExifOrientation(NULL)
      ->setColorspace($this->getExecManager()->getPackage() === 'imagemagick' ? 'sRGB' : '')
      ->setProfiles([])
      ->setFrames(1);
    $this->arguments()
      ->setSourceFormat($format)
      ->setSourceLocalPath('')
      ->reset();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSource($source) {
    parent::setSource($source);
    $this->arguments()->setSource($source);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->arguments()->getSource();
  }

  /**
   * Ensures that the local filesystem path to the image file exists.
   *
   * @return string
   *   A filesystem path.
   */
  public function ensureSourceLocalPath(): string {
    // If sourceLocalPath is NULL, then ensure it is prepared. This can
    // happen if image was identified via cached metadata: the cached data are
    // available, but the temp file path is not resolved, or even the temp file
    // could be missing if it was copied locally from a remote file system.
    if (!$this->arguments()->getSourceLocalPath() && $this->getSource()) {
      $this->eventDispatcher->dispatch(new ImagemagickExecutionEvent($this->arguments), ImagemagickExecutionEvent::ENSURE_SOURCE_LOCAL_PATH);
    }
    return $this->arguments()->getSourceLocalPath();
  }

  /**
   * Gets the source EXIF orientation.
   *
   * @return int|null
   *   The source EXIF orientation.
   */
  public function getExifOrientation() {
    return $this->exifOrientation;
  }

  /**
   * Sets the source EXIF orientation.
   *
   * @param int|null $exif_orientation
   *   The EXIF orientation.
   *
   * @return $this
   */
  public function setExifOrientation($exif_orientation): ImagemagickToolkit {
    $this->exifOrientation = $exif_orientation ? (int) $exif_orientation : NULL;
    return $this;
  }

  /**
   * Gets the source colorspace.
   *
   * @return string|null
   *   The source colorspace.
   */
  public function getColorspace() {
    return $this->colorspace;
  }

  /**
   * Sets the source colorspace.
   *
   * @param string|null $colorspace
   *   The image colorspace.
   *
   * @return $this
   */
  public function setColorspace($colorspace): ImagemagickToolkit {
    $this->colorspace = mb_strtoupper($colorspace ?? '');
    return $this;
  }

  /**
   * Gets the source profiles.
   *
   * @return string[]
   *   The source profiles.
   */
  public function getProfiles(): array {
    return $this->profiles;
  }

  /**
   * Sets the source profiles.
   *
   * @param array $profiles
   *   The image profiles.
   *
   * @return $this
   */
  public function setProfiles(array $profiles): ImagemagickToolkit {
    $this->profiles = $profiles;
    return $this;
  }

  /**
   * Gets the source image number of frames.
   *
   * @return int
   *   The number of frames of the image.
   */
  public function getFrames() {
    return $this->frames;
  }

  /**
   * Sets the source image number of frames.
   *
   * @param int|null $frames
   *   The number of frames of the image.
   *
   * @return $this
   */
  public function setFrames($frames): ImagemagickToolkit {
    $this->frames = $frames;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidth() {
    return $this->width;
  }

  /**
   * Sets image width.
   *
   * @param int|null $width
   *   The image width.
   *
   * @return $this
   */
  public function setWidth($width): ImagemagickToolkit {
    $this->width = $width;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeight() {
    return $this->height;
  }

  /**
   * Sets image height.
   *
   * @param int|null $height
   *   The image height.
   *
   * @return $this
   */
  public function setHeight($height): ImagemagickToolkit {
    $this->height = $height;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeType() {
    return $this->formatMapper->getMimeTypeFromFormat($this->arguments()->getSourceFormat());
  }

  /**
   * Returns the current ImagemagickExecArguments object.
   *
   * @return \Drupal\imagemagick\ImagemagickExecArguments
   *   The current ImagemagickExecArguments object.
   */
  public function arguments(): ImagemagickExecArguments {
    return $this->arguments;
  }

  /**
   * {@inheritdoc}
   */
  public function save($destination) {
    $this->arguments()->setDestination($destination);
    if ($ret = $this->convert()) {
      // Allow modules to alter the destination file.
      $this->eventDispatcher->dispatch(new ImagemagickExecutionEvent($this->arguments), ImagemagickExecutionEvent::POST_SAVE);
      // Reset local path to allow saving to other file.
      $this->arguments()->setDestinationLocalPath('');
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function parseFile() {
    // Get 'imagemagick_identify' metadata for this image. The file metadata
    // plugin will fetch it from the file via the ::identify() method if data
    // is not already available.
    if (!$file_md = $this->fileMetadataManager->uri($this->getSource())) {
      // No file, return.
      return FALSE;
    }

    if (!$file_md->getMetadata(static::FILE_METADATA_PLUGIN_ID)) {
      // No data, return.
      return FALSE;
    }

    // Sets the local file path to the one retrieved by identify if available.
    if ($source_local_path = $file_md->getMetadata(static::FILE_METADATA_PLUGIN_ID, 'source_local_path')) {
      $this->arguments()->setSourceLocalPath($source_local_path);
    }

    // Process parsed data from the first frame.
    $format = $file_md->getMetadata(static::FILE_METADATA_PLUGIN_ID, 'format');
    if ($this->formatMapper->isFormatEnabled($format)) {
      $this
        ->setWidth((int) $file_md->getMetadata(static::FILE_METADATA_PLUGIN_ID, 'width'))
        ->setHeight((int) $file_md->getMetadata(static::FILE_METADATA_PLUGIN_ID, 'height'))
        ->setExifOrientation($file_md->getMetadata(static::FILE_METADATA_PLUGIN_ID, 'exif_orientation'))
        ->setFrames($file_md->getMetadata(static::FILE_METADATA_PLUGIN_ID, 'frames_count'));
      $this->arguments()
        ->setSourceFormat($format);
      // Only Imagemagick allows to get colorspace and profiles information
      // via 'identify'.
      if ($this->getExecManager()->getPackage() === 'imagemagick') {
        $this->setColorspace($file_md->getMetadata(static::FILE_METADATA_PLUGIN_ID, 'colorspace') ?? '');
        $this->setProfiles($file_md->getMetadata(static::FILE_METADATA_PLUGIN_ID, 'profiles') ?? []);
      }
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Calls the convert executable with the specified arguments.
   *
   * @return bool
   *   TRUE if the file could be converted, FALSE otherwise.
   */
  protected function convert(): bool {
    $config = $this->configFactory->get('imagemagick.settings');

    // Ensure sourceLocalPath is prepared.
    $this->ensureSourceLocalPath();

    // Allow modules to alter the command line parameters.
    $command = 'convert';
    $this->eventDispatcher->dispatch(new ImagemagickExecutionEvent($this->arguments), ImagemagickExecutionEvent::PRE_CONVERT_EXECUTE);

    // Delete any cached file metadata for the destination image file, before
    // creating a new one, and release the URI from the manager so that
    // metadata will not stick in the same request.
    $this->fileMetadataManager->deleteCachedMetadata($this->arguments()->getDestination());
    $this->fileMetadataManager->release($this->arguments()->getDestination());

    // When destination format differs from source format, and source image
    // is multi-frame, convert only the first frame.
    $destination_format = $this->arguments()->getDestinationFormat() ?: $this->arguments()->getSourceFormat();
    if ($this->arguments()->getSourceFormat() !== $destination_format && ($this->getFrames() === NULL || $this->getFrames() > 1)) {
      $this->arguments()->setSourceFrames('[0]');
    }

    // Execute the command and return.
    return $this->getExecManager()->execute($command, $this->arguments) && file_exists($this->arguments()->getDestinationLocalPath());
  }

  /**
   * {@inheritdoc}
   */
  public function getRequirements() {
    $reported_info = [];
    if (stripos(ini_get('disable_functions'), 'proc_open') !== FALSE) {
      // proc_open() is disabled.
      $severity = REQUIREMENT_ERROR;
      $reported_info[] = $this->t("The <a href=':proc_open_url'>proc_open()</a> PHP function is disabled. It must be enabled for the toolkit to work. Edit the <a href=':disable_functions_url'>disable_functions</a> entry in your php.ini file, or consult your hosting provider.", [
        ':proc_open_url' => 'http://php.net/manual/en/function.proc-open.php',
        ':disable_functions_url' => 'http://php.net/manual/en/ini.core.php#ini.disable-functions',
      ]);
    }
    else {
      $status = $this->getExecManager()->checkPath($this->configFactory->get('imagemagick.settings')->get('path_to_binaries'));
      if (!empty($status['errors'])) {
        // Can not execute 'convert'.
        $severity = REQUIREMENT_ERROR;
        foreach ($status['errors'] as $error) {
          $reported_info[] = $error;
        }
        $reported_info[] = $this->t('Go to the <a href=":url">Image toolkit</a> page to configure the toolkit.', [':url' => Url::fromRoute('system.image_toolkit_settings')->toString()]);
      }
      else {
        // No errors, report the version information.
        $severity = REQUIREMENT_INFO;
        $version_info = explode("\n", preg_replace('/\r/', '', Html::escape($status['output'])));
        $value = array_shift($version_info);
        $more_info_available = FALSE;
        foreach ($version_info as $key => $item) {
          if (stripos($item, 'feature') !== FALSE || $key > 3) {
            $more_info_available = TRUE;
            break;

          }
          $reported_info[] = $item;
        }
        if ($more_info_available) {
          $reported_info[] = $this->t('To display more information, go to the <a href=":url">Image toolkit</a> page, and expand the \'Version information\' section.', [':url' => Url::fromRoute('system.image_toolkit_settings')->toString()]);
        }
        $reported_info[] = '';
        $reported_info[] = $this->t("Enabled image file extensions: %extensions", [
          '%extensions' => mb_strtolower(implode(', ', static::getSupportedExtensions())),
        ]);
      }
    }
    $requirements = [
      'imagemagick' => [
        'title' => $this->t('ImageMagick'),
        'value' => isset($value) ? $value : NULL,
        'description' => [
          '#markup' => implode('<br />', $reported_info),
        ],
        'severity' => $severity,
      ],
    ];

    return $requirements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isAvailable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSupportedExtensions() {
    return \Drupal::service('imagemagick.format_mapper')->getEnabledExtensions();
  }

  /**
   * {@inheritdoc}
   */
  public function apply($operation, array $arguments = []) {
    try {
      return parent::apply($operation, $arguments);
    }
    catch (\Throwable $t) {
      $this->logger->error(get_class($t) . ': ' . $t->getMessage(), []);
      return FALSE;
    }
  }

}
