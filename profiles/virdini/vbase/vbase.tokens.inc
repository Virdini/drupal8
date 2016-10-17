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

/**
 * Implements hook_tokens_alter().
 */
function vbase_tokens_alter(array &$replacements, array $context, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  if ($context['type'] == 'node' && !empty($context['data']['node'])) {
    if (isset($context['tokens']['title']) && !$replacements[$context['tokens']['title']]) {
      $token_service = \Drupal::service('token');
      $replacements[$context['tokens']['title']] = $token_service->replace('[current-page:title]');
    }
  }
  elseif ($context['type'] == 'current-page' && isset($context['tokens']['title']) && !$replacements[$context['tokens']['title']]) {
    $route_match = \Drupal::routeMatch();
    $route_name = $route_match->getRouteName();
    $matches = array();
    preg_match('/entity\.(.*)\.(.*)/', $route_name, $matches);
    if(!empty($matches[1]) && $matches[1] == 'node' && !empty($matches[2])) {
      switch ($matches[2]) {
        case 'edit_form':
          $node = $route_match->getParameter('node');
          $replacements[$context['tokens']['title']] = t('<em>Edit @type</em> @title', array('@type' => node_get_type_label($node), '@title' => $node->label()));
          break;
        case 'content_translation_overview':
          $node = $route_match->getParameter('node');
          $replacements[$context['tokens']['title']] = t('Translations of %label', array('%label' => $node->label()));
          break;
      }
    }
  }
}