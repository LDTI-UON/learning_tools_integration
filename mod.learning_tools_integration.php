<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * EE Learning Tools Integration Module Front End File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Paul Sijpkes
 * @link
 */


define('LTI_FILE_UPLOAD_PATH', PATH_THIRD."learning_tools_integration/cache"); //@TODO: move to control panel settings

class Learning_tools_integration {
    public $return_data;
    public $mod_class = 'Learning_tools_integration';
    public $mod_path;

    public $base_url = "";
    public $help_url = "https://bold.newcastle.edu.au/padocs";
    public $base_segment = "";
    private $perpage = 10;
    private $pagination_segment = 3; // default only
    private $allowed_groups;
    public $use_SSL = TRUE;

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
    public $lti_url_host = "";
    public $lti_url_path = "";

    public $institution;
    public $institution_id;
    public $course_id;

    public $tool_consumer_instance_id = 0; // internal context for this institution

    /* Blackboard custom launch params */
    public $vle_username = '';
    public $pk_string = ''; // used for retrieving user list with smart views from Blackboard

    public $student_username_prefix = "c";

    // general
    public $username;
    public $screen_name;
    public $session_id;
    public $email;
    public $title;
    public $file_url;

    // LRS details
    public $lrs_endpoint;
    public $lrs_username;
    public $lrs_password;

    private $context_vars;
    private $random_form_error = "";
    public $download_redirect;
    public $cookie_name = "ee_lti_plugin";
    private $session_info;
   // private $tool_id;
    private $include_groups;

    private $prev_link_url;
    private $next_link_url;
    private $first_link_url;
    private $last_link_url;

    public static $lti_plugins;

    private $plugin_setup_text;

   // private $session_domain;

    /* allow registration for admin user via LTI */
    private $admin_key = 'pkmUgZgiBm';

    private $general_message = '';

    public $debug = FALSE;

    private $maintenance_message = FALSE;
    private $maintenance_key = 'hashKeyHere';

    private $EE;

    private static $instance = NULL;

    public $member_id = -1;

    public $use_resources = 0;

    /* launch hook methods */
    private $extension_launch = array("instructor" => array(), "general" => array(), "no_template" => array());
    private $direct_hook_methods = array();
    private $lib_path;
    private $hook_path;

    /* efficiency measure to allow simple exp:module:direct_tag notation, without loading wrapped tags */
    private $direct_tags_only = FALSE;

    /* launch hook variables */
    private $hook_vars = array();

    // storage for hook toggles
    private $tmpl_toggle_tags;

    private $tmpl_value_tags;



    /**
     * Constructor
     */
    public function __construct() {
       static::$instance =& $this;

       $this->mod_path = PATH_THIRD.strtolower($this->mod_class);
       $this->lib_path = $this->mod_path.DIRECTORY_SEPARATOR.'libraries';
       $this->hook_path = $this->lib_path.DIRECTORY_SEPARATOR.'extension_hooks';
       $this->init();
	}


    public function __call($method, $args)
    {
      //echo $method. "is set? ".isset($this->$method)."<BR>";
        if (isset($this->$method) === true) {
            $func = $this->$method;
            return $func($args);
        }
    }

    public static function __callStatic($name, $args)
    {
       // Note: value of $name is case sensitive.
       if(isset(static::$name)) {
          $func = static::$method;
       }

       return $func($args);
   }
   private function use_extension_hooks() {
       return file_exists($this->lib_path) && file_exists($this->hook_path);
   }
    private function initialise_hook_toggles() {
      require_once($this->hook_path.DIRECTORY_SEPARATOR.'tmpl_params.php');

      if(isset($tmpl_extension_toggles)) {
          $this->tmpl_toggle_tags = $tmpl_extension_toggles;
          foreach($tmpl_extension_toggles as $toggle => $tag) {
                  $this->$toggle = ee() -> TMPL -> fetch_param($toggle);
          }
      }
      if(isset($tmpl_value_params)) {
        foreach($tmpl_value_params as $variable => $tag) {
              $val = ee() -> TMPL -> fetch_param($variable);
              $this->$variable = ee() -> TMPL -> fetch_param($variable);
        }
      }
    }

    // ACT directories are ignored to allow dynamic action assignment for AJAX
    // requests and such
    private function _is_hook_dir($path, $entry) {
          return is_dir($path) && $entry != '.' && $entry != '..' && $entry != 'ACT' && $entry != 'secret' && $entry != 'include';
    }

    private function _include_hook_files($path) {
      $dir = dir($path);

      while (FALSE !== ($entry = $dir->read())) {
        $entry_path = $path.DIRECTORY_SEPARATOR.$entry;

        if(is_file($entry_path)) {
              $this->_require_hook_file($entry, $entry_path);
         } else if ($this->_is_hook_dir($entry_path, $entry)) {
              $this->_load_hooks($entry_path);
         }
      }
    }

    private function _require_hook_file($entry, $entry_path) {
      $method_name = explode('.', $entry)[0];
      require_once (__DIR__."/libraries/extension_hooks/hook_autoloader.php");
      require_once($entry_path);

      if(isset($hook_method)) {
        $this->$method_name = $hook_method;

        /* only load if direct tag flag not set.  this speeds up simple templates with a single {exp:module:method} syntax. */
        if(!$this->direct_tags_only) {
              if(isset($launch_no_template)) {
                if($this->_tmpl_toggle_on($method_name)) {
                  $this->extension_launch["no_template"][$method_name] = $launch_no_template;
                  unset($launch_no_template);
                }
              }

              if(isset($launch_instructor)) {
                if($this->_tmpl_toggle_on($method_name)) {
                  $this->extension_launch["instructor"][$method_name] = $launch_instructor;
                  unset($launch_instructor);
                }
              }

              if(isset($launch_general)) {
                if($this->_tmpl_toggle_on($method_name)) {
                  $this->extension_launch["general"][$method_name] = $launch_general;
                  unset($launch_general);
                }
              }
        }
        unset($hook_method);
      }
    }

    private function _tmpl_toggle_on($method_name) {
      /* If the tag has no toggle it is automatically included */
      foreach($this->tmpl_toggle_tags as $toggle => $tags) {
            if(in_array($method_name, $tags)) {
                  return (boolean) $this->$toggle;
            }
      }
      return TRUE;
    }

    private function _load_hooks($hook_dir = NULL) {

      if(empty($hook_dir)) {
          $hook_dir = $this->hook_path;
      }

      $dir = dir($hook_dir);

      while (FALSE !== ($entry = $dir->read())) {
            $contextual_path = $hook_dir.DIRECTORY_SEPARATOR.$entry;
            if(is_file($contextual_path)) {
                  $this->_require_hook_file($entry, $contextual_path);
             } else if($this->_is_hook_dir($contextual_path, $entry)) {
                  $this->_include_hook_files($contextual_path);
            }
      }
    }

    /*  These magic methods are used
    *   to toggle hooks on and off via template variables.
    */
  public function __set($name, $value)
   {
       $this->hook_vars[$name] = $value;
   }

   public function __get($name)
   {
       if (array_key_exists($name, $this->hook_vars)) {
           return $this->hook_vars[$name];
       }

       $trace = debug_backtrace();
       trigger_error(
           'Undefined property via __get(): ' . $name .
           ' in ' . $trace[0]['file'] .
           ' on line ' . $trace[0]['line'],
           E_USER_NOTICE);
       return null;
   }

   public function __isset($name)
   {
       return isset($this->hook_vars[$name]);
   }

   public function __unset($name)
   {
       unset($this->hook_vars[$name]);
   }

  public static function get_instance() {
        if(static::$instance === NULL) {
            static::$instance =& $this;
        }

     return static::$instance;
  }

  public function get_base_url() {
    	return $this->base_url;
  }

  private function init() {
        if(isset($_GET['ltiACT'])) return FALSE;

        if(isset($_GET['ACT'])) {
            $action_id = ee()->input->get('ACT');
            $res = ee()->db->get_where('actions', array('action_id' => $action_id));

            if($res->row()) {
                  return FALSE;
            }
        }

	      $this->member_id = ee() -> session -> userdata('member_id');

        if($this->maintenance_message === TRUE) {
            if(empty($_REQUEST['custom_maint']) || $_REQUEST['custom_maint'] !== $this->maintenance_key) {
                // TODO: replace with language file...
                echo "<h1>Under Maintenance</h1>";
                echo "<p>This tool is temporarily under maintenance, please try again shortly</p>";
                return FALSE;
            }
        }

        if(!empty($_REQUEST['custom_debug'])) {
            $this->debug = TRUE;
        }

        $this -> base_segment = Learning_tools_integration_ext::$base_segment;

        if(ee()->TMPL) {
              $val = ee() -> TMPL -> fetch_param('direct_tags_only');
              $this->direct_tags_only = !empty($val);

              if($this->use_extension_hooks()) {
                  $this->initialise_hook_toggles();
                  $this->_load_hooks();
              }

              $this -> prev_link_url =   ee() -> TMPL -> fetch_param('prev_link_url');
              $this -> next_link_url =   ee() -> TMPL -> fetch_param('next_link_url');
              $this -> first_link_url =   ee() -> TMPL -> fetch_param('first_link_url');
              $this -> last_link_url =   ee() -> TMPL -> fetch_param('last_link_url');
      		        $group_id =    ee() -> TMPL -> fetch_param('group_id');

      		        $pls = ee() -> TMPL -> fetch_param('plugins');
      		        static::$lti_plugins = explode(",", strtolower($pls));
        }

        $this->plugin_setup_text = array();

        if(!empty(static::$lti_plugins)) {
            foreach(static::$lti_plugins as $plugin) {
            	if(!empty($plugin)) {
                $plugin_file = PATH_THIRD."$plugin/libraries/".$plugin."_text.php";
                  if(file_exists($plugin_file)) {
                      	require_once($plugin_file);
                  } else {
                        die("LTI Plugin '$plugin' is specified in your template tag but is not installed.");
                  }
            	}
            }
        }

        ee() -> lang -> loadfile(strtolower($this -> mod_class));

        $this -> group_id = empty($group_id) ? $this -> group_id : $group_id;

        ee()->load->helper('url');
        $this -> base_url .=  site_url() . DIRECTORY_SEPARATOR . $this -> base_segment; //.$this->get_query_string();

        $this -> context_vars[] = $this -> lti_context();

        /*load stuff that requires the lti context here */

        if (empty($this -> base_segment)) {
        	if(! ee() -> input ->post('segment')) { // for direct ajax calls
           		 echo "<h2>Please set the template path for this learning tool.</h2><hr><pre>" . var_export($this -> session_info) . "</pre>";
            	 return FALSE;
        	}
        }

        if (ee()->TMPL) {
            $this -> return_data = ee() -> TMPL -> parse_variables(ee() -> TMPL -> tagdata, $this -> context_vars);
        }
    }

    public function message_preference() {
        $key =     ee() -> input -> post('key');
        // persist user state on post request
        if (!empty($key)) {
            $state =     ee() -> input -> post('state');

            $this -> saveUserState($state, $key);
            ee() -> output -> send_ajax_response(array('success' => 'true'));
        } else {

            ee() -> output -> send_ajax_response(array('message' => 'No key supplied'));
        }
    }

    function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    private function lti_context() {
        $error = "";
        $this->session_info = Learning_tools_integration_ext::session_info();

        $_m = ee()->session->userdata('member_id');

        if (!empty($_m) && !empty($this -> session_info)) {
            $this->email = ee()->session->userdata('email');
            $this->username = ee()->session->userdata('username');
            $this->screen_name = ee()->session->userdata('screen_name');

            // convenience variables
            $this -> launch_presentation_return_url = $this -> session_info['launch_presentation_return_url'];
            $this -> tool_consumer_instance_guid = $this -> session_info['tool_consumer_instance_guid'];
            $this -> tool_consumer_instance_id = $this -> session_info['tool_consumer_instance_id'];
            $this -> tool_consumer_instance_name = $this -> session_info['tool_consumer_instance_name'];
            $this -> lis_outcome_service_url = $this -> session_info['lis_outcome_service_url'];
            $this -> lis_result_sourcedid = $this -> session_info['lis_result_sourcedid'];
            $this -> resource_link_id = $this -> session_info['resource_link_id'];
            $this -> user_id = $this -> session_info['user_id'];
            $this -> user_key = $this -> session_info['user_key'];
            $this -> context_id = $this -> session_info['context_id'];
            $this -> internal_context_id = $this -> session_info['internal_context_id'];
            $this -> context_label = $this -> session_info['context_label'];
            $this -> ext_lms = $this -> session_info['ext_lms'];
            $this -> isInstructor = $this -> session_info['isInstructor'];
            $this -> course_key = $this -> session_info['course_key'];
            $this -> course_name = $this -> session_info['course_name'];
            $this -> pk_string = $this->session_info['pk_string'];
            $this -> user_short_name = $this -> session_info['user_short_name'];
            $this -> resource_title = $this -> session_info['resource_title'];
            $this -> resource_link_description = $this -> session_info['resource_link_description'];
            $this -> ext_launch_presentation_css_url = $this -> session_info['ext_launch_presentation_css_url'];
            $this->institution_id = $this -> session_info['institution_id'];
            $this->course_id = $this -> session_info['course_id'];


            $this->lti_url_host = parse_url($this->launch_presentation_return_url, PHP_URL_HOST);
            $this->lti_url_path = parse_url($this->launch_presentation_return_url, PHP_URL_PATH);
        }

        foreach($this->extension_launch['no_template'] as $launch) {
                $launch();
        }

        $state = $this -> loadUserState($this -> user_key);

        $view_data = array('error' => $error, 'state' => empty($state) ? FALSE : $state, 'is_instructor' => $this -> isInstructor);

        //$tag_data = array('course_key' => $this -> course_key, 'course_name' => $this -> course_name, 'user_key' => $this -> user_key, 'is_instructor' => $this -> isInstructor, 'user_email' => empty($this -> email) ? 'noemail@mailnesia.com' : $this -> email, 'user_short_name' => $this -> user_short_name, 'user_name' => $this -> username, 'context_label' => $this -> context_label, 'resource_title' => $this -> resource_title, 'resource_link_description' => $this -> resource_link_description, 'launch_presentation_return_url' => $this -> launch_presentation_return_url, 'tool_consumer_instance_name' => $this -> tool_consumer_instance_name, 'general_message' => $this->general_message, 'student_table_title' => lang('students_table_title'),'javascript' => "<script>$('#hide_error').click(function() {
        $tag_data = array(
        'save_grade_example_form' => $this->save_grade_example_form(),
        'read_grade_example_form' => $this->read_grade_example_form(),
        'general_message' => $this->general_message,
            'javascript' => "<script>$('#hide_error').click(function() {

          var state = { hideError : true };

          $.post('$this->message_pref_url',
            { 'key' : '$this->user_key', 'state' : state },
            function(data){
              $('.errorBox').hide();
            }, 'json');
        });</script>",);

        if(isset($_POST['action']) && $_POST['action'] == 'save_user_grade') {
                $tag_data['save_user_grade_output'] = "<pre>".$this->save_user_grade()."</pre>";
        } else {
             $tag_data['save_user_grade_output'] = "";
        }

        if(isset($_POST['action']) && $_POST['action'] == 'read_user_grade') {
                $tag_data['read_user_grade_output'] = "<pre>".$this->read_user_grade()."</pre>";
        } else {
             $tag_data['read_user_grade_output'] = "";
        }

        $params = array("view_data" => $view_data, "tag_data" => $tag_data, "error" => $error, "state" => $state);

        if (!empty($this -> isInstructor)) {
            foreach($this->extension_launch['instructor'] as $launch) {
                    $data = $launch($params);
                    if($data) {
                        $params = $data;
                    }
            }
        }

        foreach($this->extension_launch['general'] as $launch) {
                $data = $launch($params);
                if($data) {
                    $params = $data;
                }
        }

        $view_data = $params['view_data'];
        $tag_data = $params['tag_data'];

        // re-enable CSRF (extension disables it temporarily)
        ee()->config->set_item('disable_csrf_protection', 'n');

        if($this->isInstructor) {
          $error_messages = ee() -> load -> view('lti-context-messages', $view_data, TRUE);

          $view_data = array_merge(array('error_messages' => $error_messages), $tag_data);
        } else {
          $view_data = $tag_data;
        }

        return $view_data;
    }

    public static function logToJavascriptConsole($str) {
            return "<script>(function() { console.log(\"$str\"); })();</script>";
    }

    private function pagination_config($method, $total_rows, $per_page = -1, $data_segments = NULL) {
    	$config = array();
    	$dcount = 0;
    	$data = "";

    	if($data_segments !== NULL) {
    		$dcount = count($data_segments);
    		$data = "/".implode('/', $data_segments);
    	}

        $config['base_url'] = site_url()."/".$this->base_segment."/".$method.$data;
        $config['total_rows'] = $total_rows;

        $config['page_query_string'] = FALSE;
        $config['uri_segment'] = $this->pagination_segment + $dcount;
        $config['full_tag_open'] = '<p id="paginationLinks">';
        $config['full_tag_close'] = '</p>';

        $config['per_page'] = $per_page === -1 ? $this -> perpage : $per_page;

        $config['prev_link'] = '<img src="' . $this -> prev_link_url . '" width="13" height="13" alt="&lt;" />';
        $config['next_link'] = '<img src="' . $this -> next_link_url . '" width="13" height="13" alt="&gt;" />';
        $config['first_link'] = '<img src="' . $this -> first_link_url . '" width="13" height="13" alt="&lt; &lt;" />';
        $config['last_link'] = '<img src="' . $this -> last_link_url . '" width="13" height="13" alt="&gt; &gt;" />';

        return $config;
    }

    public function save_user_grade_url() {
        $result =     ee() -> db -> get_where('actions', array('class' => $this -> mod_class, 'method' => 'save_user_grade'));
        $actid = $result -> row('action_id');
        $url = site_url() . "?ACT=$actid";
        return $url;
    }

    public function read_user_grade_url() {
        $result =     ee() -> db -> get_where('actions', array('class' => $this -> mod_class, 'method' => 'read_user_grade'));
        $actid = $result -> row('action_id');
        $url = site_url() . "?ACT=$actid";
        return $url;
    }

    private function saveUserState($state, $key) {
        ee() -> cache -> save('/learning_tools_integration/$key', $state);
    }

    private function loadUserState($key) {
        return ee() -> cache -> get('/learning_tools_integration/$key');
    }

    public static function str_random($length = 8) {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }

    public static function outputJavascript($js_vars = array(), $name = "", $direct = FALSE) {
        if(!$js_vars || count($js_vars) == 0) {
          return FALSE;
        }

        ob_start();
        if($direct === TRUE && $name !== "") {
        	include_once ("js/$name.js");
        } else {
	        foreach(static::$lti_plugins as $pearl) {
	            if(strlen($name) > 0) {
	                include_once (PATH_THIRD."$pearl/js/".$pearl."_".$name.".js");
	            } else {
	                include_once (PATH_THIRD."$pearl/js/$pearl.js");
	            }
	        }
        }
        $str = ob_get_contents();
        ob_end_clean();

        return "<script type='text/javascript'>$str</script>";
    }
    private function save_grade_example_form() {
        ee()->load->helper('form');

        $form = form_open($this->base_url);

        $data = array(
              'name'        => 'grade',
              'id'          => 'grade',
              'value'       => '0',
              'maxlength'   => '3',
              'size'        => '20',
              'style'       => 'width:10%',
            );

        $form .= form_input($data);
        $form .= form_hidden('action', 'save_user_grade');
        $form .= form_hidden('segment', $this->base_segment);
        $form .= form_submit('submit', 'Submit Grade');
        $form .= form_close();

        return $form;
    }
    private function read_grade_example_form() {
        ee()->load->helper('form');

        $form = form_open($this->base_url);
        $form .= form_hidden('segment', $this->base_segment);
        $form .= form_hidden('action', 'read_user_grade');
        $form .= form_submit('submit', 'Read Grade');
        $form.= form_close();
        return $form;
    }
    public function save_user_grade() {
      	if ($this -> isInstructor == 1) {
            return "No grades for instructors, sorry!";
        }

        ee() -> load -> helper('url');

        $grade = ee() -> input -> post('grade');
        $segment = ee() -> input -> post('segment');

        $result =     ee() -> db -> get_where('blti_keys', array('url_segment' => $segment));
        $key = $result -> row('oauth_consumer_key');
        $secret = $result -> row('secret');

        $id = uniqid();

        require_once ("xml/replace-grade-envelope.php");
        $xml_length = strlen($xml);

        $url = $this -> lis_outcome_service_url;

        require_once("ims-blti/OAuth.php");

        $bodyHash = base64_encode(sha1($xml, TRUE));
        // build oauth_body_hash
        $consumer = new OAuthConsumer($key, $secret);
        $request = OAuthRequest::from_consumer_and_token($consumer, '', 'POST', $url, array('oauth_body_hash' => $bodyHash));
        $request -> sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, '');
        $header = $request -> to_header();
        // add content type header

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("POST " . site_url() . "/$segment HTTP/1.0", "Content-Length: $xml_length", $header, "Content-Type: application/xml"));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($ch);

        curl_close($ch);

        $json_response = array();
        $xml_o = simplexml_load_string($output);
        $json_response['codeMajor'] = (string)$xml_o -> imsx_POXHeader -> imsx_POXResponseHeaderInfo -> imsx_statusInfo -> imsx_codeMajor;
        $json_response['severity'] = (string)$xml_o -> imsx_POXHeader -> imsx_POXResponseHeaderInfo -> imsx_statusInfo -> imsx_severity;
        $json_response['description'] = (string)$xml_o -> imsx_POXHeader -> imsx_POXResponseHeaderInfo -> imsx_statusInfo -> imsx_description;

        die(json_encode($json_response));
    }

    public function read_user_grade() {
    	if ($this -> isInstructor == 1) {
            return "No grades for instructors, sorry!";
        }

        ee() -> load -> helper('url');

        $grade = ee() -> input -> post('grade');
        $segment = ee() -> input -> post('segment');

        $result =     ee() -> db -> get_where('blti_keys', array('url_segment' => $segment));
        $key = $result -> row('oauth_consumer_key');
        $secret = $result -> row('secret');

        $id = uniqid();

        require_once ("xml/read-grade-envelope.php");
        $xml_length = strlen($xml);

        $url = $this -> lis_outcome_service_url;

        $bodyHash = base64_encode(sha1($xml, TRUE));

        require_once("ims-blti/OAuth.php");
        // build oauth_body_hash
        $consumer = new OAuthConsumer($key, $secret);
        $request = OAuthRequest::from_consumer_and_token($consumer, '', 'POST', $url, array('oauth_body_hash' => $bodyHash));
        $request -> sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, '');
        $header = $request -> to_header();
        // add content type header

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("POST " . site_url() . "/$segment HTTP/1.0", "Content-Length: $xml_length", $header, "Content-Type: application/xml"));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($ch);

        curl_close($ch);

        $json_response = array();

        $xml_o = simplexml_load_string($output);
        $success = $xml_o -> imsx_POXHeader -> imsx_POXResponseHeaderInfo -> imsx_statusInfo;
        $json_response['codeMajor'] = (string)$xml_o -> imsx_POXHeader -> imsx_POXResponseHeaderInfo -> imsx_statusInfo -> imsx_codeMajor;
        $json_response['severity'] = (string)$xml_o -> imsx_POXHeader -> imsx_POXResponseHeaderInfo -> imsx_statusInfo -> imsx_severity;
        $json_response['description'] = (string)$xml_o -> imsx_POXHeader -> imsx_POXResponseHeaderInfo -> imsx_statusInfo -> imsx_description;
        $json_response['resultScore'] = (string)$xml_o -> imsx_POXBody -> readResultResponse -> result -> resultScore -> textString;

        die(json_encode($json_response));
    }
  }

//spl_autoload_register(array('Learning_tools_integration', 'autoloader'));

/* End of file mod.learning_tools_integration.php */
/* Location: /system/expressionengine/third_party/learning_tools_integration/mod.learning_tools_integration.php */
