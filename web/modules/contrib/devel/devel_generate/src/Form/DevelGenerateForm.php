<?php

namespace Drupal\devel_generate\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\devel_generate\DevelGenerateBaseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a form that allows privileged users to generate entities.
 */
class DevelGenerateForm extends FormBase {

  /**
   * The manager to be used for instantiating plugins.
   */
  protected PluginManagerInterface $develGenerateManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Logger service.
   */
  protected LoggerInterface $logger;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new DevelGenerateForm object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $devel_generate_manager
   *   The manager to be used for instantiating plugins.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(
    PluginManagerInterface $devel_generate_manager,
    MessengerInterface $messenger,
    LoggerInterface $logger,
    RequestStack $request_stack,
    TranslationInterface $string_translation
  ) {
    $this->develGenerateManager = $devel_generate_manager;
    $this->messenger = $messenger;
    $this->logger = $logger;
    $this->requestStack = $request_stack;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('plugin.manager.develgenerate'),
      $container->get('messenger'),
      $container->get('logger.channel.devel_generate'),
      $container->get('request_stack'),
      $container->get('string_translation'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'devel_generate_form_' . $this->getPluginIdFromRequest();
  }

  /**
   * Returns the value of the param _plugin_id for the current request.
   *
   * @see \Drupal\devel_generate\Routing\DevelGenerateRouteSubscriber
   */
  protected function getPluginIdFromRequest() {
    $request = $this->requestStack->getCurrentRequest();
    return $request->get('_plugin_id');
  }

  /**
   * Returns a DevelGenerate plugin instance for a given plugin id.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   *
   * @return \Drupal\devel_generate\DevelGenerateBaseInterface
   *   A DevelGenerate plugin instance.
   */
  public function getPluginInstance(string $plugin_id): DevelGenerateBaseInterface {
    return $this->develGenerateManager->createInstance($plugin_id, []);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $plugin_id = $this->getPluginIdFromRequest();
    $instance = $this->getPluginInstance($plugin_id);
    $form = $instance->settingsForm($form, $form_state);
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $plugin_id = $this->getPluginIdFromRequest();
    $instance = $this->getPluginInstance($plugin_id);
    $instance->settingsFormValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    try {
      $plugin_id = $this->getPluginIdFromRequest();
      $instance = $this->getPluginInstance($plugin_id);
      $instance->generate($form_state->getValues());
    }
    catch (\Exception $e) {
      $this->logger->error($this->t('Failed to generate elements due to "%error".', ['%error' => $e->getMessage()]));
      $this->messenger->addMessage($this->t('Failed to generate elements due to "%error".', ['%error' => $e->getMessage()]));
    }
  }

}
