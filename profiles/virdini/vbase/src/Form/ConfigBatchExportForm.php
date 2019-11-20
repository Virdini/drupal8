<?php

namespace Drupal\vbase\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Serialization\Yaml;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Link;

class ConfigBatchExportForm extends FormBase {

  /**
   * Batch Builder.
   *
   * @var \Drupal\Core\Batch\BatchBuilder
   */
  protected $batchBuilder;

  /**
   * The target storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $targetStorage;
  /**
   * The source storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $sourceStorage;
  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;
  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a ConfigBatchExportForm object.
   *
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   The target storage.
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   *   The source storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The config manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   */
  public function __construct(StorageInterface $target_storage, StorageInterface $source_storage, ConfigManagerInterface $config_manager, FileSystemInterface $file_system) {
    $this->targetStorage = $target_storage;
    $this->sourceStorage = $source_storage;
    $this->configManager = $config_manager;
    $this->fileSystem = $file_system;
    $this->batchBuilder = new BatchBuilder();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage'),
      $container->get('config.storage.sync'),
      $container->get('config.manager'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vbase_config_batch_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->fileSystem->delete(file_directory_temp() . '/config.tar.gz');
    }
    catch (FileException $e) {
      // Ignore failed deletes.
    }

    $configs = [];
    // Get configuration names.
    foreach ($this->configManager->getConfigFactory()->listAll() as $name) {
      $configs[] = [
        'name' => $name,
      ];
    }
    // Get configuration names from the remaining collections.
    foreach ($this->targetStorage->getAllCollectionNames() as $collection) {
      $collection_storage = $this->targetStorage->createCollection($collection);
      foreach ($collection_storage->listAll() as $name) {
        $configs[] = [
          'name' => $name,
          'collection' => $collection,
        ];
      }
    }

    $this->batchBuilder
      ->setTitle($this->t('Exporting the full configuration'))
      ->setInitMessage($this->t('Initializing.'))
      ->setProgressMessage($this->t('Completed @current of @total.'))
      ->setErrorMessage($this->t('An error has occurred.'));

    $this->batchBuilder->setFile(drupal_get_path('profile', 'vbase') . '/src/Form/ConfigBatchExportForm.php');
    $this->batchBuilder->addOperation([$this, 'processItems'], [$configs]);
    $this->batchBuilder->setFinishCallback([$this, 'finished']);

    batch_set($this->batchBuilder->toArray());
  }

  /**
   * Processor for batch operations.
   */
  public function processItems($items, array &$context) {
    // Set default progress values.
    if (empty($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($items);
    }

    $index = $context['sandbox']['progress'];

    $name = $items[$index]['name'];
    $context['message'] = $name;
    $archiver = new ArchiveTar(file_directory_temp() . '/config.tar.gz', 'gz');
    // Get raw configuration data without overrides.
    if (!isset($items[$index]['collection'])) {
      $archiver->addString("$name.yml", Yaml::encode($this->configManager->getConfigFactory()->get($name)->getRawData()));
    }
    // Get all override data from the remaining collections.
    else {
      $collection_storage = $this->targetStorage->createCollection($items[$index]['collection']);
      $archiver->addString(str_replace('.', '/', $items[$index]['collection']) . "/$name.yml", Yaml::encode($collection_storage->read($name)));
    }
    $context['sandbox']['progress']++;

    // If not finished all tasks, we count percentage of process. 1 = 100%.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Finished callback for batch.
   */
  public function finished($success, $results, $operations) {
    if ($success) {
      $link = Link::createFromRoute('Download', 'vbase.export_config_download')->toRenderable();
      $this->messenger()->addStatus($link);
    }
  }

}
