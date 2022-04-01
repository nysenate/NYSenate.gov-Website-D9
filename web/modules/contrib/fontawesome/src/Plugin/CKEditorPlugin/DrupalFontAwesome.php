<?php

namespace Drupal\fontawesome\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "drupalfontawesome" plugin.
 *
 * @CKEditorPlugin(
 *   id = "drupalfontawesome",
 *   label = @Translation("Drupal Font Awesome"),
 *   module = "fontawesome"
 * )
 */
class DrupalFontAwesome extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'fontawesome') . '/js/plugins/drupalfontawesome/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'core/drupal.ajax',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'drupalFontAwesome_dialogTitleAdd' => $this->t('Insert Font Awesome Icon'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'DrupalFontAwesome' => [
        'label' => $this->t('Font Awesome'),
        'image' => drupal_get_path('module', 'fontawesome') . '/js/plugins/drupalfontawesome/icons/drupalfontawesome.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    // Assume that someone installing this module probably wants the help.
    return TRUE;
  }

}
