<?php
$view_data['email_settings'] = "";

$password_req = FALSE;
$query = ee()->db->get_where('lti_instructor_email', array('member_id' => $this->member_id));

$style = "<style>
                    #emailmsg {
                        display: block;
                        position: absolute;
                 		z-index: 1;
                        width: 100%;
                        height: auto;
                        top: 0;
                        border: thin solid black;
                        padding: 1em;
                        background-color: green;
                        left: 0;
                        color: white;
                        font-family: 'Arial', sans-serif;
                    }
                    #emailmsg h1 {
                        font-size: 16pt;
                    }
                    #emailmsg .validation {
                       color: #F6F593;
                    }
                    #emailmsg div {
                        float:left;
                        margin: 0.3em;
                        width: 400px;
                    }
                 	li.clearfix.item {
   						 margin-left: 2em;
					}
                 	#emailmsg ul {
					    list-style: initial !important;
					    margin: initial !important;
					    padding: initial !important;
                 		margin-left: 3em;
					}
                 	#emailmsg ul {
                 		margin: 1em;
        			}
                    </style>"; // TODO move to css

if($query->num_rows() == 0) {
	//echo "Howdy: ".$query->num_rows()." ll: ".$this->member_id;
	if(!isset($_POST['email_optout'])) {
		$div = $style."<div id=\"emailmsg\"><p>".lang('email_opt_out')."</p>%form%</div>";

		$form = form_open(site_url().'/'.$this->base_segment);

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

		$form .= "<p>".form_radio($data).lang('opt-out')."<br>".form_radio($data1).lang('opt-in')."<br>".form_submit('submit', 'Okay')."</p>";

		$form .= form_close();
		$form = str_replace('%form%', $form, $div);
		$view_data['email_settings'] = $form;
	} else {
		ee()->db->insert('lti_instructor_email', array('member_id' => $this->member_id, 'context_id' => $this->context_id, 'disabled' => ee()->input->post('optout')));
		redirect(site_url($this->base_segment));

		return;
	}
} else if($query->row()->password === NULL && $query->row()->disabled == 0) {
	ee()->load->library('form_validation');

	ee()->form_validation->set_rules('password', 'Password', 'required|matches[password_conf]');
	ee()->form_validation->set_rules('password_conf', 'Password Confirmation', 'required');

	$form_valid = ee()->form_validation->run();

	if (empty($form_valid) || $form_valid === FALSE) {
		$div = $style."<div id=\"emailmsg\"><div>%form%</div><div><p>".lang('outlook_instructions')."</div></p></div>";

		$form = form_open(site_url().'/'.$this->base_segment);

		$form .= "<h1>".lang('password_title')."</h1>";
		//$form .= form_hidden("set_password", "1");
		$data = array(
				'name'        => 'password',
				'id'          => 'password',
				'value'       => '',
				'maxlength'   => '20',
				'size'        => '20',
				'style'       => 'width:10%',
		);
		$form .= "<br>";
		$form .= "Password:".form_password($data);

		$data = array(
				'name'        => 'password_conf',
				'id'          => 'password_conf',
				'value'       => '',
				'maxlength'   => '20',
				'size'        => '20',
				'style'       => 'width:10%',
		);


		$form .= "<br>";
		$form .= "Confirm Password: ".form_password($data);
		$form.="<br>";
		$form .= "<span class='validation'>".validation_errors()."</span>";
		$form .= form_submit('submit', lang('set_outlook_password'));
		$form .= form_close();
		$form  =  str_replace('%form%', $form, $div);

		$view_data['email_settings'] = $form;
	} else {
		$password = ee()->input->post('password');
		$salt_key = Encryption::get_salt($this->user_id.$this->context_id);
		$crypted_password = Encryption::encrypt($password, $salt_key);

		ee()->db->where(array('member_id' => $this->member_id, 'context_id' => $this->context_id));
		ee()->db->update('lti_instructor_email', array('password' => $crypted_password, 'state' => '1'));

		redirect(site_url($this->base_segment));
		return;
	}
}

$time_diff = 0;
if(isset($query->row()->last_check)) {
	$time_diff = (Integer) time() - strtotime($query->row()->last_check);

	if($query->row()->password !== NULL && ($query->row()->state == 1 || $time_diff > 86400)) {
		$div = $style."<div id=\"emailmsg\">%form%</div>";

		$decrypted = Encryption::decrypt($query->row()->password, Encryption::get_salt($this->user_id.$this->context_id));

		$e_import = new EmailImport($this->username."@newcastle.edu.au", $decrypted, $this->context_id);

		$jsstr = "";
		$jsfn = "";
		$auth = $e_import->imap_auth();

		ee()->db->where(array('member_id' => $this->member_id, 'context_id' => $this->context_id));

		if (is_resource($auth)) {
			$form = "<p>Group Export connection for your email: $this->email is active.</p>";
			ee()->db->update('lti_instructor_email', array('state' => '0'));
		} else if($auth == 1) {
			$jsfn = 'var reloadMe = function() { location.reload(true); };';
			$jsstr = ', reloadMe';

			ee()->db->update('lti_instructor_email', array('password' => NULL, 'salt' => NULL, 'state' => '2'));

			$form = "<p><h1>Bad Password</h1><b>I could not connect to your email address: $this->email</b>. You will be asked for your password again in 5 seconds.</p>";
		} else if($auth > 1) {
			$form = "<p><b>I could not connect to your email address: $this->email</b>. The email server may be down, please use manual upload for the time being.</p>";
			ee()->db->update('lti_instructor_email', array('password' => NULL, 'salt' => NULL, 'state' => '3'));
		}

		$form .= "<script> $jsfn $(document).ready(function() { $('#emailmsg').delay(3000).slideUp(2500$jsstr); }); </script>";
		$form .= str_replace('%form%', $form, $div);

		$view_data['email_settings'] = $form;
	}
}

ee()->db->where(array('member_id' => $this->context_id));
ee()->db->update('lti_instructor_email', array('check_next' => '1')); // flag this course for email check by CRON job
