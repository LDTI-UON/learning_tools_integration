<?php
# @Author: ps158
# @Date:   2016-09-20T15:36:13+10:00
# @Last modified by:   ps158
# @Last modified time: 2017-04-19T14:34:52+10:00
use LTI\ExtensionHooks\Utils;

/*
* Direct method via exp:module:method syntax
*/
$hook_method = function() {
      $raw_id = ee()->input->post("id");
      $id = explode("|", $raw_id)[0];

  $user = ee()->input->post("user");
  $input_id = ee()->input->post("input_id");
  $pre_pop =  ee()->input->post('pre_pop');//ee()->TMPL->fetch_param("pre_pop");
  $lti_cache = ee()->config->item('lti_cache');

  $is_instructor = empty(ee()->input->post("user"));  //not a security risk because it only stops submission


  $path = Utils::build_course_upload_path($lti_cache, $this->context_id, $this->institution_id, $this->course_id);

    $rubric_dir = $path.DIRECTORY_SEPARATOR."rubrics".DIRECTORY_SEPARATOR."html";
    $dir = scandir($rubric_dir);
    $vars = array();

    foreach($dir as $item) {
      if(strpos($item, $id) !== FALSE) {
          if(strpos($item, "|grid|") !== FALSE) {
            $vars['grid'] = html_entity_decode(file_get_contents($rubric_dir.DIRECTORY_SEPARATOR.$item));
          }

          if(strpos($item, "|list|") !== FALSE) {
            $vars['list'] = html_entity_decode(file_get_contents($rubric_dir.DIRECTORY_SEPARATOR.$item));
          }
      }
    }

    $row = ee()->db->get_where("lti_course_link_resources",array("rubric_id" => $id))
                  ->row();
    if($row) {
      $raw = isset($row->resource_settings) ? unserialize($row->resource_settings) : NULL;

      if($raw !== NULL)  {
            $ser = $raw;
      }

      $show_scores = $raw === NULL ? 1 : $ser['rubric']['show_column_scores'];
    } else {
      $show_scores = 1;
    }

    $vars['hide_scores'] = empty($show_scores) ? file_get_contents("$this->mod_path/js/rubric_hide_scores.js") : "";
    if($is_instructor) {
        $vars['js_controls'] = "var instr = true;\n\n";
    } else {
        $vars['js_controls'] = "var instr = false;\n\n";
    }
    $vars['js_controls'] .= "var app = app || {};\napp.is_instructor = instr;";

    $vars['js_controls'] .= file_get_contents("$this->mod_path/js/rubric_controls.js");

    if(empty($user)) {
      $vars['exit_button_value'] = "Exit";
    } else {
      $vars['exit_button_value'] = "Save &amp; Close";
    }
    
    $vars['input_id'] = $input_id;
    $vars['username'] = htmlentities($user['screen_name']);
    $vars['pre_pop'] = htmlentities($pre_pop, ENT_QUOTES, 'UTF-8');
    $vars['error_messages'] = ""; // stops empty tag on main page.

    return ee() -> load -> view('rubric', $vars, TRUE);
};
?>
