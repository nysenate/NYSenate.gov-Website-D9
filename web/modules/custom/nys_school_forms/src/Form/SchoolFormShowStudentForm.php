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
 * Defines a confirmation form to confirm setting school submissions to public.
 */
class SchoolFormShowStudentForm extends ConfirmFormBase {

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
    return "school_form_entity_show_student_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->files), 'Are you sure you want show this submission publicly?', 'Are you sure you want set submissions to public?');
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
    return $this->t('Show Student');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->files = $this->privateTempStoreFactory->get('school_form_multiple_show_student_confirm')->get($this->currentUser->id());
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
      foreach ($this->files as $file) {
        $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
        ;

        $query = \Drupal::database()->select('webform_submission_data')
          ->fields('webform_submission_data', ['sid'])
          ->condition('webform_id', 'school_form')
          ->condition('value', $file->get('fid')->value)
          ->distinct();

        $sids = $query->execute()->fetchCol();
        $sid = $sids[0];
        if ($file) {
          $submission = $this->entityTypeManager->getStorage('webform_submission')->load($sid);
          $submission_timestamp = $submission->getCreatedTime();
          $node = $this->entityTypeManager->getStorage('node')->load($nid);
          if ($file instanceof FileInterface) {
            $directory = 'public://' . $node->get('field_school_form_type')->entity->label() . '/' . $node->id() . '/' . date('Y', $submission_timestamp) . '/';
            $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
            $destination = $directory . $file->getFilename();
            $this->fileRepository->move($file, $destination);
          }
        }
      }
      $this->privateTempStoreFactory->get('school_form_multiple_show_student_confirm')->delete($this->currentUser->id());
      $count = count($this->files);
      $this->logger('School Forms')->notice('@count student submissions have been moved to public access.', ['@count' => $count]);
      $this->messenger()->addMessage($this->stringTranslation->formatPlural($count, 'Show 1 submissions.', 'Show @count student submissions.'));
      $url = Url::fromRoute('nys_school_forms.school_forms', [], []);
      $form_state->setRedirectUrl($url);
    }
  }

}
