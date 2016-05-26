<?php
use LTI\ExtensionHooks\ResourceFile;

$hook_method = function() {
/* process feedback on targeted resource after randomisation*/
if(ee()->input->post('process')) {
        $is = $this->get_instructor_settings();

        if(!$is) {
          $is = new stdClass;
          $is->problem_prefix = 'problem_';
          $is->solution_prefix = 'solution_';
        }

        $rfile = new ResourceFile(LTI_FILE_UPLOAD_PATH.DIRECTORY_SEPARATOR.ee()->input->post('process'), $this->internal_context_id, $this->context_id, $this->institution_id, $this->course_id, $is->problem_prefix, $is->solution_prefix);
        $feed = $rfile->import();

        header('Content-type: application/json');
        echo json_encode(array("feedback" => $feed));
        exit;
}
};

/*
* Generate inline tags for instructor
*/
$launch_no_template = function($params = NULL) {
        $this->process_resource_file();
};
?>
