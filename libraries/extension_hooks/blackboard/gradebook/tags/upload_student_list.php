<?php
use LTI\ExtensionHooks\Utils;
use LTI\ExtensionHooks\Settings;
use LTI\ExtensionHooks\GradebookImport;

$hook_method = function() {
    $form = "";
    $errors = "";

    $setup = array();

    if (isset($_POST['do_upload'])) {
      $group_students = !empty($_POST['group_students']);

      if(!empty(\Learning_tools_integration::$lti_plugins)) {
        foreach(\Learning_tools_integration::$lti_plugins as $plugin) {
          $setup[$plugin] = !empty($_POST["setup_$plugin"]);
        }
      }
      
      $config['upload_path'] = ee()->config->item('lti_upload');
      $config['allowed_types'] = 'csv';
      $config['max_size'] = '5242880';

      ee() -> load -> library('upload', $config);

      if (! ee() -> upload -> do_upload()) {
        $errors =   ee() -> upload -> display_errors();
      } else {

        $file_data =    ee() -> upload -> data();
        $file_name = $file_data['file_name'];
        $ext = strtoupper(end(explode(".", $file_name)));

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
            $email_export_message .= Utils::get_js_file_for_output('upload_student_list');


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

    $settings = Settings::get_general_settings();

    $enable_group_import = $settings["enable_group_import"];
    $plugins_active = $settings["plugins_active"];

    $form .= "<span id='manualUploadInfo'><p>".lang('upload_student_list')."</p><br><p><strong>".lang('upload_tip')."</strong></p>";
    $form .= "<p>".form_open_multipart($this->base_url).BR;
    $form .= form_upload('userfile', 'userfile').BR;
    $form .= form_hidden('group_students', $enable_group_import ? '1' : '0')."</p>";
    //$form .= " include user groups columns<br></p>";

    if(!empty(static::$lti_plugins)) {
        foreach(static::$lti_plugins as $plugin) {
          if(!empty($plugin)) {
                $active = FALSE;
                if($settings['row_count'] == 1) {
                    $active = isset($plugins_active[$plugin]) && $plugins_active[$plugin]['active'] == 1;
                }

              $form .= form_hidden('setup_'.$plugin, $active ? '1' : '0');
            //  $form .= $this->plugin_setup_text[$plugin]."</p>";
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
