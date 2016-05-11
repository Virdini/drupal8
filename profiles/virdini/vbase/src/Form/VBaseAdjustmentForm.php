<?php
/**
 * @file
 * Contains \Drupal\vbase\Form\VBaseAdjustmentForm.
 */

namespace Drupal\vbase\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * All form elements for design adjustment.
 */
class VBaseAdjustmentForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vbase_adjustment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['tabs'] = array(
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-text',
    );
    $form['text'] = array(
      '#type' => 'details',
      '#title' => 'Text',
      '#group' => 'tabs',
      'content' => array('#theme' => 'vbase_adjustment_text'),
    );
    $form['table'] = array(
      '#type' => 'details',
      '#title' => 'Drupal Table',
      '#group' => 'tabs',
    );
    $form['table']['table'] = array(
      '#type' => 'table',
      '#group' => 'tabs',
      '#header' => array(t('Label'), t('Machine name'), t('Weight'), t('Operations')),
      '#empty' => t('There are no items yet.'),
      // TableSelect: Injects a first column containing the selection widget into
      // each table row.
      // Note that you also need to set #tableselect on each form submit button
      // that relies on non-empty selection values (see below).
      '#tableselect' => TRUE,
      // TableDrag: Each array value is a list of callback arguments for
      // drupal_add_tabledrag(). The #id of the table is automatically prepended;
      // if there is none, an HTML ID is auto-generated.
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'mytable-order-weight',
        ),
      ),
    );
    // Build the table rows and columns.
    // The first nested level in the render array forms the table row, on which you
    // likely want to set #attributes and #weight.
    // Each child element on the second level represents a table column cell in the
    // respective table row, which are render elements on their own. For single
    // output elements, use the table cell itself for the render element. If a cell
    // should contain multiple elements, simply use nested sub-keys to build the
    // render element structure for drupal_render() as you would everywhere else.
    for ($i = 0; $i < 5; $i++) {
      // TableDrag: Mark the table row as draggable.
      $form['table']['table'][$i]['#attributes']['class'][] = 'draggable';
      // TableDrag: Sort the table row according to its existing/configured weight.
      $form['table']['table'][$i]['#weight'] = $i;
  
      // Some table columns containing raw markup.
      $form['table']['table'][$i]['label'] = array(
        '#plain_text' => 'Text'. $i,
      );
      $form['table']['table'][$i]['id'] = array(
        '#plain_text' => 'Text'. $i,
      );
  
      // TableDrag: Weight column element.
      $form['table']['table'][$i]['weight'] = array(
        '#type' => 'weight',
        '#title' => 'Weight for',
        '#title_display' => 'invisible',
        '#default_value' => $i,
        // Classify the weight element for #tabledrag.
        '#attributes' => array('class' => array('mytable-order-weight')),
      );
  
      // Operations (dropbutton) column.
      $form['table']['table'][$i]['operations'] = array(
        '#type' => 'operations',
        '#links' => array(),
      );
      $form['table']['table'][$i]['operations']['#links']['edit'] = array(
        'title' => 'Edit',
        'url' => Url::fromRoute('vbase.adjustment'),
      );
      $form['table']['table'][$i]['operations']['#links']['delete'] = array(
        'title' => 'Delete',
        'url' => Url::fromRoute('vbase.adjustment'),
      );
    }
    $form['form'] = array(
      '#type' => 'details',
      '#title' => 'Form Elements',
      '#group' => 'tabs',
    );
    $form['form']['example_textfield'] = array(
      '#type' => 'textfield',
      '#title' => 'Textfield element',
      '#required' => TRUE,
      '#placeholder' => 'Placeholder',
      '#description' => 'Description textfield element',
      //'#field_prefix' => 'Field prefix',
      //'#field_suffix' => 'Field suffix',
    );
    $form['form']['example_autocomplete'] = array(
      '#type' => 'textfield',
      '#title' => 'Textfield autocomplete element',
      '#required' => TRUE,
      '#placeholder' => 'Placeholder',
      '#description' => 'Description textfield autocomplete element',
      '#autocomplete_route_name' => 'vbase.adjustment',
      '#autocomplete_route_parameters' => array(),
    );
    $form['form']['example_file'] = array(
      '#type' => 'file',
      '#title' => 'File element',
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#placeholder' => 'Placeholder',
      '#description' => 'Description file element',
      //'#field_prefix' => 'Field prefix',
      //'#field_suffix' => 'Field suffix',
    );
    $form['form']['example_password'] = array(
      '#type' => 'password',
      '#required' => TRUE,
      '#title' => 'Password element',
      '#placeholder' => 'Placeholder',
      '#description' => 'Description password element',
    );
    $form['form']['example_password_confirm'] = array(
      '#type' => 'password_confirm',
      '#required' => TRUE,
      '#title' => 'Password confirm element',
      '#placeholder' => 'Placeholder',
      '#description' => 'Description password confirm element',
      '#markup' => 'Field markup',
    );
    $form['form']['example_select'] = array(
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => 'Select element',
      '#description' => 'Description select element',
      '#options' => array(
        '1' => '1 - option',
        '2 - option group' => array(
          '2.1' => '2.1 - option',
          '2.2' => '2.2 - option',
        ),
        '3' => '3 - option',
      ),
    );
    $form['form']['example_select1'] = array(
      '#type' => 'select',
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#title' => 'Select multiple element',
      '#description' => 'Description select multiple element',
      '#options' => array(
        '1' => '1 - option',
        '2 - option group' => array(
          '2.1' => '2.1 - option',
          '2.2' => '2.2 - option',
        ),
        '3' => '3 - option',
      ),
    );
    $form['form']['example_checkbox'] = array(
      '#type' => 'checkbox',
      '#title' => 'Checkbox element',
      '#return_value' => 1,
      '#required' => TRUE,
      '#description' => 'Description checkbox element',
    );
    $form['form']['example_checkboxes'] = array(
      '#type' => 'checkboxes',
      '#required' => TRUE,
      '#options' => array(
        'c1' => 'Checkbox element 1',
        'c2' => 'Checkbox element 2',
        'c3' => 'Checkbox element 3',
      ),
      '#title' => 'Checkboxes element',
      '#description' => 'Description checkboxes element',
    );
    $form['form']['example_radios'] = array(
      '#type' => 'radios',
      '#required' => TRUE,
      '#options' => array(
        'c1' => 'Radios element 1',
        'c2' => 'Radios element 2',
        'c3' => 'Radios element 3',
      ),
      '#title' => 'Radios element',
      '#description' => 'Description radios element',
    );
    $form['form']['details'] = array(
      '#type' => 'details',
      '#description' => 'Description details element',
      '#title' => 'Details element',
      '#open' => FALSE,
      '#value' => 'Value details element',
    );
    $form['form']['details']['example_textfield'] = array(
      '#type' => 'textfield',
      '#title' => 'Textfield element',
      '#required' => TRUE,
      '#placeholder' => 'Placeholder',
      '#description' => 'Description textfield element',
    );
    $form['form']['fieldset'] = array(
      '#type' => 'fieldset',
      '#description' => 'Description fieldset element',
      '#title' => 'Fieldset element',
    );
    $form['form']['fieldset']['example_textfield'] = array(
      '#type' => 'textfield',
      '#title' => 'Textfield element',
      '#required' => TRUE,
      '#placeholder' => 'Placeholder',
      '#description' => 'Description textfield element',
    );
    $form['form']['fieldgroup'] = array(
      '#type' => 'fieldgroup',
      '#description' => 'Description fieldgroup element',
      '#title' => 'Fieldgroup element',
    );
    $form['form']['fieldgroup']['example_textfield'] = array(
      '#type' => 'textfield',
      '#title' => 'Textfield element',
      '#required' => TRUE,
      '#placeholder' => 'Placeholder',
      '#description' => 'Description textfield element',
    );
    $form['form']['example_textarea'] = array(
      '#type' => 'textarea',
      '#title' => 'Textarea element',
      '#required' => TRUE,
      '#placeholder' => 'Placeholder',
      '#description' => 'Description textarea element',
    );
    $form['form']['actions'] = array('#type' => 'actions');
    $form['form']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Actions submit',
    );
    $form['form']['actions']['button'] = array(
      '#type' => 'button',
      '#value' => 'Actions button',
    );
    $form['form']['actions1'] = array('#type' => 'actions');
    $form['form']['actions1']['extra_actions'] = array(
      '#type' => 'dropbutton',
      '#links' => array(
        'simple_form' => array(
          'title' => 'Actions Dropbutton Link 1',
          'url' => Url::fromRoute('vbase.adjustment'),
        ),
        'demo' => array(
          'title' => 'Actions Dropbutton Link 2',
          'url' => Url::fromRoute('vbase.adjustment'),
        ),
      ),
    );
    $form['form']['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Submit',
    );
    $form['form']['button'] = array(
      '#type' => 'button',
      '#value' => 'Button',
    );
    $form['form']['extra_actions'] = array(
      '#type' => 'dropbutton',
      '#links' => array(
        'simple_form' => array(
          'title' => 'Dropbutton Link 1',
          'url' => Url::fromRoute('vbase.adjustment'),
        ),
        'demo' => array(
          'title' => 'Dropbutton Link 2',
          'url' => Url::fromRoute('vbase.adjustment'),
        ),
      ),
    );
    return $form;
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
    
  }

}
