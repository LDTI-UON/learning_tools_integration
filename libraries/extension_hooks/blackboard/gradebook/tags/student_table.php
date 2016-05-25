<?php
$hook_method = function () {
    // pagination varies according to input
    $segments =   ee() -> uri -> segment_array();
    $my_segment = end($segments);

    if(count($segments) > 3) {
      $prev = $segments[count($segments) - 3];
    } else {
      $prev = prev($segments);
    }

    if ($prev == 'student_table' && is_numeric($my_segment)) {
        $rownum = $my_segment;
    } else {
        $rownum = 0;
    }

    // is_numeric avoids XSS issues
    $ppage = isset($_REQUEST['per_page']) && is_numeric($_REQUEST['per_page'])? $_REQUEST['per_page'] : $this->perpage;
    $st_search = isset($_REQUEST['st_search']) ? ee()->security->xss_clean($_REQUEST['st_search']) : "";

    // check if user went via pagination
    if(count($segments) > 3) {

      if(!isset($_REQUEST['per_page'])) {
        if($segments[$this->pagination_segment] !== $ppage) {
          $ppage = $segments[$this->pagination_segment];
        }
      }
      if(!isset($_REQUEST['st_search'])) {
        if($segments[$this->pagination_segment+1] !== $st_search) {
          $st_search = $segments[$this->pagination_segment+1];
        }
      }
    }

    if(!isset($this->include_groups)) {
        $this->include_groups = Settings::get_instructor_settings()->enable_group_import;
    }

    $groups = !empty($this -> include_groups) ? ",lti_group_contexts.group_no, lti_group_contexts.group_name" : '';
    //ee() -> db -> save_queries = true;
    ee() -> db -> select("members.member_id, members.screen_name, members.username, members.email, lti_member_resources.display_name $groups");
    ee() -> db -> join("lti_member_contexts", "members.member_id = exp_lti_member_contexts.member_id AND exp_lti_member_contexts.context_id = '$this->context_id'
                    AND lti_member_contexts.tool_consumer_instance_id = '$this->tool_consumer_instance_id' AND lti_member_contexts.is_instructor = '0'");

    if (!empty($groups)) {
        ee() -> db -> join('lti_group_contexts', 'lti_member_contexts.id = lti_group_contexts.internal_context_id', 'left outer');
    }

    ee() -> db -> join('lti_member_resources', 'lti_member_contexts.id = lti_member_resources.internal_context_id', 'left outer');

    $wsql = "(".ee()->db->dbprefix."lti_member_resources.type IS NULL OR ".ee()->db->dbprefix."lti_member_resources.type = 'P')";

    if(!empty($st_search) && $st_search !== "__empty__") {
      $gsql = "";
      if(isset($this -> include_groups)) {
        $gsql = ee()->db->dbprefix."lti_group_contexts.group_name LIKE '%$st_search%' OR";
      }

      $members_table = ee()->db->dbprefix."members";
      $wsql .= " AND ($gsql $members_table.screen_name LIKE '%$st_search%' OR $members_table.username LIKE '%$st_search%' OR $members_table.email LIKE '%$st_search%')";

    }

    ee() -> db -> where($wsql);

    ee() -> db -> from('members');

    $total =   ee() -> db -> count_all_results();

    ee() -> db -> select("members.member_id, members.screen_name, members.username, members.email, lti_member_resources.display_name $groups");
    ee() -> db -> join("lti_member_contexts", "members.member_id = lti_member_contexts.member_id AND exp_lti_member_contexts.context_id = '$this->context_id'
                    AND lti_member_contexts.tool_consumer_instance_id = '$this->tool_consumer_instance_id' AND lti_member_contexts.is_instructor = '0'");

    if (!empty($groups)) {
        ee() -> db -> join('lti_group_contexts', 'lti_member_contexts.id = lti_group_contexts.internal_context_id', 'left outer');
    }

    ee() -> db -> join('lti_member_resources', 'lti_member_contexts.id = lti_member_resources.internal_context_id', 'left outer');

    ee() -> db -> where($wsql);
    //ee() -> db -> or_where("lti_member_resources.type = 'P'");

    ee() -> db -> from('members');
    ee() -> db -> limit($ppage, $rownum);

    $query =   ee() -> db -> get();
    $vars = array();

    foreach ($query->result_array() as $row) {
        $vars['students'][$row['member_id']]['member_id'] = $row['member_id'];
        $vars['students'][$row['member_id']]['screen_name'] = $row['screen_name'];
        $vars['students'][$row['member_id']]['username'] = $row['username'];
        $vars['students'][$row['member_id']]['email'] = $row['email'];
        $vars['students'][$row['member_id']]['display_name'] = $row['display_name'];
        if (!empty($groups)) {
            $vars['students'][$row['member_id']]['group_no'] = $row['group_no'];
            $vars['students'][$row['member_id']]['group_name'] = $row['group_name'];
        }

        foreach(static::$lti_plugins as $plugin) {
             // include(PATH_THIRD."$plugin/libraries/".$plugin."_student_table.php"); @TODO finish plugin extension
           }
    }

    $vars['include_groups'] = $this -> include_groups;
    // Pass the relevant data to the paginate class so it can display the "next page" links
    ee() -> load -> library('pagination');

    $data_segments = array();
    $data_segments[] = $ppage;
    $data_segments[] = empty($st_search) ? "__empty__" : $st_search;

    $p_config = $this -> pagination_config('student_table', $total, $ppage, $data_segments);
    ee() -> pagination -> initialize($p_config);

    $vars['pagination'] =   ee() -> pagination -> create_links();

ee() -> load -> helper('form');

$ppage_output = form_open_multipart($this->base_url, array("id" => "filters"));
$ppage_output .= lang('student_rows_per_page') . ":&nbsp;".form_input(array('name' => 'per_page', 'id' => 'per_page', 'value' => $ppage, 'maxlength' => '5', 'size' => '5'));
$ppage_output .= "&nbsp;".lang('search_students') . ":&nbsp;".form_input(array('name' => 'st_search', 'id' => 'st_search', 'value' => empty($st_search) || $st_search === "__empty__" ? "" : $st_search, 'maxlength' => '20', 'size' => '9'));

$ppage_output .= form_close();
$ppage_output .= "<script type='text/javascript'>".file_get_contents($this->mod_path.'/js/input_filters.js')."</script>";
$vars['per_page'] = $ppage_output;

return ee() -> load -> view('instructor/student-table', $vars, TRUE);
};
/*
* Generate inline tags for instructor
*/
$launch_instructor = function($params) {
        $tag_data = $params['tag_data'];

        if($data = $this->student_table()) {
              $params['tag_data']['student_table'] = $data;
        }

        return $params;
    };
?>
