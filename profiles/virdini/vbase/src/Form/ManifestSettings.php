<?php

namespace Drupal\vbase\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'ManifestSettings'
 */
class ManifestSettings extends ConfigFormBase {

  /**
   * Config name
   */
  const CONFIG_NAME = 'vbase.settings.manifest';

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * Constructs a new ManifestSettings.
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
    return 'vbase_ManifestSettings_form';
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
      if ($info['type'] == 'label' || in_array($key, ['theme_color', 'background_color', 'mask_icon_color'])) {
        $form[$key] = [
          '#type' => 'textfield',
          '#title' => $this->t($info['label']),
          '#default_value' => $config->get($key),
        ];
      }
    }
    $form['display'] = [
      '#type' => 'select',
      '#title' => $this->t($definition['mapping']['display']['label']),
      '#default_value' => $config->get('display'),
      '#options' => [
        '' => '',
        'fullscreen' => 'fullscreen',
        'standalone' => 'standalone',
        'minimal-ui' => 'minimal-ui',
        'browser' => 'browser',
      ],
    ];
    $form['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t($definition['mapping']['orientation']['label']),
      '#default_value' => $config->get('orientation'),
      '#options' => [
        '' => '',
        'any' => 'any',
        'natural' => 'natural',
        'landscape' => 'landscape',
        'landscape-primary' => 'landscape-primary',
        'landscape-secondary' => 'landscape-secondary',
        'portrait' => 'portrait',
        'portrait-primary' => 'portrait-primary',
        'portrait-secondary' => 'portrait-secondary',
      ],
    ];
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
