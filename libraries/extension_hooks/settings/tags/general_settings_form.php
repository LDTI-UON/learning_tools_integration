<?php
/*  THOUGHT: ??group settings should be placed in instructor settings table, peer assessment tick box removed and
            activated automatically when placed in template
            plugins parameter??
*/
$hook_method = function() {
    $token = base64_encode($this->institution_id.$this->course_key);
    $token_valid = isset($_POST['token']) && $_POST['token'] === $token;

    $settings = Settings::get_general_settings();

    $enable_group_import = $settings["enable_group_import"];
    $plugins_active = $settings["plugins_active"];
    $row_count = $settings["row_count"];

    $table = "lti_instructor_settings";

    ee() -> load -> helper('form');
    $form = form_open($this->base_url);

    if(!empty(static::$lti_plugins)) {
        foreach(static::$lti_plugins as $plugin) {
          if(!empty($plugin)) {
                if(isset($_POST["enable_$plugin"])) {
                     $plugins_active[$plugin] = 1;
                } else if($token_valid) {
                     $plugins_active[$plugin] = 0;
                }

                $form .= "<p>".$this->plugin_setup_text[$plugin."_description"];
                $form .= form_checkbox(array('name' => "enable_$plugin", 'id' => "enable_$plugin", 'value' => '1', 'checked' => isset($plugins_active[$plugin]) && $plugins_active[$plugin] == 1));
                $form .= $this->plugin_setup_text[$plugin];
                $form .= "</p><br>";
          }
        }
    }

    if (isset($_POST['enable_group_import'])) {
        $enable_group_import = 1;
    } else if($token_valid) {
        $enable_group_import = 0;
    }

        if ($row_count == 1) {
            ee() -> db -> where(array("institution_id" => $this->institution_id, "course_key" => $this->course_key));
            ee() -> db -> update($table, array("enable_group_import" => $enable_group_import, "plugins_active" => serialize($plugins_active)));
        } else {
          if($enable_group_import !== NULL) {
              ee() -> db -> insert($table, array("course_key" => $this->course_key, "institution_id" => $this->institution_id, "enable_group_import" => $enable_group_import, "plugins_active" => serialize($plugins_active)));
          }
        }

    $form .= lang('enable_group_import') . " ";
    $form .= "<p>";
    $form .= form_checkbox(array('name' => 'enable_group_import', 'id' => 'enable_group_import', 'value' => '1', 'checked' => $enable_group_import == 1));
    $form .= form_hidden("token", $token);
    $form .= "Groups will be imported</p><br>";
    $form .= form_submit("save", "Save Group and Plugin Settings");
    $form .= form_close();

    return $form;
};

$launch_instructor = function($params) {
        $tag_data = $params['tag_data'];

        if($data = $this->general_settings_form()) {
              $params['tag_data']['general_settings_form'] = $data;
        }

        return $params;
    };
?>
