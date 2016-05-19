<?php
/*
* Added as explicit tag eg: exp:learning_tools_integration:bb_dump_gradebook , not accessible between exp:learning_tools_integration tags.
*/
require_once($this->hook_path.DIRECTORY_SEPARATOR.'blackboard'.DIRECTORY_SEPARATOR.'gradebook'.DIRECTORY_SEPARATOR.'Gradebook.php');

$hook_method = function() {
    $gradebook = new Gradebook($this);
    $json = $gradebook->bb_fetch_gradebook();

    if(is_array($json)) {
        $str = "<h1>Grade Centre Dump</h1>"."<h2>Keys</h2><pre>".var_export(array_keys($json), TRUE)."</pre>".
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
?>
