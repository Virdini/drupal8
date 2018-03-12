<?php

namespace Drupal\vbase\Form;

use Drupal\Core\Form\ConfigFormBase;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

class AntiSpamSettings extends ConfigFormBase {

  /**
   * Config name
   */
  const CONFIG_NAME = 'vbase.settings.antispam';

  /**
   * @var \Drupal\Core\Config\TypedConfigManager
   */
  protected $configTyped;

  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $config_typed) {
    parent::__construct($config_factory);
    $this->configTyped = $config_typed;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed')
    );
  }

  public function getFormId() {
    return 'vbase_antispam_settings_form';
  }

  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);
    $definition = $this->configTyped->getDefinition(self::CONFIG_NAME);
    $textfields = ['site_key' => 'google', 'secret_key' => 'google'];
    $form['google'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Google Invisible reCAPTCHA'),
    ];
    foreach ($textfields as $key => $group) {
      $form[$group][$key] = [
        '#title' => $this->t($definition['mapping'][$key]['label']),
        '#type' => 'textfield',
        '#default_value' => $config->get($key),
      ];
    }
    $form['forms'] = [
      '#type' => 'textarea',
      '#title' => $this->t($definition['mapping']['forms']['label']),
      '#default_value' => $config->get('forms') ? implode("\n", $config->get('forms')) : '',
      '#description' => $this->t('Enter form id one path per line.'),
      '#rows' => 10,
    ];
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);
    $definition = $this->configTyped->getDefinition(self::CONFIG_NAME);
    foreach ($form_state->getValues() as $key => $value) {
      if (isset($definition['mapping'][$key])) {
        if ($key == 'forms') {
          $value = preg_split('/(\r\n?|\n)/', $value);
        }
        $config->set($key, $value);
      }
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
