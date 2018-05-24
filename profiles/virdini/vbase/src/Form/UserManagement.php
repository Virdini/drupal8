<?php

namespace Drupal\vbase\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'UserManagement'
 */
class UserManagement extends ConfigFormBase {

  /**
   * Config name
   */
  const CONFIG_NAME = 'vbase.settings.users';

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * Constructs a new UserManagement.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config) {
    parent::__construct($config_factory);
    $this->typedConfigManager = $typed_config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vbase_UserManagement_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);
    $definition = $this->typedConfigManager->getDefinition(self::CONFIG_NAME);
    foreach ($definition['mapping'] as $key => $info) {
      if ($info['type'] == 'boolean') {
        $form[$key] = [
          '#type' => 'checkbox',
          '#title' => $this->t($info['label']),
          '#default_value' => $config->get($key),
        ];
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);
    if ($config->isNew()) {
      $config->set('langcode', \Drupal::service('language_manager')->getDefaultLanguage()->getId());
    }
    $definition = $this->typedConfigManager->getDefinition(self::CONFIG_NAME);
    foreach ($form_state->getValues() as $key => $value) {
      if (isset($definition['mapping'][$key])) {
        $config->set($key, $value);
      }
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }


}

