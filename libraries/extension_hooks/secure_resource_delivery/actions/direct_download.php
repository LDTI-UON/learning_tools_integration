<?php
$hook_method = function($args) {
      return ResourceFile::direct_download($args[0]);
};

$launch_no_template =  function() {
  /* download via a clickable link */
  if (isset($_GET['download_lti_resource'])) {
      $id = ee()->input->get('download_lti_resource');

      $this -> return_data = $this -> direct_download($id);
  }
};
?>
