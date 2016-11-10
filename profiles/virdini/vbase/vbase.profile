<?php
/**
 * @file
 * Enables modules and site configuration for a Virdini Base site installation.
 */

use Drupal\Core\Cache\Cache;
use Drupal\Core\Access\AccessResult;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_node_access().
 */
function vbase_node_access(\Drupal\node\NodeInterface $node, $op, \Drupal\Core\Session\AccountInterface $account) {
  
  switch ($op) {
    case 'view':
      $type = $node->bundle();
      $config = \Drupal::config('vbase.settings.cp');
      $bundles = $config->get('node_bundles');
      if (!empty($bundles) && in_array($type, $bundles) && !$account->hasPermission('vbase view protected content', $account)) {
        return AccessResult::forbidden()->addCacheableDependency($config)->cachePerPermissions();
      }
      return AccessResult::neutral()->addCacheableDependency($config)->cachePerPermissions();
      break;
  }
  
  return AccessResult::neutral();
}


/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 */
function vbase_form_install_configure_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  $form['site_information']['site_name']['#default_value'] = 'Virdini Drupal 8';
  $form['site_information']['site_mail']['#default_value'] = 'dev@virdini.net';
  $form['admin_account']['account']['name']['#default_value'] = 'admin';
  $form['admin_account']['account']['mail']['#default_value'] = 'dev@virdini.net';
  $form['regional_settings']['site_default_country']['#default_value'] = 'UA';
  $form['update_notifications']['update_status_module']['#default_value'] = array();
  $form['#submit'][] = 'vbase_form_install_configure_submit';
}

/**
 * Submission handler to disable unnecessary views.
 */
function vbase_form_install_configure_submit($form, \Drupal\Core\Form\FormStateInterface $form_state) {
  // Disable unnecessary views
  \Drupal::configFactory()->getEditable('views.view.who_s_new')->set('status', FALSE)->save(TRUE);
  \Drupal::configFactory()->getEditable('views.view.who_s_online')->set('status', FALSE)->save(TRUE);
  \Drupal::configFactory()->getEditable('views.view.content_recent')->set('status', FALSE)->save(TRUE);
  \Drupal::configFactory()->getEditable('views.view.frontpage')->set('status', FALSE)->save(TRUE);
  \Drupal::configFactory()->getEditable('views.view.taxonomy_term')->set('status', FALSE)->save(TRUE);
  \Drupal::configFactory()->getEditable('views.settings')->set('ui.always_live_preview', FALSE)->save(TRUE);
}

/**
 * Implements hook_file_validate().
 *
 * Temporary fix for https://www.drupal.org/node/2492171
 */
function vbase_file_validate(\Drupal\file\FileInterface $file) {
  $errors = array();
  $filename = $file->getFilename();
  // Transliterate and sanitize the destination filename.
  $filename_fixed = \Drupal::transliteration()->transliterate($filename, 'en', '');
  // Replace whitespace.
  $filename_fixed = str_replace(' ', '_', $filename_fixed);
  // Remove remaining unsafe characters.
  $filename_fixed = preg_replace('![^0-9A-Za-z_.-]!', '', $filename_fixed);
  // Remove multiple consecutive non-alphabetical characters.
  $filename_fixed = preg_replace('/(_)_+|(\.)\.+|(-)-+/', '\\1\\2\\3', $filename_fixed);
  // Force lowercase to prevent issues on case-insensitive file systems.
  $filename_fixed = \Drupal\Component\Utility\Unicode::strtolower($filename_fixed);
  if ($filename != $filename_fixed) {
    $directory = drupal_dirname($file->destination);
    $file->destination = file_create_filename($filename_fixed, $directory);
  }
  return $errors;
}

/**
 * Implements hook_theme().
 */
function vbase_theme($existing, $type, $theme, $path) {
  return array(
    'vbase_adjustment_text' => array('render element' => ''),
  );
}

/**
 * Implements hook_page_attachments().
 */
function vbase_page_attachments(array &$attachments) {
  $config = \Drupal::config('vbase.settings.tags');
  if ($config->get('telephone')) {
    $attachments['#attached']['html_head'][] = [[
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#attributes' => [
        'name' => 'SKYPE_TOOLBAR',
        'content' => 'SKYPE_TOOLBAR_PARSER_COMPATIBLE',
      ],
    ], 'SKYPE_TOOLBAR'];
    $attachments['#attached']['html_head'][] = [[
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#attributes' => [
        'name' => 'format-detection',
        'content' => 'telephone=no',
      ],
    ], 'format-detection'];
  }
  if ($config->get('ie_chrome')) {
    $attachments['#attached']['html_head'][] = [[
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#browsers' => ['IE' => TRUE, '!IE' => FALSE],
      '#weight' => -1000,
      '#attributes' => [
        'http-equiv' => 'X-UA-Compatible',
        'content' => 'IE=edge, chrome=1',
      ],
    ], 'ie-chrome'];
  }
  $verification = [
    'google-site-verification' => $config->get('google_verification'),
    'yandex-verification' => $config->get('yandex_verification'),
  ];
  foreach ($verification as $name => $data) {
    if (!empty($data)) {
      foreach ($data as $key => $value) {
        if ($value) {
          $attachments['#attached']['html_head'][] = [[
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'name' => $name,
              'content' => $value,
            ],
          ], $name . $key];
        }
      }
    }
  }
  // Add profile cache tags.
  $attachments['#cache']['tags'] = Cache::mergeTags(isset($attachments['#cache']['tags']) ? $attachments['#cache']['tags'] : [], $config->getCacheTags());
}

/**
 * Implements hook_page_attachments_alter().
 */
function vbase_page_attachments_alter(array &$page) {
  // Hide metatags
  $config = \Drupal::config('vbase.settings.tags');
  $keys = [];
  if (!$config->get('generator')) {
    $keys[] = 'system_meta_generator';
  }
  if (!$config->get('mobile')) {
    $keys[] = 'MobileOptimized';
    $keys[] = 'HandheldFriendly';
  }
  if (!$config->get('viewport')) {
    $keys[] = 'viewport';
  }
  if (!empty($keys) && !empty($page['#attached']['html_head'])) {
    foreach ($page['#attached']['html_head'] as $key => $value) {
      if (in_array($value[1], $keys)) {
        unset($page['#attached']['html_head'][$key]);
      }
    }
  }
  // Hide links
  $keys = ['delete-form', 'edit-form', 'version-history', 'revision'];
  if (!empty($keys) && isset($page['#attached']['html_head_link'])) {
    //print_r($page['#attached']['html_head_link']);
    foreach ($page['#attached']['html_head_link'] as $key => $value) {
      if (isset($value[0]['rel']) && in_array($value[0]['rel'], $keys)) {
        unset($page['#attached']['html_head_link'][$key]);
      }
    }
  }
}

/**
 * Implements hook_entity_view_alter().
 */
function vbase_entity_view_alter(array &$build) {
  // Cheking view_mode for node.
  if ($build['#view_mode'] === 'full') {
    // Cheking html_head_link on attached tags in head.
    if (!isset($build['#attached']['html_head_link'])) {
      return;
    }
    $keys = ['delete-form', 'edit-form', 'version-history', 'revision'];
    foreach ($build['#attached']['html_head_link'] as $key => $value) {
      if (isset($value[0]['rel']) && in_array($value[0]['rel'], $keys)) {
        unset($build['#attached']['html_head_link'][$key]);
      }
    }
  }
}

/**
 * Helper function to get current page title
 */
function _vbase_get_title() {
  return \Drupal::service('token')->replace('[current-page:title]');
}

/**
 * Implements hook_module_implements_alter().
 */
function vbase_module_implements_alter(&$implementations, $hook) {
  if ($hook === 'page_attachments_alter') {
    $group = $implementations['vbase'];
    unset($implementations['vbase']);
    $implementations['vbase'] = $group;
  }
  // Remove editor module implementation of entity hooks
  elseif (in_array($hook, ['entity_insert', 'entity_update', 'entity_delete', 'entity_revision_delete'])) {
    unset($implementations['editor']);
  }
}

/**
 * This is an edited copy of function editor_entity_insert()
 * that uses _vbase_editor_get_file_uuids_by_field()
 * Implements hook_entity_insert().
 */
function vbase_entity_insert(EntityInterface $entity) {
  // Only act on content entities.
  if (!($entity instanceof FieldableEntityInterface)) {
    return;
  }
  $referenced_files_by_field = _vbase_editor_get_file_uuids_by_field($entity);
  foreach ($referenced_files_by_field as $field => $uuids) {
    _editor_record_file_usage($uuids, $entity);
  }
}

/**
 * This is an edited copy of function editor_entity_update()
 * that uses _vbase_editor_get_file_uuids_by_field()
 * Implements hook_entity_update().
 */
function vbase_entity_update(EntityInterface $entity) {
  // Only act on content entities.
  if (!($entity instanceof FieldableEntityInterface)) {
    return;
  }

  // On new revisions, all files are considered to be a new usage and no
  // deletion of previous file usages are necessary.
  if (!empty($entity->original) && $entity->getRevisionId() != $entity->original->getRevisionId()) {
    $referenced_files_by_field = _vbase_editor_get_file_uuids_by_field($entity);
    foreach ($referenced_files_by_field as $field => $uuids) {
      _editor_record_file_usage($uuids, $entity);
    }
  }
  // On modified revisions, detect which file references have been added (and
  // record their usage) and which ones have been removed (delete their usage).
  // File references that existed both in the previous version of the revision
  // and in the new one don't need their usage to be updated.
  else {
    $original_uuids_by_field = _vbase_editor_get_file_uuids_by_field($entity->original);
    $uuids_by_field = _vbase_editor_get_file_uuids_by_field($entity);
    // Detect file usages that should be incremented.
    foreach ($uuids_by_field as $field => $uuids) {
      $added_files = _vbase_diff_once($uuids_by_field[$field], $original_uuids_by_field[$field]);
      _editor_record_file_usage($added_files, $entity);
    }
    // Detect file usages that should be decremented.
    foreach ($original_uuids_by_field as $field => $uuids) {
      $removed_files = _vbase_diff_once($original_uuids_by_field[$field], $uuids_by_field[$field]);
      _editor_delete_file_usage($removed_files, $entity, 1);
    }
  }
}

/**
 * This is an edited copy of function editor_entity_insert()
 * that uses _vbase_editor_get_file_uuids_by_field()
 * Implements hook_entity_delete().
 */
function vbase_entity_delete(EntityInterface $entity) {
  // Only act on content entities.
  if (!($entity instanceof FieldableEntityInterface)) {
    return;
  }
  $result = \Drupal::database()->select('file_usage', 'f')->fields('f', array('fid'))
                                ->condition('module', 'editor')->condition('type', $entity->getEntityTypeId())
                                ->condition('id', $entity->id())->execute();
  foreach ($result as $record) {
    if ($file = \Drupal\file\Entity\File::load($record->fid)) {
      \Drupal::service('file.usage')->delete($file, 'editor', $entity->getEntityTypeId(), $entity->id(), 0);
    }
  }
}

/**
 * This is an edited copy of function editor_entity_insert()
 * that uses _vbase_editor_get_file_uuids_by_field()
 * Implements hook_entity_revision_delete().
 */
function vbase_entity_revision_delete(EntityInterface $entity) {
  // Only act on content entities.
  if (!($entity instanceof FieldableEntityInterface)) {
    return;
  }
  $referenced_files_by_field = _vbase_editor_get_file_uuids_by_field($entity);
  foreach ($referenced_files_by_field as $field => $uuids) {
    _editor_delete_file_usage($uuids, $entity, 1);
  }
}

/**
 * This is an edited copy of function _editor_get_file_uuids_by_field()
 * Finds all files referenced (data-entity-uuid) by formatted text fields.
 *
 * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
 *   An entity whose fields to analyze.
 *
 * @return array
 *   An array of file entity UUIDs.
 */
function _vbase_editor_get_file_uuids_by_field(FieldableEntityInterface $entity) {
  $uuids = array();
  $field_definitions = $entity->getFieldDefinitions();
  $formatted_text_fields = _editor_get_formatted_text_fields($entity);
  foreach ($formatted_text_fields as $formatted_text_field) {
    // In case of a translatable field, iterate over all its translations.
    if ($field_definitions[$formatted_text_field]->isTranslatable() && $entity instanceof TranslatableInterface) {
      $langcodes = array_keys($entity->getTranslationLanguages());
    }
    else {
      $langcodes = [LanguageInterface::LANGCODE_NOT_APPLICABLE];
    }
    $text = '';
    $field_items = $entity->get($formatted_text_field);
    foreach ($field_items as $field_item) {
      if (!empty($langcodes)) {
        foreach ($langcodes as $langcode) {
          if ($langcode == LanguageInterface::LANGCODE_NOT_APPLICABLE) {
            $field_items = $entity->get($formatted_text_field);
          }
          else {
            $field_items = $entity->getTranslation($langcode)->get($formatted_text_field);
          }
          foreach ($field_items as $field_item) {
            $text .= $field_item->value;
          }
        }
      }
      else {
        $text .= $field_item->value;
      }
    }
    $uuids[$formatted_text_field] = _editor_parse_file_uuids($text);
  }
  return $uuids;
}

/**
 * Computes the difference of arrays.
 *
 * The main difference from the array_diff() is that this method does not
 * remove duplicates. For example:
 * @code
 *   array_diff([1, 1, 1], [1]); // []
 *   _vbase_diff_once([1, 1, 1], [1]); // [1, 1]
 * @endcode
 *
 * Keys are maintained from the $array1.
 *
 * The comparison of items is always performed in the strict (===) mode.
 *
 * @param array $array1
 *   The array to compare from.
 * @param array $array2
 *   The array to compare to.
 *
 * @return array
 */
function _vbase_diff_once(array $array1, array $array2) {
  foreach ($array2 as $item) {
    // Always use strict mode because otherwise there could be fatal errors on
    // object conversions.
    $key = array_search($item, $array1, TRUE);
    if ($key !== FALSE) {
      unset($array1[$key]);
    }
  }
  return $array1;
}
