<?php

namespace Drupal\security_review\Checks;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Checks for unsafe extensions in the allowed extensions settings of fields.
 */
class UploadExtensions extends Check {

  /**
   * {@inheritdoc}
   */
  public function getNamespace() {
    return 'Security Review';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Allowed upload extensions';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'upload_extensions';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // If field is not enabled return with INFO.
    if (!$this->moduleHandler()->moduleExists('field')) {
      return $this->createResult(CheckResult::INFO);
    }

    $result = CheckResult::SUCCESS;
    $findings = [];

    // Check field configuration entities.
    foreach (FieldConfig::loadMultiple() as $entity) {
      /** @var FieldConfig $entity */
      $extensions = $entity->getSetting('file_extensions');
      if ($extensions != NULL) {
        $extensions = explode(' ', $extensions);
        $intersect = array_intersect($extensions, $this->security()->unsafeExtensions());
        // $intersect holds the unsafe extensions this entity allows.
        foreach ($intersect as $unsafe_extension) {
          $findings[$entity->id()][] = $unsafe_extension;
        }
      }
    }

    if (!empty($findings)) {
      $result = CheckResult::FAIL;
    }

    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t(
      'File and image fields allow for uploaded files. Some extensions are considered dangerous because the files can be evaluated and then executed in the browser. A malicious user could use this opening to gain control of your site. Review <a href=":url">all fields on your site</a>.',
      [':url' => Url::fromRoute('entity.field_storage_config.collection')->toString()]
    );

    return [
      '#theme' => 'check_help',
      '#title' => 'Allowed upload extensions',
      '#paragraphs' => $paragraphs,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    $findings = $result->findings();
    if (empty($findings)) {
      return [];
    }

    $paragraphs = [];
    $paragraphs[] = $this->t('The following extensions are considered unsafe and should be removed or limited from use. Or, be sure you are not granting untrusted users the ability to upload files.');

    $items = [];
    foreach ($findings as $entity_id => $unsafe_extensions) {
      $entity = FieldConfig::load($entity_id);
      /** @var FieldConfig $entity */

      foreach ($unsafe_extensions as $extension) {
        $item = $this->t(
          'Review @type in <em>@field</em> field on @bundle',
          [
            '@type' => $extension,
            '@field' => $entity->label(),
            '@bundle' => $entity->getTargetBundle(),
          ]
        );

        // Try to get an edit url.
        try {
          $url_params = ['field_config' => $entity->id()];
          if ($entity->getTargetEntityTypeId() == 'node') {
            $url_params['node_type'] = $entity->getTargetBundle();
          }
          $items[] = Link::createFromRoute(
            $item,
            sprintf('entity.field_config.%s_field_edit_form', $entity->getTargetEntityTypeId()),
            $url_params
          );
        }
        catch (RouteNotFoundException $e) {
          $items[] = $item;
        }
      }
    }

    return [
      '#theme' => 'check_evaluation',
      '#paragraphs' => $paragraphs,
      '#items' => $items,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluatePlain(CheckResult $result) {
    $findings = $result->findings();
    if (empty($findings)) {
      return '';
    }

    $output = '';
    foreach ($findings as $entity_id => $unsafe_extensions) {
      $entity = FieldConfig::load($entity_id);
      /** @var FieldConfig $entity */

      $output .= $this->t(
        '@bundle: field @field',
        [
          '@bundle' => $entity->getTargetBundle(),
          '@field' => $entity->label(),
        ]
      );
      $output .= "\n\t" . implode(', ', $unsafe_extensions) . "\n";
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('Only safe extensions are allowed for uploaded files and images.');

      case CheckResult::FAIL:
        return $this->t('Unsafe file extensions are allowed in uploads.');

      case CheckResult::INFO:
        return $this->t('Module field is not enabled.');

      default:
        return $this->t('Unexpected result.');
    }
  }

}
