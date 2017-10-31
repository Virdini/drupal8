<?php

namespace Drupal\vbase\Plugin\ImageToolkit\Operation\imagick;

use Drupal\imagick\Plugin\ImageToolkit\Operation\imagick\ImagickOperationBase;
use Drupal\imagick\Plugin\ImageToolkit\Operation\imagick\ImagickOperationTrait;
use Imagick;

/**
 * Defines vbase Virdini Optimize operation.
 *
 * @ImageToolkitOperation(
 *   id = "vbase_voptimize",
 *   toolkit = "imagick",
 *   operation = "voptimize",
 *   label = @Translation("Virdini Optimize"),
 *   description = @Translation("Optimize the image")
 * )
 */
class vOptimize extends ImagickOperationBase {

  use ImagickOperationTrait;

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function process(Imagick $resource, array $arguments) {
    $resource->stripImage();
    if ($resource->getImageMimeType() == 'image/jpeg') {
      $resource->setImageProperty('jpeg:sampling-factor', '4:2:0'); //$resource->setSamplingFactors(['2x2', '1x1', '1x1']);
      $resource->setInterlaceScheme(Imagick::INTERLACE_JPEG);
    }
    return TRUE;
  }

}
