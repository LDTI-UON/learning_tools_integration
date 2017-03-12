<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Learning_tools_integration_ext {

	var $settings        = array();

	var $name       = 'Learning Tools Integration';
	/*version line (do not delete the line below, auto updated on build) */
	var $version 			= '3.4.1';//#build version#

	var $description    = 'authenticates user based on LTI launch';
	var $settings_exist = 'n';
	var $docs_url       = '';

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
	public $general_message = "";

	protected $flashdata = null;
    //public $preview_member_id; seems too complex to implement at this stage

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

	//private $session_id;
	private static $session_info;
	private $session_domain;
	private $cookie_name = "ee_lti_plugin";
	private $use_SSL = TRUE;

	private $debug = TRUE;

	public $lti_error = NULL; //for error tag in templates

	public $base_segment;

	private $LTI_ACT_services;
	/**
	 * Constructor
	 *
	 * @param   mixed   Settings array or empty string if none exist.
	*/
	function __construct($settings='')
	{
			$this->settings = $settings;
      ee()->config->set_item('disable_csrf_protection', 'y'); // this is re-enabled after login
			ee()->lang->loadfile('lti_peer_assessment');

			// comment these out for production
			/*if(isset($_GET['ltiACT'])) {
				header('Access-Control-Allow-Origin: *');
				header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
			}*/

			$mod_path = PATH_THIRD.strtolower($this->mod_class).DIRECTORY_SEPARATOR;

			ee()->config->load("lti_config");
			// these services bypass LTI, any services here should be secured against XSS and
			// embody strong validation

			global $LTI_ACT_services;

			include_once($mod_path."libraries/extension_hooks/ACT_params.php");

			$this->LTI_ACT_services = $LTI_ACT_services;

			ee()->load->library('logger');
	}


	private static function get_user_agent() {
				$agent = $_SERVER['HTTP_USER_AGENT'];

				if(strpos($agent, 'MSIE') !== FALSE) {
						return "IE";
				}

				if(strpos($agent, 'Mozilla') !== FALSE) {
						if(strpos($agent, 'Firefox') !== FALSE || strpos($agent, 'Chrome') !== FALSE) {
								return "MOZILLA";
						}
				}
	}

	private static function set_safe_xframe_header($referer) {
		$agent = static::get_user_agent();
		if($agent === "IE") {
				ee()->output->set_header("X-Frame-Options: ALLOW-FROM $referer:*");
		} else {
				ee()->output->set_header("Content-Security-Policy: script-src 'self' 'unsafe-inline' 'unsafe-eval' ajax.googleapis.com code.jquery.com maxcdn.bootstrapcdn.com; default-src 'self' $referer:*; style-src 'self' 'unsafe-inline' fonts.googleapis.com maxcdn.bootstrapcdn.com $referer:*; img-src 'self' $referer:*; frame-ancestors 'self' $referer:*; font-src 'self' fonts.gstatic.com maxcdn.bootstrapcdn.com");
		}
	}

	private static function deny_xframe_header() {
		$agent = static::get_user_agent();

		if($agent === "IE") {
			ee()->output->set_header("X-Frame-Options: DENY");
		}	else {
			ee()->output->set_header("Content-Security-Policy: default-src 'self';");
		}
	}

	function get_session($session, $id = NULL) {
		session_start();
		$uid = isset($_SESSION['apeg_uid']) ? $_SESSION['apeg_uid'] : $id;
		session_write_close();

		// set validation
		$session->validation = ee()->config->item('website_session_type'); // cookies only!

		$session->sdata['session_id'] = ee()->input->cookie($session->c_session);

		// Did we find a session ID?
		$session_set = isset($session->sdata['session_id']);

		// Fetch Session Data
		// IMPORTANT: The session data must be fetched before the member data so don't move this.
		if ($session_set === TRUE && $session->fetch_session_data() === TRUE)
		{
			$session->session_exists = TRUE;
		}

		if ($session->session_exists === TRUE && $id === NULL)
		{
				$session->update_session();
		}
		else
		{
			$session->create_new_session($uid);
			//$session->sdata['session_id'] = ee()->input->cookie($session->c_session);
		}

		$session->fetch_member_data();

		$this->session_id = $session->sdata['session_id'];

		// Kill old sessions
		$session->delete_old_sessions();

		// Merge Session and User Data Arrays
		// We merge these into into one array for portability
		$session->userdata = array_merge($session->userdata, $session->sdata);
		ee()->extensions->end_script = TRUE;

		$_SESSION['apeg_uid'] = $uid;
	}

	function authenticate($session) {
		if(!isset($session)) {
				die("No session object!");
		}

    // **** don't use in the CP ****
		if(strpos(@$_SERVER['REQUEST_URI'], 'admin.php') !== FALSE) {
			return FALSE;
		}

		if(!empty($_GET['ACT'])) {
			return FALSE;
		}

		if(isset($_GET['URL'])) return FALSE;

		/* embed in iFrame in Blackboard */
		if(!empty($_GET['BB_EMBED'])) {
				if(isset($_SERVER['HTTP_REFERER'])) {
						$referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
						static::set_safe_xframe_header($referer);
				}
				return FALSE;
		}

		if(ee()->config->item('website_session_type') !== 'c') {
        die("Please set the website session type to 'Cookies only'.");
    }

    if(!isset($session) || empty($session)) {
        die("I'm unable to retrieve EE session object in sessions_end hook.");
    }

		if(!ee()->input->post("segment") && !isset($_GET['s']) && !isset($_GET['ltiACT'])) { // if not an ajax or download request
			$segs = ee()->uri->segment_array();
			if(empty($segs[1])) return FALSE;

			$myseg = $segs[1];

			if(strlen($myseg) == 0) {
						return FALSE;
			}

			$result = ee()->db->get_where('blti_keys', array('url_segment' => $segs[1]));

			if($result->num_rows() == 0) {
					return FALSE;
			}
		} else {
			if(ee()->input->post("segment")) {
					$myseg = ee()->input->post("segment");
			} else if(isset($_GET['s'])) {
					$myseg = ee()->input->get("s");
			} else if(isset($_GET['ltiACT'])) {
				// only one bypass action can be called at a time
					$ltiACT = ee('Security/XSS')->clean($_GET['ltiACT']);

					if(!empty($this->LTI_ACT_services[$ltiACT])) {
							global $ACT_hook;

							$mod_path = PATH_THIRD.strtolower($this->mod_class).DIRECTORY_SEPARATOR;
							if(file_exists($mod_path."libraries/extension_hooks")) {
									include_once($mod_path."libraries/extension_hooks/ACT_params.php");
									require_once($mod_path.$this->LTI_ACT_services[$ltiACT]);

									$ACT_hook();
							}

							return FALSE;
					} else {
						die('This resource is not available. <pre> '.var_export($this->LTI_ACT_services, TRUE));
					}
			}
		}

		$this->base_segment = $myseg;
		ee()->config->_global_vars['base_segment'] = $myseg;

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

		if(!$this->use_SSL) {
			ee()->logger->developer("WARNING: LTI is being used on a non-secure connection.\n Always use LTI on SSL only in production.\n");
		}

		$new_launch = isset($_REQUEST['user_id']) && isset($_REQUEST['oauth_consumer_key']) && isset($_REQUEST['context_id']);

		if(!isset($_REQUEST['user_id']) && isset($_REQUEST['oauth_consumer_key']) && isset($_REQUEST['context_id'])) {
				die('Please ensure that your LMS is passing the user credentials. This is set in your LTI adminstration area.');
		}

		if (!$new_launch && empty(static::$session_info)) {

			$this->get_session($session);
			$uid = $session->userdata('member_id');

			if(!empty($uid)) {
				static::$session_info = $this -> unserializeSession($uid);

				// session was FALSE, so session_id was not set on first round...
				if(static::$session_info === FALSE) {
					die("<span class='session_expired'><h2>I couldn't retrieve your session details. Please return to the course and click the link again.</h2></span>");
				}

				$referer = static::$session_info['tool_consumer_instance_guid'];
				static::set_safe_xframe_header($referer);

			  /* set global variables */
				$this->set_globals(static::$session_info);
			} else {
        			die("<span class='session_expired'>".var_export($_REQUEST, TRUE)."<h2>[$uid] Your session has expired. Please return to the course and click the link again [".__LINE__."]</h2></span>");
			}
		}

		if($new_launch) {
			if(isset($_SESSION['apeg_uid'])) unset($_SESSION['apeg_uid']);

			// clickjack prevention
			$deny_iframe = isset($_SERVER['HTTP_REFERER']);

			if($deny_iframe) {
				$referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
				$res = ee()->db->get_where("lti_tool_consumer_instances", array('guid' => $referer));

				if($res->row()) {
						$deny_iframe = $res->row()->id ? FALSE : TRUE;
				}
			}

			if($deny_iframe) {
					static::deny_xframe_header();
			}	else {
				  static::set_safe_xframe_header($referer);
			}

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

    	ee()->load->helper('url');
		$ee_uri = uri_string();

		function error_call($e, &$me) {
			$str = strtolower($e);
			$error = "";


			if(stripos($str, "expired timestamp") !== FALSE) {
						$error = lang('session_expired');
			} else {
						if(is_a($e, "Exception")) {
								$error = "<p>".$e->getMessage()."</p><pre>".$e->getTraceAsString()."</pre>";
						}
			}

			ee()->output->set_status_header(403);
			ee()->output->set_output($error);
			ee()->output->_display();

			return $error;
		}

		require_once ('ims-blti/blti.php');

		try {
				$context = new BLTI( array('key_column' => 'oauth_consumer_key', 'secret_column' => 'secret',
						'context_column' => 'context_id', 'url_segment_column' => 'url_segment', 'force_ssl' => $this->use_SSL, 'url_segment' => $this->base_segment, 'ee_uri' => $ee_uri),
						false, false);

				if (!$context -> valid) {
						$error = error_call($context->message, $this);

						$this->lti_error = $error;
						ee()->config->_global_vars['lti_has_error'] = $this->lti_error;

							//exit;
				}
		} catch (OAuthException $e) {
						$error = error_call($e, $this);

						$this->lti_error = $error;
						ee()->config->_global_vars['lti_has_error'] = $this->lti_error;

						//exit;
		}

		//$userKey = $context -> getUserKey();
		$this -> email = $context -> getUserEmail();
		//$email_error = '';

		if (empty($this -> email)) {
			$this -> email = $_REQUEST['tool_consumer_instance_contact_email'];
			$this->general_message .= "This appears to be a demo account, using TCs login email: $this->email";
		}

		$id = 0;

		$bb_instructor = FALSE;
		$roles = ee()->input->post('custom_vle_user_role');
		$bb_instructor =
			! ( strpos($roles,"instructor") === FALSE )  ||
			! ( strpos($roles,"administrator") === FALSE ) ||
			! ( strpos($roles,"A") === FALSE );

		$this->isInstructor = $context->isInstructor() || $bb_instructor;

		if (! $this->isInstructor ) {
			$this -> lis_result_sourcedid = isset($_REQUEST["lis_result_sourcedid"]) ? ee('Security/XSS')->clean($_REQUEST["lis_result_sourcedid"]) : 'not set';
		}

		$this -> user_key = $context -> getUserKey();
		$this -> course_key = $context -> getCourseKey();

		if(!empty($_REQUEST['custom_vle_coursename'])) {
			$this->course_name = ee('Security/XSS')->clean($_REQUEST['custom_vle_coursename']);
		} else {
			$this -> course_name = $context -> getCourseName();
		}

        if(!empty($_REQUEST['custom_vle_pk_string'])) {
			$this->vle_pk_string = ee('Security/XSS')->clean($_REQUEST['custom_vle_pk_string']);
		}

		$this -> user_short_name = $context -> getUserShortName();
		$this -> resource_title = $context -> getResourceTitle();
		$this -> resource_link_description = htmlspecialchars($context -> getResourceLinkDescription());

		$uk = $context->getUserKey();
		if(empty($uk)) {
			if(!$context->complete) {
				if(isset($context->message)) {
						throw new Exception($context->message);
				} else {
						throw new Exception("invalid LTI request");
				}
			}
		}

		$_tkey = explode(":", $context->getUserKey());
		$this->user_id = $_tkey[1];

		$this->vle_username = ee('Security/XSS')->clean($_REQUEST['custom_vle_username']);

		$sql_data = array();
		// check if instructor has imported this user (LTI user_id will be NULL)
		$sql_data['username'] = $this->vle_username;

		$context_rows = ee() -> db -> get_where('lti_member_contexts', $sql_data);
		$_temp_r = $context_rows->row();

		$sql_data['user_id'] = NULL;
		// if this user wasn't imported, check if this context already exists
		if($context_rows->num_rows() == 0) {
			$sql_data['user_id'] = $this->user_id;

			$context_rows = ee() -> db -> get_where('lti_member_contexts', $sql_data);
			$_temp_r = $context_rows->row();
		}

		//$count = 0;
		// if the context exists, then get the member record
		if($context_rows->num_rows() > 0) {
			$_temp_id = $_temp_r->member_id;

			$lti_member = ee('Model')->get('Member', $_temp_id)->first();
		}

		// if the member record doesn't exist create it
		if (empty($lti_member)) {
			// ... but first check that the member username doesn't already exist    (@TODO add institution prefix for usernames...)
      $current_member = ee('Model')->get('Member')->filter('username', '==', $this->vle_username)->first();

			if(!$current_member) {
				$this->screen_name = ee('Security/XSS')->clean($_REQUEST['lis_person_name_given']).' '.ee('Security/XSS')->clean($_REQUEST['lis_person_name_family']);

				if(!empty($this->vle_username)) {
					if(FALSE !== strpos($this->vle_username, "previewuser")) {
						// ensure we are using unique email for previewuser
						$this->email = $this->vle_username."@".$this->session_domain;
					}

          $member_data = array('username' => $this->vle_username, 'screen_name' => $this->screen_name, 'group_id' => $this->group_id, 'email' => $this->email, 'last_visit' => time(), 'last_activity' => time(), 'join_date' => time());

					ee()->config->load('lti_config');
					$cache = ee()->config->item('lti_ghost');
					$k = random_string();
					file_put_contents($cache.DIRECTORY_SEPARATOR.$k, serialize($member_data));

					ee()->db->where(array('method' => 'create_ghost_session'));
					$id = ee()->db->get('actions')->row()->action_id;

					$l = ee()->input->post('launch_presentation_return_url');

					//header("Location: ".base_url()."?k=$k&ACT=$id");
					echo "<html><head></head><body><p>Just generating your profile. Please wait... </p>
					<script src='//cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.slim.min.js'></script>
					<script type='text/javascript'>
					$(document).ready(function() {
						console.log('ready');
								setTimeout(
										function() {
											console.log('fired');
											document.location =  \"".base_url()."?k=$k&l=$l&ACT=$id\";
										}, 1500
								);
					});
					</script></body></html>";
					exit();


				} else {
					die("We were not able to verify the user identity.  To fix this please set the vle_username parameter in the custom LTI launch settings.");
				}
			} else {
				$id = $current_member->member_id; //$query->row()->member_id;
				$this->screen_name = $current_member->screen_name; //query->row()->screen_name;
			}
		} else {
				$id = $lti_member->member_id;
		}

		$this->get_session($session, $id);

		if(!isset($id)) die("FATAL ERROR - user not registered");

	  	 // finally, update context
         $context_data = array("user_id" => $this -> user_id, "username" => $this->vle_username, "member_id" => $id, "session_id" => $this->session_id,"context_id" => $this -> context_id, "context_label" => $this -> context_label, 'course_name' => $this->course_name,
                                    "ext_lms" => $this -> ext_lms, "tool_consumer_instance_id" => $this -> tool_consumer_instance_id, "tool_consumer_instance_name" => $this -> tool_consumer_instance_name,
                                    "is_instructor" => $this -> isInstructor);



         $sql =    ee() -> db -> insert_string('lti_member_contexts', $context_data) . " ON DUPLICATE KEY UPDATE user_id = '$this->user_id', session_id = '".$this->session_id."', context_label='$this->context_label', course_name= '$this->course_name',
												ext_lms = '$this->ext_lms', tool_consumer_instance_name = '$this->tool_consumer_instance_name',
												is_instructor = '$this->isInstructor', last_launched_on = CURRENT_TIMESTAMP";

		 ee() -> db -> query($sql);

		$result =    ee() -> db -> affected_rows();

		$newres =   ee() -> db -> get_where('lti_member_contexts', array('user_id' => $this -> user_id, "member_id" => $id, "context_id" => $this -> context_id, "tool_consumer_instance_id" => $this -> tool_consumer_instance_id));
		$newrow = $newres -> row();
		$this -> internal_context_id = $newrow -> id;

		if(!empty($this->email)) {
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
		static::$session_info = array_merge(static::$session_info , array('base_segment' => $this->base_segment));

		$this -> serializeSession(static::$session_info, $id);

		$this -> username =   $session -> userdata('username');
		$this -> screen_name =   $session -> userdata('screen_name');

			/* set global variables */
			$this->set_globals(static::$session_info);
		}
	}

	protected function _prep_flashdata($session)
	{
		if ($cookie = ee()->input->cookie('flash'))
		{
			if (strlen($cookie) > 32)
			{
				$signature = substr($cookie, -32);
				$payload = substr($cookie, 0, -32);

				if (md5($payload.$session->sess_crypt_key) == $signature)
				{
					$session->flashdata = unserialize(stripslashes($payload));
					$session->_age_flashdata();

					return;
				}
			}
		}

		$session->flashdata = array();
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
				ee()->config->_global_vars['resource_link_id'] = $session_info['resource_link_id'];
				ee()->config->_global_vars['lti_user_short_name'] = $session_info['user_short_name'];
				ee()->config->_global_vars['resource_title'] = $session_info['resource_title'];
				ee()->config->_global_vars['resource_link_description'] = $session_info['resource_link_description'];
				ee()->config->_global_vars['lti_user_email'] = $session_info['user_email'];
				ee()->config->_global_vars['ext_launch_presentation_css_url'] = $session_info['ext_launch_presentation_css_url'];
				ee()->config->_global_vars['css_link_tags'] = $session_info['css_link_tags'];

				//Blackboard specific
				ee()->config->_global_vars['bb_pk_string'] = $this->vle_pk_string;
				ee()->config->_global_vars['bb_username'] = $this->vle_username;
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

	private function _lti_session_query($member_id) {
		$query =   ee() -> db -> get_where('lti_member_contexts', $this->_session_where_clause($member_id));
		return $query;
	}

	private function _session_where_clause($member_id) {
		return array("member_id" => $member_id);
	}

	private function unserializeSession($member_id) {
		$query = $this->_lti_session_query($member_id);

		if ($query -> num_rows() > 0) {
			$row = $query -> row();
			return unserialize($row -> session_data);
		}

		return FALSE;
	}

	private function serializeSession($session_info, $member_id) {
		$ser_session = array('session_data' => serialize($session_info));

		ee() -> db -> where($this->_session_where_clause($member_id));
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
				'hook'      => 'sessions_start',
				//'settings'  => serialize($this->settings),
				'priority'  => 1,
				'version'   => $this->version,
				'enabled'   => 'y'
		);

		ee()->db->insert('extensions', $data);
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
