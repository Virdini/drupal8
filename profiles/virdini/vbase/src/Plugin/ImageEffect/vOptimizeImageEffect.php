<?php

namespace Drupal\vbase\Plugin\ImageEffect;

use Drupal\Core\Image\ImageInterface;
use Drupal\image\ImageEffectBase;

/**
 * Autorotates an image resource.
 *
 * @ImageEffect(
 *   id = "image_voptimize",
 *   label = @Translation("Virdini Optimize"),
 *   description = @Translation("Optimize the image if Imagick toolkit is enabled for image management.")
 * )
 */
class vOptimizeImageEffect extends ImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if ($image->getToolkitId() == 'imagick') {
      $image->apply('voptimize');
    }
    return TRUE;
  }

}
