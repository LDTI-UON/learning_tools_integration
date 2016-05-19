<?php
class Settings {
  private $lti_module;

  function __construct($lti_module) {
      $this->lti_module = &$lti_module;
  }

  public function get_instructor_settings() {
      $result =  ee() -> db -> get_where("lti_instructor_settings", array("course_key" => $this->lti_module->course_key, "institution_id" => $this->lti_module->institution_id));

      if ($result -> num_rows() == 1) {
          return($result -> row());
      } else {
          return FALSE;
      }
  }

  public function get_general_settings() {
      $result =   ee() -> db -> get_where("lti_instructor_settings", array("course_key" => $this->lti_module->course_key, "institution_id" => $this->lti_module->institution_id));

      $row_count = $result -> num_rows();
      $plugins_active = array();

      if ($row_count == 1) {
          $enable_group_import = $result -> row() -> enable_group_import;
          $pa = $result -> row() -> plugins_active;
          if(!empty($pa)) {
              $plugins_active = unserialize($pa);
          }
      } else {
          $enable_group_import = 1;
      }

     return array("enable_group_import" => $enable_group_import, "plugins_active" => $plugins_active, "row_count" => $row_count);
  }
}
?>
