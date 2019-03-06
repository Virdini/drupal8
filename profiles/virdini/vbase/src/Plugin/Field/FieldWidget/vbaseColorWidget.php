<?php

namespace Drupal\vbase\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'vbase_color' widget.
 *
 * @FieldWidget(
 *   id = "vbase_color",
 *   label = @Translation("vBase Color"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class vbaseColorWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $properties = $items->getItemDefinition()->getPropertyDefinitions();
    $element['value'] = $element + [
      '#field_prefix' => '#',
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => 8,
    ];
    $element['#attributes']['class'][] = 'vbase-color-container';
    return $element;
  }

}
