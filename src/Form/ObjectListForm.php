<?php

namespace Drupal\middleware_sql_driver\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a form that configures forms module settings.
 */
class ObjectListForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'middleware_sql_driver_object_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'middleware_sql_driver.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $my_form, FormStateInterface $form_state, $parent_form = NULL) {
        $form = !is_null($parent_form)? $parent_form: $my_form;
        $local_form = &$form;

        $config = $this->config('middleware_sql_driver.settings');
        // $config->delete('objects')->save();
        
        $form['sql'] = [
            '#type' => 'details',
            '#title' => $this->t('SQL Objects'),
            '#tree' => TRUE
        ];

        $local_form = &$form['sql'];
        // This section allows you to add new configuration node.

        if(!is_null($parent_form)){
            $form['sql']['#group'] = 'information';
        }

        // Get the list of objects from the config store
        if(is_null($config->get('objects'))){
            $config->set('objects', [])->save();
        }
        $object_list = $config->get('objects');
        
        $options = [];
        foreach($object_list as $name => $data)
        {
            $options[$name] = [
                'Name' => $this->l(strtoupper($name), Url::fromUri("internal:/middleware_core/objects/sql/{$data['internal_name']}")), 
                'InternalName' => $data['internal_name'],// Url::fromUri('internal:/reports/search')), 
                'Description' => $data['description'],
                'Actions' => [
                    '#type' => 'markup',
                    // 'delete' => [
                    //     '#type' => [
                    //         '#type' => 'submit',
                    //         '#value' => $this->t('Delete')
                    //     ]
                    // ]
                ]
            ];
        }

        $local_form['table'] = [
            '#type' => 'table',
            '#header' => [
                'Name' => $this->t('Object name'),
                'InternalName' => $this->t('Internal name'),
                'Description' => $this->t('Description'),
                'Actions' => $this->t('Actions'),
            ],
            '#rows' => $options,   
        ];

        // This section allows you to add new configuration node.
        $local_form['add_group'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Add new configuration'),
            '#prefix' => '<hr/>',
            '#tree' => TRUE
        ];

        $local_form['add_group']['display_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Object label'),
            '#default_value' => '',
            '#attributes' => [
                'placeholder' => $this->t('Name of the entity')
            ],
        ];

        $local_form['add_group']['internal_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Internal name'),
            '#default_value' => '',
            '#attributes' => [
                'placeholder' => $this->t('Internal name of the entity')
            ],
        ];

        $local_form['add_group']['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Description'),
            '#format' => 'plain',
            '#default_value' => '',
            '#attributes' => [
                'placeholder' => $this->t('Describe this object')
            ]
        ];

        $local_form['add_group']['add'] = [
            '#type' => 'submit',
            '#value' => $this->t('Add +'),
        ];

        $local_form['add_group']['add']['#submit'][] =[$this, 'addObject'];
        $local_form['add_group']['add']['#validate'][] = [$this, 'validateAddObject'];
        

        // In case this form was invoked directly.
        if(is_null($parent_form)){
            return parent::buildForm($form, $form_state);
        } else {
            return $form;
        }
    }

    public function addObject(array &$form, FormStateInterface $form_state){
        $values = $form_state->getValues()['sql'];
        $objects = isset($values['objects'])?$values['objects']:[];     
        $displayName = ($values['add_group']['display_name']);
        $internalName = strtolower($values['add_group']['internal_name']);  
        $description = strtolower($values['add_group']['description']);  
        // $objects[$values['add_group']['display_name']] = [];

        $this->config('middleware_sql_driver.settings')
          ->set("objects.{$displayName}", [
            'internal_name' => $internalName,
            'description' => $description, 
            'datasource' => 'default',
            'fields' => []
          ])
          ->save();

        // parent::submitForm($form, $form_state);
        drupal_set_message($this->t('Instance @name added', [
            '@name' => $displayName
        ]));
    }

    public function validateAddObject(array &$form, FormStateInterface $form_state){
        $local_form = &$form['sql'];
        
        $config = $this->config('middleware_sql_driver.settings');
        $values = $form_state->getValues()['sql'];

        // Check the instance name.
        $displayName = strtolower($values['add_group']['display_name']);  
        $internalName = strtolower($values['add_group']['internal_name']);        
        if((\strlen($displayName) < 1)) {
            $form_state->setError($local_form['add_group']['display_name'], 'Name cannot be blank: ');
            return;
        } else if((\strlen($internalName) < 1)) {
            $form_state->setError($local_form['add_group']['internal_name'], 'Internal name cannot be blank: ');
            return;
        } else if(!is_null($config->get("objects.{$displayName}"))){
            // Check that the name does not already exist.
            $form_state->setError($local_form['add_group']['display_name'], $this->t('An instance with the same name (@name) exists', [
                '@name' => $displayName
            ]));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        
    }
}