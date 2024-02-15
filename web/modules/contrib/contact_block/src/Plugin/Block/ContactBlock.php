<?php

namespace Drupal\contact_block\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepository;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\contact\Access\ContactPageAccess;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'ContactBlock' block.
 *
 * @Block(
 *   id = "contact_block",
 *   admin_label = @Translation("Contact block"),
 * )
 */
class ContactBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The EntityFormBuilder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilder
   */
  protected $entityFormBuilder;

  /**
   * The Renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The contact form configuration entity.
   *
   * @var \Drupal\contact\Entity\ContactForm
   */
  protected $contactForm;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The access check of personal contact.
   *
   * @var \Drupal\contact\Access\ContactPageAccess
   */
  protected $checkContactPageAccess;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepository
   */
  protected $entityDisplayRepository;

  /**
   * Constructor for ContactBlock block class.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The route match service.
   * @param \Drupal\contact\Access\ContactPageAccess $check_contact_page_access
   *   Check the access of personal contact.
   * @param \Drupal\Core\Entity\EntityDisplayRepository $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, EntityFormBuilderInterface $entity_form_builder, RendererInterface $renderer, CurrentRouteMatch $route_match, ContactPageAccess $check_contact_page_access, EntityDisplayRepository $entity_display_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->entityFormBuilder = $entity_form_builder;
    $this->renderer = $renderer;
    $this->routeMatch = $route_match;
    $this->checkContactPageAccess = $check_contact_page_access;
    $this->entityDisplayRepository = $entity_display_repository;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('config.factory'),
      $container->get('entity.form_builder'),
      $container->get('renderer'),
      $container->get('current_route_match'),
      $container->get('access_check.contact_personal'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $contact_form = $this->getContactForm();
    $contact_message = $this->createContactMessage();

    // Deny access when the configured contact form has been deleted.
    if (empty($contact_form)) {
      return AccessResult::forbidden();
    }

    if ($contact_message->isPersonal()) {
      /** @var \Drupal\user\Entity\User $user */
      $user = $this->routeMatch->getParameter('user');

      // Deny access to the contact form if we are not on a user related page
      // or we have no access to that page.
      if (empty($user)) {
        return AccessResult::forbidden();
      }

      // Use the regular personal contact access service to check.
      return $this->checkContactPageAccess->access($user, $account);
    }

    // Access to other contact forms is equal to the permission of the
    // entity.contact_form.canonical route.
    return $contact_form->access('view', $account, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_form = $this->configFactory->get('contact.settings')->get('default_form');

    return [
      'label' => $this->t('Contact block'),
      'contact_form' => $default_form,
      'form_display' => 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $options = $this->entityTypeManager
      ->getStorage('contact_form')
      ->loadMultiple();
    foreach ($options as $key => $option) {
      $options[$key] = $option->label();
    }

    $form['contact_form'] = [
      '#type' => 'select',
      '#title' => $this->t('Contact form'),
      '#options' => $options,
      '#default_value' => $this->configuration['contact_form'],
      '#required' => TRUE,
    ];

    $form['form_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Form display'),
      '#options' => $this->entityDisplayRepository->getFormModeOptions('contact_message'),
      '#default_value' => $this->configuration['form_display'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['contact_form'] = $form_state->getValue('contact_form');
    $this->configuration['form_display'] = $form_state->getValue('form_display');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = [];

    /** @var \Drupal\contact\Entity\ContactForm $contact_form */
    $contact_form = $this->getContactForm();
    if ($contact_form) {
      $contact_message = $this->createContactMessage();

      // The personal contact form has a fixed recipient: the user who's
      // contact page we visit. We use the 'user' property from the URL
      // to determine this user. For example: user/{user}.
      if ($contact_message->isPersonal()) {
        $user = $this->routeMatch->getParameter('user');
        $contact_message->set('recipient', $user);
      }

      $form_display = $this->configuration['form_display'];
      $form = $this->entityFormBuilder->getForm($contact_message, $form_display);
      $form['#form_display'] = $form_display;
      $form['#cache']['contexts'][] = 'user.permissions';
      $this->renderer->addCacheableDependency($form, $contact_form);

      $form['#contextual_links']['contact_block'] = [
        'route_parameters' => ['contact_form' => $contact_form->id()],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {

    $dependencies = array_merge_recursive(parent::calculateDependencies(), ['config' => []]);

    // Add the contact form as a dependency.
    if ($contact_form = $this->getContactForm()) {
      $dependencies['config'][] = $contact_form->getConfigDependencyName();
    }

    return $dependencies;
  }

  /**
   * Loads the contact form entity.
   *
   * @return \Drupal\contact\Entity\ContactForm|null
   *   The contact form configuration entity. NULL if the entity does not exist.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getContactForm() {
    if (!isset($this->contactForm)) {
      if (isset($this->configuration['contact_form'])) {
        $this->contactForm = $this->entityTypeManager
          ->getStorage('contact_form')
          ->load($this->configuration['contact_form']);
      }
    }
    return $this->contactForm;
  }

  /**
   * Creates the contact message entity without saving it.
   *
   * @return \Drupal\contact\Entity\Message|null
   *   The contact message entity. NULL if the entity does not exist.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function createContactMessage() {
    $contact_message = NULL;

    $contact_form = $this->getContactForm();
    if ($contact_form) {
      $contact_message = $this->entityTypeManager
        ->getStorage('contact_message')
        ->create(['contact_form' => $contact_form->id()]);
    }
    return $contact_message;
  }

}
