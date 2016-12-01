<?php
use LTI\ExtensionHooks\Encryption;
use LTI\ExtensionHooks\Gradebook;
use LTI\ExtensionHooks\Auth;
use LTI\ExtensionHooks\Utils;

$hook_method = function($view_data) {
        ee() -> load -> helper('url');
            $view_data['email_settings'] = "";

            $password_req = FALSE;
            $query = ee()->db->get_where('lti_instructor_credentials', array('member_id' => $this->member_id, 'context_id' => $this->context_id));

            if($query->num_rows() == 0) {

               if(!isset($_POST['email_optout'])) {
                   $form = form_open($this->base_url, $this->base_form_attr);
                   $form .= "<div class='form-group'>";
                   $data = array(
                                          'name'        => 'optout',
                                          'id'          => 'optout',
                                          'value'       => '1',
                                          'checked'   => FALSE,
                                    );
                     $data1 = array(
                                          'name'        => 'optout',
                                          'id'          => 'optout',
                                          'value'       => '0',
                                          'checked'   => TRUE,
                                    );

                   $form.= form_hidden('email_optout', 'posted');

                   $form .= "<div class='radio-inline'>".form_radio($data).lang('opt-out')."</div><div class='radio-inline'>".form_radio($data1).lang('opt-in')."</div>    ".form_submit('submit', 'Okay', $this->form_submit_class);
                   $form .= "</div>";
                   $form .= form_close();
                   $div = Utils::bootstrap_message_modal(array('id' => 'sync_message',
                                                            'header' => 'Blackboard Gradebook Sync',
                                                                   'instructions' =>
                                                                                   lang('email_opt_out'),
                                                                                   'form' => $form
                                                                                 )
                                                               );
                   $view_data['email_settings'] = $div;
                 } else {
                    ee()->db->insert('lti_instructor_credentials', array('member_id' => $this->member_id, 'context_id' => $this->context_id, 'resource_link_id' => $this->resource_link_id, 'disabled' => ee()->input->post('optout')));
                    redirect($this->base_url);

                    return;
                }
            } else {

                if($query->row()->disabled == 0) {
                    if($query->row()->password === NULL) {

                ee()->load->library('form_validation');
                ee()->form_validation->set_rules('password', 'Password', 'required|matches[password_conf]');
                ee()->form_validation->set_rules('password_conf', 'Password Confirmation', 'required');

                $form_valid = ee()->form_validation->run();

                if (empty($form_valid) || $form_valid === FALSE) {

                    $form = form_open($this->base_url, $this->base_form_attr)."<div class='container'>";

                    $form .= "<div class='row'><div class='col-xs-10'><h2>".lang('password_title')."</h2></div></div>";

                        $data = array(
                                      'name'        => 'password',
                                      'id'          => 'password',
                                      'value'       => '',
                                      'maxlength'   => '18',
                                      'size'        => '12',
                                      'class'       => 'form-control form-control-xs'
                                );

                    $form .= "<div class='row'><div class='col-xs-3'><label for='password'>Password:</label></div><div class='col-xs-4'>".form_password($data)."</div></div>";

                     $data = array(
                                      'name'        => 'password_conf',
                                      'id'          => 'password_conf',
                                      'value'       => '',
                                      'maxlength'   => '18',
                                      'size'        => '12',
                                      'class'       => 'form-control form-control-xs'
                                );


                    $form .= "<div class='row'><div class='col-xs-3'><label for='password_conf'>Confirm Password: </label></div><div class='col-xs-4'>".form_password($data)."</div></div>";
                    $form .= "<div class='row'><div class='col-xs-10'>";
                    $form .= form_submit('submit', lang('set_outlook_password'), $this->form_submit_class);
                    $form .= "</div></div></div>";
                    $form .= form_close();


                    $contents = Utils::bootstrap_message_modal(
                               array('id' => 'sync_message',
                                      'header' => 'Blackboard User Sync',
                                     'instructions' => ''/*lang('outlook_instructions')*/,
                                     'form' => $form)
                           );

                    $view_data['email_settings'] = $contents;
                } else {
                    $password = ee()->input->post('password');

                    $salt_key = Encryption::get_salt($this->user_id.$this->context_id);
                    $crypted_password = Encryption::encrypt($password, $salt_key);

                    ee()->db->where(array('member_id' => $this->member_id, 'context_id' => $this->context_id, 'resource_link_id' => $this->resource_link_id));
                    ee()->db->update('lti_instructor_credentials', array('password' => $crypted_password, 'state' => '1'));

                    redirect($this->base_url);
                    return;
                }
            }

            $time_diff = 0;
            if(isset($query->row()->uploaded)) {
                $time_diff = (Integer) time() - strtotime($query->row()->uploaded);

                    if((isset($_POST['force_sync']) ||
                                (
                                    !empty($_REQUEST['user_id']) && !empty($_REQUEST['context_id'])
                                )
                        ) && !empty($query->row()->password)) {

                        $decrypted = Encryption::decrypt($query->row()->password, Encryption::get_salt($this->user_id.$this->context_id));

                        ee()->db->where(array('member_id' => $this->member_id, 'context_id' => $this->context_id, 'resource_link_id' => $this->resource_link_id));

                        if($decrypted !== FALSE) {

                        $bb_auth = new Auth($this);

                        $auth = $bb_auth->bb_lms_login($this->username, $decrypted);
                        $this->gradebook_auth = $auth;

                        $jsstr = "";
                        $jsfn = "";
                        $form = "";
                            if ($auth === 0) {
                                ee()->db->update('lti_instructor_credentials', array('state' => '0'));

                                // groups only imported if the grade book has been changed or
                                // the syncronize button has been selected
                                $lastLogEntryTS = isset($_POST['force_sync']) ? -1 : $query->row()->lastLogEntryTS;

                                $bb_groups = new Gradebook($this);
                                $imported = $bb_groups->bb_import_groups_from_gradebook($lastLogEntryTS);

                                if(is_array($imported)) {
                                     // if not changed then update
                                    if(isset($imported['lastLogEntryTS']) && $imported['lastLogEntryTS'] !== FALSE) {
                                        ee()->db->where(array('member_id' => $this->member_id, 'context_id' => $this->context_id, 'resource_link_id' => $this->resource_link_id, ));

                                        ee()->db->update('lti_instructor_credentials', array('lastLogEntryTS' => $imported['lastLogEntryTS']));
                                    }

                                    if(!empty($imported['message'])) {
                                        $form .= "<p>".$imported['message']."</p>";
                                    }
                                    if(!empty($imported['errors'])) {
                                        $form .= "<p class='.warning'><b>ATTENTION!  ".$imported['errors']."</b></p>";
                                    }

                                    if(!empty($imported['errors']) || !empty($imported['message'])) {
                                        $form .= "<p><b>Group/Student Sync <a target=\"_blank\" href=\"$this->help_url/guides/Instructors#gradebook-syncronisation\">
                                              <img class=\"contextual_help_inline\" src=\"".URL_THIRD_THEMES."lti_peer_assessment/Help-48.png\"></a></b>";

                                        $form .= "<p><em>Your Groups will automatically sync everytime you access this tool from Blackboard.</em></p>";
                                    }
                                }
                            } else if($auth === 1) {
                                $jsfn = 'var reloadMe = function() { location.reload(true); };';
                                $jsstr = ', reloadMe';

                                ee()->db->update('lti_instructor_credentials', array('password' => NULL, 'state' => '2'));

                                $form = "<p><h1>Bad Password</h1><b>I could not connect to Grade Centre. You will be asked for your password again in 5 seconds.</p>";
                            } else if($auth === 2) {
                                 $form = "<p><h1>UoNline Server Down</h1><b>I could not connect to Grade Centre. The UoNline server may be down, please use manual upload for the time being.</p>";
                                 ee()->db->update('lti_instructor_credentials', array('password' => NULL, 'state' => '3'));
                            }

                            if(!empty($form)) {
                              $form .= "<script> $jsfn </script>";

                              $div = Utils::bootstrap_message_modal(
                                  array('id' => 'sync_message',
                                      'header' => 'Blackboard User Sync',
                                      'instructions' => "Your Grade Centre connection to this course is active.",
                                      'form' => $form));

                              $view_data['email_settings'] = $div;
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
