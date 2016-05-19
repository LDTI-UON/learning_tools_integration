<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

require_once ("libraries/utils.php");

class Learning_tools_integration_ext {

	var $settings        = array();

	var $name       = 'Learning Tools Integration';
	var $version        = '2.0';
	var $description    = 'authenticates user based on LTI launch';
	var $settings_exist = 'n';
	var $docs_url       = '';

    //var $settings = array();

	private $mod_class = "Learning_tools_integration";
	private $remember_me = FALSE;

	public $launch_presentation_return_url = "";
	public $tool_consumer_instance_name = "";
	public $lis_outcome_service_url = "";
	public $tool_consumer_instance_guid = ""; // launch guid, must be defined in the lti_tool_consumer_instances table
	public $lis_result_sourcedid = "";
	public $resource_link_id = "";
	public $ext_launch_presentation_css_url = "";
	public $user_id = "";
	public $context_id = "";
	public $context_label = "";
	public $ext_lms = "";
	public $course_key = "";
	public $course_name = "";
	public $user_key = "";
	public $user_email = "";
	public $user_short_name = "";
	public $resource_title = "";
	public $resource_link_description = "";
	public $message_pref_url = "";
	public $group_id = '6';
	public $internal_context_id = 0;
	public $isInstructor = 0;
    public $preview_member_id;

	public $institution;

	public $institution_id;
    public $course_id;

    public $tool_consumer_instance_id = 0; // internal context for this institution

    // these variables are obtained as launch parameters from Blackboard
	public $vle_username = "";
	public $vle_pk_string = "";

	public $student_username_prefix = "c";

	// general
	public $username;
	public $screen_name;
	public $session_id;
	public $email;
	public $title;
	public $file_url;

	private static $session_info;
	private $session_domain;
	private $cookie_name = "ee_lti_plugin";
	private $use_SSL = TRUE;

	private $debug = TRUE;

	public static $base_segment;
	/**
	 * Constructor
	 *
	 * @param   mixed   Settings array or empty string if none exist.
	*/
	function __construct($settings='')
	{
			$this->settings = $settings;
      ee()->config->set_item('disable_csrf_protection', 'y');
	}

	function authenticate($session) {
    // **** don't use in the CP ****
		if(strpos(@$_SERVER['REQUEST_URI'], 'admin.php') !== FALSE) {
			return FALSE;
		}

        if(isset($_GET['URL'])) return FALSE;
        // *** end don't use in CP ****

        if(ee()->config->item('website_session_type') !== 'c') {
            die("Please set the website session type to 'Cookies only'.");
        }

        if(!isset($session) || empty($session)) {
            die("I'm unable to retrieve EE session object in sessions_end hook.");
        }

		if(!ee()->input->post("segment") && !isset($_GET['s'])) { // if not an ajax or download request
			$segs = ee()->uri->segment_array();

			$myseg = array_pop($segs);

			if(strlen($myseg) == 0) {
						die('This URL is only accessible via a legitimate LTI launch.');
			}

			$result = ee()->db->get_where('blti_keys', array('url_segment' => $myseg));

			// may be a sub-page
			if($result->num_rows() == 0) {
				$set = implode("|", $segs);
				$set = "'$set'";
				//echo "Calling set $set";
				if(strlen($set) == 0) {
						die('This URL is only accessible via a legitimate LTI launch.');
				}

				ee()->db->where("url_segment REGEXP ($set)");
				$result = ee()->db->get('blti_keys');

				if($result->num_rows() == 0) {
					// not a registered LTI launch.
					return FALSE;
				} else {
					$myseg = $result->row()->url_segment;
				}
			}
		} else {
			if(ee()->input->post("segment")) {
				$myseg = ee()->input->post("segment");
			} else if(isset($_GET['s'])) {
				$myseg = ee()->input->get("s");
			}
		}

		static::$base_segment = $myseg;

		$this->session_domain = $_SERVER['HTTP_HOST'];

		if(isset( $_SERVER['HTTP_REFERER'])) {
			$url =  explode("://", $_SERVER['HTTP_REFERER']);
			$protocol = strtolower($url[0]);
		} else {
			$protocol = "http";
		}

		if(strpos($protocol, "https") === FALSE) {
			$this->use_SSL = FALSE;
		}

		/*if($this->debug) {
			if(!$this->use_SSL) {
				//$output .= "Your VLE's protocol is insecure HTTP, please get a secure SSL (HTTPS) connection.<br>";
			}
		}*/

		$new_launch = isset($_REQUEST['user_id']) && isset($_REQUEST['context_id']);

		if (!$new_launch && empty(static::$session_info)) {
			$_m = $session->userdata('member_id');

			if(!empty($_m)) {
				static::$session_info = $this -> unserializeSession($_m, $session);

				// session was FALSE, so session_id was not set on first round...
				if(static::$session_info === FALSE) {
					die("<span class='session_expired'><h2>I couldn't retrieve your session details. Please return to the course and click the link again [".__LINE__."].</h2></span>");
				}
				  /* set global variables */
					$this->set_globals(static::$session_info);
			} else {
               die("<span class='session_expired'><h2>Your session has expired. Please return to the course and click the link again [".__LINE__."]</h2></span>");
			}
		}

		if($new_launch) {

		if(empty($_REQUEST['custom_vle_username'])) {
			$this->general_message = "Please set the vle_username parameter in the LTI launch settings for your VLE.";
		}

        if(empty($_REQUEST['custom_vle_pk_string'])) {
			$this->general_message = "Please set the vle_pk_string parameter in the LTI launch settings for your VLE. This will allow group and user import from Blackboard.";
		}

		ee() -> load -> helper('url');

	/*	if (isset($_REQUEST["resource_link_description"])) {
			$string = htmlspecialchars($_REQUEST["resource_link_description"]);
		} else {
			$string = "";
		}*/

	/*	if (strlen($string) > 0) {
			if (strlen($string) != strlen(strip_tags($string))) {
				ee() -> lang -> loadfile('learning_tools_integration');
				$error = lang("error_html_in_resource_link_description");
			}
		}*/

		$this -> resource_link_id = $_REQUEST["resource_link_id"];
		$this -> user_id = $_REQUEST['user_id'];
		$this -> context_id = $_REQUEST['context_id'];
		$this -> context_label = $_REQUEST['context_label'];
		$this -> ext_lms = isset($_REQUEST['ext_lms']) ? $_REQUEST['ext_lms'] : 'not provided';
		$this -> ext_launch_presentation_css_url = isset($_REQUEST['ext_launch_presentation_css_url']) ? $_REQUEST['ext_launch_presentation_css_url'] : 'default.css';

		// set other variables
		if (isset($_REQUEST["launch_presentation_return_url"])) {
			$this -> launch_presentation_return_url = $_REQUEST["launch_presentation_return_url"];
			$this -> tool_consumer_instance_name = isset($_REQUEST["tool_consumer_instance_name"]) ? $_REQUEST["tool_consumer_instance_name"] : "Not provided";
			$this -> lis_outcome_service_url = isset($_REQUEST["lis_outcome_service_url"]) ? $_REQUEST["lis_outcome_service_url"] : "No marking service enabled";
			$this -> tool_consumer_instance_guid = $_REQUEST["tool_consumer_instance_guid"];

			$lms_check = strtolower($this->ext_lms);

			if(strpos($lms_check, 'moodle') !== FALSE) {
				$this -> tool_consumer_instance_guid = $_REQUEST["tool_consumer_instance_guid"];
			}
			else if(strpos($lms_check, 'bb') !== FALSE || strpos($lms_check, 'learn') !== FALSE) {
				if(isset( $_SERVER['HTTP_REFERER'])) {
					$this -> tool_consumer_instance_guid = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
				} else {
					$this -> tool_consumer_instance_guid = "localhost";
				}
			}

			$result = ee()->db->get_where('lti_tool_consumer_instances', array('guid' => $this->tool_consumer_instance_guid));

			$r = $result->row();

			if(empty($r->id)) {
				die(lang('vle_not_defined')." ".$this->tool_consumer_instance_guid);
			}
            unset($result);

			$this->tool_consumer_instance_id = $r->id;
			$result = ee()->db->get_where('lti_institutions', array('id' => $r->id));
			$this->institution = empty($r->id) ? lang('institution_name_not_set') : $result->row()->name;

            unset($result);

                        if(!empty($r->id)) {
                            $this->institution_id = $r->id;

                             // ensure course context exists
                            $ir = ee()->db->get_where('lti_course_contexts', array('context_id' => $this->context_id, 'institution_id' => $this->institution_id));

                            if($ir->num_rows() == 0) {
                                ee()->db->insert('lti_course_contexts', array('context_id' => $this->context_id, 'institution_id' => $this->institution_id));
                                $this->course_id = ee()->db->insert_id();
                            }  else {
                                $this->course_id = $ir->row()->id;
                            }
                        } else {
                            die('Please set an institution name in the institutions table.');
                        }
		} else {
			echo lang('not_launch_request');
		}
		//echo "GOT ".__LINE__;
        ee()->load->helper('url');
		$ee_uri = uri_string();  //ee() -> functions -> fetch_current_uri();

		require_once ('ims-blti/blti.php');
		$context = new BLTI( array('key_column' => 'oauth_consumer_key', 'secret_column' => 'secret',
				'context_column' => 'context_id', 'url_segment_column' => 'url_segment', 'force_ssl' => $this->use_SSL, 'url_segment' => static::$base_segment, 'ee_uri' => $ee_uri),
				false, false);

		if (!$context -> valid) {
			echo "<p>" . lang('error_could_not_establish_context') . "&nbsp;&nbsp;".$context -> message . " <br>Note: check the segment you have added to the learning tools table. The segment I'm at is: <b>".static::$base_segment."</b></p>\n";

			if($this->debug) {
				print "<pre>";
				print_r ($_REQUEST);
			}
			return false;
		}

		//$userKey = $context -> getUserKey();
		$this -> email = $context -> getUserEmail();
		//$email_error = '';

		if (empty($this -> email)) {
			$this -> email = $_REQUEST['tool_consumer_instance_contact_email'];
			$this->general_message .= "This appears to be a demo account, using TCs login email: $this->email";
		}

		$id = 0;

		//$result =     ee() -> db -> get_where('actions', array('class' => $this -> mod_class, 'method' => 'message_preference'));
		//$actid = $result -> row('action_id');
          //  var_dump($actid);
		//$this -> message_pref_url = site_url() . "?ACT=$actid";

          //      unset($result);

		if ($context -> isInstructor() == 0) {
			$this -> lis_result_sourcedid = isset($_REQUEST["lis_result_sourcedid"]) ? ee()->security->xss_clean($_REQUEST["lis_result_sourcedid"]) : 'not set';
		}

		$this -> isInstructor = $context -> isInstructor();
		$this -> user_key = $context -> getUserKey();
		$this -> course_key = $context -> getCourseKey();

		if(!empty($_REQUEST['custom_vle_coursename'])) {
			$this->course_name = ee()->security->xss_clean($_REQUEST['custom_vle_coursename']);
		} else {
			$this -> course_name = $context -> getCourseName();
		}

        if(!empty($_REQUEST['custom_vle_pk_string'])) {
			$this->vle_pk_string = ee()->security->xss_clean($_REQUEST['custom_vle_pk_string']);
		}

    $this->preview_member_id = isset($_REQUEST['custom_preview_member_id']) ? ee()->security->xss_clean($_REQUEST['custom_preview_member_id']) : 0;

		$this -> user_short_name = $context -> getUserShortName();
		$this -> resource_title = $context -> getResourceTitle();
		$this -> resource_link_description = htmlspecialchars($context -> getResourceLinkDescription());

		$_tkey = explode(":", $context->getUserKey());
		$this->user_id = $_tkey[1];

		$this->vle_username = ee()->security->xss_clean($_REQUEST['custom_vle_username']);

		$sql_data = array();
		// check if instructor has imported this user (LTI user_id will be NULL)
		$sql_data['username'] = $this->vle_username;
		$sql_data['user_id'] = NULL;

		$context_rows = ee() -> db -> get_where('lti_member_contexts', $sql_data);
		$_temp_r = $context_rows->row();

		// if this user wasn't imported, check if this context already exists
		if(! $_temp_r) {
			$sql_data['user_id'] = $this->user_id;

			$context_rows = ee() -> db -> get_where('lti_member_contexts', $sql_data);
			$_temp_r = $context_rows->row();
		}

		// if the context exists, then get the member record
		if($temp_r) {
			$_temp_id = $_temp_r->member_id;

			$rows = ee() -> db -> get_where('members', array('member_id' => $_temp_id));

			$count = $rows -> num_rows();
		}

		// if the member record doesn't exist create it
		if (empty($count)) {
			// ... but first check that the member username doesn't already exist    (@TODO add institution prefix for usernames...)
      $current_member = ee('Model')->get('Member')->filter('username', '==', $this->vle_username)->first();

			if(!$current_member) {
				$this->screen_name = ee()->security->xss_clean($_REQUEST['lis_person_name_given']).' '.ee()->security->xss_clean($_REQUEST['lis_person_name_family']);

				if(!empty($this->vle_username)) {
					if(FALSE !== strpos($this->vle_username, "previewuser")) {
						// ensure we are using unique email for previewuser
						$this->email = $this->vle_username."@".$this->session_domain;
					}

                    $member = ee('Model')->make('Member', array('username' => $this->vle_username, 'screen_name' => $this->screen_name, 'group_id' => $this->group_id, 'email' => $this->email, 'last_visit' => time(), 'last_activity' => time(), 'join_date' => time()));

                    $member->save();

					$id = $member->member_id;
				} else {
					die("We were not able to verify the user identity.  To fix this please set the vle_username parameter in the custom LTI launch settings.");
				}
			} else {
				$id = $current_member->member_id; //$query->row()->member_id;
				$this->screen_name = $current_member->screen_name; //query->row()->screen_name;
			}
		} else {
			$row = $rows -> row();
			$id = $row -> member_id;
		}

		// start new session for this member
		if($session->userdata('member_id') != $id) {
			$this->start_session($id, $session);
		}
		$session -> fetch_member_data();

	  	 // finally, update context
         $context_data = array("user_id" => $this -> user_id, "username" => $this->vle_username, "member_id" => $id, "session_id" => $session->userdata('session_id'),"context_id" => $this -> context_id, "context_label" => $this -> context_label, 'course_name' => $this->course_name,
                                    "ext_lms" => $this -> ext_lms, "tool_consumer_instance_id" => $this -> tool_consumer_instance_id, "tool_consumer_instance_name" => $this -> tool_consumer_instance_name,
                                    "is_instructor" => $this -> isInstructor);

         $sql =    ee() -> db -> insert_string('lti_member_contexts', $context_data) . " ON DUPLICATE KEY UPDATE user_id = '$this->user_id', session_id = '".$session->userdata('session_id')."', context_label='$this->context_label', course_name= '$this->course_name',
												ext_lms = '$this->ext_lms', tool_consumer_instance_name = '$this->tool_consumer_instance_name',
												is_instructor = '$this->isInstructor', last_launched_on = CURRENT_TIMESTAMP";

		 ee() -> db -> query($sql);

		$result =    ee() -> db -> affected_rows();

		$newres =   ee() -> db -> get_where('lti_member_contexts', array('user_id' => $this -> user_id, "member_id" => $id, "context_id" => $this -> context_id, "tool_consumer_instance_id" => $this -> tool_consumer_instance_id));
		$newrow = $newres -> row();
		$this -> internal_context_id = $newrow -> id;

		if($session->userdata('group_id') == 6 && !empty($this->email)) {
			// update email (ensuring we have correct credentials)
			ee()->db->where('member_id', $id);
			ee()->db->update('members', array("email" => $this->email));
		}

		static::$session_info = array('launch_presentation_return_url' => $this -> launch_presentation_return_url, 'tool_consumer_instance_name' => $this -> tool_consumer_instance_name, 'lis_outcome_service_url' => $this -> lis_outcome_service_url,
				'tool_consumer_instance_guid' => $this -> tool_consumer_instance_guid, 'tool_consumer_instance_id' => $this -> tool_consumer_instance_id,
				'lis_result_sourcedid' => $this -> lis_result_sourcedid, 'resource_link_id' => $this -> resource_link_id,
				'user_id' => $this -> user_id, 'user_key' => $this -> user_key, 'context_id' => $this -> context_id, 'internal_context_id' => $this -> internal_context_id,
				'context_label' => $this -> context_label, 'ext_lms' => $this -> ext_lms, 'isInstructor' => $this -> isInstructor, 'course_key' => $this -> course_key,
				'course_name' => $this -> course_name, 'user_short_name' => $this -> user_short_name, 'resource_title' => $this -> resource_title,
				'resource_link_description' => $this -> resource_link_description, 'ext_launch_presentation_css_url' => $this -> ext_launch_presentation_css_url,
                                'institution_id' => $this->institution_id, 'course_id' => $this->course_id, 'pk_string' =>$this->vle_pk_string, 'base_url' => ee()->config->item('site_url'), 'css_link_tags' => $this->css_link_tags(), 'user_email' => $this->email,
				 );

		// persist base segment for future tag calls
		static::$session_info = array_merge(static::$session_info , array('base_segment' => static::$base_segment));

		$this -> serializeSession(static::$session_info, $session);

		$this -> username =   $session -> userdata('username');
		$this -> screen_name =   $session -> userdata('screen_name');

		/* set global variables */
		$this->set_globals(static::$session_info);
			}
	}
	private function set_globals($session_info) {
			ee()->config->_global_vars['launch_presentation_return_url'] = $session_info['launch_presentation_return_url'];
	ee()->config->_global_vars['tool_consumer_instance_name'] = $session_info['tool_consumer_instance_name'];
	ee()->config->_global_vars['lis_outcome_service_url'] = $session_info['lis_outcome_service_url'];
	ee()->config->_global_vars['tool_consumer_instance_guid'] = $session_info['tool_consumer_instance_guid'];
	ee()->config->_global_vars['tool_consumer_instance_id'] = $session_info['tool_consumer_instance_id'];
	ee()->config->_global_vars['lti_internal_context_id'] = $session_info['user_key'];
	ee()->config->_global_vars['lti_user_id'] = $session_info['user_id'];
	ee()->config->_global_vars['lti_user_key'] = $session_info['user_key'];
	ee()->config->_global_vars['lti_context_id'] = $session_info['context_id'];
	ee()->config->_global_vars['lti_context_label'] = $session_info['context_label'];
	ee()->config->_global_vars['ext_lms'] = $session_info['ext_lms'];
	ee()->config->_global_vars['is_instructor'] = $session_info['isInstructor'];
	ee()->config->_global_vars['course_key'] = $session_info['course_key'];
	ee()->config->_global_vars['course_name'] = $session_info['course_name'];
			ee()->config->_global_vars['pk_string'] = $session_info['user_key'];
	ee()->config->_global_vars['lti_user_short_name'] = $session_info['user_short_name'];
	ee()->config->_global_vars['resource_title'] = $session_info['resource_title'];
	ee()->config->_global_vars['resource_link_description'] = $session_info['resource_link_description'];
			ee()->config->_global_vars['lti_user_email'] = $session_info['user_email'];
			ee()->config->_global_vars['lti_username'] = $session_info['user_key'];
	ee()->config->_global_vars['ext_launch_presentation_css_url'] = $session_info['ext_launch_presentation_css_url'];
			ee()->config->_global_vars['css_link_tags'] = $session_info['css_link_tags'];;
			//experimental student preview for use with Blackboard (not implemented in this version)
			ee()->config->_global_vars['preview_member_id'] = $this->preview_member_id;
	}

	 private function css_link_tags() {
			$consumer_css = explode(",", $this -> ext_launch_presentation_css_url);

			$consumer_css_header = "";
			foreach ($consumer_css as $css) {
					$consumer_css_header .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$css\">\n";
			}

			return $consumer_css_header;
	}
	public static function session_info() {
			return static::$session_info;
	}

	private function _lti_session_query($session) {
		$query =   ee() -> db -> get_where('lti_member_contexts', $this->_session_where_clause($session));
		return $query;
	}

	private function _session_where_clause($session) {
		return array('session_id' => $session->userdata('session_id'), "member_id" => $session->userdata('member_id'));
	}

	private function unserializeSession($member_id, $session) {
		$query = $this->_lti_session_query($session);

		if ($query -> num_rows() > 0) {
			$row = $query -> row();
			return unserialize($row -> session_data);
		}

		return FALSE;
	}

	private function serializeSession($session_info, $session) {
		$ser_session = array('session_data' => serialize($session_info));

		ee() -> db -> where($this->_session_where_clause($session));
		ee() -> db -> update('lti_member_contexts', $ser_session);
	}

	/**
	 * Activate Extension
	 *
	 * This function enters the extension into the exp_extensions table
	 *
	 * @see https://ellislab.com/codeigniter/user-guide/database/index.html for
	 * more information on the db class.
	 *
	 * @return void
	 */
	function activate_extension()
	{

		$data = array(
				'class'     => __CLASS__,
				'method'    => 'authenticate',
				'hook'      => 'sessions_end',
				//'settings'  => serialize($this->settings),
				'priority'  => 1,
				'version'   => $this->version,
				'enabled'   => 'y'
		);

		ee()->db->insert('extensions', $data);
	}

	/* This is a variation of the same function in the Auth.php file*/

	private function start_session($member_id, $session)
	{
		/* Not using the $multi login function, LTI checks for existing login already */
		$sess_type = 'website_session_type';

		// Create a new session
		$this->session_id = $session->create_new_session(
					$member_id,
					FALSE
		);

		if (ee()->config->item($sess_type) != 's')
		{
			//$expire = ee()->remember->get_expiry();

			ee()->input->delete_cookie($session->c_anon);

			// (un)set remember me
			if ($this->remember_me)
			{
				ee()->remember->create();
			}
			else
			{
				ee()->remember->delete();
			}
		}

		// We're trusting LTI here to be water tight...
		// Delete old password lockouts
		$session->delete_password_lockout();
	}

	/**
	 * Update Extension
	 *
	 * This function performs any necessary db updates when the extension
	 * page is visited
	 *
	 * @return  mixed   void on update / false if none
	 */
	function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}

		if ($current < '1.0')
		{
			// Update to version 1.0
		}

		ee()->db->where('class', __CLASS__);
		ee()->db->update(
				'extensions',
				array('version' => $this->version)
		);
	}
	/**
	 * Disable Extension
	 *
	 * This method removes information from the exp_extensions table
	 *
	 * @return void
	 */
	function disable_extension()
	{
		ee()->db->where('class', __CLASS__);
		ee()->db->delete('extensions');
	}
	// END
}
