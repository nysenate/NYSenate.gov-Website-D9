<?php

namespace Drupal\devel\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form that displays all the config variables to edit them.
 */
class ConfigsList extends FormBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new ConfigsList object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    RedirectDestinationInterface $redirect_destination,
    TranslationInterface $string_translation
  ) {
    $this->configFactory = $config_factory;
    $this->redirectDestination = $redirect_destination;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('redirect.destination'),
      $container->get('string_translation'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'devel_config_system_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $filter = ''): array {
    $form['filter'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter variables'),
      '#attributes' => ['class' => ['container-inline']],
      '#open' => isset($filter) && trim($filter) != '',
    ];
    $form['filter']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Variable name'),
      '#title_display' => 'invisible',
      '#default_value' => $filter,
    ];
    $form['filter']['actions'] = ['#type' => 'actions'];
    $form['filter']['actions']['show'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    $header = [
      'name' => ['data' => $this->t('Name')],
      'edit' => ['data' => $this->t('Operations')],
    ];

    $rows = [];
    $destination = $this->redirectDestination->getAsArray();

    // List all the variables filtered if any filter was provided.
    $names = $this->configFactory->listAll($filter);

    foreach ($names as $config_name) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('devel.config_edit', ['config_name' => $config_name]),
        'query' => $destination,
      ];
      $rows[] = [
        'name' => $config_name,
        'operation' => ['data' => ['#type' => 'operations', '#links' => $operations]],
      ];
    }

    $form['variables'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No variables found'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $filter = $form_state->getValue('name');
    $form_state->setRedirectUrl(Url::FromRoute('devel.configs_list', ['filter' => Html::escape($filter)]));
  }

}
