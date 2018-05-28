<?php

namespace Drupal\vbase\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "vbase_table",
 *   title = @Translation("Virdini Table"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class Table extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    $text = preg_replace_callback('@<table([^>]*)>(.+?)</table>@s', [$this, 'processTableCallback'], $text);
    $result->setProcessedText($text);
    return $result;
  }

  /**
   * Callback to replace content of the <table> elements.
   *
   * @param array $matches
   *   An array of matches passed by preg_replace_callback().
   *
   * @return string
   *   A formatted string.
   */
  private function processTableCallback(array $matches) {
    return '<figure class="vtable"><table'. $matches[1] .'>'. $matches[2] .'</table></figure>';
  }

}
