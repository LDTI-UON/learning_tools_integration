<?php
# @Author: ps158
# @Date:   2016-09-20T15:36:13+10:00
# @Last modified by:   ps158
# @Last modified time: 2017-04-21T13:33:20+10:00

use LTI\ExtensionHooks\Settings;

$hook_method = function () {
    // pagination varies according to input
    $segments =   ee() -> uri -> segment_array();
    $my_segment = end($segments);
    $vars = array();
    $plugin_filters = array();
    $resource_link_id = $this->resource_link_id;

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

    $ppage = NULL;
    $st_search = NULL;

    if(isset($_COOKIE["ppage"]) && !empty($_COOKIE["ppage"]))
          $ppage = $_COOKIE["ppage"];

    if(isset($_COOKIE["st_search"]) && !empty($_COOKIE["st_search"]))
          $st_search = $_COOKIE["st_search"];

    if(empty($ppage) || (isset($_REQUEST['per_page']) && $ppage != $_REQUEST['per_page'])) {
          $ppage = isset($_REQUEST['per_page']) && is_numeric($_REQUEST['per_page']) ? ee()->input->get_post('per_page') : $this->perpage;

          unset($_COOKIE["ppage"]);
          setcookie("ppage", $ppage, time() + 1800, $this->base_url);
     }

    if(empty($st_search) || (isset($_REQUEST['st_search']) && $st_search != $_REQUEST['st_search'])) {
          $st_search = isset($_REQUEST['st_search']) ? ee()->input->get_post('st_search') : "";

          unset($_COOKIE["st_search"]);
          setcookie("st_search", $st_search, time() + 1800, $this->base_url);
    }

    if(!isset($this->include_groups)) {
        $settings = Settings::get_instructor_settings();
        if(is_object($settings)) {
            $this->include_groups = $settings->enable_group_import;
        } else {
            $this->include_groups = TRUE; // enable group import by default
        }
    }

    foreach(static::$lti_plugins as $plugin) {
            if(file_exists(PATH_THIRD."$plugin/libraries/".$plugin."_student_table_request.php")) {
                  include(PATH_THIRD."$plugin/libraries/".$plugin."_student_table_request.php");
            }
    }

    $groups = !empty($this -> include_groups) ? ",lti_group_contexts.group_no, lti_group_contexts.group_name, lti_group_contexts.group_id" : '';

    $wsql = "(".ee()->db->dbprefix."lti_member_resources.type IS NULL OR ".ee()->db->dbprefix."lti_member_resources.type = 'P')";

    foreach(static::$lti_plugins as $plugin) {
        if(file_exists(PATH_THIRD."$plugin/libraries/".$plugin."_student_table.php")) {
              include(PATH_THIRD."$plugin/libraries/".$plugin."_student_table.php");
        }
    }
    //ee() -> db -> save_queries = true;
    ee() -> db -> select("members.member_id, members.screen_name, members.username, members.email, lti_member_resources.display_name $groups");
    ee() -> db -> join("lti_member_contexts", "members.member_id = exp_lti_member_contexts.member_id AND exp_lti_member_contexts.context_id = '$this->context_id'
                    AND lti_member_contexts.tool_consumer_instance_id = '$this->tool_consumer_instance_id' AND lti_member_contexts.is_instructor = '0'");

    if (!empty($groups)) {
        ee() -> db -> join('lti_group_contexts', 'lti_member_contexts.id = lti_group_contexts.internal_context_id', 'left outer');
    }

    ee() -> db -> join('lti_member_resources', 'lti_member_contexts.id = lti_member_resources.internal_context_id', 'left outer');


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

    $total = ee() -> db -> count_all_results();

    $group_context_column = "";

    if (!empty($groups)) {
        $group_context_column = "lti_group_contexts.id as group_context_id, lti_group_contexts.group_id,";
    };

    ee() -> db -> select($group_context_column."members.member_id, members.screen_name, members.username, members.email, lti_member_contexts.last_launched_on, lti_member_resources.display_name $groups");
    ee() -> db -> join("lti_member_contexts", "members.member_id = lti_member_contexts.member_id AND exp_lti_member_contexts.context_id = '$this->context_id'
                    AND lti_member_contexts.tool_consumer_instance_id = '$this->tool_consumer_instance_id' AND lti_member_contexts.is_instructor = '0'");

    if (!empty($groups)) {
        ee() -> db -> join('lti_group_contexts', 'lti_member_contexts.id = lti_group_contexts.internal_context_id', 'left outer');
    }

    ee() -> db -> join('lti_member_resources', 'lti_member_contexts.id = lti_member_resources.internal_context_id', 'left outer');
    ee() -> db -> where($wsql);
    ee() -> db -> from('members');
    ee() -> db -> limit($ppage, $rownum);

    $query =   ee() -> db -> get();

    $vars['result_count'] = count($query->result_array());

    foreach ($query->result_array() as $row) {
        $vars['students'][$row['member_id']]['member_id'] = $row['member_id'];
        $vars['students'][$row['member_id']]['screen_name'] = $row['screen_name'];
        $vars['students'][$row['member_id']]['username'] = $row['username'];
        $vars['students'][$row['member_id']]['email'] = $row['email'];
        $vars['students'][$row['member_id']]['file_display_name'] = $row['display_name'];
        $vars['students'][$row['member_id']]['last_launched_on'] = $row['last_launched_on'];

        if (!empty($groups)) {
            $vars['students'][$row['member_id']]['group_no'] = $row['group_no'];
            $vars['students'][$row['member_id']]['group_name'] = $row['group_name'];
        }

        foreach(static::$lti_plugins as $plugin) {
                if(file_exists(PATH_THIRD."$plugin/libraries/".$plugin."_student_table_row.php")) {
                      include(PATH_THIRD."$plugin/libraries/".$plugin."_student_table_row.php");
                }
        }
    }

    $vars['include_groups'] = $this -> include_groups;
    // Pass the relevant data to the paginate class so it can display the "next page" links
    ee() -> load -> library('pagination');

    if(!isset($_COOKIE["ppage"]) && $ppage)
        $_COOKIE["ppage"] = $ppage;

    if(!isset($_COOKIE["st_search"]) && $st_search)
        $_COOKIE["st_search"] = $st_search;

    foreach($plugin_filters as $key => $val) {
        if(!empty($val)) {
              $_COOKIE[$key] = $val;
        } else {
            unset($_COOKIE[$key]);
        }
    }

    $p_config = $this -> pagination_config('student_table', $total, $ppage);

    ee() -> pagination -> initialize($p_config);

    $vars['pagination'] =   ee() -> pagination -> create_links();

ee() -> load -> helper('form');

$ppage_output = form_open_multipart($this->base_url, array("id" => "filters"));
$ppage_output .= lang('student_rows_per_page') . ":&nbsp;".form_input(array('name' => 'per_page', 'id' => 'per_page', 'value' => $ppage, 'maxlength' => '5', 'size' => '5'));
$ppage_output .= "&nbsp;".lang('search_students') . ":&nbsp;".form_input(array('name' => 'st_search', 'id' => 'st_search', 'value' => empty($st_search) || $st_search === "__empty__" ? "" : $st_search, 'maxlength' => '20', 'size' => '9'));

  foreach(static::$lti_plugins as $plugin) {
          if(file_exists(PATH_THIRD."$plugin/libraries/".$plugin."_student_table_ppage.php")) {
                include_once(PATH_THIRD."$plugin/libraries/".$plugin."_student_table_ppage.php");
          }
  }

$ppage_output .= form_close();
$ppage_output .= "<script type='text/javascript'>".file_get_contents($this->mod_path.'/js/input_filters.js')."</script>";
$vars['per_page'] = $ppage_output;
$vars['table_class'] = $this->table_class;
$vars['table_wrapper_class'] = $this->table_wrapper_class;
$vars['base_url'] = $this->base_url;
$vars['lti_plugins'] = static::$lti_plugins;

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
