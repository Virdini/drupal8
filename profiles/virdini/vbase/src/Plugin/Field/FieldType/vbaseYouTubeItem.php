<?php

namespace Drupal\vbase\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'vbase_youtube' field type.
 *
 * @FieldType(
 *   id = "vbase_youtube",
 *   module = "vbase",
 *   label = @Translation("vBase YouTube"),
 *   category = @Translation("Virdini"),
 *   default_widget = "vbase_youtube",
 *   default_formatter = "vbase_youtube"
 * )
 */
class vbaseYouTubeItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar_ascii',
          'length' => 64,
        ],
        'resp' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
        'width' => [
          'type' => 'int',
          'size' => 'medium',
        ],
        'height' => [
          'type' => 'int',
          'size' => 'medium',
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('YouTube Video Id'))
      ->setRequired(TRUE);
    $properties['resp'] = DataDefinition::create('boolean')
      ->setLabel(t('Responsive'));
    $properties['width'] = DataDefinition::create('integer')
      ->setLabel(t('Width'));
    $properties['height'] = DataDefinition::create('integer')
      ->setLabel(t('Height'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
