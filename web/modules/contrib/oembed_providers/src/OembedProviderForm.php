<?php

namespace Drupal\oembed_providers;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the oEmbed provider edit/add forms.
 */
class OembedProviderForm extends EntityForm {

  /**
   * The entityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs an oEmbedProviderForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MessengerInterface $messenger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The array containing the complete form.
   */
  public function form(array $form, FormStateInterface $form_state) {
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit oEmbed provider</em> @label', ['@label' => $this->entity->label()]);
    }

    $entity = $this->entity;

    $form['doc_markup'] = [
      '#markup' => $this->t('For documentation regarding oEmbed provider definitions, see <a href="@url">oEmbed.com</a>', ['@url' => 'https://oembed.com/']),
      // Simulate warning message.
      '#prefix' => '<div role="contentinfo" aria-label="Warning message" class="messages messages--warning">',
      '#suffix' => '</div>',
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Provider name'),
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['provider_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Provider URL'),
      '#default_value' => $entity->get('provider_url'),
      '#required' => TRUE,
    ];

    $form['endpoints'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Endpoints'),
      '#prefix' => '<div id="endpoints-wrapper">',
      '#suffix' => '</div>',
    ];

    // Set add/remove variables if form is being reloaded by an AJAX action.
    $endpoint_add = $form_state->get('endpoint_add');
    $endpoint_remove = $form_state->get('endpoint_remove');

    // Get endpoints.
    $endpoints = ($entity->get('endpoints')) ? $entity->get('endpoints') : [];

    // An array to stores index keys for endpoints.
    $endpoint_indices = [];
    // Loop through endpoints.
    foreach ($endpoints as $endpoint_id => $endpoint_value) {
      // Remove 'actions' array; it's not an endpoint.
      if ($endpoint_id === 'actions') {
        unset($endpoints['actions']);
      }
      else {
        // If original form load (e.g. not AJAX), then array keys are indexed
        // and can be used outright.
        if (is_numeric($endpoint_id)) {
          // Start endpoint count at 1, not 0.
          $endpoint_indices[] = $endpoint_id + 1;
        }
        // If AJAX form load, then array keys are "endpoints-[key]".
        else {
          // Add existing key to indexed array.
          $parts = explode('-', $endpoint_id);
          $endpoint_indices[] = $parts[1];
        }
      }
    }

    $endpoint_count = count($endpoints);

    // If there are no endpoints yet, then set default values.
    if ($endpoint_count == 0) {
      $endpoint_count = 1;
      $endpoint_indices[] = 1;
    }

    // If an endpoint is being added, increment the endpoint count.
    if ($endpoint_add) {
      $endpoint_count++;
    }

    for ($i = 0; $i < $endpoint_count; $i++) {
      // Use existing endpoint index keys when generating form array.
      // If an index doesn't exist, then add 1 to the last index key value.
      $num = (isset($endpoint_indices[$i])) ? $endpoint_indices[$i] : $endpoint_indices[$i - 1] + 1;

      $form['endpoints']['endpoint-' . $num] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Endpoint #@num', ['@num' => $num]),
        '#prefix' => '<div id="endpoint-' . $num . '">',
        '#suffix' => '</div>',
      ];

      $form['endpoints']['endpoint-' . $num]['schemes'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Endpoint schemes'),
        '#description' => $this->t('URLs from a provider that may have an embedded representation (each scheme is entered on a new line)<br>E.g. http://custom-provider.example.com/id/*<br>https://custom-provider.example.com/id/*'),
        // Convert indexed array to a string of values, delimited by a new line.
        '#default_value' => (isset($endpoints[$i]['schemes'])) ? implode(PHP_EOL, $endpoints[$i]['schemes']) : '',
        '#required' => TRUE,
      ];

      $form['endpoints']['endpoint-' . $num]['url'] = [
        // Use 'textfield' instead of 'url' to avoid validation issues with the
        // `{format}` string, which is supported by core Media.
        // See Drupal\media\OEmbed\Endpoint::__construct().
        '#type' => 'textfield',
        '#title' => $this->t('Endpoint URL'),
        '#description' => $this->t('A URL where the consumer can request representations for scheme URLs<br>E.g. https://custom-provider.example.com/api/v2/oembed/'),
        '#default_value' => (isset($endpoints[$i]['url'])) ? $endpoints[$i]['url'] : '',
        '#required' => TRUE,
      ];

      $form['endpoints']['endpoint-' . $num]['discovery'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Discovery'),
        '#description' => $this->t('Whether or not a provider supports discoverability of supported formats'),
        '#default_value' => (isset($endpoints[$i]['discovery'])) ? (int) $endpoints[$i]['discovery'] : 0,
      ];

      $form['endpoints']['endpoint-' . $num]['formats'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Available formats'),
        '#description' => $this->t('Formats explicitly supported by the provider'),
        '#options' => [
          'json' => $this->t('JSON'),
          'xml' => $this->t('XML'),
        ],
      ];
      // Convert checkboxes values from boolean to expected FAPI values
      // for default value.
      if (isset($endpoints[$i]['formats'])) {
        $formats_default_value = [];
        foreach ($endpoints[$i]['formats'] as $format => $format_value) {
          if ($format_value == TRUE) {
            $formats_default_value[$format] = $format;
          }
          elseif ($format_value == FALSE) {
            $formats_default_value[$format] = 0;
          }
        }
        $form['endpoints']['endpoint-' . $num]['formats']['#default_value'] = $formats_default_value;
      }

      // Only provide remove button if either 1) there is more than one
      // endpoint and no endpoints are being removed or 2) there are more than
      // two endpoints and an endpoint is being removed. The last endpoint
      // cannot be removed.
      if (($endpoint_count > 1 && !$endpoint_remove)
        || $endpoint_count > 2 && $endpoint_remove
        ) {
        $form['endpoints']['endpoint-' . $num]['actions']['remove_endpoint'] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove endpoint'),
          '#submit' => ['::removeCallback'],
          '#ajax' => [
            'callback' => '::ajaxCallback',
            'wrapper' => 'endpoints-wrapper',
          ],
          '#name' => 'remove-endpoint' . $num,
        ];
      }
    }

    // Only after entire form is build should any removed endpoint be removed.
    if ($endpoint_remove) {
      unset($form['endpoints'][$endpoint_remove]);
    }

    $form['endpoints']['actions'] = [
      '#type' => 'actions',
    ];

    $form['endpoints']['actions']['add_endpoint'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add an endpoint'),
      '#submit' => ['::addCallback'],
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'endpoints-wrapper',
      ],
      '#button_type' => 'default',
      '#name' => 'add-endpoint',
    ];

    $form['#tree'] = TRUE;

    // Reset AJAX variables.
    $form_state->set('endpoint_add', NULL);
    $form_state->set('endpoint_remove', NULL);

    return parent::form($form, $form_state);
  }

  /**
   * AJAX callback function.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An array of endpoints form elements.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['endpoints'];
  }

  /**
   * AJAX helper function for adding endpoints.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addCallback(array &$form, FormStateInterface $form_state) {
    $form_state->set('endpoint_add', TRUE);
    $form_state->setRebuild();
  }

  /**
   * AJAX helper function for removing endpoints.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    // Get triggering element, so the correct endpoint is removed.
    $trigger = $form_state->getTriggeringElement();
    $form_state->set('endpoint_remove', $trigger['#parents'][1]);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // If validation is triggered by an AJAX action, then skip validation.
    $trigger = $form_state->getTriggeringElement();
    if (in_array('remove_endpoint', $trigger['#parents'])
      || in_array('add_endpoint', $trigger['#parents'])
      ) {
      $form_state->clearErrors();
      return;
    }
    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();
    unset($values['endpoints']['actions']);

    // Loop through endpoints.
    foreach ($values['endpoints'] as $endpoint_id => $endpoint_value) {
      // Convert schemes from string delimited by returns to indexed array.
      $schemes = preg_split('/\R/', $endpoint_value['schemes']);
      foreach ($schemes as $key => $scheme) {
        // Verify schemes are valid URLs.
        if (!$this->urlIsValid($scheme)) {
          $form_state->setError(
            $form['endpoints'][$endpoint_id]['schemes'],
            $this->t('A valid URL is required on line @line.', ['@line' => $key + 1])
          );
        }

        // Validate scheme URL.
        // Mimic Drupal\media\OEmbed\Endpoint::__construct() in how the
        // `{format}` string is handled.
        $endpoint_url = str_replace('{format}', 'format', $endpoint_value['url']);
        if (!UrlHelper::isValid($endpoint_url)) {
          $form_state->setError(
            $form['endpoints'][$endpoint_id]['url'],
            $this->t('The URL @url is not valid.', ['@url' => $endpoint_url])
          );
        }
      }

      // Either discovery or formats is required.
      if (empty($endpoint_value['discovery'])
        && empty($endpoint_value['formats']['json'])
        && empty($endpoint_value['formats']['xml'])
      ) {
        $form_state->setError(
          $form['endpoints'][$endpoint_id]['formats'],
          $this->t('If discovery is disabled, then one or more formats must be explicitly defined for an endpoint.')
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $endpoints = $entity->get('endpoints');
    unset($endpoints['actions']);

    // Prepare endpoints to be stored as an indexed array.
    $endpoints_indexed_array = [];
    foreach ($endpoints as $value) {
      unset($value['actions']);
      // Convert schemes from string delimited by returns to indexed array.
      $value['schemes'] = preg_split('/\R/', $value['schemes']);
      $value['discovery'] = (bool) $value['discovery'];
      $value['formats']['json'] = (bool) $value['formats']['json'];
      $value['formats']['xml'] = (bool) $value['formats']['xml'];

      $endpoints_indexed_array[] = $value;
    }

    $entity->set('endpoints', $endpoints_indexed_array);
    $status = $entity->save();

    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('The %label oEmbed provider was created.', [
        '%label' => $entity->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label oEmbed provider was updated.', [
        '%label' => $entity->label(),
      ]));
    }

    $form_state->setRedirect('entity.oembed_provider.collection');
  }

  /**
   * Helper function to check whether an oEmbed provider config entity exists.
   *
   * @param string $id
   *   A machine name.
   *
   * @return bool
   *   Whether or not the machine name already exists.
   */
  public function exist($id) {
    // The 'add' namespace is reserved.
    if ($id == 'add') {
      return TRUE;
    }
    $entity = $this->entityTypeManager->getStorage('oembed_provider')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * Verifies the syntax of the given absolute URL.
   *
   * This method is adapted from Drupal\Component\Utility\UrlHelper::isValid().
   *
   * @param string $url
   *   The URL to verify.
   *
   * @return bool
   *   Whether or not the URL is valid.
   *
   * @see \Drupal\Component\Utility\UrlHelper::isValid()
   */
  public function urlIsValid($url) {
    // If parse_url() can't parse it, then reject.
    if (!$parsed_url = parse_url($url)) {
      return FALSE;
    }
    // Only HTTP and HTTPS are valid schemes.
    if (!in_array($parsed_url['scheme'], ['http', 'https'])) {
      return FALSE;
    }
    // User, password, and fragments are not valid.
    if (isset($parsed_url['user']) || isset($parsed_url['pass']) || isset($parsed_url['fragment'])) {
      return FALSE;
    }
    // Valid the host, which may contain an optional wildcard subdomain.
    // *.example.com is valid.
    // *.*.example.com is invalid.
    // *.com is invalid.
    $host = (bool) preg_match("
      /^
      (?:
        (?:([*\.]?)([a-z0-9\-\.]{2,})(\.)([a-z0-9\-\.]+)|%[0-9a-f]{2})+
        |(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])
      )
    $/xi", $parsed_url['host']);
    if (!$host) {
      return FALSE;
    }

    return TRUE;
  }

}
