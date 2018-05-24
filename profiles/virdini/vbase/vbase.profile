<?php

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Render\Element\Email;
use Drupal\user\UserInterface;

/**
 * Implements hook_entity_access().
 */
function vbase_entity_access(EntityInterface $entity, $operation, AccountInterface $account) {
  switch ($operation) {
    case 'view':
      if (in_array($entity->getEntityTypeId(), ['node', 'taxonomy_term'])) {
        $config = \Drupal::config('vbase.settings.cp');
        $bundles = $config->get($entity->getEntityTypeId() == 'node' ? 'node_bundles' : '');
        return AccessResult::forbiddenIf(!empty($bundles) && in_array($entity->bundle(), $bundles)
                                         && !$account->hasPermission('vbase view protected content'))
                ->addCacheableDependency($config)
                ->cachePerPermissions();
      }
      break;
    case 'update':
      if ($entity->getEntityTypeId() == 'user') {
        return AccessResult::forbiddenIf($entity->id() == 1 && $account->id() != 1)
                ->cachePerPermissions();
      }
      break;
  }
  return AccessResult::neutral();
}

/**
 * Implements hook_ENTITY_TYPE_presave() for node.
 */
function vbase_node_presave(EntityInterface $entity) {
  if ($entity->get('pubdate')->getString() == 0) {
    $entity->get('pubdate')->setValue([]);
  }
}

/**
 * Implements hook_ENTITY_TYPE_load() for node.
 */
function vbase_node_load($entities) {
  foreach ($entities as $entity) {
    if ($entity->get('pubdate')->getString() == 0) {
      $entity->get('pubdate')->setValue([]);
    }
  }
}

/**
 * Implements hook_ENTITY_TYPE_presave() for user.
 */
function vbase_user_presave(EntityInterface $entity) {
  $name = $entity->getAccountName();
  if (!$name || strpos($name, 'vbase_') !== 0 || !$entity->isNew()
      || !\Drupal::config('vbase.settings.users')->get('register_by_email')) {
    return;
  }
  // Strip illegal characters.
  $email_name = trim(preg_replace('/[^\x{80}-\x{F7} a-zA-Z0-9@_.\'-]/', '', str_replace('@', '_at_', $entity->getEmail())));
  // If there's nothing left use a default.
  if (empty($email_name)) {
    $email_name = $name;
  }
  // Truncate to a reasonable size.
  if (Unicode::strlen($email_name) > (UserInterface::USERNAME_MAX_LENGTH - 10)) {
    $email_name = Unicode::substr($email_name, 0, UserInterface::USERNAME_MAX_LENGTH - 11);
  }
  $i = 0;
  $user = FALSE;
  do {
    $new_name = !$i ? $email_name : $email_name . '_' . $i;
    $user = user_load_by_name($new_name);
    $i++;
  } while ($user);
  $entity->setUsername($new_name);
}

/**
 * Implements hook_cron().
 */
function vbase_cron() {
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $query = $storage->getQuery();
  $query->condition('status', 0)
        ->condition('pubdate', 0, '<>')
        ->condition('pubdate', time(), '<');
  foreach ($storage->loadMultiple($query->execute()) as $entity) {
    _vbase_publish_delayed($entity);
    if ($entity->isTranslatable()) {
      foreach ($entity->getTranslationLanguages(FALSE) as $lang) {
        _vbase_publish_delayed($entity->getTranslation($lang->getId()));
      }
    }
  }
}

function _vbase_publish_delayed(EntityInterface $entity) {
  if (!$entity->isPublished() && $entity->get('pubdate')->getString() != 0 && $entity->get('pubdate')->getString() <= time()) {
    $entity->setPublished(TRUE)->save();
  }
}

/**
 * Implements hook_entity_base_field_info().
 */
function vbase_entity_base_field_info(EntityTypeInterface $entity_type) {
  if ($entity_type->id() == 'node') {
    $fields['pubdate'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Published on'))
      ->setDescription(t('The time that the node was published.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'vbase_datetime_timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);
    return $fields;
  }
}

/**
 * Implements hook_entity_field_access()
 */
function vbase_entity_field_access($operation, FieldDefinitionInterface $field_definition, AccountInterface $account) {
  if ($operation == 'edit' && $field_definition->getName() == 'pubdate' && $field_definition->getTargetEntityTypeId() == 'node') {
    if ($account->hasPermission('pubdate field admin') || $account->hasPermission($field_definition->getTargetEntityTypeId() .' pubdate field')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    return AccessResult::forbidden()->cachePerPermissions();
  }
  return AccessResult::neutral();
}

/**
 * Implements hook_form_BASE_FORM_ID_alter() for node_form().
 */
function vbase_form_node_form_alter(&$form) {
  if (isset($form['pubdate'])) {
    $form['pubdate']['#group'] = 'author';
  }
}

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 */
function vbase_form_install_configure_form_alter(&$form) {
  $form['site_information']['site_name']['#default_value'] = 'Virdini Drupal 8';
  $form['site_information']['site_mail']['#default_value'] = 'dev@virdini.net';
  $form['admin_account']['account']['name']['#default_value'] = 'admin';
  $form['admin_account']['account']['mail']['#default_value'] = 'dev@virdini.net';
  $form['regional_settings']['site_default_country']['#default_value'] = 'UA';
  $form['update_notifications']['enable_update_status_module']['#default_value'] = 0;
  $form['update_notifications']['enable_update_status_emails']['#default_value'] = 0;
}

/**
 * Implements hook_mail_alter().
 */
function vbase_mail_alter(&$message) {
  if (isset($message['params']['vbase_attachments'])
      && is_array($message['params']['vbase_attachments'])) {
    $uid = 'vbase-' . md5(uniqid(time(), TRUE));
    $lines = 'This is a multi-part message in MIME format.'."\r\n";
    $lines .= "--$uid\r\n";
    $lines .= 'Content-Type: '. $message['headers']['Content-Type'] ."\r\n\r\n";
    $lines .= wordwrap(implode("\r\n\r\n", $message['body'])) . "\r\n\r\n";
    foreach ($message['params']['vbase_attachments'] as $attachment) {
      if ($attachment['content']) {
        $lines .= "--$uid\r\n";
        $lines .= 'Content-Type: '. $attachment['mime'] .'; name="'. $attachment['name'] ."\"\r\n";
        $lines .= 'Content-Transfer-Encoding: base64'."\r\n";
        $lines .= 'Content-Disposition: attachment; filename="'. $attachment['name'] ."\"\r\n";
        $lines .= chunk_split(base64_encode($attachment['content'])) ."\r\n\r\n";
      }
    }
    $lines .= "--$uid--";
    $message['body'] = [$lines];
    $message['headers']['Content-Type'] = 'multipart/mixed; boundary="'. $uid .'"';
  }
}

/**
 * Implements hook_form_alter().
 */
function vbase_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if ($form_id == 'user_register_form') {
    $config = \Drupal::config('vbase.settings.users');
    vbase_add_cacheable_dependency($form, $config);
    if ($config->get('register_by_email')) {
      $form['account']['name']['#type'] = 'value';
      $form['account']['name']['#value'] = 'vbase_' . user_password();
    }
  }
  elseif ($form_id == 'user_login_form') {
    $config = \Drupal::config('vbase.settings.users');
    vbase_add_cacheable_dependency($form, $config);
    if ($config->get('login_by_email')) {
      $form['name']['#title'] = t('Email address');
      $form['name']['#type'] = 'email';
      $form['name']['#maxlength'] = Email::EMAIL_MAX_LENGTH;
      $form['name']['#element_validate'][] = 'vbase_user_login_by_email';
      $form['name']['#description'] = t('Enter your email address.');
      $form['pass']['#description'] = t('Enter the password that accompanies your email address.');
    }
  }
  $config = \Drupal::config('vbase.settings.antispam');
  vbase_add_cacheable_dependency($form, $config);
  if ($config->get('site_key') && $config->get('secret_key')
       && in_array($form_id, $config->get('forms') ?: [])) {
    $form['vbase_antispam'] = [
      '#type' => 'hidden',
      '#default_value' => '',
    ];
    $attributes = [
      'class' => ['g-recaptcha'],
      'data-size' => 'invisible',
      'data-sitekey' => $config->get('site_key'),
      'data-callback' => 'vBaseAntiSpamSubmit',
    ];
    $form['vbase_antispam_widget'] = [
      '#markup' => '<div' . new Attribute($attributes) . '></div>',
    ];
    $form['#attached']['library'][] = 'vbase/antispam';
    if ($form_id == 'user_login_form') {
      $form['vbase_antispam']['#element_validate'] = ['vbase_antispam_element_validate'];
    }
    else {
      $form['#validate'][] = 'vbase_antispam_form_validate';
    }
  }
}

/**
 * Set username by mail.
 */
function vbase_user_login_by_email(&$element, FormStateInterface $form_state, &$form) {
  $mail = trim($form_state->getValue('name'));
  if (!empty($mail)) {
    if ($user_by_mail = user_load_by_mail($mail)) {
      $user_by_name = user_load_by_name($mail);
      if (!$user_by_name || $user_by_mail->id() == $user_by_name->id()) {
        $form_state->setValue('name', $user_by_mail->getAccountName());
      }
      else {
        $form_state->setError($element, t('Email validation conflict, please notify administrator.'));
      }
    }
    else {
      $user_input = $form_state->getUserInput();
      $query = isset($user_input['name']) ? ['name' => $user_input['name']] : [];
      $form_state->setError($element, t('Unrecognized email address or password. <a href=":password">Forgot your password?</a>', [':password' => Url::fromRoute('user.pass', [], ['query' => $query])->toString()]));
    }
  }
}

function vbase_antispam_element_validate(&$element, FormStateInterface $form_state, &$form) {
  if (!\Drupal::service('vbase.antispam')->verify($form_state->getValue('vbase_antispam'),  \Drupal::request()->getClientIp())) {
    $form_state->setError($element, t('You did not pass the spam test ;('));
    $form['#validate'] = [];
  }
}

function vbase_antispam_form_validate(&$form, FormStateInterface $form_state) {
  if (!\Drupal::service('vbase.antispam')->verify($form_state->getValue('vbase_antispam'),  \Drupal::request()->getClientIp())) {
    $form_state->clearErrors();
    $form_state->setError($form['vbase_antispam'], t('You did not pass the spam test ;('));
  }
}

/**
 * Implements hook_file_validate().
 *
 * Temporary fix for https://www.drupal.org/node/2492171
 */
function vbase_file_validate(FileInterface $file) {
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
  $filename_fixed = Unicode::strtolower($filename_fixed);
  if ($filename != $filename_fixed) {
    $directory = drupal_dirname($file->destination);
    $file->destination = file_create_filename($filename_fixed, $directory);
  }
  return $errors;
}

/**
 * Implements hook_theme().
 */
function vbase_theme() {
  return [
    'vbase_adjustment_text' => [
      'render element' => '',
    ],
    'developers' => [
      'variables' => [],
    ],
  ];
}

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_developers(array &$variables) {
  $config = \Drupal::config('vbase.settings.developers');
  vbase_add_cacheable_dependency($variables, $config);
  if (!($variables['logo'] = $config->get('logo'))) {
    $variables['logo'] = 'logo.svg';
  }
  $variables['width'] = $config->get('width');
  if ($variables['width'] < 70) {
    $variables['width'] = 70;
  }
  $variables['label_display'] = $config->get('label');
  $key = (int)$config->get('developed') .'-'. (int)$config->get('maintained');
  switch ($key) {
    case '1-0':
      $variables['title'] = t('Website was developed by Virdini');
      $variables['label'] = t('Developed');
      break;
    case '0-1':
      $variables['title'] = t('Website maintained by Virdini');
      $variables['label'] = t('Maintained');
      break;
    default:
      $variables['title'] = t('Website was developed and maintained by Virdini');
      $variables['label'] = t('Developed and maintained');
      break;
  }
}

/**
 * Implements hook_preprocess_page().
 */
function vbase_preprocess_page(array &$variables) {
  $variables['developers'] = ['#theme' => 'developers'];
}

/**
 * Implements hook_page_attachments().
 */
function vbase_page_attachments(array &$attachments) {
  $config = \Drupal::config('vbase.settings.manifest');
  vbase_add_cacheable_dependency($attachments, $config);
  if (($short = $config->get('short_name')) || $config->get('name')) {
    $attachments['#attached']['html_head_link'][] = [[
      'rel' => 'manifest',
      'href' => Url::fromRoute('vbase.manifest')->setAbsolute()->toString(),
    ]];
    if ($short) {
      $attachments['#attached']['html_head'][] = [[
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'apple-mobile-web-app-title',
          'content' => $short,
        ],
      ], 'apple-mobile-web-app-title'];
      $attachments['#attached']['html_head'][] = [[
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'application-name',
          'content' => $short,
        ],
      ], 'application-name'];
    }
    \Drupal::service('vbase.manifest')->setIconLinks($attachments);
  }
  if ($config->get('theme_color')) {
    $attachments['#attached']['html_head'][] = [[
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#attributes' => [
        'name' => 'theme-color',
        'content' => $config->get('theme_color'),
      ],
    ], 'theme-color'];
  }

  $config = \Drupal::config('vbase.settings.browsers');
  vbase_add_cacheable_dependency($attachments, $config);
  if ($config->get('ie')) {
    $attachments['#attached']['library'][] = 'vbase/ie.'. $config->get('ie');
  }

  $config = \Drupal::config('vbase.settings.tags');
  vbase_add_cacheable_dependency($attachments, $config);
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
      '#browsers' => ['!IE' => FALSE],
      '#weight' => -1000,
      '#attributes' => [
        'http-equiv' => 'X-UA-Compatible',
        'content' => 'IE=edge, chrome=1',
      ],
    ], 'ie-chrome'];
  }
}

/**
 * Implements hook_page_attachments_alter().
 */
function vbase_page_attachments_alter(array &$attachments) {
  // Hide metatags
  $config = \Drupal::config('vbase.settings.tags');
  vbase_add_cacheable_dependency($attachments, $config);
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
  if (!empty($keys) && !empty($attachments['#attached']['html_head'])) {
    foreach ($attachments['#attached']['html_head'] as $key => $value) {
      if (in_array($value[1], $keys)) {
        unset($attachments['#attached']['html_head'][$key]);
      }
    }
  }
  // Hide links
  $keys = [
    'delete-form',
    'edit-form',
    'version-history',
    'revision',
    'drupal:content-translation-overview',
    'drupal:content-translation-add',
    'drupal:content-translation-edit',
    'drupal:content-translation-delete',
  ];
  if (!$config->get('shortlink')) {
    $keys[] = 'shortlink';
  }
  if ($base = $config->get('base')) {
    $keys[] = 'canonical';
  }
  if (!empty($keys) && isset($attachments['#attached']['html_head_link'])) {
    foreach ($attachments['#attached']['html_head_link'] as $key => $value) {
      if (isset($value[0]['rel']) && in_array($value[0]['rel'], $keys)) {
        unset($attachments['#attached']['html_head_link'][$key]);
      }
    }
  }
  if ($base) {
    $url = \Drupal::service('path.matcher')->isFrontPage() ? Url::fromRoute('<front>') : Url::fromRouteMatch(\Drupal::routeMatch());
    $canonical = $base . $url->toString();
    global $pager_page_array;
    if (is_array($pager_page_array) && !empty($pager_page_array) && ($pager_page_array[0] != 0 || count($pager_page_array) > 1)) {
      $canonical .= '?page='. implode(',', $pager_page_array);
    }
    $attachments['#attached']['html_head_link'][] = [
      ['rel' => 'canonical', 'href' => $canonical],
      FALSE,
    ];
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
    $config = \Drupal::config('vbase.settings.tags');
    vbase_add_cacheable_dependency($build, $config);
    $keys = ['delete-form', 'edit-form', 'version-history', 'revision'];
    if (!$config->get('shortlink')) {
      $keys[] = 'shortlink';
    }
    if ($config->get('base')) {
      $keys[] = 'canonical';
    }
    foreach ($build['#attached']['html_head_link'] as $key => $value) {
      if (isset($value[0]['rel']) && in_array($value[0]['rel'], $keys)) {
        unset($build['#attached']['html_head_link'][$key]);
      }
    }

  }
}

/**
 * Implements hook_preprocess_html().
 *
 * @see template_preprocess_html()
 */
function vbase_preprocess_html(array &$variables) {
  if (!isset($variables['head_attributes'])) {
    $variables['head_attributes'] = new Attribute();
  }
}

function vbase_add_cacheable_dependency(array &$build, $object) {
  if (!isset($build['#cache'])) {
    $build['#cache'] = [];
  }
  $meta_a = CacheableMetadata::createFromRenderArray($build);
  $meta_b = CacheableMetadata::createFromObject($object);
  $meta_a->merge($meta_b)->applyTo($build);
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
  if (in_array($hook, ['entity_view_alter', 'page_attachments_alter'])) {
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
 * Temporary. Invalidate bundle based cache tags
 *
 * @see https://www.drupal.org/project/drupal/issues/2145751
 */
function _vbase_invalidate_bundle_list_cache(EntityInterface $entity) {
  if ($entity instanceof ContentEntityInterface) {
    $tags = [$entity->getEntityTypeId() .'_list:'. $entity->bundle()];
    \Drupal::service('cache_tags.invalidator')->invalidateTags($tags);
  }
}

/**
 * This is an edited copy of function editor_entity_insert()
 * that uses _vbase_editor_get_file_uuids_by_field()
 * Implements hook_entity_insert().
 */
function vbase_entity_insert(EntityInterface $entity) {
  _vbase_invalidate_bundle_list_cache($entity);
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
  _vbase_invalidate_bundle_list_cache($entity);
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
  _vbase_invalidate_bundle_list_cache($entity);
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
