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
use Drupal\file\FileInterface;
use Drupal\Core\Url;
use Drupal\file\FileRepository;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Defines a confirmation form to confirm setting school submissions to public.
 */
class SchoolFormShowStudentForm extends ConfirmFormBase {

  /**
   * The array of files to show public.
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
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * File repository service.
   *
   * @var \Drupal\file\FileRepository
   */
  protected $fileRepository;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Entity Type Mananger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a SchoolFormDeleteForm form object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\File\FileSystem $fileSystem
   *   File system service.
   * @param \Drupal\file\FileRepository $fileRepository
   *   File repository service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The String translation.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory,
  EntityTypeManagerInterface $entity_type_manager,
  AccountInterface $account,
  FileSystem $fileSystem,
  FileRepository $fileRepository,
  TranslationInterface $string_translation,
  EntityTypeManager $entityTypeManager) {
    $this->privateTempStoreFactory = $temp_store_factory;
    $this->fileStorage = $entity_type_manager->getStorage('file');
    $this->currentUser = $account;
    $this->fileSystem = $fileSystem;
    $this->fileRepository = $fileRepository;
    $this->setStringTranslation($string_translation);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('file_system'),
      $container->get('file.repository'),
      $container->get('string_translation'),
      $container->get('entity_type.manager'),
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

    return \Drupal::request()->headers->get('referer');
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
      return new RedirectResponse($this->getCancelUrl());
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
      $count = count($this->files);
      $webform_id = '';
      foreach ($this->files as $file) {
        if ($file instanceof FileInterface) {
          $query = \Drupal::database()->select('webform_submission_data')
            ->fields('webform_submission_data', ['sid'])
            ->fields('webform_submission_data', ['webform_id'])
            ->condition('value', $file->get('fid')->value)
            ->distinct();

          $sids = $query->execute()->fetchAll();
          $sid = $sids[0]->sid;
          $webform_id = $sids[0]->webform_id;
          if (!empty($sid)) {
            $submission = $this->entityTypeManager->getStorage('webform_submission')->load($sid);
            $entity_id = $submission->getSourceEntity();
            $webform = $submission->getWebform();
            $submission_timestamp = $submission->getCreatedTime();
            $school_form_type = '';
            if ($entity_id) {
              $nid = $entity_id->id();
              $node = $this->entityTypeManager->getStorage('node')->load($nid);
              $school_form_type = $node->get('field_school_form_type')->entity->label();
              $directory = 'public://' . $school_form_type . '/' . $node->id() . '/' . date('Y', $submission_timestamp) . '/';
            }
            else {
              $directory = 'public://' . 'webform' . '/' . $webform->id() . '/' . $sid . '/';
            }
            $file_uri = $file->getFileUri();
            $file_exists_error = $this->fileSystem->getDestinationFilename($file_uri, FileSystemInterface::EXISTS_ERROR);
            if (!$file_exists_error) {
              $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
              $destination = $directory . $file->getFilename();
              $this->fileRepository->move($file, $destination);
            }
            else {
              $this->logger('School Forms')->notice('The file you selected to show does not exist.');
              $this->messenger()->addMessage($this->stringTranslation->formatPlural($count,
                '1 file does not exist in the file system. Cannot show student submission file.',
                'A file you selected do not exits in the file system. Cannot show student submission file.'));
              if ($webform_id == 'school_form_thanksgiving') {
                $url = Url::fromRoute('nys_school_forms.school_forms_thanksgiving', [], []);
                $form_state->setRedirectUrl($url);
              }
              elseif ($webform_id == 'school_form_earth_day') {
                $url = Url::fromRoute('nys_school_forms.school_forms_earth_day', [], []);
                $form_state->setRedirectUrl($url);
              }
            }
          }
        }
        $this->privateTempStoreFactory->get('school_form_multiple_show_student_confirm')->delete($this->currentUser->id());
        $this->logger('School Forms')->notice('@count student submissions have been moved to public access.', ['@count' => $count]);
        $this->messenger()->addMessage($this->stringTranslation->formatPlural($count, 'Show 1 submissions.', 'Show @count student submissions.'));
        if ($webform_id == 'school_form_thanksgiving') {
          $url = Url::fromRoute('nys_school_forms.school_forms_thanksgiving', [], []);
          $form_state->setRedirectUrl($url);
        }
        elseif ($webform_id == 'school_form_earth_day') {
          $url = Url::fromRoute('nys_school_forms.school_forms_earth_day', [], []);
          $form_state->setRedirectUrl($url);
        }
      }
    }
  }

}
