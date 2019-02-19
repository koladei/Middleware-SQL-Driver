<?php

namespace Drupal\middleware_sql_driver\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a form that configures forms module settings.
 */
class ConfigurationForm extends ConfigFormBase {

    private $delta = 0;

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'middleware_sql_driver_admin_settings';
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
        
        $form['sql'] = [
            '#type' => 'details',
            '#title' => $this->t('SQL Driver Settings'),
            '#details' => $this->t('SQL server settings'),
            '#tree' => TRUE
        ];

        $local_form = &$form['sql'];
        // This section allows you to add new configuration node.

        if(!is_null($parent_form)){
            $form['sql']['#group'] = 'information';
        }

        $local_form['connections'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Exising configurations')
        ];

        // $config->delete('connections')->save();
        if(!is_null($config->get('connections'))){
            $connections = $config->get('connections');
            foreach($connections as $name => $connection){
                $local_form['connections'][$name] = [
                    '#type' => 'fieldset',
                    '#title' => $this->t($name),
                    '#colapsible' => TRUE,
                    '#colapsed' => TRUE,
                    '#suffix' => '<hr/>',
                ];
                
                $local_form['connections'][$name]['ConnectionString'] = [
                    '#type' => 'textfield',
                    '#title' => $this->t('Connection string'),
                    '#default_value' => (isset($connection['ConnectionString'])?$connection['ConnectionString']:'')
                ];
                
                $local_form['connections'][$name]['Username'] = [
                    '#type' => 'textfield',
                    '#title' => $this->t('Username'),
                    '#default_value' => (isset($connection['Username'])?$connection['Username']:'')
                ];
                
                $local_form['connections'][$name]['Password'] = [
                    '#type' => 'password',
                    '#title' => $this->t('Password'),
                    '#attributes' => [
                        'value' => isset($connection['Password'])?$connection['Password']:''
                    ]
                ];
                
                $local_form['connections'][$name]['DatabaseType'] = [
                    '#type' => 'select',
                    '#title' => $this->t('Database Type'),
                    '#options' => ['mysql' => $this->t('MySQL'), 'mssql' => $this->t('Microsoft SQL Server')],
                    '#default_value' => (isset($connection['DatabaseType'])?$connection['DatabaseType']:'')
                ];

                $local_form['connections'][$name]['save'] = [
                    '#type' => 'submit',
                    '#value' => $this->t('Save')
                ];

                $local_form['connections'][$name]['remove'] = [
                    '#type' => 'submit',
                    '#value' => $this->t('Remove')
                ];
                $local_form['connections'][$name]['remove']['#submit'][] = [$this, 'updateConnectionInstance'];
                $local_form['connections'][$name]['remove']['#submit'][] = [$this, 'removeConnectionInstance'];
            }
        }

        // This section allows you to add new configuration node.
        $local_form['add_group'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Add new configuration'),
            '#prefix' => '<hr/>',
            '#tree' => TRUE
        ];

        $local_form['add_group']['config_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Label'),
            '#default_value' => $this->t('default'),
        ];

        $local_form['add_group']['add'] = [
            '#type' => 'submit',
            '#value' => $this->t('Add +'),
        ];

        $local_form['add_group']['add']['#submit'][] =[$this, 'addConnectionInstance'];
        $local_form['add_group']['add']['#validate'][] = [$this, 'validateAddConnectionInstance'];

        // In case this form was invoked directly.
        if(is_null($parent_form)){
            return parent::buildForm($form, $form_state);
        } else {
            return $form;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $values = $form_state->getValues()['sql'];
        $this->config('middleware_sql_driver.settings')
          ->set('connections', $values['connections'])
          ->save();
        parent::submitForm($form, $form_state);
    }

    public function validateRemoveConnectionInstance(array &$form, FormStateInterface $form_state){
        
    }

    public function updateConnectionInstance(array &$form, FormStateInterface $form_state){
        

        $element = $form_state->getTriggeringElement();
        $parents = $element['#parents'];
        $values = $form_state->getValues()['sql'];
        $instanceName = strtolower($values['add_group']['config_name']);  

        $connection = $values['connections'][$parents[2]];//isset($values['connections'])?$values['connections'][$parents[1]]:$values['connections'][$parents[2]];
        // return;


        $this->config('middleware_sql_driver.settings')
          ->set("{$parents[1]}.{$parents[2]}", $connection)
          ->save();

        // parent::submitForm($form, $form_state);
        drupal_set_message($this->t('Instance @name added', [
            '@name' => $instanceName
        ]));
    }

    public function removeConnectionInstance(array &$form, FormStateInterface $form_state){
        $element = $form_state->getTriggeringElement();
        $parents = $element['#parents'];
        
        $this->config('middleware_sql_driver.settings')
          ->clear("connections.{$parents[2]}")
          ->save();
        
        drupal_set_message($this->t('Instance <b>@name</b> removed successfully', [
            '@name' => $parents[1]
        ]));
    }

    public function addConnectionInstance(array &$form, FormStateInterface $form_state){
        $values = $form_state->getValues()['sql'];
        $connections = isset($values['connections'])?$values['connections']:[];     
        $connections[$values['add_group']['config_name']] = [];
        $instanceName = strtolower($values['add_group']['config_name']);  

        $this->config('middleware_sql_driver.settings')
          ->set('connections', $connections)
          ->save();
        parent::submitForm($form, $form_state);
        drupal_set_message($this->t('Instance @name added', [
            '@name' => $instanceName
        ]));
    }

    public function validateAddConnectionInstance(array &$form, FormStateInterface $form_state){
        $local_form = &$form['sql'];
        
        $config = $this->config('middleware_sql_driver.settings');
        $values = $form_state->getValues()['sql'];

        // Check the instance name.
        $instanceName = strtolower($values['add_group']['config_name']);        
        if(\strlen($instanceName) < 1) {
            $form_state->setError($local_form['add_group']['config_name'], 'Name cannot be blank: ');
            return;
        } else if(!is_null($config->get("connections.{$instanceName}"))){
            // Check that the name does not already exist.
            $form_state->setError($local_form['add_group']['config_name'], $this->t('An instance with the same name (@name) exists', [
                '@name' => $instanceName
            ]));
        }
    }
}