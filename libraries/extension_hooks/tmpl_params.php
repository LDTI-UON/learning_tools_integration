<?php
/*
    This list is used to provide template toggles for inline, pre-rendered tags.
    Template toggle set to 1/0 OR true/false in {exp:learning_tools_integration} tag
    format is:  'toggle name' => list of tags to toggle
*/
$tmpl_extension_toggles = array('use_resource_delivery' => array('resource_settings_form',
                                                      'upload_student_resources_form',
                                                      'random_form',
                                                      'random_remainder_form',
                                                      'resource_table',
                                                      'random_form_error',
                                                      'process_resource_file',
                                                      'download_link',
                                                    ),
                                                );

/*
* This array provides a list of parameters and the method that they supply the value too from the template.
*
*/
$tmpl_value_params = array('download_redirect' => 'download_resource');
?>
