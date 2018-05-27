<?php

function vbase_preprocess_vbase_youtube(array &$variables) {
  $variables['#attached']['library'][] = 'vbase/youtube';
  if (!$variables['width']) {
    $variables['width'] = 4 * 4 * 40;
  }
  if (!$variables['height']) {
    $variables['height'] = 3 * 4 * 40;
  }
  $variables['_width'] = $variables['width'];
  $variables['_height'] = $variables['height'];
  $variables['ratio'] = round(100 * $variables['_height'] / $variables['_width'], 2);
  if ($variables['responsive']) {
    $variables['width'] = '100%';
    $variables['height'] = '100%';
    $variables['attributes']['style'][] = 'padding-top:'. $variables['ratio'] .'%';
  }
}
