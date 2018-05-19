<?php

namespace Drupal\vbase\Plugin\Field\FieldWidget;
use Drupal\Core\Datetime\Plugin\Field\FieldWidget\TimestampDatetimeWidget;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'vbase datetime timestamp' widget.
 *
 * @FieldWidget(
 *   id = "vbase_datetime_timestamp",
 *   label = @Translation("vbase Datetime Timestamp"),
 *   field_types = {
 *     "timestamp",
 *     "created",
 *   }
 * )
 */
class vbaseTimestampDatetimeWidget extends TimestampDatetimeWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    unset($element['value']['#description']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => &$item) {
      // @todo The structure is different whether access is denied or not, to
      //   be fixed in https://www.drupal.org/node/2326533.
      if (isset($item['value']) && $item['value'] instanceof DrupalDateTime) {
        $item['value'] = $item['value']->getTimestamp();
      }
      elseif (isset($item['value']['object']) && $item['value']['object'] instanceof DrupalDateTime) {
        $item['value'] = $item['value']['object']->getTimestamp();
      }
      else {
        $item['value'] = 0;
      }
    }
    return $values;
  }

}
