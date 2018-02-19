<?php
use LTI\ExtensionHooks\Encryption;
use LTI\ExtensionHooks\Gradebook;
use LTI\ExtensionHooks\Auth;
use LTI\ExtensionHooks\Utils;

$hook_method = function($view_data) {


        function jsEscape($str) {
            return $str; //no longer required
            $str = str_replace(array("\r\n", "\n", "\r"), ' ', $str);
            $str = addslashes($str);

            return $str;
        }

        $is_super = ee()->session->userdata('group_id') == 1;

        ee() -> load -> helper('url');

        $view_data['settings_modal'] = "";

        $password_req = FALSE;
        $query = ee()->db->get_where('lti_instructor_credentials', array('member_id' => $this->member_id, 'context_id' => $this->context_id));

            if($query->num_rows() == 0) {

              /* if(!isset($_POST['gradebook_sync_optout']) && $is_super) {
                  $attr = $this->base_form_attr;
                  $attr['id'] = "optout";

                   $form = form_open($this->base_url, $attr);

                   $data = array(
                                          'name'        => 'optout',
                                        //  'id'          => 'optout',
                                          'value'       => 'out',
                                          'checked'    => TRUE,
                                    );
                     $data1 = array(
                                          'name'        => 'optout',
                                        //  'id'          => 'optout',
                                          'value'       => 'in',
                                    );

                   $form .= form_hidden('gradebook_sync_optout', '1');

                   $form .= "<div class='radio-inline' style='margin-top: 1em;'><label>";
                   $form .= form_radio($data);
                   $form .= lang('opt-out');
                   $form .= "</label></div>";

                   $form .= "<div class='radio-inline' style='margin-top: 1em; margin-right: 1em;'><label>";
                   $form .= form_radio($data1);
                   $form .= lang('opt-in');
                   $form .= "</label></div>";
                   $form .= "<input type='submit' value='submit' id='optout_submit' style='display:none'>";
                   $form .= form_close();
                   $modal = array('id' => 'sync_message',
                                  'header' => 'Blackboard Gradebook Sync',
                                 'instructions' => lang('gradebook_sync_optout'),
                                  'form' => jsEscape($form),
                                  'callback_f' => "function(){ console.log($('div.modal form#optout')); $('div.modal form#optout').submit(); }",
                                  'button_label' => 'OK',
                                  'button_type' => 'ok'
                                  );
                    $view_data['settings_modal'] = ee()->load->view('modal', $modal, TRUE);
                 } else {
                    $disabled = ee()->input->post('optout') == "out" ? 1 : 0;
                    ee()->db->insert('lti_instructor_credentials', array('member_id' => $this->member_id, 'context_id' => $this->context_id, 'resource_link_id' => $this->resource_link_id, 'disabled' => $disabled));
                    redirect($this->base_url);

                    exit();
                }*/
                // always opt-in
                $disabled = "0"; //ee()->input->post('optout') == "out" ? 1 : 0;
                ee()->db->insert('lti_instructor_credentials', array('member_id' => $this->member_id, 'context_id' => $this->context_id, 'resource_link_id' => $this->resource_link_id, 'disabled' => $disabled));
                redirect($this->base_url);

                exit();
            } else {

                $password = $query->row()->password;

                if($password) {
                    $decrypted = Encryption::decrypt($password, Encryption::get_salt($this->user_id.$this->context_id));
                }

                $auth = NULL;
                if(isset($decrypted)) {
                    $bb_auth = new Auth($this);
                    $auth = $bb_auth->bb_lms_login($this->username, $decrypted);
                }

            //    echo "AUTH: $auth";
                if($query->row()->disabled == 0) {
                    if($auth === NULL || $auth === 1) {

                ee()->load->library('form_validation');
                $rules = array('required' => 'Please fill in both fields',
                'matches[password_conf]' => "Passwords don't match");

                ee()->form_validation->set_rules('password_conf', 'Password Confirmation', 'required', $rules);
                unset($rules['matches[password_conf]']);

                ee()->form_validation->set_rules('password', 'Password', 'required|matches[password_conf]', $rules);

                $form_valid = ee()->form_validation->run();

                if (empty($form_valid) || $form_valid === FALSE) {

                    $form = form_open($this->base_url, $this->base_form_attr)."<div class='container'>";
                    $e = form_error('password');

                    if($auth === 1) {
                        $e = "Incorrect password, please try again.";
                    }

                    $form .= "<div class='row'><div class='col-sm-3'><h5>"
                              .lang('password_title')."</h5>
                              <p style='color: red'><b>$e</b></p></div></div>";

                    $data = array(
                                  'name'        => 'password',
                                  'id'          => 'password',
                                  'value'       => '',
                                  'maxlength'   => '18',
                                  'size'        => '12',
                                  'class'       => 'form-control form-control-xs'
                            );

                    $form .= "<div class='row'><div class='col-sm-0'><label for='password'>Password:</label></div><div class='col-sm-2'>".form_password($data)."</div></div>";

                     $data = array(
                                      'name'        => 'password_conf',
                                      'id'          => 'password_conf',
                                      'value'       => '',
                                      'maxlength'   => '18',
                                      'size'        => '12',
                                      'class'       => 'form-control form-control-xs'
                                );

                    $form .= "<div class='row'><div class='col-sm-0'><label for='password_conf'>Confirm Password: </label></div><div class='col-sm-2'>".form_password($data)."</div></div>";
                    $form .= "<input type='submit' id='auth_submit' style='display:none'></input>";
                    $form .= "</div></div>";
                    $form .= form_close();

                    $modal = array('id' => 'sync_message',
                           'header' => 'Blackboard User Sync',
                           'size' => 'small',
                          'instructions' => ''/*lang('outlook_instructions')*/,
                          'form' => jsEscape($form),
                          'callback_f' => "function(){ $('div.modal form').submit();};",
                          'button_label' => lang('set_external_password'),
                          'button_type' => 'ok'
                    );

                    $view_data['settings_modal'] =   ee()->load->view('modal', $modal, TRUE);
                } else {
                    $password = ee()->input->post('password');

                    $salt_key = Encryption::get_salt($this->user_id.$this->context_id);
                    $crypted_password = Encryption::encrypt($password, $salt_key);

                    ee()->db->where(array('member_id' => $this->member_id, 'context_id' => $this->context_id, 'resource_link_id' => $this->resource_link_id));
                    ee()->db->update('lti_instructor_credentials', array('password' => $crypted_password, 'state' => '1'));

                    redirect($this->base_url);
                    exit();
                }
            }

            $time_diff = 0;
            if(isset($query->row()->uploaded)) {
                $time_diff = (Integer) time() - strtotime($query->row()->uploaded);
              //  $is_super = !empty($_REQUEST['user_id']) && !empty($_REQUEST['context_id'];
                if(isset($_POST['syncronize']) && !empty($query->row()->password)) {

                        ee()->db->where(array('member_id' => $this->member_id, 'context_id' => $this->context_id, 'resource_link_id' => $this->resource_link_id));

                        if($decrypted !== FALSE) {

                        $this->gradebook_auth = $auth;

                        $jsstr = "";
                        $jsfn = "";
                        $form = "";
                            if ($auth === 0) {
                                ee()->db->update('lti_instructor_credentials', array('state' => '0'));

                                // groups only imported if the grade book has been changed or
                                // the syncronize button has been selected
                                $lastLogEntryTS = isset($_POST['syncronize']) ? -1 : $query->row()->lastLogEntryTS;

                                $bb_groups = new Gradebook($this);
                                $imported = $bb_groups->bb_import_groups_from_gradebook($lastLogEntryTS);

                                if(is_array($imported)) {
                                     // if not changed then update
                                    if(isset($imported['lastLogEntryTS']) && $imported['lastLogEntryTS'] !== FALSE) {
                                        ee()->db->where(array('member_id' => $this->member_id, 'context_id' => $this->context_id, 'resource_link_id' => $this->resource_link_id, ));

                                        ee()->db->update('lti_instructor_credentials', array('lastLogEntryTS' => $imported['lastLogEntryTS']));
                                    }

                                    if(!empty($imported['message'])) {
                                        $form .= "<p class='.text-success'>".$imported['message']."</p>";
                                    }
                                    if(!empty($imported['errors'])) {
                                        $form .= "<p class='.text-danger'><b>ATTENTION!  ".$imported['errors']."</b></p>";
                                    }

                                    if(!empty($imported['errors']) || !empty($imported['message'])) {
                                        $form .= "<p><b>Group/Student Sync <a target=\"_blank\" href=\"$this->help_url/guides/Instructors#gradebook-syncronisation\">
                                              <img class=\"contextual_help_inline\" src=\"".URL_THEMES."lti_peer_assessment/Help-48.png\"></a></b>";

                                        $form .= "<p><em>Your Groups will automatically sync everytime you access this tool from Blackboard.</em></p>";
                                    }
                                }
                            } else if($auth === 1) {
                                $jsfn = 'var reloadMe = function() { location.reload(true); };';
                                $jsstr = ', reloadMe';

                                ee()->db->update('lti_instructor_credentials', array('password' => NULL, 'state' => '2'));

                                $form = "<p><h1>Bad Password</h1><b>I could not connect to Grade Centre. You will be asked for your password again in 5 seconds.</p>";
                            } else if($auth === 2) {
                                 $form = "<p><h1>Server Down</h1><b>I could not connect to $this->base_url, using [$decrypted]. The server may be down, please use manual upload for the time being.</p>";
                                 ee()->db->update('lti_instructor_credentials', array('password' => NULL, 'state' => '3'));
                            }

                            if(!empty($form)) {
                              $form .= "<script> $jsfn </script>";

                              $modal =
                                  array('id' => 'sync_message',
                                      'header' => 'Blackboard User Sync',
                                      'instructions' => "Your Grade Centre connection to this course is active.",
                                      'form' => jsEscape($form));

                              $view_data['settings_modal'] = ee()->load->view('modal', $modal, TRUE);
                            }
                        } else {
                            ee()->db->delete("lti_instructor_credentials");
                        }
                        $view_data['css_special'] = ".contentPane { margin: 0 12px 0 222px; }";
                    } else {
                        $view_data['css_special'] = ".contentPane { margin: 0 12px 0 12px; }";
                    }
        }
    }
}
return $view_data;
};

$launch_instructor = function($params) {
        $view_data = $params['view_data'];

        if($data = $this->gradebook_import_prompt($view_data)) {
              $params['view_data'] = $data;
        }

        return $params;
    };
?>
