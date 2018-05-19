<?php

namespace Drupal\vbase;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Provides dynamic override permissions for nodes of different types.
 */
class NodePermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of additional permissions.
   *
   * @return array
   *   An array of permissions.
   */
  public function nodeTypePermissions() {
    $permissions = [];
    foreach (NodeType::loadMultiple() as $node_type) {
      $type = $node_type->id();
      $label = $node_type->label();
      $permissions[$type .' pubdate field'] = [
        'title' => $type .' pubdate field',
      ];
    }
    return $permissions;
  }

}
