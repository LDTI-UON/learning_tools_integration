<?php
$hook_method = function() {
  $model = new ResourceModel($this);
    if ($this -> isInstructor == 0 && $this -> total_resources() < 2) {
        return $this -> download_resource();
    }

    $segments =   ee() -> uri -> segment_array();
    $my_segment = isset($segments[$this->pagination_segment]) ? $segments[$this->pagination_segment] : 'resource_table';

    if (prev($segments) == 'resource_table' && is_numeric($my_segment)) {
        $rownum = $my_segment;
    } else {
        $rownum = 0;
    }

    if ($this -> isInstructor != 0) {

        ee() -> db -> select('lti_member_resources.id, lti_member_resources.display_name');
        ee() -> db -> join('lti_member_contexts', 'lti_member_contexts.id = lti_member_resources.uploader_internal_context_id');
        ee() -> db -> where("lti_member_contexts.context_id = '$this->context_id'");
        ee() -> db -> from('lti_member_resources');
        $total =   ee() -> db -> count_all_results();

        ee() -> db -> select('lti_member_resources.id, lti_member_resources.display_name');
        ee() -> db -> join('lti_member_contexts', 'lti_member_contexts.id = lti_member_resources.uploader_internal_context_id');
        ee() -> db -> where("lti_member_contexts.context_id = '$this->context_id'");
        ee() -> db -> from('lti_member_resources');
        ee() -> db -> limit($this -> perpage, $rownum);

    } else {
        $type = $this -> is_solution_request() ? 'S' : 'P';

        ee() -> db -> select('lti_member_resources.id, lti_member_resources.display_name');
        //ee() -> db -> join('lti_member_contexts', 'lti_member_contexts.id = lti_member_resources.internal_context_id');
        ee() -> db -> where(array('lti_member_resources.internal_context_id' => $this -> internal_context_id, 'type' => $type));
        ee() -> db -> from('lti_member_resources');
        $total =   ee() -> db -> count_all_results();


        ee() -> db -> select('lti_member_resources.id, lti_member_resources.display_name, lti_member_resources.file_name');
        //ee() -> db -> join('lti_member_contexts', 'lti_member_contexts.id = lti_member_resources.internal_context_id');
        ee() -> db -> where(array('lti_member_resources.internal_context_id' => $this -> internal_context_id, 'type' => $type));
        ee() -> db -> from('lti_member_resources');
        ee() -> db -> limit($this -> perpage, $rownum);
    }

    $query =   ee() -> db -> get();

    $vars = array();

    foreach ($query->result_array() as $row) {
        $vars['resources'][$row['id']]['id'] = $row['id'];
        $vars['resources'][$row['id']]['display_name'] = $row['display_name'];
    }

    // Pass the relevant data to the paginate class so it can display the "next page" links
    ee() -> load -> library('pagination');
    $p_config = $this -> pagination_config('resource_table', $total);
    ee() -> pagination -> initialize($p_config);

    $vars['pagination'] =   ee() -> pagination -> create_links();

    return  ee() -> load -> view('resource-table', $vars, TRUE);
};
/*
* Generate inline tags for instructor
*/
$launch_instructor = function($params) {
        $tag_data = $params['tag_data'];

        if($data = $this->resource_table()) {
              $params['tag_data']['resource_table'] = $data;
        }

        return $params;
    };
?>
