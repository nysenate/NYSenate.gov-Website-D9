<?php

namespace Drupal\oembed_providers;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\oembed_providers\OEmbed\ProviderRepositoryDecorator;
use Drupal\oembed_providers\Traits\HelperTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the oEmbed provider bucket edit/add forms.
 */
class OembedProviderBucketForm extends EntityForm {

  use HelperTrait;

  /**
   * The decorated oEmbed ProviderRepository.
   *
   * @var \Drupal\oembed_providers\OEmbed\ProviderRepositoryDecorator
   */
  protected $providerRepository;

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
   * Constructs an oEmbedProviderBucketForm object.
   *
   * @param \Drupal\oembed_providers\OEmbed\ProviderRepositoryDecorator $provider_repository
   *   The decorated oEmbed ProviderRepository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ProviderRepositoryDecorator $provider_repository, EntityTypeManagerInterface $entityTypeManager, MessengerInterface $messenger) {
    $this->providerRepository = $provider_repository;
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media.oembed.provider_repository'),
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
      $form['#title'] = $this->t('<em>Edit oEmbed provider bucket</em> @label', ['@label' => $this->entity->label()]);
    }

    $entity = $this->entity;

    $form['security_warning'] = [
      '#markup' => $this->disabledProviderSecurityWarning(),
      // Simulate warning message.
      '#prefix' => '<div role="contentinfo" aria-label="Warning message" class="messages messages--warning">',
      '#suffix' => '</div>',
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bucket name'),
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

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $entity->get('description'),
    ];

    $providers = $this->providerRepository->getAll();
    $provider_keys = [];
    foreach ($providers as $provider) {
      $provider_keys[$provider->getName()] = $provider->getName();
    }

    $form['markup'] = [
      '#markup' => $this->t('<p>Providers enabled below will be made available to this media source.</p>'),
    ];

    $form['providers'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed Providers'),
      '#default_value' => ($entity->get('providers')) ? $entity->get('providers') : [],
      '#options' => $provider_keys,
    ];

    $form['#attached']['library'][] = 'oembed_providers/provider_bucket_form';

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $providers = Checkboxes::getCheckedCheckboxes($entity->get('providers'));
    $entity->set('providers', $providers);

    $status = $entity->save();

    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('The %label oEmbed provider bucket was created.', [
        '%label' => $entity->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label oEmbed provider bucket was updated.', [
        '%label' => $entity->label(),
      ]));
    }

    $form_state->setRedirect('entity.oembed_provider_bucket.collection');
  }

  /**
   * Helper function to check if oEmbed provider bucket config entity exists.
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
    $entity = $this->entityTypeManager->getStorage('oembed_provider_bucket')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
