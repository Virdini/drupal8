<?php
/**
 * @file
 * Token callbacks for the vbase profile.
 */

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
function vbase_token_info() {
  $info = ['tokens' => []];
  $fields = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('node');
  if (!empty($fields)) {
    foreach ($fields as $field_name => $field) {
      if ($field instanceof FieldStorageConfigInterface
          && $field->getType() == 'entity_reference'
          && $field->getSetting('target_type') == 'taxonomy_term') {
        $info['tokens']['virdini-term-alias'][$field_name .':alias'] = [
          'name' => $field_name,
          'description' => t('@field_name field.', ['@field_name' => $field_name]),
          'type' => 'alias',
        ];
      }
    }
    if (isset($info['tokens']['virdini-term-alias'])) {
      $info['types']['virdini-term-alias'] = [
        'name' => t('Virdini Term Alias'),
        'description' => t('Tokens from node reference fields.'),
        'needs-data' => 'node',
      ];
      $info['tokens']['node']['virdini-term-alias'] = [
        'name' => t('Virdini Term Alias'),
        'description' => t('Tokens from node reference fields.'),
        'type' => 'virdini-term-alias',
      ];
    }
  }
  $info['tokens']['term']['virdini-parent-alias'] = [
    'name' => t('Parent term alias'),
    'description' => t("The parent term alias of the taxonomy term, if one exists."),
    'type' => 'alias',
  ];
  return $info;
}

/**
 * Implements hook_tokens().
 */
function vbase_tokens($type, array $tokens, array $data = array(), array $options = array(), BubbleableMetadata $bubbleable_metadata) {
  if (isset($options['langcode'])) {
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }
  $replacements = array();
  if ($type == 'node' && !empty($data['node'])) {
    $node = $data['node'];
    foreach ($tokens as $name => $original) {
      $_name = explode(':', $name);
      switch ($_name[0]) {
        case 'virdini-term-alias':
          $translation = \Drupal::service('entity.repository')->getTranslationFromContext($node, $langcode, array('operation' => 'node_tokens'));
          if ($translation->hasField($_name[1]) && ($items = $translation->get($_name[1])) && !$items->isEmpty() && $tid = $items->target_id) {
            $term = \Drupal::EntityTypeManager()->getStorage('taxonomy_term')->load($tid);
            $bubbleable_metadata->addCacheableDependency($term);
            $path = '/'. $term->toUrl()->getInternalPath();
            $alias = \Drupal::service('path.alias_manager')->getAliasByPath($path, $langcode);
            if ($path != $alias) {
              $replacements[$original] = ltrim($alias, '/');
            }
          }
          break;
      }
    }
  }
  elseif ($type == 'term' && !empty($data['term'])) {
    $term = $data['term'];
    $taxonomy_storage = \Drupal::EntityTypeManager()->getStorage('taxonomy_term');
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'virdini-parent-alias':
          if ($parents = $taxonomy_storage->loadParents($term->id())) {
            $parent = array_pop($parents);
            $bubbleable_metadata->addCacheableDependency($parent);
            $path = '/'. $parent->toUrl()->getInternalPath();
            $alias = \Drupal::service('path.alias_manager')->getAliasByPath($path, $langcode);
            if ($path != $alias) {
              $replacements[$original] = ltrim($alias, '/');
              $pattern = \Drupal::service('pathauto.generator')->getPatternByEntity($parent)->getPattern();
              if (preg_match('/[^\[]*/', $pattern, $matches) && $prefix = $matches[0]) {
                $replacements[$original] = substr($replacements[$original], strlen($prefix));
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
function vbase_tokens_alter(array &$replacements, array $context, BubbleableMetadata $bubbleable_metadata) {
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