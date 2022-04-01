<?php

namespace Drupal\simple_sitemap\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Class FormHelper
 * @package Drupal\simple_sitemap\Form
 */
class FormHelper {
  use StringTranslationTrait;

  const PRIORITY_HIGHEST = 10;
  const PRIORITY_DIVIDER = 10;

  /**
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  protected $generator;

  /**
   * @var \Drupal\simple_sitemap\EntityHelper
   */
  protected $entityHelper;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Form\FormState
   */
  protected $formState;

  /**
   * @var string|null
   */
  protected $entityCategory;

  /**
   * @var string
   */
  protected $entityTypeId;

  /**
   * @var string
   */
  protected $bundleName;

  /**
   * @var string
   */
  protected $instanceId;

  /**
   * @var array
   */
  protected $settings;

  /**
   * @var array
   */
  protected static $allowedFormOperations = [
    'default',
    'edit',
    'add',
    'register',
  ];

  /**
   * @var array
   */
  protected static $changefreqValues = [
    'always',
    'hourly',
    'daily',
    'weekly',
    'monthly',
    'yearly',
    'never',
  ];

  protected static $cronIntervals = [
    1,
    3,
    6,
    12,
    24,
    48,
    72,
    96,
    120,
    144,
    168,
  ];

  /**
   * FormHelper constructor.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   * @param \Drupal\simple_sitemap\EntityHelper $entityHelper
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   */
  public function __construct(
    Simplesitemap $generator,
    EntityHelper $entityHelper,
    AccountProxyInterface $current_user
  ) {
    $this->generator = $generator;
    $this->entityHelper = $entityHelper;
    $this->currentUser = $current_user;
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   */
  public function processForm(FormStateInterface $form_state) {
    $this->formState = $form_state;
    $this->cleanUpFormInfo();

    if ($this->getEntityDataFromFormEntity()) {
      $this->negotiateSettings();
    }

    return $this->supports();
  }

  /**
   * @param string $entity_category
   * @return $this
   */
  public function setEntityCategory($entity_category) {
    $this->entityCategory = $entity_category;
    return $this;
  }

  /**
   * @return null|string
   */
  public function getEntityCategory() {
    return $this->entityCategory;
  }

  /**
 * @param string $entity_type_id
 * @return $this
 */
  public function setEntityTypeId($entity_type_id) {
    $this->entityTypeId = $entity_type_id;

    return $this;
  }

  /**
   * @return string
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * @param string $bundle_name
   * @return $this
   */
  public function setBundleName($bundle_name) {
    $this->bundleName = $bundle_name;

    return $this;
  }

  /**
   * @return string
   */
  public function getBundleName() {
    return $this->bundleName;
  }

  /**
   * @param string $instance_id
   * @return $this
   */
  public function setInstanceId($instance_id) {
    $this->instanceId = $instance_id;

    return $this;
  }

  /**
   * @return string
   */
  public function getInstanceId() {
    return $this->instanceId;
  }

  /**
   * @return bool
   */
  protected function supports() {

    // Do not alter the form if it is irrelevant to sitemap generation.
    if (empty($this->getEntityCategory())) {
      return FALSE;
    }

    // Do not alter the form if user lacks certain permissions.
    if (!$this->currentUser->hasPermission('administer sitemap settings')) {
      return FALSE;
    }

    // Do not alter the form if entity is not enabled in sitemap settings.
    if (!$this->generator->entityTypeIsEnabled($this->getEntityTypeId())) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @return bool
   */
  public function entityIsNew() {
    return !empty($entity = $this->getFormEntity()) ? $entity->isNew() : TRUE;
  }

  /**
   * @param array $form_fragment
   * @return $this
   */
  public function displayRegenerateNow(&$form_fragment) {
    $form_fragment['simple_sitemap_regenerate_now'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Regenerate all sitemaps after hitting <em>Save</em>'),
      '#description' => $this->t('This setting will regenerate all sitemaps including the above changes.'),
      '#default_value' => FALSE,
    ];
    if ($this->generator->getSetting('cron_generate')) {
      $form_fragment['simple_sitemap_regenerate_now']['#description'] .= '<br>' . $this->t('Otherwise the sitemaps will be regenerated during a future cron run.');
    }

    return $this;
  }

  /**
   * @return $this
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function negotiateSettings() {

    $this->settings = $this->generator->setVariants(TRUE)
      ->getBundleSettings($this->getEntityTypeId(), $this->getBundleName(), TRUE, TRUE);
    if ($this->getEntityCategory() === 'instance') {

      //todo Should spit out variant => settings and not just settings; to do this, alter getEntityInstanceSettings() to include 'multiple variants' option.
      foreach ($this->settings as $variant_name => $settings) {
        if (NULL !== $instance_id = $this->getInstanceId()) {
          $this->settings[$variant_name] = $this->generator
            ->setVariants($variant_name)
            ->getEntityInstanceSettings($this->getEntityTypeId(), $instance_id);
        }
        $this->settings[$variant_name]['bundle_settings'] = $settings;
      }
    }

    return $this;
  }

  /**
   * @param $form_fragment
   * @return $this
   */
  public function displayEntitySettings(&$form_fragment) {
    $bundle_name = !empty($this->getBundleName())
      ? $this->entityHelper->getBundleLabel($this->getEntityTypeId(), $this->getBundleName())
      : $this->t('undefined');

    $variants = $this->generator->getSitemapManager()->getSitemapVariants(NULL, FALSE);
    $form_fragment['settings']['#markup'] = empty($variants)
      ? $this->t('At least one sitemap variants needs to be defined for a bundle to be indexable.<br>Variants can be configured <a href="@url">here</a>.', ['@url' => $GLOBALS['base_url'] . '/admin/config/search/simplesitemap/variants'])
      : '<strong>' . $this->t('Sitemap variants') . '</strong>';

    foreach ($variants as $variant => $definition) {
      $form_fragment['settings'][$variant] = [
        '#type' => 'details',
        '#title' => '<em>' . $this->t($definition['label']) . '</em>',
        '#open' => !empty($this->settings[$variant]['index']),
      ];

      // Disable fields of entity instance whose bundle is not indexed.
      $form_fragment['settings'][$variant]['#disabled'] = $this->getEntityCategory() === 'instance' && empty($this->settings[$variant]['bundle_settings']['index']);

      // Index
      $form_fragment['settings'][$variant]['index_' . $variant . '_' . $this->getEntityTypeId() . '_settings'] = [
        '#type' => 'radios',
        '#default_value' => (int) $this->settings[$variant]['index'],
        '#options' => [
          $this->getEntityCategory() === 'instance'
            ? $this->t('Do not index this <em>@bundle</em> entity in variant <em>@variant_label</em>', ['@bundle' => $bundle_name, '@variant_label' => $this->t($variants[$variant]['label'])])
            : $this->t('Do not index entities of type <em>@bundle</em> in variant <em>@variant_label</em>', ['@bundle' => $bundle_name, '@variant_label' => $this->t($variants[$variant]['label'])]),
          $this->getEntityCategory() === 'instance'
            ? $this->t('Index this <em>@bundle entity</em> in variant <em>@variant_label</em>', ['@bundle' => $bundle_name, '@variant_label' => $this->t($variants[$variant]['label'])])
            : $this->t('Index entities of type <em>@bundle</em> in variant <em>@variant_label</em>', ['@bundle' => $bundle_name, '@variant_label' => $this->t($variants[$variant]['label'])]),
        ],
        '#attributes' => ['class' => ['enabled-for-variant', $variant]],
      ];

      if ($this->getEntityCategory() === 'instance' && isset($this->settings[$variant]['bundle_settings']['index'])) {
        $form_fragment['settings'][$variant]['index_' . $variant . '_' . $this->getEntityTypeId() . '_settings']['#options'][(int) $this->settings[$variant]['bundle_settings']['index']] .= ' <em>(' . $this->t('default') . ')</em>';
      }

      // Priority
      $form_fragment['settings'][$variant]['priority_' . $variant . '_' . $this->getEntityTypeId() . '_settings'] = [
        '#type' => 'select',
        '#title' => $this->t('Priority'),
        '#description' => $this->getEntityCategory() === 'instance'
          ? $this->t('The priority this <em>@bundle</em> entity will have in the eyes of search engine bots.', ['@bundle' => $bundle_name])
          : $this->t('The priority entities of this type will have in the eyes of search engine bots.'),
        '#default_value' => $this->settings[$variant]['priority'],
        '#options' => $this->getPrioritySelectValues(),
        '#states' => [
          'visible' => [':input[name="index_' . $variant . '_' . $this->getEntityTypeId() . '_settings"]' => ['value' => 1]],
        ],
      ];

      if ($this->getEntityCategory() === 'instance' && isset($this->settings[$variant]['bundle_settings']['priority'])) {
        $form_fragment['settings'][$variant]['priority_' . $variant . '_' . $this->getEntityTypeId() . '_settings']['#options'][$this->formatPriority($this->settings[$variant]['bundle_settings']['priority'])] .= ' (' . $this->t('default') . ')';
      }

      // Changefreq
      $form_fragment['settings'][$variant]['changefreq_' . $variant . '_' . $this->getEntityTypeId() . '_settings'] = [
        '#type' => 'select',
        '#title' => $this->t('Change frequency'),
        '#description' => $this->getEntityCategory() === 'instance'
          ? $this->t('The frequency with which this <em>@bundle</em> entity changes. Search engine bots may take this as an indication of how often to index it.', ['@bundle' => $bundle_name])
          : $this->t('The frequency with which entities of this type change. Search engine bots may take this as an indication of how often to index them.'),
        '#default_value' => isset($this->settings[$variant]['changefreq']) ? $this->settings[$variant]['changefreq'] : NULL,
        '#options' => $this->getChangefreqSelectValues(),
        '#states' => [
          'visible' => [':input[name="index_' . $variant . '_' . $this->getEntityTypeId() . '_settings"]' => ['value' => 1]],
        ],
      ];

      if ($this->getEntityCategory() === 'instance' && isset($this->settings[$variant]['bundle_settings']['changefreq'])) {
        $form_fragment['settings'][$variant]['changefreq_' . $variant . '_' . $this->getEntityTypeId() . '_settings']['#options'][$this->settings[$variant]['bundle_settings']['changefreq']] .= ' (' . $this->t('default') . ')';
      }

      // Images
      $form_fragment['settings'][$variant]['include_images_' . $variant . '_' . $this->getEntityTypeId() . '_settings'] = [
        '#type' => 'select',
        '#title' => $this->t('Include images'),
        '#description' => $this->getEntityCategory() === 'instance'
          ? $this->t('Determines if images referenced by this <em>@bundle</em> entity should be included in the sitemap.', ['@bundle' => $bundle_name])
          : $this->t('Determines if images referenced by entities of this type should be included in the sitemap.'),
        '#default_value' => isset($this->settings[$variant]['include_images']) ? (int) $this->settings[$variant]['include_images'] : 0,
        '#options' => [$this->t('No'), $this->t('Yes')],
        '#states' => [
          'visible' => [':input[name="index_' . $variant . '_' . $this->getEntityTypeId() . '_settings"]' => ['value' => 1]],
        ],
      ];

      if ($this->getEntityCategory() === 'instance' && isset($this->settings[$variant]['bundle_settings']['include_images'])) {
        $form_fragment['settings'][$variant]['include_images_' . $variant . '_' . $this->getEntityTypeId() . '_settings']['#options'][(int) $this->settings[$variant]['bundle_settings']['include_images']] .= ' (' . $this->t('default') . ')';
      }
    }

    return $this;
  }

  /**
   * Checks if this particular form is a bundle form, or a bundle instance form
   * and gathers sitemap settings from the database.
   *
   * @return bool
   *   TRUE if this is a bundle or bundle instance form, FALSE otherwise.
   */
  protected function getEntityDataFromFormEntity() {
    if (!$form_entity = $this->getFormEntity()) {
      return FALSE;
    }

    $entity_type_id = $form_entity->getEntityTypeId();
    $sitemap_entity_types = $this->entityHelper->getSupportedEntityTypes();
    if (isset($sitemap_entity_types[$entity_type_id])) {
      $this->setEntityCategory('instance');
    }
    else {
      /** @var \Drupal\Core\Entity\EntityType $sitemap_entity_type */
      foreach ($sitemap_entity_types as $sitemap_entity_type) {
        if ($sitemap_entity_type->getBundleEntityType() === $entity_type_id) {
          $this->setEntityCategory('bundle');
          break;
        }
      }
    }

    // Menu fix.
    $this->setEntityCategory(
      NULL === $this->getEntityCategory() && $entity_type_id === 'menu'
        ? 'bundle'
        : $this->getEntityCategory()
    );

    switch ($this->getEntityCategory()) {
      case 'bundle':
        $this->setEntityTypeId($this->entityHelper->getBundleEntityTypeId($form_entity));
        $this->setBundleName($form_entity->id());
        $this->setInstanceId(NULL);
        break;

      case 'instance':
        $this->setEntityTypeId($entity_type_id);
        $this->setBundleName($this->entityHelper->getEntityInstanceBundleName($form_entity));
        // New menu link's id is '' instead of NULL, hence checking for empty.
        $this->setInstanceId(!$this->entityIsNew() ? $form_entity->id() : NULL);
        break;

      default:
        return FALSE;
    }
    return TRUE;
  }

  /**
   * Gets the object entity of the form if available.
   *
   * @return \Drupal\Core\Entity\EntityBase|false
   *   Entity or FALSE if non-existent or if form operation is
   *   'delete'.
   */
  protected function getFormEntity() {
    $form_object = $this->formState->getFormObject();
    if (NULL !== $form_object
      && method_exists($form_object, 'getOperation')
      && method_exists($form_object, 'getEntity')
      && in_array($form_object->getOperation(), self::$allowedFormOperations)) {
      return $form_object->getEntity();
    }

    return FALSE;
  }

  /**
   * Removes gathered form information from service object.
   *
   * Needed because this service may contain form info from the previous
   * operation when revived from the container.
   *
   * @return $this
   */
  public function cleanUpFormInfo() {
    $this->entityCategory = NULL;
    $this->entityTypeId = NULL;
    $this->bundleName = NULL;
    $this->instanceId = NULL;
    $this->settings = NULL;

    return $this;
  }

  /**
   * Checks if simple_sitemap values have been changed after submitting the form.
   * To be used in an entity form submit.
   *
   * @param $form
   * @param array $values
   *
   * @return bool
   *   TRUE if simple_sitemap form values have been altered by the user.
   *
   * @todo Make it work with variants.
   */
  public function valuesChanged($form, array $values) {
//    foreach (self::$valuesToCheck as $field_name) {
//      if (!isset($form['simple_sitemap'][$field_name]['#default_value'])
//        || (isset($values[$field_name]) && $values[$field_name] != $form['simple_sitemap'][$field_name]['#default_value'])) {
//        return TRUE;
//      }
//    }
//
//    return FALSE;

    return TRUE;
  }

  /**
   * Gets the values needed to display the variant dropdown setting.
   *
   * @return array
   */
  public function getVariantSelectValues() {
    return array_map(
      function($variant) { return $this->t($variant['label']); },
      $this->generator->getSitemapManager()->getSitemapVariants(NULL, FALSE)
    );
  }

  /**
   * Returns correct default value for variant select list.
   *
   * If only one variant is available, return it, otherwise check if a default
   * variant is provided and return it.
   *
   * @param string|null $default_value
   *  Actual default value from the database.
   *
   * @return string|null
   *  Value to be set on form.
   */
  public function getVariantSelectValuesDefault($default_value) {
    $options = $this->getVariantSelectValues();
    return NULL === $default_value
      ? (1 === count($options)
        ? array_keys($options)[0]
        : (!empty($default = $this->generator->getSetting('default_variant'))
          ? $default
          : $default_value
        )
      )
      : $default_value;
  }

  /**
   * Gets the values needed to display the priority dropdown setting.
   *
   * @return array
   */
  public function getPrioritySelectValues() {
    $options = [];
    foreach (range(0, self::PRIORITY_HIGHEST) as $value) {
      $value = $this->formatPriority($value / self::PRIORITY_DIVIDER);
      $options[$value] = $value;
    }

    return $options;
  }

  /**
   * Gets the values needed to display the changefreq dropdown setting.
   *
   * @return array
   */
  public function getChangefreqSelectValues() {
    $options = ['' => $this->t('- Not specified -')];
    foreach (self::getChangefreqOptions() as $setting) {
      $options[$setting] = $this->t($setting);
    }

    return $options;
  }

  /**
   * @return array
   */
  public static function getChangefreqOptions() {
    return self::$changefreqValues;
  }

  /**
   * @param string $priority
   * @return string
   */
  public function formatPriority($priority) {
    return number_format((float) $priority, 1, '.', '');
  }

  /**
   * @param string|int $priority
   * @return bool
   */
  public static function isValidPriority($priority) {
    return is_numeric($priority) && $priority >= 0 && $priority <= 1;
  }

  /**
   * @param string $changefreq
   * @return bool
   */
  public static function isValidChangefreq($changefreq) {
    return in_array($changefreq, self::$changefreqValues);
  }

  /**
   * @return array
   */
  public static function getCronIntervalOptions() {
    /** @var \Drupal\Core\Datetime\DateFormatter $formatter */
    $formatter = \Drupal::service('date.formatter');
    $intervals = array_flip(self::$cronIntervals);
    foreach ($intervals as $interval => &$label) {
      $label = $formatter->formatInterval($interval * 60 * 60);
    }

    return [0 => t('On every cron run')] + $intervals;
  }

  /**
   * @return string
   */
  public static function getDonationText() {
    return '<div class="description">' . t('If you would like to say thanks and support the development of this module, a <a target="_blank" href="@url">donation</a> will be much appreciated.', ['@url' => 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5AFYRSBLGSC3W']) . '</div>';
  }
}
