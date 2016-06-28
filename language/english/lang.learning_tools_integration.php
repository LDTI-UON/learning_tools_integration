<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$lang = array(
	'learning_tools_integration_module_name' =>
	'Learning Tools Integration',

	'learning_tools_integration_module_description' =>
	'Provides LTI context and authentication for LMS integration',

	'module_home' => 'Learning Tools Integration Home',

	'consumers_title' => 'Available Learning Tools',

	'consumer_name' => 'Provider Name',
	'no_consumers_registered' => 'No providers are registered',
	'show_consumers' => "LTI Provider Segments",

	'add_consumer' => 'Add LTI Provider',

	'add_consumer_name' => 'Display Name',
	'add_consumer_key' => 'Key',
	'add_consumer_secret' => 'Secret',
	'add_url_segment' => 'URL Segments (separate by \'/\' )',
	'delete_consumers' => 'Delete Selected LTI Provider(s)',
    'add_consumer_guid' => 'Add Tool Consumer GUID',
     'add_consumer_guid_description' => 'The guid is the URL of the tool consumer without the protocol e.g. www.stanford.edu',
	'list_institutions' => 'Registered Institution Contexts',
	'institutions_title' => 'Registered Institutions',
	'institution_id' => 'ID',
	'institution_name' => 'Name',
	'add_institution' => 'Add an Institution',
	'add_institution_name' => 'Name',
	'no_institutions_registered' => 'You have not yet registered any institutions for LTI use',
	'list_contexts' => 'List Member Contexts',
    'contexts_description' => 'These are all users that have logged in via the LMS/VLE using LTI.  You can launch a local copy via the Launch button, but it will log you out of this admin session.  When you are launching a link via the LMS, ensure that you <strong>logout</strong> of ExpressionEngine before launching a tool in the same browser to avoid a session     conflict.',
	 'context_member_id' => 'Member ID',
     'context_user_id' => 'User ID',
     'contexts_title' => 'LTI User Contexts',
     'context_launch' => 'Launch',
     'context_launch_title' => 'Launch a tool as this user',
      'context_username' => 'User name (as used on VLE)',
      'or_add_consumer' => 'Add a new Learning Tool Segment',
      'context_context_id' => 'Context ID',
      'context_tool_consumer_instance_name' => 'Consumer Instance Name',
      'context_is_instructor' => 'Instructor (0 = No, 1 = Yes)',
	'error_html_in_resource_link_description' => 'You have HTML in your resource link description. Please check your LMS\s documentation for how to setup an LTI link.',
	 'error_could_not_establish_context' => 'Could not establish context: ',
	 'consumer_deleted' => 'Provider(s) successfully deleted.',
	 'consumer_delete_question' => 'Are you sure you want to delete this/these provider(s)?',
	 'not_launch_request' => 'This does not appear to be an LTI launch request.',
	 'no_students_in_this_context' => 'There are no students currently registered for LTI services in this course',
	 'screen_name' => 'Full Name',
	 'username' => 'Username',
	 'email' => 'Email',
	 'students_table_title' => 'Students registered for personalised resources',
	 'no_resources_in_this_context' => 'No resources are currently available for this course',
	 'file_name' => 'File name',
	 'instructor_resource_table_heading' => 'Resources available to this course',
	 'student_resource_table_heading' => 'Resources available to you',
	 'random_form_error' => 'Note that there were not enough resources for each user in the group, so some users missed out.',
	 'session_expired' => 'Your session has expired after 30 minutes of inactivity, please login again by <a href="javascript:history.back()">returning to your course page</a> and clicking on the link.',
	 'download_error' => 'Sorry there was a problem with your download, no filename was supplied.  Are you logged in?',
	 "group_no" => "Group Number",
	 "group_name" => "Group Name",
	 'problem_prefix' => "Problem Prefix",
	 'solution_prefix' => "Solution Prefix",
	 'verify_email_address' => "Your University Email Address:",
	 'student_id1' => 'Student ID',
	 'student_id2' => 'Verify Student ID',
	 'staff_numberplate1' => 'Staff ID (Username you use to login)',
     'staff_numberplate2' => 'Verify Staff ID',
	 'submit_user_verification' => 'Submit User Verification',
	 'institution_name_not_set' => 'Institution name not set',
	 'vle_not_defined' => 'Your VLE\'s identity has not been defined, please add it to the consumer table.
	                       You will not be able to run this tool until you have done so. Your consumer\'s identity is: ',
	 'delete_selected' => 'Delete Selected Items',
	 'institution_delete_question' => 'This will delete this institution and all its related context GUIDs. Are you sure?',
	 'instance_guid' => 'GUID',
	 'instance_id' => 'ID',
	 'add_instance' => 'Add a tool_consumer_instance_guid',
	 'upload_tip' => 'Tip: if you make a mistake in the spreadsheet, you can correct it and re-upload all or part of the file.  Your changes will be applied.',
	 'no_contexts_registered' => 'No contexts are registered.  This usually means that no-one has launched the tool yet.',
     'blackboard_custom_msg' => "For Blackboard, copy and paste this into your tool consumer\'s launch parameters:<br><br>",
     'blackboard_custom_vars' =>
        "\tvle_coursename=@X@course.course_name@X@\n\tvle_username=@X@user.id@X@\n\tvle_pk_string=@X@course.pk_string@X@\n\tvle_user_role=@X@user.role@X@\n\tdebug=true\n\tmaint=",
        'set_outlook_password' => 'Save Password',
        'outlook_instructions' => "<p>You will <b>ONLY</b> be asked for your password for any of the following reasons:
    <ul>
      <li>access this tool for the first time</li>
	  <li>you provided an incorrect password to this tool earlier</li>
      <li>it's been over 90 days since your last login</li>
      <li>you've changed your login password for UoNline</li>
    </ul></p><p>What is my password used for? Your password is used to allow you to import users and groups from Blackboard directly into this tool.</p>",
    'email_opt_out' => "Would you like to import Blackboard groups and users export directly from Grade Centre?<br> <span style='font-size: 8pt; color: lightgray'>
						Note: This will require you to provide your UoNline password.</span>",
    'password_title' => "Please provide your password",
		'opt-out' => 'No',
		'opt-in' => 'Yes',
		'upload_student_list' => 'Export a student list from Blackboard Groups, save as a <b>Windows Formatted CSV</b> file, and then upload it here to give them access to
peer assessment &amp; targeted resources for this course.',
		'email_export_message' => 'Grade Centre group import is active. <br><br>
        You can ',
		'access_manual_upload_link' => 'access manual upload here',
		'email_export_bad_password' => 'Your UoNline password is incorrect, so I can\'t access email import',
		'email_export_not_functional' => "The email server appears to be down, please upload manually",
		'student_rows_per_page' => 'Rows per page',
		'search_students' => 'Search',
    'enable_group_import' => 'If selected, student groups will be imported from Blackboard.',
);

/* End of file lang.learning_tools_integration.php */
/* Location: /system/expressionengine/third_party/learning_tools_integration/language/english/lang.learning_tools_integration.php */
