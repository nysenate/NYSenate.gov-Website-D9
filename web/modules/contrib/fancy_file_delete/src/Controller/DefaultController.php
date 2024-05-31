<?php

namespace Drupal\fancy_file_delete\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the fancy_file_delete module.
 */
class DefaultController extends ControllerBase {

  /**
   * Default Page Controller.
   */
  public function info() {
    $html  = '<h2>' . $this->t('Fancy File Delete Options') . '</h2>';
    $html .= '<ol>';
    $html .= '<li>' . $this->t('<b>LIST:</b> View of all managed files with an <em>option to force</em> delete them via VBO custom actions') . '</li>';
    $html .= '<li>' . $this->t('<b>MANUAL:</b> Manually deleting managed files by FID (and an  <em>option to force</em> the delete if you really want to).') . '</li>';
    $html .= '<li>' . $this->t('<b>ORPHANED:</b> Deleting unused files from the whole install that are no longer attached to nodes & the file usage table. AKA deleting all the orphaned files.') . '</li>';
    $html .= '<li>' . $this->t('<b>UNMANAGED:</b> Deleting unused files from the default files directory that are not in the file managed table. AKA deleting all the unmanaged files.') . '</li>';
    $html .= '</ol>';

    return ['#markup' => $html];
  }

}
