<?php
/**
 * @file
 * Contains \Drupal\vbase\VBaseUnicode.
 */

namespace Drupal\vbase;

use Drupal\Component\Utility\Unicode;

class VBaseUnicode extends Unicode {

  /**
   * {@inheritdoc}
   */
  public static function mimeHeaderEncode($string, $shorten = FALSE) {
    if (preg_match('/[^\x20-\x7E]/', $string)) {
      // floor((75 - strlen("=?UTF-8?B??=")) * 0.75);
      $chunk_size = 47;

      $suffix = FALSE;
      if (preg_match('/ <([^>]*)>$/', $string, $match) &&
          \Drupal::service('email.validator')->isValid($match[1])) {
        $suffix = $match[0];
        $suffix_len = self::strlen($suffix);
        $string = self::truncate($string, self::strlen($string) - $suffix_len);
      }

      $len = strlen($string);
      $output = '';
      while ($len > 0) {
        $chunk = static::truncateBytes($string, $chunk_size);
        $output .= ' =?UTF-8?B?' . base64_encode($chunk) . "?=\n";
        if ($shorten) {
          break;
        }
        $c = strlen($chunk);
        $string = substr($string, $c);
        $len -= $c;
      }

      if ($suffix && $suffix_len + $c > $chunk_size) {
        $suffix = "\n" . $suffix;
      }
      $output = trim($output);
      $result = $suffix ? $output . $suffix : $output;
      return $result;
    }
    return $string;
  }

}
