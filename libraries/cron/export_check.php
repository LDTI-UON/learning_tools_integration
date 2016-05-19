<?php
if (php_sapi_name() != "cli") { die("No browser script access"); }

/*
 * THIS FILE IS NO LONGER IMPLEMENTED

   Blackboard changes its export email format too often, its also less elegant than using
   the getJSON endpoint for grade book
   -------
 * Imports the last Group Bulk Export email from instructors email address
 * This loops through all instructor emails that have used the tool in the previous 30 mins
 * Emails are only imported once. To re-import existing email delete the
 *
 */

require_once(dirname(__FILE__).'/cronfig.php');

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

require_once($cronfig['env_path']."/expressionengine/third_party/learning_tools_integration/libraries/cron/env.php");

$cwd = PATH_THIRD."learning_tools_integration/libraries";
require_once($cwd."/GradebookImport.php");
require_once($cwd."/EmailImport.php");
require_once($cwd."/Encryption.php");

//require_once(PATH_THIRD.'learning_tools_integration/ext.learning_tools_integration.php');
require_once(PATH_THIRD.'learning_tools_integration/mod.learning_tools_integration.php');

// NOTE: this table no longer exists.
$query = ee()->db->get_where('lti_instructor_email', array('check_next' => '1'));

if($query->num_rows() > 0) {
	foreach($query->result() as $row) {
			if($row->state == 0) {
				$r = NULL;
					$sq = ee()->db->get_where('lti_member_contexts', array('member_id' => $row->member_id, 'context_id' => $row->context_id));

					if($sq->num_rows() > 0) {
						$mq = ee()->db->get_where('members', array('member_id' => $row->member_id));
						if($mq->num_rows() > 0) {
							$context_id = $sq->row()->context_id;
							$tool_consumer_instance_id = $sq->row()->tool_consumer_instance_id;

							//$salt = Encryption::get_salt($sq->row()->user_id.$sq->row()->context_id);
							//$password = Encryption::decrypt($row->password, $salt);

							$import = new EmailImport($mq->row()->username.'@newcastle.edu.au', Encryption::decrypt($row->password, $salt), $sq->row()->context_id, "Bulk Export Complete", "do-not-reply@blackboard.com");

							$path = $import->fetch_export_csv_from_outlook();

							if($path !== FALSE) {
								print date('d-m-Y h:m:s', time())." -- Wrote export file to path: ".$path."\n";
								print date('d-m-Y h:m:s', time())." -- Importing CSV file...\n\n";

								$fileimport = new FileImport($row->member_id, $row->context_id);
								$r = $fileimport->import('1', $path);
								$email_text = " -- $r[message]\n-----------\n$r[errors]";
								print date('d-m-Y h:m:s', time()).$email_text;
							}
						}
					}
					else {
						print date('d-m-Y h:m:s', time())." -- No member contexts yet.\n";
					}

					if(is_array($r) && isset($email_text)) {

						// To send HTML mail, the Content-type header must be set
						$headers  = 'MIME-Version: 1.0' . "\r\n";
						$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

						$headers .= "To: ".$mq->row()->username." \r\n";
						$headers .= 'From: donotreply@bold.newcastle.edu.au' . "\r\n";

						if( mail($mq->row()->username."@newcastle.edu.au", "Bulk Import Complete - Peer Assessment Tool (".$sq->row()->course_name.")", "$r[message]<hr>$r[errors]", $headers) ) {
							print date('d-m-Y h:m:s', time())." -- Sent email to ".$mq->row()->username."@newcastle.edu.au\n";
						} else {
						    print date('d-m-Y h:m:s', time())." -- Sent email FAILED ".$mq->row()->username."@newcastle.edu.au\n";
						}
					} /*else {
						print date('d-m-Y h:m:s', time())." -- No emails to send for ".$mq->row()->username."@newcastle.edu.au\n";
					}*/

					/* reset email import every 30 mins, this is turned back on by the instructor accessing the tool */
					if((time() - $row->last_check) > 30 * 60 * 60 ) {
						ee()->db->where(array('member_id' => $row->member_id, 'context_id' => $row->context_id));
						//ee()->db->update('lti_instructor_email', array('check_next' => '0'));
					}
			}
	}
}

