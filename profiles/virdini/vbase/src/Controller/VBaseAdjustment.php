<?php
/**
 * @file
 * Contains \Drupal\vbase\Controller\VBaseAdjustment.
 */

namespace Drupal\vbase\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * All elements for design adjustment.
 */
class VBaseAdjustment extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function virdini() {
    drupal_set_message('Test status message.', 'status');
    drupal_set_message('Test 1 status message.', 'status');
    drupal_set_message('Test warning message.', 'warning');
    drupal_set_message('Test 1 warning message.', 'warning');
    drupal_set_message('Test error message.', 'error');
    drupal_set_message('Test 1 error message.', 'error');
    $build = array();
    pager_default_initialize(100, 5);
    $build['pager'] = array(
      '#prefix' => 'Pager:',
      '#type' => 'pager',
    );
    $build['form'] = \Drupal::formBuilder()->getForm('\Drupal\vbase\Form\VBaseAdjustmentForm');
    return $build;
  }

}
