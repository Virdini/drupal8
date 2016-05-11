<?php
/**
 * @file
 * Enables modules and site configuration for a Virdini Base site installation.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 */
function vbase_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {
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
function vbase_form_install_configure_submit($form, FormStateInterface $form_state) {
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
function vbase_file_validate(Drupal\file\FileInterface $file) {
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

function vbase_page_attachments(array &$attachments) {
  
}

