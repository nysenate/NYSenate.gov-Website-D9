<?php

namespace Drupal\nys_dashboard\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Session\AccountProxy;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Your senator filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("your_senator_filter")
 */
class YourSenatorFilter extends FilterPluginBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  public $entityTypeManager;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  public $currentUser;

  /**
   * Request stack service.
   *
   * @var \Drupal\Core\Http\RequestStack
   */
  public $requestStack;

  /**
   * Constructs a YourSenaterFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   Current user service.
   * @param \Drupal\Core\Http\RequestStack $requestStack
   *   Request stack service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManager $entityTypeManager,
    AccountProxy $currentUser,
    RequestStack $requestStack
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('request_stack'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'checkbox',
      '#title' => 'Only show content from my senator',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    // Workaround to potential bug in views_combine where wrong input sent to
    // combined view.
    if ($input['your_senator_filter']) {
      $request_params = $this->requestStack->getCurrentRequest()->query->all();
      if (empty($request_params['your_senator_filter'])) {
        return FALSE;
      }
    }
    return parent::acceptExposedInput($input);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $current_user_senator_id = $this->entityTypeManager
      ->getStorage('user')
      ->load($this->currentUser->id())
      ?->field_district
      ?->entity
      ?->field_senator
      ?->entity
      ?->id();

    if (empty($current_user_senator_id)) {
      return;
    }

    $this->value = $current_user_senator_id;
    parent::query();
  }

}
