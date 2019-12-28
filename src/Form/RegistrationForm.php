<?php

namespace Drupal\register_multiple\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\erf\Entity\Registration;

/**
 * Implements the SimpleForm form controller.
 *
 * This example demonstrates a simple form with a single text input element. We
 * extend FormBase which is the simplest form base class used in Drupal.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class RegistrationForm extends FormBase {

  
  const SETTINGS = 'register_multiple.settings';
  /**
   * Build the simple form.
   *
   * A build form method constructs an array that defines how markup and
   * other form elements are included in an HTML form.
   *
   * @param array $form
   *   Default form array structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object containing current form state.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  
  private function getRegistrationSettings(){
    
    $settings = array();
    
    $config = $this->config(static::SETTINGS);
    $settings['node_type'] = $config->get('node_type');
    $settings['registration_field'] = $config->get('registration_field');
    $settings['registration_start'] = $config->get('registration_start');
    $settings['registration_end'] = $config->get('registration_end');
    $settings['registration_instructions'] = $config->get('registration_instructions');
    
    return $settings;
  }
  
  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL) {
 
    $registration_list = array ();
    
    if (is_null($user)) {
      $current_user = \Drupal::currentUser()->id();
	} else {
      $current_user = $user->id();
    }
    
    $settings = $this->getRegistrationSettings();
    
    
    //Get all nodes of type match
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1) //published or not
      ->condition('type', $settings['node_type']) //content type
//      ->pager(10); //specify results to return
      ;
    $nids = $query->execute();
    
    
    //get the default form structure
    $bundle = array('type' => 'default',);
    $entity = Registration::create($bundle);
    $registration_form = \Drupal::service('entity.form_builder')->getForm($entity, 'default');
     
    $existing_registrations = Registration::loadMultiple();
    
    //cycle through all of the matches   
    foreach ($nids as $nid) {
      $node = \Drupal\node\Entity\Node::load($nid);
      $title = $node->title->value;
      $date = $node->field_match_date->value;
      $start_date = $settings['registration_start'];
      $end_date = $settings['registration_end'];
    
      // for all nodes in the future
      if (($date >= $start_date) & ($date <= $end_date)  /* & ($date >= $today) */ ) {

        //check if the registration already exists
        $existing_reg = 0;
        foreach ($existing_registrations as $reg_key => $reg_values) {
          $reg_user = $reg_values->get('user_id')->getValue() ['0'] ['target_id'];
          $reg_match = $reg_values->get($settings['registration_field'])->getValue() ['0'] ['target_id'];
          
          if (($reg_user == $current_user) AND ($reg_match == $nid)) {
            //Current user has already registered for this match, so default to existing data
            $existing_reg = $reg_key;
          }
        }
        
        //If we had a matching existing registration, then lets load it
        if ($existing_reg > 0) {
          $this_registration = Registration::load($existing_reg);
        }
        
        
        $registration_list [$nid] = [
            '#type' => 'details',
            '#title' => $title . ' - ' . (date ( "l, jS F Y", (strtotime ( $date )) )),
            '#sortkey' => (date ( "Y m d", (strtotime ( $date )) ) . '.' . $nid . '.00')
        ];
        
        $registration_list [$nid] ['#sortkey'] = (date ( "Y m d", (strtotime ( $date )) ) . '.' . $nid . '.0');
        
        $registration_list [$nid][$nid . '.title'] = [
            '#markup' => '<H1><strong>' . $title . '</strong></H1> ' . (date ( "l, jS F Y", (strtotime ( $date )) )),
            '#weight' => 0, // move to the top of the list
            '#sortkey' => (date ( "Y m d", (strtotime ( $date )) ) . '.' . $nid . '.00')
        ];
        
        foreach ($registration_form as $key => $value)
        {
          
          $field_key = $nid . '|'. $key;
          
  
          if (substr($key, 0, 5) == 'field') {
            //copy across the field definitions from the template form
            $registration_list [$nid][$field_key] = $registration_form [$key];
            $registration_list [$nid][$field_key] ['#sortkey'] = (date ( "Y m d", (strtotime ( $date )) ) . '.' . $nid . '.' . $registration_form [$key] ['#weight']);
            $registration_list [$nid][$field_key] ['widget'] ['#field_name'] =  $field_key;
            $registration_list [$nid][$field_key] ['widget'] ['#name'] =  $field_key;
            $registration_list [$nid][$field_key] ['widget'] ['#parents'] [0] =  $key; //$field_key;
            $registration_list [$nid][$field_key] ['widget'] ['#array_parents'] [0] =  $key; //$field_key;
            
            if ($existing_reg > 0) {
              //Set the value to be the same as the existing registration
              $value_array = $this_registration->get($key)->getValue();
              if (is_array($value_array)) {
                $defaultvalue = $this_registration->get($key)->getValue() ;
                if (isset($defaultvalue ['0']['target_id'])) {
                  $registration_list [$nid][$field_key] ['widget'] ['#value'] =  $defaultvalue ['0']['target_id'];
                } elseif (isset($defaultvalue ['0']['value'])) {
                  $registration_list [$nid][$field_key] ['widget'] ['#value'] =  $defaultvalue ['0']['value'];
                }
              }
            }
          
            if ($key == 'field_registration_match') {
              //and then do some additional manipulations for the match field
              $defaultvalueformatch = array(0 => 'node-'.$nid);
              $registration_list [$nid][$field_key] ['widget'] ['#value'] =  $defaultvalueformatch;
              $registration_list [$nid][$field_key] ['widget'] ['#access'] =  FALSE;
              
            }
            
            
            if ($key == 'field_registration_rifle') {
              $registration_list [$nid][$field_key] ['widget'] [0] ['target_id']['#selection_settings']['view']['arguments'][0] = 1;
            }
            
            
            if (isset($registration_list [$nid][$field_key] ['widget'] [0])) {
              //likely to be a text field, so need another layer of setting the name
              $registration_list [$nid][$field_key] ['widget'] [0] ['value'] ['#name'] =  $field_key . '[0][value]';
              $registration_list [$nid][$field_key] ['widget'] [0] ['value'] ['#parents'] [0] =  $key; //$field_key;
              $registration_list [$nid][$field_key] ['widget'] [0] ['value'] ['#array_parents'] [0] =  $key; //$field_key;
              $registration_list [$nid][$field_key] ['widget'] [0] ['#array_parents'] [0] =  $key; //$field_key;
              $registration_list [$nid][$field_key] ['widget'] [0] ['#parents'] [0] =  $key; //$field_key;
              
              
              if ($existing_reg > 0) {
                //Set the value to be the same as the existing registration
                $value_array = $this_registration->get($key)->getValue();
                if (is_array($value_array)) {
                  $defaultvalue = $this_registration->get($key)->getValue();
                  if (isset($defaultvalue ['0']['target_id'])) {  // $defaultvalue was set a few lines above
                    $registration_list [$nid][$field_key] ['widget'] [0] ['value'] ['#value'] =  $defaultvalue ['0']['target_id'];
                  } elseif (isset($defaultvalue ['0']['value'])) {
                    $registration_list [$nid][$field_key] ['widget'] [0] ['value'] ['#value'] =  $defaultvalue ['0']['value'];
                  }
                }
              }
            }
          }
          
        }
        

      }
    }
    
    //Execute the sort of the array - sort key is the date
    $weight = 1;
    $registration_list = $this->array_sort ( $registration_list, '#sortkey', SORT_ASC );
    foreach ($registration_list as $key => $value) {
      $registration_list[$key]['#weight'] = $weight;
      $weight = $weight + 1;
    }
 
    
    $form ['registration_entity'] = $registration_list;
    $form ['registration_entity'] ['#tree'] = FALSE;
    
    $form['Instructions'] = [
      '#type' => 'item',
      '#markup' => $settings['registration_instructions'],
      '#weight' => -1000000,
    ];
    
    $form['User'] = [
        '#type' => 'textfield',
        '#value' => $current_user,
        '#access' => FALSE,
    ];
    
    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Getter method for Form ID.
   *
   * The form ID is used in implementations of hook_form_alter() to allow other
   * modules to alter the render array built by this form controller. It must be
   * unique site wide. It normally starts with the providing module's name.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'register_multiple_registration';
  }

  /**
   * Implements form validation.
   *
   * The validateForm method is the default method called to validate input on
   * a form.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    /*
    $title = $form_state->getValue('title');
    if (strlen($title) < 5) {
      // Set an error for the form element with a key of "title".
      $form_state->setErrorByName('title', $this->t('The title must be at least 5 characters long.'));
    }
    */
  }

  /**
   * Implements a form submit handler.
   *
   * The submitForm method is the default method called for any submit elements.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /*
     * This would normally be replaced by code that actually does something
     * with the title.
     */
    $form_values = $form_state->getValues(array());
    $form_input = $form_state->getUserInput(array());
    $user = $form_values['User'];
    
    $settings = $this->getRegistrationSettings();
    
    if (is_null($user)) {
        $current_user = \Drupal::currentUser()->id();
    } else {
        $current_user = $user;
    }
    
    //Get all nodes of type match
    $query = \Drupal::entityQuery('node')
    ->condition('status', 1) //published or not
    ->condition('type', $settings['node_type']) //content type
    //      ->pager(10); //specify results to return
    ;
    $nids = $query->execute();
    
    //create the entity registration form
    $bundle = array('type' => 'default',);
    $existing_registrations = Registration::loadMultiple();
    
    //cycle through all of the matches
    foreach ($nids as $nid) {
      $new_form_state = array();
      
      
      //check if the registration already exists
      $existing_reg = 0;
      foreach ($existing_registrations as $reg_key => $reg_values) {
        $reg_user = $reg_values->get('user_id')->getValue() ['0'] ['target_id'];
        $reg_match = $reg_values->get($settings['registration_field'] )->getValue() ['0'] ['target_id'];
        
        if (($reg_user == $current_user) AND ($reg_match == $nid)) {
          //Current user has already registered for this match, so default to existing data
          $existing_reg = $reg_key;
        }
      }
      
      if ($existing_reg > 0) {
        //if registration exists then set the entity to be the existing one
        $entity = Registration::load($existing_reg);
      } else {
        //if registration doesn't exist yet then create new item
        $entity = Registration::create($bundle);
      }
      
      //first, set the match
      $inner_array = array('target_id' => $nid, 'target_type' => 'node');
      $value_array = array(0 => $inner_array);
      $entity->set($settings['registration_field'] ,$value_array,TRUE);
      $is_valid_nid = FALSE;
      
      foreach ($form_input as $field=>$field_value) {
        if (strpos($field, '|') !== false) {
          $form_nid = substr($field,0,strpos($field, '|'));
          $field_name = substr($field,strpos($field, '|')+1);
          if ($form_nid == $nid) {
            $is_valid_nid = TRUE;
            
            if ($field_name != $settings['registration_field'] ) {
              if (is_array($field_value[0])) {
                $inner_array = array('value' => $field_value[0]['value'], 'target_id' => $field_value[0]['value']);
              } else {
                $inner_array = array('value' => $field_value, 'target_id' => $field_value);
              }
              $value_array = array(0 => $inner_array);
              $entity->set($field_name,$value_array,TRUE);
            }
          }
       }
      }
      if ($is_valid_nid == TRUE) {
        // only save the entity if the NID existed on the form
        $entity->set('user_id', $current_user);
        $entity->save();
      }
    }
    
    $this->messenger()->addMessage(t('Registration for matches successful.'));
    
    
  }

  
  function array_sort($array, $on, $order = SORT_ASC) {
    
    $new_array = array ();
    $sortable_array = array ();
    
    if (count ( $array ) > 0) {
      foreach ( $array as $k => $v ) {
        if (is_array ( $v )) {
          foreach ( $v as $k2 => $v2 ) {
            if ($k2 == $on) {
              $sortable_array [$k] = $v2;
            }
          }
        } else {
          $sortable_array [$k] = $v;
        }
      }
      
      switch ($order) {
        case SORT_ASC :
          asort ( $sortable_array );
          break;
        case SORT_DESC :
          arsort ( $sortable_array );
          break;
      }
      
      foreach ( $sortable_array as $k => $v ) {
        $new_array [$k] = $array [$k];
      }
    }
    
    return $new_array;
  }
}
