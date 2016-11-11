<?php
$hook_method = function() {
    $form = "";
    if (isset($_POST['do_random'])) {
        // first clear all resources so we don't double up
        ee()->db->where(array('uploader_internal_context_id' => $this -> internal_context_id));
        ee()->db->update('lti_member_resources', array('internal_context_id' => NULL));

        // get all problem resources to get a total problem/solution count
        ee() -> db -> where(array('uploader_internal_context_id' => $this -> internal_context_id, 'type' => 'P'));
        $result =  ee() -> db -> get('lti_member_resources');

        $res = $result -> result();
        $res_count = $result -> num_rows();
        shuffle($res);

        ee() -> db -> where(array('context_id' => $this -> context_id, 'tool_consumer_instance_id' => $this -> tool_consumer_instance_id, 'is_instructor' => '0'));
        ee() -> db -> from('lti_member_contexts');

        $_m_res =   ee() -> db -> get();
        $mem_count = $_m_res -> num_rows();

        if($mem_count > 0) {
          $mem_res = $_m_res->result();
        } else {
          return;
        }

        $used_contexts = array();
       // $used_resources = array();
        $deb = "";

        $batch = array();
        foreach ($res as $row) {
              foreach ($mem_res as $mem_row) {
                  if(in_array($mem_row->id, $used_contexts) === FALSE) {
                    $data = array('internal_context_id' => $mem_row->id, 'base_name' => $row->base_name);
                      $batch[] = $data;

                      $used_contexts[] = $mem_row->id;
                      $used_resources[] = $row->id;
                      break;
                 }
              }
        }
        $__i = count($used_resources);

        if($__i < $mem_count) {
          $message = "<p>Not enough resources for all members of this course ($__i Resources > $mem_count Students). Therefore, ".((Integer)$mem_count-$__i)." students have no resources assigned to them.</p>";
        }

        $success = ee()->db->update_batch('lti_member_resources', $batch, 'base_name');

        $ur_count = count($used_resources);
        $uc_count = count($used_contexts);
        $error = "";

        if ($mem_count > $uc_count) {
            $this -> random_form_error = lang('_error');
        }

        $form .= "<p>$ur_count resources assigned to $uc_count users.</p>";
    }

    ee() -> load -> helper('form');

    $form .= "<p>By clicking the button below you will randomly assign a unique resource to each student,
          this resource will appear when they click on the link in this course.<br> <strong>
          <span style='color: red;'>WARNING: THIS BUTTON WILL RE-ASSIGN RESOURCES EVERYTIME IT IS CLICKED.
          Use the <u>remainder</u> button below if you wish to assign resources to remaining students.</strong></p>";
    $form .= form_open_multipart($this->base_url);
    $form .= form_hidden('do_random', 'yep');
    $form .= form_submit("Randomly", "Assign a unique resource to each student");
    $form .= form_close();

    return $form;
};
/*
* Generate inline tags for instructor
*/
$launch_instructor = function(&$params) {
        $tag_data = $params['tag_data'];

        if($data = $this->random_form()) {
              $params['tag_data']['random_form'] = $data;
        }
    };
?>
