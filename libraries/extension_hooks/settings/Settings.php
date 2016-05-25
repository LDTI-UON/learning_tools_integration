<?php
class Settings {
  private static $lti_module;

  private static function _load_lti() {
      if(empty(static::$lti_module)) {
            static::$lti_module = Learning_tools_integration::get_instance();
        }
  }

  public static function get_instructor_settings() {
      static::_load_lti();

      $result =  ee() -> db -> get_where("lti_instructor_settings", array("course_key" => static::$lti_module->course_key, "institution_id" => static::$lti_module->institution_id));

      if ($result -> num_rows() == 1) {
          return($result -> row());
      } else {
          return FALSE;
      }
  }

  public static function get_general_settings() {
      static::_load_lti();

      $result =  ee() -> db -> get_where("lti_instructor_settings", array("course_key" => static::$lti_module->course_key, "institution_id" => static::$lti_module->institution_id));

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
