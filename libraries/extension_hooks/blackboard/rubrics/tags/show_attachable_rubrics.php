<?php
use LTI\ExtensionHooks\Utils;
use LTI\ExtensionHooks\BB_Resources;
use LTI\ExtensionHooks\BB_RubricArchive;
use LTI\ExtensionHooks\BB_Rubrics;

$hook_method = function() {

if(empty($this->isInstructor)) { return FALSE; }

if(isset($_POST['no_reload'])) { return FALSE; }

if(isset($_GET['rubric_id'])) {
  foreach(static::$lti_plugins as $plugin) {
    require_once (PATH_THIRD.$plugin.DIRECTORY_SEPARATOR."libraries".DIRECTORY_SEPARATOR.$plugin."_rubric.php");
  }
}

$vars = array();
$config = array();
$errors = "";
$form = "";
$msg = "";

$init_rubric_res = ee()->db->get_where("lti_course_link_resources", array("course_id" => $this->course_id, "resource_link_id" => $this->resource_link_id));

$init_rubric = NULL;
if($init_rubric_res->num_rows() == 1) {
  $init_rubric = $init_rubric_res->row()->rubric_id;
}

if(empty($init_rubric) || $init_rubric === 'undefined' && $init_rubric !== 'no_rubric') {
    $init_rubric = ee()->config->item('default_rubric_uid');
}

$write_path = ee()->config->item('lti_cache');

$default_rubrics = $write_path."default/rubrics/html";
$path = Utils::build_course_upload_path($write_path, $this->context_id, $this->institution_id, $this->course_id);

$rubric_dir = $path.DIRECTORY_SEPARATOR."rubrics";

if(!file_exists($rubric_dir)){
  if(!mkdir($rubric_dir)) {
    die("Unable to create rubric folder ($path).");
  } else {
    chmod($rubric_dir, 0775);
  }
}

$resources = new BB_Resources($rubric_dir); // check for imsmanifest.xml
$rubric_html_dir = $rubric_dir.DIRECTORY_SEPARATOR."html";

  // import new rubrics
if($resources->isValid() === TRUE) {

  if(!file_exists($rubric_html_dir)){
    if(!mkdir($rubric_html_dir)) {
      die("Unable to create rubric html source folder.");
    }
  }

  $rubric_builder = new BB_Rubrics($resources->rubric->bbFile, $rubric_dir);
  $rubrics = $rubric_builder->getRubrics();

  foreach($rubrics as $key => $rub) {
    $file_name = $rubric_html_dir.DIRECTORY_SEPARATOR.$rub['title']."|grid|$rub[total_score]|$key.html";
    file_put_contents($file_name, $rub["grid_html"]);

    $file_name = $rubric_html_dir.DIRECTORY_SEPARATOR.$rub['title']."|list|$rub[total_score]|$key.html";
    file_put_contents($file_name, $rub["list_html"]);
  }
}

$dir = array(); $d_dir = array();

if(file_exists($rubric_html_dir)) {
  $dir = scandir($rubric_html_dir);
}

if(file_exists($default_rubrics)) {
  $d_dir = scandir($default_rubrics);
}

$dir = array_merge($dir, $d_dir);

ee() -> load -> helper('form');

$options = array("del" => "-- no rubric --");

if(! function_exists("_allowed")) {
  function _allowed($_m) {
    return (!empty($_m) && $_m !== "." && $_m !== "..");
  }
}

$dir = array_filter($dir, "_allowed");
$show_scores = array();
$raw_init_id = "del";

foreach($dir as $item) {
        if(strpos($item, '.html') !== FALSE) {
            $filename = explode("|", $item);
            $title = $filename[0];
            $score = $filename[2];

            $id = explode(".", $filename[count($filename)-1])[0];

                if($init_rubric == $id) {
                    $init_rubric = $init_rubric."|".$score;
                    $raw_init_id = $id;
                }


            $row = ee()->db->get_where('lti_course_link_resources', array("rubric_id" => $id))->row();
            $raw = !empty($row) ? $row->resource_settings : NULL;
            $settings = unserialize($raw);

            $show_scores[$id] = $raw !== NULL && isset($settings['rubric']['show_column_scores']) ? $settings['rubric']['show_column_scores'] : 1;

            $id = $id."|".$score;
            $options[$id] = $title;
          }
}

$form .= form_open_multipart($this->base_url);
$form .= form_label("Available Rubrics:  ", "rubric_dd");

$form .= form_dropdown("rubrics", $options, $init_rubric, "id='rubric_dd' class='form-control'");

$button = array('name' => 'preview', 'id' => 'preview_btn', 'value' => 'true', 'content' => 'Preview', 'class' => $this->button_class.' form-control');
$form .= form_button($button);

$checkbox = array(
    'name'        => 'show_scores ',
    'id'          => 'show_scores',
    'value'       => 'yes',
    'checked'     => !empty($show_scores[$raw_init_id]),
    'style'       => 'margin:10px',
    );

$form .= form_label(' show rubric cell scores.', 'show_scores', array('for' => 'show_scores'));
$form .= form_checkbox($checkbox, array('class' => 'form-control'));
$form .= "<p>";

$form .= form_button('Attach Rubric to Assessment', 'attach', "id='attach' class='$this->button_class form-control'");
$form .= "<img id='rub_loader' src='".URL_THIRD_THEMES."learning_tools_integration/img/loader.gif' style='display:none'/><span id='loader_msg'></span>";
$form .= "</p>";
$form .= form_close();

$vars['form'] = $form;
$vars['base_url'] = $this->base_url;

$vars['disable_instructor_score_setting'] = !empty($init_rubric);
return ee() -> load -> view('instructor/rubric-interface.php', $vars, TRUE);
};

/*
* Generate inline tags for instructor
*/
$launch_instructor = function($params) {
      $tag_data = $params['tag_data'];

      if($data = $this->upload_blackboard_rubric()) {
            $params['tag_data']['show_attachable_rubrics'] = $data;
      }

      return $params;
  };
?>
