<?php
$hook_method = function () {
    $table = "lti_instructor_settings";
    $result =   ee() -> db -> get_where($table, array("course_key" => $this->course_key, "institution_id" => $this->institution_id));
    $row_count = $result -> num_rows();

    if ($row_count == 1) {
        $problem_prefix = $result -> row() -> problem_prefix;
        $solution_prefix = $result -> row() -> solution_prefix;
    }

    if(isset($_POST['problem_prefix'])) {
        $problem_prefix = ee() -> input -> post("problem_prefix");
    }

    if(isset($_POST['solution_prefix'])) {
        $solution_prefix = ee() -> input -> post("solution_prefix");
    }

    if (isset($_POST['save_settings'])) {
        if ($row_count == 1) {
            ee() -> db -> where(array("institution_id" => $this->institution_id, "course_key" => $this->course_key));
            ee() -> db -> update($table, array("problem_prefix" => $problem_prefix, "solution_prefix" =>  $solution_prefix));
        } else {
            ee() -> db -> insert($table, array("course_key" => $this->course_key, "institution_id" => $this->institution_id, "problem_prefix" => $problem_prefix, "solution_prefix" => $solution_prefix));
        }
    }

    ee() -> load -> helper('form');

    $form = "<p>ZIP file problem and solution settings for $this->course_name.</p>";
    $form .= form_open_multipart($this->base_url);
    $form .= form_hidden('save_settings', '1');
    $form .= lang('problem_prefix') . " ";
    $form .= form_input(array('name' => 'problem_prefix', 'id' => 'problem_prefix', 'value' => $problem_prefix, 'maxlength' => '20', 'size' => '20'));
    $form .= "<br>";
    $form .= lang('solution_prefix') . " ";
    $form .= form_input(array('name' => 'solution_prefix', 'id' => 'solution_prefix', 'value' => $solution_prefix, 'maxlength' => '20', 'size' => '20'));
    $form .= "<br>";
    $form .= form_submit("Save Settings", "Save", $this->form_submit_class);
    $form .= form_close();

    return $form;
};
/*
* Generate inline tags for instructor
*/
$launch_instructor = function($params) {
        $tag_data = $params['tag_data'];

        if($data = $this->resource_settings_form()) {
              $params['tag_data']['resource_settings_form'] = $data;
        }

        return $params;
    };
?>
