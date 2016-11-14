<?php
/**
 * @file
 * Token callbacks for the vbase profile.
 */

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
function vbase_token_info() {
  $info = [];
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
          $translation = \Drupal::entityManager()->getTranslationFromContext($node, $langcode, array('operation' => 'node_tokens'));
          if ($translation->hasField($_name[1]) && ($items = $translation->get($_name[1])) && !$items->isEmpty() && $tid = $items->target_id) {
            $url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $tid]);
            $path = '/'. $url->getInternalPath();
            $alias = \Drupal::service('path.alias_manager')->getAliasByPath($path, $langcode);
            if ($path != $alias) {
              $replacements[$original] = ltrim($alias, '/');
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