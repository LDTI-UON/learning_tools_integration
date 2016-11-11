<?php
$hook_method = function() {
  if(!empty($this->use_resources) && $this->use_resources === 'download_link') {
    $tag_data["download_resource"] = $this -> download_resource();
  }
};

/*
* Generate inline tags for instructor
*/
$launch_general = function($params = NULL) {
        $this->download_link();

      return $params;
};
?>
