<?php

namespace Drupal\vbase\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Component\Utility\Html;

/**
 * @Filter(
 *   id = "vbase_secure",
 *   title = @Translation("Virdini Secure"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = -100
 * )
 */
class Secure extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $tags = [
      'script',
      'style',
      'embed',
      'object',
    ];
    $text = preg_replace('#<(' . implode( '|', $tags) . ')(?:[^>]+)?>.*?</\1>#s', '', $text);
    $dom = Html::load($text);
    foreach ($dom->getElementsByTagName('link') as $href) {
      $href->parentNode->removeChild($href);
    }
    $text = Html::serialize($dom);
    return new FilterProcessResult($text);
  }

}
