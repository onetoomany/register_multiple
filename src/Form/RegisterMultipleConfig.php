<?php

namespace Drupal\register_multiple\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\erf\Entity\Registration;

/**
 * Configure example settings for this site.
 */
class RegisterMultipleConfig extends ConfigFormBase {

  /** 
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'register_multiple.settings';

  
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'register_multiple_settings';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    
    //Get all the nodes to display as a drop-down
    $node_types = \Drupal\node\Entity\NodeType::loadMultiple();
    // If you need to display them in a drop down:
    $options = [];
    foreach ($node_types as $node_type) {
      $options[$node_type->id()] = $node_type->label();
    }
    
    $form['node_type'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Select what type of node is used to registering against'),
      '#default_value' => $config->get('node_type'),
      '#attributes' => [
            'style' => 'width: 30em;'
        ],
    ];  

    
    //Get all the fields from the registration form
    //First, get the default form structure
    $bundle = array('type' => 'default',);
    $entity = Registration::create($bundle);
    $registration_form = \Drupal::service('entity.form_builder')->getForm($entity, 'default');
    
    $options = array();
    foreach ($registration_form as $key => $value) {
      if (substr($key, 0, 5) == 'field') {
        $options[$key] = $key;
      }
    }
      
    $form['registration_field'] = [
        '#type' => 'select',
        '#options' => $options,
      '#title' => $this->t('Select what field name is used to store the registration'),
        '#default_value' => $config->get('registration_field'),
        '#attributes' => [
            'style' => 'width: 30em;'
        ],
    ];  
    
    $form['registration_instructions'] = [
        '#type' => 'text_format',
        '#format' => 'full_html',
        '#title' => $this->t('Instructions for the users to complete their registration.'),
        '#default_value' => $config->get('registration_instructions'),
    ];  
    
    $form['registration_start'] = [
        '#type' => 'date',
        '#title' => $this->t('Select minimum date for registration'),
        '#default_value' => $config->get('registration_start'),
    ];  
    
    $form['registration_end'] = [
        '#type' => 'date',
        '#title' => $this->t('Select maximum date for registration'),
        '#default_value' => $config->get('registration_end'),
    ];  
    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $debug = $form_state->getValue('registration_instructions');
    
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('node_type', $form_state->getValue('node_type'))
      ->set('registration_field', $form_state->getValue('registration_field'))
      ->set('registration_start', $form_state->getValue('registration_start'))
      ->set('registration_end', $form_state->getValue('registration_end'))
      ->set('registration_instructions', $form_state->getValue('registration_instructions')['value'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}