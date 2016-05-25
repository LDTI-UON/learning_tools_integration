<?php
require_once($this->hook_path.DIRECTORY_SEPARATOR.'secure_resource_delivery'.DIRECTORY_SEPARATOR.'ResourceFile.php');

$hook_method = function() {
    $errors = "";
    $form = "";

    if (!LTI_FILE_UPLOAD_PATH) {
        return "<p><strong>{upload_student_resources_form} says &quot;Please set an upload path.&quot;</strong></p>";
    }

    $problem_prefix = 'problem_';
    $solution_prefix = 'solution_';

    $row = Settings::get_instructor_settings();

    if($row) {
         $problem_prefix = $row -> problem_prefix;
         $solution_prefix = $row -> solution_prefix;
    }

    $working_dir = LTI_FILE_UPLOAD_PATH.DIRECTORY_SEPARATOR.$this->context_id.$this->institution_id.$this->course_id;

    $form .= "<p><br><b>You can upload ZIP files larger than 50MB to the course folder via SSH/SFTP</b></em></p>";

    function _f($i) {
      return strpos(strtolower($i), '.zip') !== FALSE;
    }

    if(file_exists($working_dir)) {
        $files = scandir($working_dir);
        $files = array_filter($files, "_f");
        if(count($files) > 0) {
          $form .= "<p>ZIP files ready for processing.</p>";
          $form .= "<p><ul>";

          foreach($files as $zip) {
                  $form .= "<li><a href='#' class='process_file' data-filename='$zip'>$zip</a></li>";
          }

          $mod_dir = strtolower($this->mod_class);
          $form .= "</ul></p>";
          $form .= "<script type='text/javascript'>";
          $js = file_get_contents(PATH_THIRD.$mod_dir.DIRECTORY_SEPARATOR."js".DIRECTORY_SEPARATOR.'process_file.js');

          $tokens = array("%suburl%", "%loaderurl%");
          $replace = array(ee()->uri->uri_string, URL_THIRD_THEMES.$mod_dir.DIRECTORY_SEPARATOR."img".DIRECTORY_SEPARATOR."processing-file.gif");

          $js = str_replace($tokens, $replace, $js);

          $form .= $js;
          $form .= "</script>";
        } else {
            $form .= "<p>There are currently no Resource ZIP files available for processing.</p>";
        }
    } else {
      if(!mkdir($working_dir)) {
        $form .= "<p><b>Could not create working directory for resource zip files.</b></p>";
      }
    }

    $config = array();

    if (isset($_POST['do_resource_upload'])) {
        $config['upload_path'] = LTI_FILE_UPLOAD_PATH;
        $config['allowed_types'] = 'zip';
        $config['max_size'] = '51200';

        ee() -> load -> library('upload', $config);

        if (! ee() -> upload -> do_upload()) {
            $errors = "<br>" .  ee() -> upload -> display_errors();
        } else {
            $file_data =    ee() -> upload -> data();

            $resourceFile = new ResourceFile($file_data['full_path'], $this->internal_context_id, $this->context_id, $this->institution_id, $this->course_id, $problem_prefix, $solution_prefix);

            $form .= $resourceFile->import();
       }
    }
    ee() -> load -> helper('form');

    $form .= "<p>&nbsp;</p><p><em>Upload smaller resource zip files here (50MB max). $errors</br></br>";
    $form .= form_open_multipart($this->base_url);
    $form .= form_upload('userfile', 'userfile');
    $form .= form_hidden('do_resource_upload', 'yep');
    $form .= form_hidden('ee_lti_token', $this -> cookie_name);
    $form .= form_submit("upload", "Upload");
    $form .= form_close();
    $form.="</p>";

    return $form;
};
/*
* Generate inline tags for instructor
*/
$launch_instructor = function($params) {
        $tag_data = $params['tag_data'];

        if($data = $this->upload_student_resources_form()) {
              $params['tag_data']['upload_student_resources_form'] = $data;
        }

        return $params;
    };

?>
