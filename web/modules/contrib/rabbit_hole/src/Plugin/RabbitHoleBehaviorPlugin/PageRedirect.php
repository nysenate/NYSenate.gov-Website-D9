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
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginBase;
use Drupal\rabbit_hole\Exception\InvalidRedirectResponseException;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager;
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

  const REDIRECT_MOVED_PERMANENTLY = 301;
  const REDIRECT_FOUND = 302;
  const REDIRECT_SEE_OTHER = 303;
  const REDIRECT_NOT_MODIFIED = 304;
  const REDIRECT_USE_PROXY = 305;
  const REDIRECT_TEMPORARY_REDIRECT = 307;

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
   * Rabbit hole behavior plugins manager.
   *
   * @var \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginBase
   */
  protected $rhBehaviorPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandlerInterface $mhi,
    Token $token,
    RabbitHoleBehaviorPluginManager $rh_behaviour_plugin_manager) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $mhi;
    $this->token = $token;
    $this->cacheMetadata = new BubbleableMetadata();
    $this->rhBehaviorPluginManager = $rh_behaviour_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('token'),
      $container->get('plugin.manager.rabbit_hole_behavior_plugin')
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
    $target = $this->configuration['redirect'];

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
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'redirect' => '',
      'redirect_code' => static::REDIRECT_MOVED_PERMANENTLY,
      'redirect_fallback_action' => 'access_denied',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    parent::buildConfigurationForm($form, $form_state);
    $entity_type_id = $form_state->get('entity_type_id');

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

    $form['redirect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect path'),
      '#default_value' => $this->configuration['redirect'],
      '#description' => '<p>' . implode('</p><p>', $description) . '</p>',
      '#attributes' => ['class' => ['rabbit-hole-redirect-setting']],
      '#maxlength' => 2000,
    ];

    // Display a list of tokens if the Token module is enabled.
    if ($this->moduleHandler->moduleExists('token')) {
      $token_type = \Drupal::service('token.entity_mapper')->getTokenTypeForEntityType($entity_type_id);

      $form['redirect']['#element_validate'][] = 'token_element_validate';
      $form['redirect']['#token_types'] = [$token_type];
      $form['token_help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [$token_type],
      ];
    }

    // Add the redirect response setting.
    $form['redirect_code'] = [
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
      '#default_value' => $this->configuration['redirect_code'],
      '#description' => $this->t('The response code that should be sent to the users browser. Follow @link for more information on response codes.', [
        '@link' => Link::fromTextAndUrl($this->t('this link'), Url::fromUri('http://api.drupal.org/api/drupal/includes--common.inc/function/drupal_goto/7'))->toString(),
      ]),
      '#attributes' => ['class' => ['rabbit-hole-redirect-response-setting']],
    ];

    // Add fallback action setting with all available options except page
    // redirect.
    $fallback_options = $this->rhBehaviorPluginManager->getBehaviors();
    unset($fallback_options[$this->getPluginId()]);
    $form['redirect_fallback_action'] = [
      '#type' => 'radios',
      '#title' => $this->t('Fallback behavior'),
      '#options' => $fallback_options,
      '#default_value' => $this->configuration['redirect_fallback_action'],
      '#description' => $this->t('What should happen when the redirect is invalid/empty?'),
      '#attributes' => ['class' => ['rabbit-hole-redirect-fallback-action-setting']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $redirect = $form_state->getValue('redirect');

    if (empty($redirect)) {
      $form_state->setError($form['redirect'], $this->t('Redirect path is required.'));
    }
    elseif (!UrlHelper::isExternal($redirect) && $redirect !== '<front>') {
      $scheme = parse_url($redirect, PHP_URL_SCHEME);

      // Check if internal URL matches requirements of
      // \Drupal\Core\Url::fromUserInput.
      $accepted_internal_characters = [
        '/',
        '?',
        '#',
        '[',
      ];

      if ($scheme === NULL && !\in_array(substr($redirect, 0, 1), $accepted_internal_characters)) {
        $form_state->setError($form['redirect'], $this->t("Internal path '@string' must begin with a '/', '?', '#', or be a token.", ['@string' => $redirect]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['redirect'] = $form_state->getValue('redirect');
    $this->configuration['redirect_code'] = $form_state->getValue('redirect_code');
    $this->configuration['redirect_fallback_action'] = $form_state->getValue('redirect_fallback_action');
  }

  /**
   * @deprecated in rabbit_hole:2.0.0 and is removed from rabbit_hole:3.0.0.
   *   There is no need for additional fields, as all configuration is stored in
   *   a single serialized field.
   *
   * @see https://www.drupal.org/node/3359194
   */
  public function alterExtraFields(array &$fields) {
    $fields['rh_redirect'] = BaseFieldDefinition::create('string')
      ->setName('rh_redirect')
      ->setLabel($this->t('Rabbit Hole redirect path.'))
      ->setDescription($this->t('The path to where the user should get redirected to.'))
      ->setTranslatable(TRUE);
    $fields['rh_redirect_response'] = BaseFieldDefinition::create('integer')
      ->setName('rh_redirect_response')
      ->setLabel($this->t('Rabbit Hole redirect response code'))
      ->setDescription($this->t('Specifies the HTTP response code that should be used when perform a redirect.'))
      ->setTranslatable(TRUE);
    $fields['rh_redirect_fallback_action'] = BaseFieldDefinition::create('string')
      ->setName('rh_redirect_fallback_action')
      ->setLabel($this->t('Rabbit Hole redirect fallback action'))
      ->setDescription($this->t('Specifies the action that should be used when the redirect path is invalid or empty.'))
      ->setTranslatable(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFallbackAction(EntityInterface $entity) {
    $fallback_action = $this->configuration['redirect_fallback_action'];
    return !empty($fallback_action) ? $fallback_action : parent::getFallbackAction($entity);
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
  protected function getActionResponseCode(EntityInterface $entity) {
    return $this->configuration['redirect_code'];
  }

}
