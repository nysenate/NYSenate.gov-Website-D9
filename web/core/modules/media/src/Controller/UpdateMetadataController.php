<?php

namespace Drupal\media\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller that triggers metadata updates on a given media item.
 *
 * @internal
 *   Controllers are internal.
 */
class UpdateMetadataController extends ControllerBase {

  /**
   * Updates metadata on a media entity and reloads the page.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media item for which to update metadata.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to return the user to the page they were on.
   */
  public function updateMetadata(MediaInterface $media) {
    $media->enforceMetadataUpdate();
    $this->entityTypeManager()->getStorage('media')->save($media);
    $this->messenger()->addStatus($this->t('Updated metadata on media item %label', [
      '%label' => $media->label(),
    ]));
    return new RedirectResponse($this->reloadPage());
  }

  /**
   * Reloads the previous page or return to the media overview.
   */
  protected function reloadPage() {
    if ($destination = $this->getRedirectDestination()->get()) {
      return $destination;
    }
    return Url::fromRoute('entity.media.collection')->toString();
  }

}
