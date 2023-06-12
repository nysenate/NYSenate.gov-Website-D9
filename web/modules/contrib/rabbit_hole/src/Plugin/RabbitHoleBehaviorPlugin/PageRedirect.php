<?php

namespace Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Link;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginBase;
use Drupal\rabbit_hole\Exception\InvalidRedirectResponseException;
use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Redirects to another page.
 *
 * @RabbitHoleBehaviorPlugin(
 *   id = "page_redirect",
 *   label = @Translation("Page redirect")
 * )
 */
class PageRedirect extends RabbitHoleBehaviorPluginBase implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  const RABBIT_HOLE_PAGE_REDIRECT_DEFAULT = '';
  const RABBIT_HOLE_PAGE_REDIRECT_RESPONSE_DEFAULT = 301;

  const REDIRECT_MOVED_PERMANENTLY = 301;
  const REDIRECT_FOUND = 302;
  const REDIRECT_SEE_OTHER = 303;
  const REDIRECT_NOT_MODIFIED = 304;
  const REDIRECT_USE_PROXY = 305;
  const REDIRECT_TEMPORARY_REDIRECT = 307;

  /**
   * The redirect path.
   *
   * @var string
   */
  private $path;

  /**
   * The HTTP response code.
   *
   * @var string
   */
  private $code;

  /**
   * The entity plugin manager.
   *
   * @var \Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager
   */
  protected $rhEntityPluginManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Cache metadata for the redirect response.
   *
   * @var \Drupal\Core\Render\BubbleableMetadata
   */
  protected $cacheMetadata;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RabbitHoleEntityPluginManager $rhepm,
    ModuleHandlerInterface $mhi,
    Token $token) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->rhEntityPluginManager = $rhepm;
    $this->moduleHandler = $mhi;
    $this->token = $token;
    $this->cacheMetadata = new BubbleableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.rabbit_hole_entity_plugin'),
      $container->get('module_handler'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function performAction(EntityInterface $entity, Response $current_response = NULL) {
    $target = $this->getActionTarget($entity);
    $response_code = $this->getActionResponseCode($entity);

    // The fallback action is executed if redirect target is either empty or
    // has invalid value.
    if (empty($target)) {
      return $this->getFallbackAction($entity);
    }

    switch ($response_code) {
      case self::REDIRECT_MOVED_PERMANENTLY:
      case self::REDIRECT_FOUND:
      case self::REDIRECT_SEE_OTHER:
      case self::REDIRECT_TEMPORARY_REDIRECT:
        if ($current_response === NULL) {
          $redirect_response = new TrustedRedirectResponse($target, $response_code);
          $redirect_response->addCacheableDependency($this->cacheMetadata);
          return $redirect_response;
        }
        else {
          // If a response already exists we don't need to do anything with it.
          return $current_response;
        }
        // TODO: I don't think this is the correct way to handle a 304 response.
      case self::REDIRECT_NOT_MODIFIED:
        if ($current_response === NULL) {
          $not_modified_response = new Response();
          $not_modified_response->setStatusCode(self::REDIRECT_NOT_MODIFIED);
          $not_modified_response->headers->set('Location', $target);
          return $not_modified_response;
        }
        else {
          // If a response already exists we don't need to do anything with it.
          return $current_response;
        }
        // TODO: I have no idea if this is actually the correct way to handle a
        // 305 response in Symfony/D8. Documentation on it seems a bit sparse.
      case self::REDIRECT_USE_PROXY:
        if ($current_response === NULL) {
          $use_proxy_response = new Response();
          $use_proxy_response->setStatusCode(self::REDIRECT_USE_PROXY);
          $use_proxy_response->headers->set('Location', $target);
          return $use_proxy_response;
        }
        else {
          // If a response already exists we don't need to do anything with it.
          return $current_response;
        }
      default:
        throw new InvalidRedirectResponseException();
    }
  }

  /**
   * Returns the action target URL object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the action is being performed on.
   *
   * @return string|null
   *   Absolute destination URL or NULL if proper URL wasn't found.
   */
  public function getActionTarget(EntityInterface $entity) {
    $target = $entity->get('rh_redirect')->value;
    if (empty($target)) {
      $bundle_settings = $this->getBundleSettings($entity);
      $target = $bundle_settings->get('redirect');
      $this->cacheMetadata->addCacheableDependency($bundle_settings);
    }

    // Replace any tokens if applicable.
    $langcode = $entity->language()->getId();

    if ($langcode == LanguageInterface::LANGCODE_NOT_APPLICABLE) {
      $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    }

    // Convert <front> into valid URI.
    $target = $target === '<front>' ? 'base:/' : $target;
    $target = $this->token->replace($target, [
      $entity->getEntityTypeId() => $entity,
    ], [
      'clear' => TRUE,
      'langcode' => $langcode,
    ], $this->cacheMetadata);
    $target = PlainTextOutput::renderFromHtml($target);

    if (empty($target)) {
      return NULL;
    }

    // If non-absolute URI, pass URL through Drupal's URL generator to
    // handle languages etc.
    if (!UrlHelper::isExternal($target)) {
      $scheme = parse_url($target, PHP_URL_SCHEME);
      if ($scheme === NULL) {
        $target = 'internal:' . $target;
      }

      try {
        $target = Url::fromUri($target)->toString();
      }
      catch (\InvalidArgumentException $exception) {
        return NULL;
      }
    }

    return $target;
  }

  /**
   * Returns the action response code.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the action is being performed on.
   *
   * @return int
   *   Redirect code.
   */
  public function getActionResponseCode(EntityInterface $entity) {
    $target = $entity->get('rh_redirect')->value;
    if (empty($target)) {
      $bundle_settings = $this->getBundleSettings($entity);
      $response_code = $bundle_settings->get('redirect_code');
      $this->cacheMetadata->addCacheableDependency($bundle_settings);
    }
    else {
      $response_code = $entity->get('rh_redirect_response')->value;
    }
    return $response_code;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(
    array &$form,
    FormStateInterface $form_state,
    $form_id,
    EntityInterface $entity = NULL,
    $entity_is_bundle = FALSE,
    ImmutableConfig $bundle_settings = NULL
  ) {

    $redirect = NULL;
    $redirect_code = NULL;
    $redirect_fallback_action = NULL;

    if ($entity_is_bundle) {
      $redirect = $bundle_settings->get('redirect');
      $redirect_code = $bundle_settings->get('redirect_code');
      $redirect_fallback_action = $bundle_settings->get('redirect_fallback_action');
    }
    elseif (isset($entity)) {
      $redirect = isset($entity->rh_redirect->value)
        ? $entity->rh_redirect->value
        : self::RABBIT_HOLE_PAGE_REDIRECT_DEFAULT;
      $redirect_code = isset($entity->rh_redirect_response->value)
        ? $entity->rh_redirect_response->value
        : self::RABBIT_HOLE_PAGE_REDIRECT_RESPONSE_DEFAULT;
      $redirect_fallback_action = isset($entity->rh_redirect_fallback_action->value)
        ? $entity->rh_redirect_fallback_action->value
        : 'bundle_default';
    }
    else {
      $redirect = NULL;
      $redirect_code = NULL;
    }

    $form['rabbit_hole']['redirect'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Redirect settings'),
      '#attributes' => ['class' => ['rabbit-hole-redirect-options']],
      '#states' => [
        'visible' => [
          ':input[name="rh_action"]' => ['value' => $this->getPluginId()],
        ],
      ],
    ];

    // Get the default value for the redirect path.
    // Build the descriptive text.
    $description = [];
    $description[] = $this->t('Enter the %front tag, relative path or the full URL that the user should get redirected to. Query strings and fragments are supported, such as %example.', [
      '%front' => '<front>',
      '%example' => 'http://www.example.com/?query=value#fragment',
    ]);
    $description[] = $this->t('You may enter tokens in this field, such as %example1 or %example2.', [
      '%example1' => '[node:field_link]',
      '%example2' => '/my/view?page=[node:field_page_number]',
    ]);

    $form['rabbit_hole']['redirect']['rh_redirect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect path'),
      '#default_value' => $redirect,
      '#description' => '<p>' . implode('</p><p>', $description) . '</p>',
      '#attributes' => ['class' => ['rabbit-hole-redirect-setting']],
      '#element_validate' => [],
      '#after_build' => [],
      '#states' => [
        'required' => [
          ':input[name="rh_action"]' => ['value' => $this->getPluginId()],
        ],
      ],
      '#maxlength' => 2000,
    ];

    $entity_type_id = NULL;
    if (isset($entity)) {
      $entity_type_id = $entity_is_bundle
        ? $entity->getEntityType()->getBundleOf()
        : $entity->getEntityTypeId();
    }
    else {
      $entity_type_id = $this->rhEntityPluginManager
        ->loadSupportedGlobalForms()[$form_id];
    }

    $entity_type_for_tokens = NULL;
    if ($this->moduleHandler->moduleExists('token')) {
      $token_map = $this->rhEntityPluginManager->loadEntityTokenMap();
      $entity_type_for_tokens = $token_map[$entity_type_id];

      $form['rabbit_hole']['redirect']['rh_redirect']['#element_validate'][]
        = 'token_element_validate';
      $form['rabbit_hole']['redirect']['rh_redirect']['#after_build'][]
        = 'token_element_validate';
      $form['rabbit_hole']['redirect']['rh_redirect']['#token_types']
        = [$entity_type_for_tokens];
    }

    // Add the redirect response setting.
    $form['rabbit_hole']['redirect']['rh_redirect_response'] = [
      '#type' => 'select',
      '#title' => $this->t('Response code'),
      '#options' => [
        301 => $this->t('301 (Moved Permanently)'),
        302 => $this->t('302 (Found)'),
        303 => $this->t('303 (See other)'),
        304 => $this->t('304 (Not modified)'),
        305 => $this->t('305 (Use proxy)'),
        307 => $this->t('307 (Temporary redirect)'),
      ],
      '#default_value' => $redirect_code,
      '#description' => $this->t('The response code that should be sent to the users browser. Follow @link for more information on response codes.', [
        '@link' => Link::fromTextAndUrl($this->t('this link'), Url::fromUri('http://api.drupal.org/api/drupal/includes--common.inc/function/drupal_goto/7'))->toString(),
      ]),
      '#attributes' => ['class' => ['rabbit-hole-redirect-response-setting']],
    ];

    // Add fallback action setting with all available options except page
    // redirect.
    $fallback_options = $form['rh_action']['#options'];
    unset($fallback_options['page_redirect']);

    if (isset($fallback_options['bundle_default'])) {
      $args = $fallback_options['bundle_default']->getArguments();
      $bundle_settings = $this->getBundleSettings($entity);
      $bundle_fallback = $bundle_settings->get('redirect_fallback_action');
      $fallback_options['bundle_default'] = $this->t('Global @bundle fallback (@setting)', [
        '@bundle' => $args['@bundle'],
        '@setting' => $bundle_fallback,
      ]);
    }

    $form['rabbit_hole']['redirect']['rh_redirect_fallback_action'] = [
      '#type' => 'radios',
      '#title' => $this->t('Fallback behavior'),
      '#options' => $fallback_options,
      '#default_value' => $redirect_fallback_action,
      '#description' => $this->t('What should happen when the redirect is invalid/empty?'),
      '#attributes' => ['class' => ['rabbit-hole-redirect-fallback-action-setting']],
    ];

    // Display a list of tokens if the Token module is enabled.
    if ($this->moduleHandler->moduleExists('token')) {
      $form['rabbit_hole']['redirect']['token_help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [$entity_type_for_tokens],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterExtraFields(array &$fields) {
    $fields['rh_redirect'] = BaseFieldDefinition::create('string')
      ->setName('rh_redirect')
      ->setLabel($this->t('Rabbit Hole redirect path.'))
      ->setDescription($this->t('The path to where the user should get redirected to.'));
    $fields['rh_redirect_response'] = BaseFieldDefinition::create('integer')
      ->setName('rh_redirect_response')
      ->setLabel($this->t('Rabbit Hole redirect response code'))
      ->setDescription($this->t('Specifies the HTTP response code that should be used when perform a redirect.'));
    $fields['rh_redirect_fallback_action'] = BaseFieldDefinition::create('string')
      ->setName('rh_redirect_fallback_action')
      ->setLabel($this->t('Rabbit Hole redirect fallback action'))
      ->setDescription($this->t('Specifies the action that should be used when the redirect path is invalid or empty.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getFallbackAction(EntityInterface $entity) {
    $fallback_action = $entity->get('rh_redirect_fallback_action')->value;
    if (empty($fallback_action) || $fallback_action === 'bundle_default') {
      $bundle_settings = $this->getBundleSettings($entity);
      $fallback_action = $bundle_settings->get('redirect_fallback_action');
      $this->cacheMetadata->addCacheableDependency($bundle_settings);
    }
    return !empty($fallback_action) ? $fallback_action : parent::getFallbackAction($entity);
  }

}
