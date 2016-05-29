<?php
/**
 * @file
 * Enables modules and site configuration for a Virdini Base site installation.
 */

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
  //\Drupal::configFactory()->getEditable('views.view.frontpage')->set('display.feed_1.display_options.enabled', FALSE)->save(TRUE);
  //\Drupal::configFactory()->getEditable('views.view.taxonomy_term')->set('display.feed_1.display_options.enabled', FALSE)->save(TRUE);
  \Drupal::configFactory()->getEditable('views.settings')->set('ui.always_live_preview', FALSE)->save(TRUE);
}

/**
 * Implements hook_file_validate().
 *
 * Temporary fix for https://www.drupal.org/node/2492171
 */
function vbase_file_validate(\Drupal\file\FileInterface $file) {
  $errors = array();
  $transliteration = new \Drupal\Component\Transliteration\PhpTransliteration;
  $filename = $file->getFilename();
  $filename_fixed = $transliteration->transliterate($filename);
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
 * Implements hook_module_implements_alter().
 */
function vbase_module_implements_alter(&$implementations, $hook) {
  if ($hook === 'page_attachments_alter') {
    $group = $implementations['vbase'];
    unset($implementations['vbase']);
    $implementations['vbase'] = $group;
  }
}
