<?php
/**
 * @file
 * Contains \Drupal\json_forms\Form\AdminArticleForm.
 */

namespace Drupal\json_forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\JsonFormsArticle;

class AdminArticleForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'json_forms.admin_article';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $article_id = NULL, $action = NULL) {
    // $first will be 'add', or the article Id
    // $second will be blank or an action 'edit' or 'delete'
    // like:
    // /article/add
    // /article/dpt-2342423-234234234-/edit
    // /article/dpt-2342423-234234234-/delete

    $this_article_data = array();

    //$enabled = \Drupal::config('system.performance')->get('cache.page.enabled');

    $jf = new \Drupal\json_forms\JsonFormsArticle(); // use our interface class; connects to EDAN by default

    try {
      $articles_schema = $jf->adminGetArticleSchema();
    }
    catch(Exception $ex) {
      drupal_set_message(t("Unable to retrieve article schema: %err", array('%err' => $ex->getMessage())));
      return $form;
    }

    if($action == 'edit' && NULL !== $article_id) {
      try {
        $this_article_data = $jf->adminGetArticleById($article_id);
      }
      catch(Exception $ex) {
        drupal_set_message(t("Unable to retrieve article schema: %err", array('%err' => $ex->getMessage())));
        return $form;
      }
      $this_article_data = $this->_generateFlattenedFromArray($this_article_data);
    }

    $form = array();

    // loop through the schema, generating the form
    if(isset($articles_schema->schema->properties) && isset($articles_schema->authoringForm)) {

      $article_properties = $articles_schema->schema->properties;
      $author_form = $articles_schema->authoringForm;

      $i = 0;
      foreach($author_form as $field_name => $field_or_fieldset) {
        //dpm($field_name);
        //dpm((array)$field_or_fieldset);

        $fieldset = NULL;
        if(isset($field_or_fieldset->fields)) {
          // create the fieldset
          $field = $field_or_fieldset;
          $field->field_name = $field_name;
          if(isset($this_article_data[$field_name])) {
            $field->default_value = $this_article_data[$field_name];
          }
          $f = $this->_generateFormField($field);
          if(!empty($f)) { $form += $f; }

          // create the child fields
          $parent_field = $field_name;
          foreach($field_or_fieldset->fields as $child_field_name => $child_field) {
            $field = $child_field;
            $field->field_name = $child_field_name;
            if(isset($this_article_data[$parent_field][$child_field_name])) {
              if(is_array($this_article_data[$parent_field][$child_field_name])) {
                $field->default_value = implode('\r\n', $this_article_data[$parent_field][$child_field_name]);
              }
              else {
                $field->default_value = $this_article_data[$parent_field][$child_field_name];
              }
            }
            elseif(isset($this_article_data[$parent_field][0][$child_field_name])) {
              $field->default_value = $this_article_data[$parent_field][0][$child_field_name];
            }

            $field->parent_field = $parent_field;

            $f = $this->_generateFormField($field);
            if(!empty($f)) { $form[$field_name] += $f; }

          }
        }
        elseif(isset($field_or_fieldset->ux->field_type)) {
          // add item to $form array
          $field = $field_or_fieldset;
          $field->field_name = $field_name;
          if(isset($this_article_data[$field_name])) {
            $field->default_value = $this_article_data[$field_name];
          }
		    elseif(isset($this_article_data['content'][$field_name])) {
            $field->default_value = isset($this_article_data['content'][$field_name]) ? $this_article_data['content'][$field_name] : '';
          }
          $f = $this->_generateFormField($field);
          if(!empty($f)) { $form += $f; }

        }

        $i++;
      }
    }

    $form['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    //return parent::buildForm($form, $form_state);
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  //array &$form, Drupal\Core\Form\FormStateInterface $form_state
  public function validateForm(array &$form, FormStateInterface $form_state) {

    //@todo validate
//      dpm($form);
//    dpm($form_state);

    // check required, field values

  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // perform save- new or existing

    //    parent::submitForm($form, $form_state);
    $article_array = $this->_generateArrayFromFormValues($form, $form_state);
    $article_array['type'] = 'article';

    $jf = new \Drupal\json_forms\JsonFormsArticle(); // use our interface class; connects to EDAN by default
    $return = $jf->adminSaveArticle($article_array);


    //dpm($return);
    if(isset($return['error'])) {
      drupal_set_message($this->t($return['error']), 'error');
      //dpm($return['response']);
    }
    else {
      drupal_set_message($this->t('Done saving!'));
      // redirect to the list of articles
      $form_state->setRedirect('json_forms.admin_get_all_articles');
      return;
    }

  }

  private function _generateFormField($field_data) {

    $f = array();

    $field_name = isset($field_data->parent_field)
      ? $field_data->parent_field . '__' . $field_data->field_name
      : $field_data->field_name;

    switch($field_data->ux->field_type) {
      case 'hidden':
        $f[$field_name] = array(
          '#type' => 'hidden'
        );
        break;

      case 'fieldset':
        $f[$field_name] = array(
          '#type' => 'fieldset',
          '#title' => isset($field_data->label) ? $field_data->label : $field_name
        );
        break;

      case 'textarea':
        $f[$field_name] = array(
          '#type' => 'textarea',
          '#title' => isset($field_data->label) ? $field_data->label : $field_name
        );
        break;

      case 'text':
        //@todo - no, use multiple textareas; for now we separate by linebreak
        if(isset($field_data->multiple) && TRUE == $field_data->multiple) {
          $f[$field_name] = array(
            '#type' => 'textarea',
            '#title' => isset($field_data->label) ? $field_data->label : $field_name
          );
        }
        else {
          $f[$field_name] = array(
            '#type' => 'textfield',
            '#title' => isset($field_data->label) ? $field_data->label : $field_name
          );
        }
        break;

      case 'radios':
        // look for enum
        $values = array(1 => "true", 0 => "false");
        if(isset($field_data->enum)) {
          $values = array();
          // really Drupal??
          $vals = (array)$field_data->enum;
          foreach($vals as $k => $v) {
            $newk = (string)$k;
            $values[$newk] = $v;
          }

        }
        $f[$field_name] = array(
          '#type' => 'radios',
          '#title' => isset($field_data->label) ? $field_data->label : $field_name,
          '#options' => $values
        );

        break;
      default:
        break;
    }

    if(isset($field_data->default_value)) {
        $f[$field_name]['#default_value'] = $field_data->default_value;
    }

    if(isset($field_data->help_text)) {
      $f[$field_name]['#description'] = $field_data->help_text;
    }

    return $f;

  }

  private function _generateArrayFromFormValues(&$form, $form_state) {

    $edan_field_values = array();

    $jf = new \Drupal\json_forms\JsonFormsArticle(); // use our interface class; connects to EDAN by default

    try {
      $articles_schema = $jf->adminGetArticleSchema();

      if(isset($articles_schema->schema->properties)) {
        $article_properties_temp = $articles_schema->schema->properties;

        foreach($article_properties_temp as $temp_name => $temp_data) {
          if(!isset($temp_data->properties) && !empty($temp_data)) {
            if(strlen(trim($form_state->getValue($temp_name))) > 0) {
              $edan_field_values[$temp_name] = $form_state->getValue($temp_name);
            }
          }
        }

      } // if we got some properties of the schema

      if(isset($articles_schema->schema->properties->content->properties)) {
        $article_properties_temp = $articles_schema->schema->properties->content->properties;

        foreach($article_properties_temp as $temp_name => $temp_data) {
          if(isset($temp_data->properties)) {
            foreach($temp_data->properties as $k => $v) {
              $field_name = $temp_name . '__' . $k;
              if(strlen(trim($form_state->getValue($field_name))) > 0) {
                // special case for online media, which is an array of media
                if($temp_name == 'online_media') {
                  $edan_field_values['content'][$temp_name][0][$k] = $form_state->getValue($field_name);
                }
                else {
                  $edan_field_values['content'][$temp_name][$k] = $form_state->getValue($field_name);
                }
              }
            }
          }
          else {
            if(strlen(trim($form_state->getValue($temp_name))) > 0) {
              $edan_field_values['content'][$temp_name] = $form_state->getValue($temp_name);
            }
          }

        }

        //dpm($edan_field_values);

      } // if we got some properties of the schema content
    }
    catch(Exception $ex) {
      drupal_set_message(t("Unable to retrieve article schema: %err", array('%err' => $ex->getMessage())));
      return $edan_field_values;
    }

    return $edan_field_values;
  }

  private function _generateFlattenedFromArray($edan_field_values) {

    $flattened = array();

    /**
     * Flatten the array to just name-value pairs
     * We have an array like:
     * content => (
     *  'title' => 'blah',
     *  'legacy_data' => array(
     *    ...
     *   ),
     *  ...
     * ),
     *
    */

    if(isset($edan_field_values) && count($edan_field_values) > 0) {
      $flattened = $this->_flatten_values($edan_field_values);
    } // if we got some properties of the schema

    return $flattened;
  }

  function _flatten_values($var, $parent_var = NULL) {

    $flat = array();

    // loop through
    if(is_array($var)) {
      foreach($var as $k => $v) {
        if( !is_array($v)) {
          if(NULL == $parent_var) {
            $flat[$k] = $v;
          }
          else {
            $flat[$parent_var][$k] = $v;
          }
        }
        elseif($k !== 'online_media' && is_array($v) && isset($v[0])) {
          if(NULL == $parent_var) {
          $flat[$k] = array_values($v);
        }
        else {
            $flat[$parent_var][$k] = array_values($v);
          }
        }
        elseif($k == 'online_media' && is_array($v) && isset($v[0]) && is_array($v[0])) {
          $flat[$k] = $this->_flatten_values($v[0]);
        }
        else {
          $flat += $this->_flatten_values($v, $k);
        }
      }
    }

    return $flat;
  }

} // class AdminArticle