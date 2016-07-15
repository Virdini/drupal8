<?php
/**
 * @file
 * Token callbacks for the vbase profile.
 */

/**
 * Implements hook_token_info().
 */
function vbase_token_info() {
  $info['types']['virdini-term-alias'] = [
    'name' => t('Virdini Term Alias'),
    'description' => t('Tokens from node reference fields.'),
    'needs-data' => 'node',
  ];
  $fields = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('node');
  foreach ($fields as $field_name => $field) {
    if ($field instanceof \Drupal\field\FieldStorageConfigInterface
        && $field->getType() == 'entity_reference'
        && $field->getSetting('target_type') == 'taxonomy_term') {
      $info['tokens']['virdini-term-alias'][$field_name .':alias'] = [
        'name' => $field_name,
        'description' => t('@field_name field.', array('@field_name' => $field_name)),
        'type' => 'alias',
      ];
    }
  }
  $info['tokens']['node']['virdini-term-alias'] = array(
    'name' => $info['types']['virdini-term-alias']['name'],
    'description' => $info['types']['virdini-term-alias']['description'],
    'type' => 'virdini-term-alias',
  );
  return $info;
}

/**
 * Implements hook_token_info_alter().
 */
function vbase_token_info_alter(&$info) {
  $info['tokens']['current-page']['titlefix'] = [
    'name' => t('Fixed Title'),
    'description' => t('The title of the current page.') .' (Fixed)',
  ];
}

/**
 * Implements hook_tokens().
 */
function vbase_tokens($type, array $tokens, array $data = array(), array $options = array(), \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();
  // Current page tokens.
  if ($type == 'node' && !empty($data['node'])) {
    $node = $data['node'];
    foreach ($tokens as $name => $original) {
      $_name = explode(':', $name);
      switch ($_name[0]) {
        case 'virdini-term-alias':
          if (isset($_name[1]) && $_name[1]) {
            $item = $node->get($_name[1]);
            if ($item instanceof \Drupal\Core\Field\EntityReferenceFieldItemList) {
              $terms = $item->referencedEntities();
              if (!empty($terms) && $terms[0] instanceof \Drupal\taxonomy\Entity\Term) {
                $replacements[$original] = ltrim($terms[0]->url(), '/');
              }
            }
          }
          break;
      }
    }
  }
  elseif ($type == 'current-page') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'titlefix':
          $replacements[$original] = _vbase_get_title();
          break;
      }
    }
  }
  return $replacements;
}
