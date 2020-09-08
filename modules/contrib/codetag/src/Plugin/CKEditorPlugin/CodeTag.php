<?php

namespace Drupal\codetag\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "codeTag" plugin.
 *
 * @CKEditorPlugin(
 *   id = "codeTag",
 *   label = @Translation("CodeTag"),
 * )
 */
class CodeTag extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/codetag/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'Code' => [
        'label' => $this->t('CodeTag'),
        'image' => 'libraries/codetag/icons/code.png',
      ],
    ];
  }

}
