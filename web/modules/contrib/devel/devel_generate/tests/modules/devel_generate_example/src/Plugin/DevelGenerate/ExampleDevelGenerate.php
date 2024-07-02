<?php

namespace Drupal\devel_generate_example\Plugin\DevelGenerate;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a ExampleDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "devel_generate_example",
 *   label = "Example",
 *   description = "Generate a given number of examples.",
 *   url = "devel_generate_example",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 50,
 *     "kill" = FALSE
 *   }
 * )
 */
class ExampleDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * Provides system time.
   */
  protected TimeInterface $time;

  /**
   * Constructs a new UserDevelGenerate object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Provides system time.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    MessengerInterface $messenger,
    LanguageManagerInterface $language_manager,
    ModuleHandlerInterface $module_handler,
    TranslationInterface $string_translation,
    TimeInterface $time
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $messenger, $language_manager, $module_handler, $string_translation);
    $this->time = $time;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $entity_type_manager,
      $container->get('messenger'),
      $container->get('language_manager'),
      $container->get('module_handler'),
      $container->get('string_translation'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {

    $form['num'] = [
      '#type' => 'textfield',
      '#title' => $this->t('How many examples would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#size' => 10,
    ];

    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete all examples before generating new examples.'),
      '#default_value' => $this->getSetting('kill'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values): void {
    $num = $values['num'];
    $kill = $values['kill'];

    if ($kill) {
      $this->setMessage($this->t('Old examples have been deleted.'));
    }

    // Creating user in order to demonstrate
    // how to override default business login generation.
    $edit = [
      'uid'     => NULL,
      'name'    => 'example_devel_generate',
      'pass'    => '',
      'mail'    => 'example_devel_generate@example.com',
      'status'  => 1,
      'created' => $this->time->getRequestTime(),
      'roles' => '',
      // A flag to let hook_user_* know that this is a generated user.
      'devel_generate' => TRUE,
    ];

    $account = user_load_by_name('example_devel_generate');
    if (!$account) {
      $account = $this->entityTypeManager->getStorage('user')->create($edit);
    }

    // Populate all fields with sample values.
    $this->populateFields($account);

    $account->save();

    $this->setMessage($this->t('@num_examples created.', [
      '@num_examples' => $this->formatPlural($num, '1 example', '@count examples'),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams(array $args, array $options = []): array {
    return [
      'num' => $options['num'],
      'kill' => $options['kill'],
    ];
  }

}
