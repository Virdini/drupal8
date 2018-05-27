<?php

namespace Drupal\vbase\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'vbase_youtube' widget.
 *
 * @FieldWidget(
 *   id = "vbase_youtube",
 *   label = @Translation("vBase YouTube"),
 *   field_types = {
 *     "vbase_youtube"
 *   }
 * )
 */
class vbaseYouTubeWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $properties = $items->getItemDefinition()->getPropertyDefinitions();
    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => 15,
    ];
    $element['#type'] = 'fieldset';
    $element['#attributes']['class'][] = 'vbase-youtube-container';
    $element['value']['#title'] = $properties['value']->getLabel();
    $element['width'] = [
      '#type' => 'number',
      '#title' => $properties['width']->getLabel(),
      '#default_value' => isset($items[$delta]->width) ? $items[$delta]->width : 0,
    ];
    $element['height'] = [
      '#type' => 'number',
      '#title' => $properties['height']->getLabel(),
      '#default_value' => isset($items[$delta]->height) ? $items[$delta]->height : 0,
    ];
    $element['resp'] = [
      '#type' => 'checkbox',
      '#title' => $properties['resp']->getLabel(),
      '#default_value' => isset($items[$delta]->resp) ? $items[$delta]->resp : 0,
    ];
    return $element;
  }

}
