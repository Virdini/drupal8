<?php

namespace Drupal\vbase\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\NodeType;

/**
 * Configure site content protection settings for this site.
 */
class ContentProtectionSettingsForm extends ConfigFormBase {

  /**
   * Config name
   */
  const CONFIG_NAME = 'vbase.settings.cp';

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $configTyped;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeTypeStorage;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $vocabularyStorage;

  /**
   * Constructs a ContentProtectionSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $config_typed
   *
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              EntityTypeManagerInterface $entity_type_manager,
                              TypedConfigManagerInterface $config_typed) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->configTyped = $config_typed;

    $this->nodeTypeStorage = $this->entityTypeManager->getStorage('node_type');
    $this->vocabularyStorage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vbase_content_protection_settings';
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
    $definition = $this->configTyped->getDefinition(self::CONFIG_NAME);

    $form['node_bundles'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t($definition['mapping']['node_bundles']['label']),
      '#options' => [],
      '#default_value' => $config->get('node_bundles'),
    ];
    foreach ($this->nodeTypeStorage->loadMultiple() as $entity) {
      $form['node_bundles']['#options'][$entity->id()] = $entity->label();
    }

    $form['taxonomy_vocabularies'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t($definition['mapping']['taxonomy_vocabularies']['label']),
      '#options' => [],
      '#default_value' => $config->get('taxonomy_vocabularies'),
    ];
    foreach ($this->vocabularyStorage->loadMultiple() as $entity) {
      $form['taxonomy_vocabularies']['#options'][$entity->id()] = $entity->label();
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config(self::CONFIG_NAME);
    $definition = $this->configTyped->getDefinition(self::CONFIG_NAME);
    foreach ($form_state->getValues() as $key => $value) {
      if (isset($definition['mapping'][$key])) {
        $config->set($key, $value);
      }
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
