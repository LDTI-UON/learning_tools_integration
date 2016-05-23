<?php
require_once($this->hook_path.DIRECTORY_SEPARATOR.'secure_resource_delivery'.DIRECTORY_SEPARATOR.'ResourceFile.php');
require_once($this->hook_path.DIRECTORY_SEPARATOR.'secure_resource_delivery'.DIRECTORY_SEPARATOR.'ResourceModel.php');

$hook_method = function() {
    if($this -> isInstructor != 0) {
      if(ee()->session->userdata('group_id') == 1) {
          echo "<h2>Super User message: No access for instructors</h2>";
      }
      return FALSE;
    }

    $model = new ResourceModel($this);
    $total = $model -> total_resources();

    if ($total != 1) {
      if(ee()->session->userdata('group_id') == 1) {
          echo "<h2>Super User message: No file to download</h2>";
      }
       return FALSE;
    }

    $type = $this -> is_solution_request() ? 'solution' : 'problem';

    $sqltype = $type == 'solution' ? 'S' : 'P';

    //echo "GOT HERE $total<br>";
    ee() -> db -> select('lti_member_resources.id, lti_member_resources.display_name, lti_member_resources.file_name, lti_member_resources.salt');
    ee() -> db -> join('lti_member_contexts', 'lti_member_contexts.id = lti_member_resources.internal_context_id');
    ee() -> db -> where(array("lti_member_resources.internal_context_id" => $this -> internal_context_id, 'type' => $sqltype));
    ee() -> db -> from('lti_member_resources');
    $query =   ee() -> db -> get();

    $row = $query -> row();

    return ResourceFile::download_file($row -> file_name, $type, $row -> salt);
};

/*
* Generate inline tags for instructor
*/
$launch_general = function(& $params = NULL) {
        $this->download_resource();

      return NULL;
};

?>
