<?php
require_once($this->hook_path.DIRECTORY_SEPARATOR.'blackboard'.DIRECTORY_SEPARATOR.'gradebook'.DIRECTORY_SEPARATOR.'GradebookImport.php');
require_once($this->hook_path.DIRECTORY_SEPARATOR.'settings'.DIRECTORY_SEPARATOR.'Settings.php');

$hook_method = function() {
    $form = "";
    $errors = "";

    $setup = array();

    if (isset($_POST['do_upload'])) {
      $group_students = isset($_POST['group_students']) ? $_POST['group_students'] : '';


      if(!empty(Learning_tools_integration::$lti_plugins)) {
        foreach(Learning_tools_integration::$lti_plugins as $plugin) {
          $setup[$plugin] = !empty($_POST["setup_$plugin"]) ? $_POST["setup_$plugin"] : '';
        }
      }

      $config['upload_path'] = LTI_FILE_UPLOAD_PATH;
      $config['allowed_types'] = 'csv';
      $config['max_size'] = '5242880';

      ee() -> load -> library('upload', $config);

      if (! ee() -> upload -> do_upload()) {
        $errors =   ee() -> upload -> display_errors();
      } else {

        $file_data =    ee() -> upload -> data();
        $file_name = $file_data['file_name'];
        $ext = strtoupper(end(explode(".", $file_name)));

        /*if (!in_array($ext, array("CSV"))) {
          $errors .= "<br>'$ext' Filetype not allowed.";
        }*/

        if (!$errors) {
          $form .= "<h1>Upload Successful</h1>";

          // instantiate file import object
          $importer = new GradebookImport($this->member_id, $this->context_id, $setup);

          $result = $importer->import($group_students, $file_data['full_path']);
        }

      $errors .= $result['errors'];
      $form .= $result['message'];
      }
    } else {

      $query = ee()->db->get_where('lti_instructor_credentials', array('member_id' => $this->member_id));

      if($query->num_rows() > 0) {
        if($query->row()->state == 0) {
            $email_export_message = "<p tyle='color: darkblue; font-weight: 900'>".lang('email_export_message')." <a href='#' id='manual'>".lang('access_manual_upload_link')."</a></p>";
            $email_export_message .= get_js_file_for_output('upload_student_list');


        } else if ($query->row()->state == 2){
          $email_export_message = "<p style='color: white; font-weight: 900; background-color: red'>".lang('email_export_bad_password')."</p>";
        } else if ($query->row()->state == 3){
          $email_export_message = "<p style='color: white; font-weight: 900; background-color: red'>".lang('email_export_not_functional')."</p>";
        }
        if(isset($email_export_message)) {
          $form .= $email_export_message;
        }
      }
    }

    ee() -> load -> helper('form');
    ee() -> load -> helper('url');

    $settingsOb = new Settings($this);
    $settings = $settingsOb->get_general_settings();

    $enable_group_import = $settings["enable_group_import"];
    $plugins_active = $settings["plugins_active"];

    $form .= "<span id='manualUploadInfo'><p>".lang('upload_student_list')."<br><strong>".lang('upload_tip')."</strong></p>";
    $form .= form_open_multipart($this->base_url);
    $form .= form_upload('userfile', 'userfile');
    $form .= "<br><br><p>Change these settings in <b>General Settings for Groups &amp; Plugins</b><br><br>If selected, will include group columns in upload<br>";
    $form .= form_checkbox(array('name'=>'group_students', 'id' => 'group_students', 'value' =>'1', 'checked' => $enable_group_import == 1, "disabled" => "disabled"));
    $form .= " include user groups columns<br></p>";

    if(!empty(static::$lti_plugins)) {
        foreach(static::$lti_plugins as $plugin) {
          if(!empty($plugin)) {
                $active = FALSE;
                if($settings['row_count'] == 1) {
                    $active = isset($plugins_active[$plugin]) && $plugins_active[$plugin] == 1;
                }

                $form .= "<br><p>".$this->plugin_setup_text[$plugin."_description"];
              $form .= form_checkbox(array('name' => 'setup_'.$plugin, 'id'=>'setup_'.$plugin, "value" =>'1', "checked" => $active, 'disabled' => 'disabled'));
              $form .= $this->plugin_setup_text[$plugin]."</p>";
          }
        }
    }

    $form .= "<br>";
    $form .= form_hidden('do_upload', 'yep');
    $form .= form_submit("upload", "Upload");
    $form .= form_close();

    if(!empty($errors)) {

        $form .= "<span id='lti_peer_assess_error_field' class='errorTextField' style='display: block; color: white; font-size: 10pt; font-family: courier, monospace; background-color: black; padding: 0.5em'>
                    $errors
                  </span><script type='application/javascript'>$(document).ready(function() { $(\"html,body\").animate({
    scrollTop: $(\"span#lti_peer_assess_error_field\").offset().top
}, 1000);  }); </script>";
        $form .= "<br>";
    }
    $form .= "</span>";

return $form;
};

/*
* Generate inline tags
*/
$launch_instructor = function($params) {
        $tag_data = $params['tag_data'];

        if($data = $this->upload_student_list()) {
              $params['tag_data']['upload_student_list'] = $data;
        }

        return $params;
    };
?>
