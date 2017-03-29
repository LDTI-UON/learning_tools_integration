<?php
use LTI\ExtensionHooks\Gradebook;
/*
* Added as explicit tag eg: exp:learning_tools_integration:bb_dump_gradebook , not accessible between exp:learning_tools_integration tags.
*/

$hook_method = function() {
    $gradebook = new Gradebook($this);
    $json = $gradebook->bb_fetch_gradebook();

    if(is_array($json)) {
      $as_json = json_encode($json);

      file_put_contents(ee()->config->item('lti_cache').'raw_gradebook.json', $as_json);

      $str = "<h1>Grade Centre Dump</h1><em>The raw json has been placed in the raw_gradebook.json file in the application cache directory.</em><h2>Keys</h2><pre>".var_export(array_keys($json), TRUE)."</pre>".
        "<h1>Grade Book</h1>";

        $gbook = isset($json['cachedBook']) ? $json['cachedBook'] : $json;

        foreach(array_keys($gbook) as $key) {
            $str .= "<h2>$key</h2>";
            $str .= "<pre>".var_export($gbook[$key], TRUE)."</pre>";
        }

        return $str;
    } else {
        return "<h1>You are not authorised to access grade centre for this course</h1>";
    }
};

$launch_instructor =  function($params){
  // this task for Super Users only
  if(ee()->session->userdata('group_id') != 1) return;

  $tag_data = $params['tag_data'];

  if($data = $this->bb_dump_gradebook()) {
        $params['tag_data']['bb_dump_gradebook'] = $data;
  }

  return $params;
};
?>
