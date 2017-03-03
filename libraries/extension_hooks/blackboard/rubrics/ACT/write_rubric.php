<?php
# this ACT is a direct hook, no LTI auth
include_once(__DIR__.'/../../../classes/Utils.php');
include_once(__DIR__.'/../../../classes/BB_Resources.php');
include_once(__DIR__.'/../../../classes/BB_RubricArchive.php');
include_once(__DIR__.'/../../../classes/BB_Rubrics.php');


$ACT_hook = function() {

  set_time_limit(0);
  ob_implicit_flush(true);
  ob_end_flush();

  global $ACT_score;

  if(isset($_POST['do_upload_rubric'])) {
    if(isset($_POST['no_reload'])) { return FALSE; }

    if(isset($_POST['show_scores'])) {
          $show_scores = ee()->input->post('show_scores');
    }

    $vars = array();
    $config = array();
    $errors = "";
    $form = "";
    $msg = "";

    $linkgen_id = isset($_POST['linkgen_id']) ? ee()->input->post('linkgen_id') : NULL;
    $resource_link_id = isset($_POST['resource_link_id']) ? ee('Security/XSS')->clean($_POST['resource_link_id']) : $this->resource_link_id;
    $institution_id = isset($_POST['institution_id']) ? ee('Security/XSS')->clean($_POST['institution_id']) : $this->institution_id;

    if($linkgen_id !== NULL) {
        $result = ee()->db->get_where('lti_course_contexts', array('context_id' => $linkgen_id));

        if($result->row()) {
            $last_id = $result->row()->context_id;
            $institution_id = $result->row()->institution_id;
        } else {
            ee()->db->insert('lti_course_contexts', array('institution_id' => $institution_id, 'context_id' => $linkgen_id));
            $last_id = ee()->db->insert_id();
        }

    } else {
        die("Linkgen id is required");
    }

    if(!isset($last_id)) {
        die("Course generation failed, couldn't obtain course id.");
    }

    $course_id = $last_id;
    $context_id = $linkgen_id;

    $write_path = ee()->config->item("lti_cache");
    $path = LTI\ExtensionHooks\Utils::build_course_upload_path($write_path, $context_id, $institution_id, $course_id);

    $rubric_dir = $path.DIRECTORY_SEPARATOR."rubrics";

    if(!file_exists($rubric_dir)){
      if(!mkdir($rubric_dir)) {
        die(json_encode(array("error" => "Unable to create rubric folder.")));
      } else {
        chmod($rubric_dir, 0700);
      }
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['rubric_file']['tmp_name']);

    if ($mime !== "application/zip")
    {
        die(json_encode(array("error" => "Invalid file format")));
    }

    $target_file = $rubric_dir.DIRECTORY_SEPARATOR.basename($_FILES['rubric_file']['name']);
    move_uploaded_file($_FILES['rubric_file']['tmp_name'], $target_file);


    if (!$errors) {
        //  $msg = "Upload Successful";
          LTI\ExtensionHooks\BB_RubricArchive::unpack($rubric_dir, $_FILES['rubric_file']['name'], $rubric_dir);
    }

    $resources = new LTI\ExtensionHooks\BB_Resources($rubric_dir); // check for imsmanifest.xml
    $rubric_html_dir = $rubric_dir.DIRECTORY_SEPARATOR."html";

    // import new rubrics
    if($resources->isValid() === TRUE) {

      if(!file_exists($rubric_html_dir)){
        if(!mkdir($rubric_html_dir)) {
          die(json_encode(array("error" => "Unable to create rubric html source folder.")));
        }
      }

      $rubric_builder = new LTI\ExtensionHooks\BB_Rubrics($resources->rubric->bbFile, $rubric_dir);
      $rubrics = $rubric_builder->getRubrics();

      foreach($rubrics as $key => $rub) {
        $file_name = $rubric_html_dir.DIRECTORY_SEPARATOR.$rub['title']."|grid|$rub[total_score]|$key.html";
        file_put_contents($file_name, $rub["grid_html"]);

        $file_name = $rubric_html_dir.DIRECTORY_SEPARATOR.$rub['title']."|list|$rub[total_score]|$key.html";
        file_put_contents($file_name, $rub["list_html"]);

        $init_rubric_res = ee()->db->get_where("lti_course_link_resources", array("course_id" => $course_id, "resource_link_id" => $resource_link_id, "rubric_id" => $key));

        if($init_rubric_res->num_rows() == 0) {
            $settings = array("rubric" => array("show_column_scores" => $show_scores));
            $ser = serialize($settings);
            ee()->db->insert("lti_course_link_resources", array("course_id" => $course_id, "resource_link_id" => $resource_link_id, "rubric_id" => $key, "resource_settings" => $ser));
        }
      }
    }

    $dir = array();

    if(file_exists($rubric_html_dir)) {
      $dir = scandir($rubric_html_dir);
    }

    $filename = explode("|", $file_name);
    $ACT_score = $filename[2]; // for use with ACT_lti

    $vals = array("score" => $ACT_score, "message" => "Rubric file uploaded successfully.");
    die(json_encode($vals));
  } else {
    die(json_encode(array("error" => "Invalid request")));
  }
}

?>
