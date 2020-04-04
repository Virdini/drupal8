<?php

namespace Drupal\imagick\Plugin\ImageToolkit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ImageToolkit\ImageToolkitBase;
use Drupal\Core\ImageToolkit\ImageToolkitOperationManagerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\image\Entity\ImageStyle;
use Drupal\imagick\ImagickConst;
use Drupal\Core\File\FileSystem;
use Imagick;
use ImagickPixel;
use ImagickException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Imagick toolkit for image manipulation within Drupal.
 *
 * @ImageToolkit(
 *   id = "imagick",
 *   title = @Translation("Imagick image manipulation toolkit")
 * )
 */
class ImagickToolkit extends ImageToolkitBase {

  const TEMP_DIR = 'temporary://';
  const TEMP_PREFIX = 'imagick_';

  const CONFIG = 'imagick.config';
  const CONFIG_JPEG_QUALITY = 'jpeg_quality';
  const CONFIG_OPTIMIZE = 'optimize';
  const CONFIG_RESIZE_FILTER = 'resize_filter';
  const CONFIG_STRIP_METADATA = 'strip_metadata';

  /**
   * @var resource|null
   */
  protected $resource = NULL;

  /**
   * @var string
   */
  protected $mimeType;

  /**
   * @var array|null
   */
  protected $preLoadInfo = NULL;

  /**
   * @var \Drupal\Core\File\FileSystem
   */
  private $fileSystem;

  /**
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManager
   */
  private $streamWrapperManager;

  /**
   * Destructs a Imagick object.
   */
  public function __destruct() {
    if (is_object($this->resource)) {
      $this->resource->clear();
    }
  }

  /**
   * ImagickToolkit constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param array $plugin_definition
   * @param \Drupal\Core\ImageToolkit\ImageToolkitOperationManagerInterface $operation_manager
   * @param \Drupal\imagick\Plugin\ImageToolkit\LoggerInterface $logger
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\File\FileSystem $fileSystem
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManager $streamWrapperManager
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ImageToolkitOperationManagerInterface $operation_manager, LoggerInterface $logger, ConfigFactoryInterface $config_factory, FileSystem $fileSystem, StreamWrapperManager $streamWrapperManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $operation_manager, $logger, $config_factory);

    $this->fileSystem = $fileSystem;
    $this->streamWrapperManager = $streamWrapperManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('image.toolkit.operation.manager'),
      $container->get('logger.channel.image'),
      $container->get('config.factory'),
      $container->get('file_system'),
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * Loads an image from a file.
   *
   * @return bool
   *   TRUE or FALSE, based on success.
   */
  protected function load() {
    // Return immediately if the image file is not valid.
    if (!$this->isValid()) {
      return FALSE;
    }

    if (!$path = $this->getPath()) {
      return FALSE;
    }

    $success = FALSE;
    try {
      $resource = new Imagick();
      $resource->setBackgroundColor(new ImagickPixel('transparent'));
      $resource->readImage($path);
      $this->setResource($resource);

      $success = TRUE;
    } catch (ImagickException $e) {}

    // cleanup local file if the original was remote
    if ($this->isRemoteUri($path)) {
      file_unmanaged_delete($path);
    }

    return $success;
  }

  /**
   * Sets the Imagick image resource.
   *
   * @param Imagick $resource
   *   The Imagick image resource.
   *
   * @return $this
   */
  public function setResource($resource) {
    $this->preLoadInfo = NULL;
    $this->resource = $resource;

    return $this;
  }


  /** Retrieves the Imagick image resource.
   *
   * @return Imagick|resource|null
   *   The Imagick image resource, or NULL if not available.
   */
  public function getResource() {
    if (!is_object($this->resource)) {
      $this->load();
    }

    return $this->resource;
  }

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    return ((bool) $this->preLoadInfo || (bool) $this->resource);
  }

  /**
   * {@inheritdoc}
   */
  public function save($destination) {
    $resource = $this->getResource();

    if ($this->isValidUri($destination)) {
      // If destination is not local, save image to temporary local file.
      if ($this->isRemoteUri($destination)) {
        $permanent_destination = $destination;
        $destination = $this->fileSystem->tempnam(self::TEMP_DIR, self::TEMP_PREFIX);
      }

      // Convert stream wrapper URI to normal path.
      $destination = $this->fileSystem->realpath($destination);
    }

    // If preferred format is set, use it as prefix for writeImage
    // If not this will throw a ImagickException exception
    try {
      $image_format = $resource->getImageFormat();
      $destination = implode(':', [$image_format, $destination]);
    } catch (ImagickException $e) {}

    // Only compress JPEG files because other filetypes will increase in filesize
    if (isset($image_format) && in_array($image_format, ['JPEG', 'JPG', 'JPE'])) {
      // Get image quality from effect or global setting
      $quality = $resource->getImageProperty('quality') ?: $this->configFactory->get(self::CONFIG)->get(self::CONFIG_JPEG_QUALITY);
      // Set image compression quality
      $resource->setImageCompressionQuality($quality);

      // Optimize images
      if ($this->configFactory->get(self::CONFIG)->get(self::CONFIG_OPTIMIZE)) {
        // Using recommendations from Google's Page Speed docs: https://developers.google.com/speed/docs/insights/OptimizeImages
        $resource->setSamplingFactors(['2x2', '1x1', '1x1']);
        $resource->setColorspace(Imagick::COLORSPACE_RGB);
        $resource->setInterlaceScheme(Imagick::INTERLACE_JPEG);
      }
    }

    // Strip metadata
    if ($this->configFactory->get(self::CONFIG)->get(self::CONFIG_STRIP_METADATA)) {
      $resource->stripImage();
    }

    // Write image to destination
    if (isset($image_format) && in_array($image_format, ['GIF'])) {
      if (!$resource->writeImages($destination, TRUE)) {
        return FALSE;
      }
    }
    else {
      if (!$resource->writeImage($destination)) {
        return FALSE;
      }
    }

    // Move temporary local file to remote destination.
    if (isset($permanent_destination)) {
      return (bool) file_unmanaged_move($destination, $permanent_destination, FILE_EXISTS_REPLACE);
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidth() {
    if ($this->preLoadInfo) {
      return $this->preLoadInfo['geometry']['width'];
    }
    elseif ($resource = $this->getResource()) {
      $data = $resource->getImageGeometry();

      return $data['width'];
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHeight() {
    if ($this->preLoadInfo) {
      return $this->preLoadInfo['geometry']['height'];
    }
    elseif ($resource = $this->getResource()) {
      $data = $resource->getImageGeometry();

      return $data['height'];
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeType() {
    return $this->mimeType;
  }

  /**
   * ensure that we have a local filepath since Imagick does not support remote stream wrappers
   *
   * @return string
   */
  protected function getPath() {
    $source = $this->getSource();

    return ($this->isRemoteUri($source) ?
      $this->copyRemoteFileToLocalTemp($source) :
      $this->fileSystem->realpath($source));
  }

  /**
   * {@inheritdoc}
   */
  public function parseFile() {
    $path = $this->getPath();

    try {
      $image = new Imagick($path);

      // Get image data
      $this->mimeType = $image->getImageMimeType();
      $this->preLoadInfo = $image->identifyImage();

      if ($this->isRemoteUri($path)) {
        file_unmanaged_delete($path);
      }

      return TRUE;
    }
    catch (ImagickException $e) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['jpeg'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('JPEG specific settings'),
      '#description' => $this->t('<strong>Tip: </strong>Generated images can be converted to the JPEG format using the Convert effect.'),
    ];
    $form['jpeg'][self::CONFIG_JPEG_QUALITY] = [
      '#type' => 'number',
      '#title' => $this->t('Quality'),
      '#description' => $this->t('Higher values mean better image quality but bigger files. Quality level below 80% is not advisable when using ImageMagick.'),
      '#min' => 0,
      '#max' => 100,
      '#default_value' => $this->configFactory->get(self::CONFIG)->get(self::CONFIG_JPEG_QUALITY),
      '#field_suffix' => $this->t('%'),
    ];

    $form['jpeg'][self::CONFIG_OPTIMIZE] = [
      '#type' => 'checkbox',
      '#title' => t('Use Google Pagespeed Insights image optimization.'),
      '#description' => t('See the <a href=":url" target="_blank">guidelines</a> for further information.', [':url' => 'https://developers.google.com/speed/docs/insights/OptimizeImages']),
      '#default_value' => $this->configFactory->get(self::CONFIG)->get(self::CONFIG_OPTIMIZE),
    ];

    $form[self::CONFIG_RESIZE_FILTER] = [
      '#type' => 'select',
      '#title' => t('Imagic resize filter'),
      '#description' => t('Define the resize filter for image manipulations. If you\'re not sure what you should enter here, leave the default settings.'),
      '#options' => [
        -1 => t('- None -'),
        imagick::FILTER_UNDEFINED => 'FILTER_UNDEFINED',
        imagick::FILTER_POINT => 'FILTER_POINT',
        imagick::FILTER_BOX => 'FILTER_BOX',
        imagick::FILTER_TRIANGLE => 'FILTER_TRIANGLE',
        imagick::FILTER_HERMITE => 'FILTER_HERMITE',
        imagick::FILTER_HANNING => 'FILTER_HANNING',
        imagick::FILTER_HAMMING => 'FILTER_HAMMING',
        imagick::FILTER_BLACKMAN => 'FILTER_BLACKMAN',
        imagick::FILTER_GAUSSIAN => 'FILTER_GAUSSIAN',
        imagick::FILTER_QUADRATIC => 'FILTER_QUADRATIC',
        imagick::FILTER_CUBIC => 'FILTER_CUBIC',
        imagick::FILTER_CATROM => 'FILTER_CATROM',
        imagick::FILTER_MITCHELL => 'FILTER_MITCHELL',
        imagick::FILTER_LANCZOS => 'FILTER_LANCZOS',
        imagick::FILTER_BESSEL => 'FILTER_BESSEL',
        imagick::FILTER_SINC => 'FILTER_SINC',
      ],
      '#default_value' => $this->configFactory->get(self::CONFIG)->get(self::CONFIG_RESIZE_FILTER),
    ];

    $form[self::CONFIG_STRIP_METADATA] = [
      '#type' => 'checkbox',
      '#title' => t('Strip images of all metadata.'),
      '#description' => t('Eg. profiles, comments, ...'),
      '#default_value' => $this->configFactory->get(self::CONFIG)->get(self::CONFIG_STRIP_METADATA),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    /** @var ImageStyle $style */
    foreach (ImageStyle::loadMultiple() as $style) {
      $style->flush();
    }

    $this->configFactory->getEditable(self::CONFIG)
      ->set(self::CONFIG_JPEG_QUALITY, $form_state->getValue(['imagick', 'jpeg', self::CONFIG_JPEG_QUALITY]))
      ->set(self::CONFIG_OPTIMIZE, $form_state->getValue(['imagick', 'jpeg', self::CONFIG_OPTIMIZE]))
      ->set(self::CONFIG_RESIZE_FILTER, $form_state->getValue(['imagick', self::CONFIG_RESIZE_FILTER]))
      ->set(self::CONFIG_STRIP_METADATA, $form_state->getValue(['imagick', self::CONFIG_STRIP_METADATA]))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function isAvailable() {
    return _imagick_is_available();
  }

  /**
   * @param $uri
   *
   * @return bool
   */
  private function isValidUri($uri) {
    $scheme = $this->fileSystem->uriScheme($uri);
    return ($scheme && $this->fileSystem->validScheme($scheme));
  }

  /**
   * Returns TRUE if the $uri points to a remote location, FALSE otherwise.
   *
   * @param $uri
   * @return bool
   */
  private function isRemoteUri($uri) {
    if (!$this->isValidUri($uri)) {
      return FALSE;
    }

    $local_wrappers = $this->streamWrapperManager
      ->getWrappers(StreamWrapperInterface::LOCAL);

    return !in_array($this->fileSystem->uriScheme($uri), array_keys($local_wrappers));
  }

  /**
   * Given a remote source it will copy its contents to a local temporary file.
   *
   * @param $source
   * @return bool
   */
  private function copyRemoteFileToLocalTemp($source) {
    if (!$tmp_file = file_unmanaged_copy(
      $source,
      $this->fileSystem->tempnam(self::TEMP_DIR, self::TEMP_PREFIX),
      FILE_EXISTS_REPLACE
    )) {
      return FALSE;
    }

    return $this->fileSystem->realpath($tmp_file);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSupportedExtensions() {
    return ImagickConst::getSupportedExtensions();
  }

}
