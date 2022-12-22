<?php

namespace Drupal\nys_school_forms\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form to confirm deletion of school submissions.
 */
class SchoolFormDeleteForm extends ConfirmFormBase {

  /**
   * The array of files to delete.
   *
   * @var string[][]
   */
  protected $files = [];

  /**
   * The private tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * The file storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a SchoolFormDeleteForm form object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The String translation.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, AccountInterface $account, TranslationInterface $string_translation) {
    $this->privateTempStoreFactory = $temp_store_factory;
    $this->fileStorage = $entity_type_manager->getStorage('file');
    $this->currentUser = $account;
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "school_form_entity_delete_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->files), 'Are you sure you want to delete this submission?', 'Are you sure you want to delete these submissions?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('nys_school_forms.school_forms');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->files = $this->privateTempStoreFactory->get('school_form_multiple_delete_confirm')->get($this->currentUser->id());
    if (empty($this->files)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    $form['files'] = [
      '#theme' => 'item_list',
      '#items' => array_map(function ($file) {
        return $file->getFilename();
      }, $this->files),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('confirm') && !empty($this->files)) {
      $this->fileStorage->delete($this->files);
      $this->privateTempStoreFactory->get('school_form_multiple_delete_confirm')->delete($this->currentUser->id());
      $count = count($this->files);
      $this->logger('School Forms')->notice('Deleted @count submissions.', ['@count' => $count]);
      $this->messenger()->addMessage($this->stringTranslation->formatPlural($count, 'Deleted 1 submissions.', 'Deleted @count submissions.'));
    }
    $form_state->setRedirect('nys_school_forms.school_forms');
  }

}
