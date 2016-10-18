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
 * Implements hook_tokens().
 */
function vbase_tokens($type, array $tokens, array $data = array(), array $options = array(), \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();
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
  return $replacements;
}

/**
 * Implements hook_tokens_alter().
 */
function vbase_tokens_alter(array &$replacements, array $context, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  if ($context['type'] == 'node' && !empty($context['data']['node'])) {
    if (isset($context['tokens']['title']) && !$replacements[$context['tokens']['title']]) {
      $replacements[$context['tokens']['title']] = \Drupal::service('token')->replace('[current-page:title]');
    }
  }
  elseif ($context['type'] == 'current-page' && isset($context['tokens']['title'])) {
    $route_match = \Drupal::routeMatch();
    $route_name = $route_match->getRouteName();
    if ($route_name == 'contact.site_page') {
      $config = \Drupal::config('contact.settings');
      $contact_form = \Drupal::entityTypeManager()
        ->getStorage('contact_form')
        ->load($config->get('default_form'));
        $replacements[$context['tokens']['title']] = $contact_form->label();
    }
    else {
      $matches = array();
      preg_match('/entity\.(.*)\.(.*)/', $route_name, $matches);
      if(!$replacements[$context['tokens']['title']] && !empty($matches[1]) && $matches[1] == 'node' && !empty($matches[2])) {
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
      elseif(!empty($matches[1]) && $matches[1] == 'contact_form' && !empty($matches[2])) {
        // TODO: $replacements[$context['tokens']['title']] = $route_name;
      }
    }
  }
}