<?php

namespace Drupal\config_split\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The entity form.
 */
class ConfigSplitEntityForm extends EntityForm {

  /**
   * The drupal state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Drupal\Core\Extension\ThemeHandler definition.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The drupal state.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   */
  public function __construct(StateInterface $state, ThemeHandlerInterface $themeHandler) {
    $this->state = $state;
    $this->themeHandler = $themeHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\config_split\Entity\ConfigSplitEntityInterface $config */
    $config = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $config->label(),
      '#description' => $this->t("Label for the Configuration Split setting."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $config->id(),
      '#machine_name' => [
        'exists' => '\Drupal\config_split\Entity\ConfigSplitEntity::load',
      ],
    ];

    $form['static_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Static Settings'),
      '#description' => $this->t("These settings need a cache clear when overridden in settings.php and the split needs to be single imported before the config import for new values to take effect."),
    ];
    $form['static_fieldset']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Describe this config split setting. The text will be displayed on the <em>Configuration Split setting</em> list page.'),
      '#default_value' => $config->get('description'),
    ];
    $form['static_fieldset']['folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Folder'),
      '#description' => $this->t('The directory, relative to the Drupal root, to which to save the filtered config. Recommended is a sibling directory of what you defined in <code>$config_directories[CONFIG_SYNC_DIRECTORY]</code> in settings.php, for more information consult the README.<br/>Configuration related to the "filtered" items below will be split from the main configuration and exported to this folder.<br/>Leave the folder empty to use a special database storage if you do not want to share the configuration.'),
      '#default_value' => $config->get('folder'),
    ];
    $form['static_fieldset']['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#description' => $this->t('The weight to order the splits.'),
      '#default_value' => $config->get('weight'),
    ];
    $form['static_fieldset']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#description' => $this->t('Active splits get used by default, this property can be overwritten like any other config entity in settings.php.'),
      '#default_value' => ($config->get('status') ? TRUE : FALSE),
    ];

    $form['blacklist_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Complete Split'),
      '#description' => $this->t("<em>Complete Split:</em>
       Configuration listed here will be removed from the sync directory and
       saved in the split directory instead. Modules will be removed from
       core.extension when exporting (and added back when importing with the
       split enabled.)"),
    ];

    $module_handler = $this->moduleHandler;
    $modules = array_map(function ($module) use ($module_handler) {
      return $module_handler->getName($module->getName());
    }, $module_handler->getModuleList());
    // Add the existing ones with the machine name so they do not get lost.
    $modules = $modules + array_combine(array_keys($config->get('module')), array_keys($config->get('module')));

    // Sorting module list by name for making selection easier.
    asort($modules, SORT_NATURAL | SORT_FLAG_CASE);

    $multiselect_type = 'select';
    if (!$this->useSelectList()) {
      $multiselect_type = 'checkboxes';
      // Add the css library if we use checkboxes.
      $form['#attached']['library'][] = 'config_split/config-split-form';
    }

    $form['blacklist_fieldset']['module'] = [
      '#type' => $multiselect_type,
      '#title' => $this->t('Modules'),
      '#description' => $this->t('Select modules to split. Configuration depending on the modules is automatically split off completely as well.'),
      '#options' => $modules,
      '#size' => 20,
      '#multiple' => TRUE,
      '#default_value' => array_keys($config->get('module')),
    ];

    // We should probably find a better way for this.
    $theme_handler = $this->themeHandler;
    $themes = array_map(function ($theme) use ($theme_handler) {
      return $theme_handler->getName($theme->getName());
    }, $theme_handler->listInfo());
    $form['blacklist_fieldset']['theme'] = [
      '#type' => $multiselect_type,
      '#title' => $this->t('Themes'),
      '#description' => $this->t('Select themes to split.'),
      '#options' => $themes,
      '#size' => 5,
      '#multiple' => TRUE,
      '#default_value' => array_keys($config->get('theme')),
    ];
    // At this stage we do not support themes. @TODO: support themes.
    $form['blacklist_fieldset']['theme']['#access'] = FALSE;

    $options = array_combine($this->configFactory()->listAll(), $this->configFactory()->listAll());

    $form['blacklist_fieldset']['blacklist_picker'] = [
      '#type' => $multiselect_type,
      '#title' => $this->t('Configuration items'),
      '#description' => $this->t('Select configuration to split. Configuration depending on split modules does not need to be selected here specifically.'),
      '#options' => $options,
      '#size' => 20,
      '#multiple' => TRUE,
      '#default_value' => array_intersect($config->get('blacklist'), array_keys($options)),
    ];
    $form['blacklist_fieldset']['blacklist_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Additional configuration'),
      '#description' => $this->t('Select additional configuration to split. One configuration key per line. You can use wildcards.'),
      '#size' => 5,
      '#default_value' => implode("\n", array_diff($config->get('blacklist'), array_keys($options))),
    ];

    $form['graylist_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Conditional Split'),
      '#description' => $this->t("<em>Conditional Split:</em>
       Configuration listed here will be left untouched in the main sync
       directory. The <em>currently active</em> version will be exported to the
       split directory.<br />
       Use this for configuration that is different on your site but which
       should also remain in the main sync directory."),
    ];

    $form['graylist_fieldset']['graylist_picker'] = [
      '#type' => $multiselect_type,
      '#title' => $this->t('Configuration items'),
      '#description' => $this->t('Select configuration to split conditionally.'),
      '#options' => $options,
      '#size' => 20,
      '#multiple' => TRUE,
      '#default_value' => array_intersect($config->get('graylist'), array_keys($options)),
    ];
    $form['graylist_fieldset']['graylist_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Additional configuration'),
      '#description' => $this->t('Select additional configuration to conditionally split. One configuration key per line. You can use wildcards.'),
      '#size' => 5,
      '#default_value' => implode("\n", array_diff($config->get('graylist'), array_keys($options))),
    ];

    $form['graylist_fieldset']['graylist_dependents'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include dependent configuration'),
      '#description' => $this->t('If this is set, conditionally split configuration will also include configuration that depends on it.'),
      '#default_value' => ($config->get('graylist_dependents') ? TRUE : FALSE),
    ];

    $form['graylist_fieldset']['graylist_skip_equal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Split only when different'),
      '#description' => $this->t('If this is set, conditionally split configuration will not be exported to the split directory if it is equal to the one in the main sync directory.'),
      '#default_value' => ($config->get('graylist_skip_equal') ? TRUE : FALSE),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $folder = $form_state->getValue('folder');
    if (static::isConflicting($folder)) {
      $form_state->setErrorByName('folder', $this->t('The split folder can not be in the sync folder.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Transform the values from the form to correctly save the entity.
    $extensions = $this->config('core.extension');
    // Add the configs modules so we can save inactive splits.
    $module_list = $extensions->get('module') + $this->entity->get('module');

    $moduleSelection = $this->readValuesFromPicker($form_state->getValue('module'));
    $form_state->setValue('module', array_intersect_key($module_list, $moduleSelection));

    $themeSelection = $this->readValuesFromPicker($form_state->getValue('theme'));
    $form_state->setValue('theme', array_intersect_key($extensions->get('theme'), $themeSelection));

    $blacklistSelection = $this->readValuesFromPicker($form_state->getValue('blacklist_picker'));
    $form_state->setValue('blacklist', array_merge(
      array_keys($blacklistSelection),
      $this->filterConfigNames($form_state->getValue('blacklist_text'))
    ));

    $graylistSelection = $this->readValuesFromPicker($form_state->getValue('graylist_picker'));
    $form_state->setValue('graylist', array_merge(
      array_keys($graylistSelection),
      $this->filterConfigNames($form_state->getValue('graylist_text'))
    ));

    parent::submitForm($form, $form_state);
  }

  /**
   * If the chosen or select2 module is active, the form must use select field.
   *
   * @return bool
   *   True if the form must use a select field
   */
  protected function useSelectList() {
    // Allow the setting to be overwritten with the drupal state.
    $stateOverride = $this->state->get('config_split_use_select');
    if ($stateOverride !== NULL) {
      // Honestly this is probably only useful in tests or if another module
      // comes along and does what chosen or select2 do.
      return (bool) $stateOverride;
    }

    // Modules make the select widget useful.
    foreach (['chosen', 'select2_all'] as $module) {
      if ($this->moduleHandler->moduleExists($module)) {
        return TRUE;
      }
    }

    // Fall back to checkboxes.
    return FALSE;
  }

  /**
   * Read values selected depending on widget used: select or checkbox.
   *
   * @param array $pickerSelection
   *   The form value array.
   *
   * @return array
   *   Array of selected values
   */
  protected function readValuesFromPicker(array $pickerSelection) {
    if ($this->useSelectList()) {
      $moduleSelection = $pickerSelection;
    }
    else {
      // Checkboxes return a value for each item. We only keep the selected one.
      $moduleSelection = array_filter($pickerSelection, function ($value) {
        return $value;
      });
    }

    return $moduleSelection;
  }

  /**
   * Filter text input for valid configuration names (including wildcards).
   *
   * @param string|string[] $text
   *   The configuration names, one name per line.
   *
   * @return string[]
   *   The array of configuration names.
   */
  protected function filterConfigNames($text) {
    if (!is_array($text)) {
      $text = explode("\n", $text);
    }

    foreach ($text as &$config_entry) {
      $config_entry = strtolower($config_entry);
    }

    // Filter out illegal characters.
    return array_filter(preg_replace('/[^a-z0-9_\.\-\*]+/', '', $text));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $config_split = $this->entity;
    $status = $config_split->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Configuration Split setting.', [
          '%label' => $config_split->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Configuration Split setting.', [
          '%label' => $config_split->label(),
        ]));
    }
    $folder = $form_state->getValue('folder');
    if (!empty($folder) && !file_exists($folder)) {
      $this->messenger()->addWarning(
        $this->t('The storage path "%path" for %label Configuration Split setting does not exist. Make sure it exists and is writable.',
          [
            '%label' => $config_split->label(),
            '%path' => $folder,
          ]
        ));
    }
    $form_state->setRedirectUrl($config_split->toUrl('collection'));
  }

  /**
   * Check whether the folder name conflicts with the default sync directory.
   *
   * @param string $folder
   *   The split folder name to check.
   *
   * @return bool
   *   True if the folder is inside the sync directory.
   */
  protected static function isConflicting($folder) {
    return strpos(rtrim($folder, '/') . '/', rtrim(Settings::get('config_sync_directory'), '/') . '/') !== FALSE;
  }

}
