<?php

/**
 * @file JsonFormsData.php
 *
 * Used to auto-generate Drupal forms from a schema, and validate data against a schema.
 *
 */

namespace Drupal\json_forms {

  class JsonFormsData {

    public function __construct() {

    }

    /** Get schema for the supplied content type. */
    public function getSchema($content_type) {

      $schema_json = NULL;

      $json_path = drupal_get_path('module', 'json_forms') . '/json-forms-' . $content_type . '.schema.json';
      if (!file_exists($json_path)) {
        throw new \ErrorException('Missing JSON file for schema ' . $content_type . '.');
      }
      else {
        $schema_json_string = file_get_contents ($json_path);
        try {
          $schema_json = json_decode($schema_json_string);
        }
        catch(\ErrorException $ex2) {
          throw new \ErrorException('Error reading JSON from file for schema ' . $content_type . ': ' . $ex2->getMessage());
        }
      }

      return $schema_json;
    }

    /** Given a content type, generate an empty shell structure for data for this content type. */
    public function getRecordShell($content_type) {

      $data_structure = array();
      //@todo- return the minimum viable structure for the specified $content_type

      return $data_structure;
    }

    public function getRecord($content_type, $id) {

      $json_data = $this->readData($content_type);
      $json_record = NULL;

      foreach($json_data as $j) {
        if(isset($j->id) && $j->id == $id) {
          $json_record = $j;
        }
      }

      return $json_record;
    }

    public function writeRecord($content_type, $json_record) {

      $json_data = $this->readData($content_type);
      $new_json_data = array();
      $found_record = FALSE;

      if(isset($json_record->id)) {
        foreach($json_data as $j) {
          if(isset($j->id) && $j->id == $json_record->id) {
            $new_json_data[] = $json_record;
            $found_record = TRUE;
          }
          else {
            $new_json_data[] = $j;
          }
        }
      }
      else {
        $new_json_data = $json_data;
      }

      if(FALSE == $found_record) {
        $new_json_data[] = $json_record;
      }

      $this->writeData($content_type, $new_json_data);
    }

    public function deleteRecord($content_type, $content_id) {

      $json_data = $this->readData($content_type);
      $new_json_data = array();
      foreach($json_data as $j) {
        if(isset($j->id) && $j->id == $content_id) {
          // skip this to delete it
        }
        else {
          $new_json_data[] = $j;
        }

      }

      $this->writeData($content_type, $new_json_data);

    }

    public function readData($content_type) {

      $json_data = NULL;

      $json_path = drupal_get_path('module', 'json_forms') . '/json-forms-' . $content_type . '.json';
      if (!file_exists($json_path)) {
        throw new \ErrorException('Missing JSON file for ' . $content_type . ' data.');
      }
      else {
        $json_string = file_get_contents($json_path);
        try {
          $json_data = json_decode($json_string);
        }
        catch(\ErrorException $ex2) {
          throw new \ErrorException('Error reading JSON from file for ' . $content_type . ' content: ' . $ex2->getMessage());
        }
      }

      return $json_data;
    }

    public function writeData($content_type, $json_data) {

      $json_path = drupal_get_path('module', 'json_forms') . '/json-forms-' . $content_type . '.json';
      try {
        file_put_contents($json_path, $json_data);
      }
      catch(\ErrorException $ex2) {
        throw new \ErrorException('Error writing JSON to file for ' . $content_type . ' content: ' . $ex2->getMessage());
      }

    }

    /** Validate $content_data against the schema indicated by $content_type. */
    public function validateAgainstSchema($content_type, $content_data) {

      $is_valid = TRUE;

      $schema = $this->getSchema($content_type);

      //@todo- is $content_data valid given our schema?


      return $is_valid;
    }

    public function generateDrupalForm($schema, $json_data) {
      // given a schema defined by $schema and data in $json_data
      // generate a Drupal form

      //@todo
    }

    public function generateContentFromDrupalForm($content_type, $form, $form_state) {

      //@todo

    }

  } // JsonFormsData
} // namespace

?>