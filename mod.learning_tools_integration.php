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

require_once ("libraries/utils.php");
require_once ("libraries/ResourceFile.php");

define('LTI_FILE_UPLOAD_PATH', PATH_THIRD."learning_tools_integration/cache"); //@TODO: move to control panel settings

class Learning_tools_integration {
    public $return_data;
    public $mod_class = 'Learning_tools_integration';
    public $mod_path;

    private $theme_folder = "/themes/third_party/";
    public $base_url = "";
    private $base_segment = "";
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
    private $download_redirect;
    private $cookie_name = "ee_lti_plugin";
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
    private $maintenance_key = 'working17923';

    private $EE;

    private static $instance = NULL;

    public $member_id = -1;

    public $use_resources = 0;

    /* launch hook methods */
    private $extension_launch = array("instructor" => array(), "general" => array());
    private $lib_path;
    private $hook_path;

    /* launch hook variables */
    private $hook_vars = array();

    // storage for hook toggles
    private $tmpl_param_tags;
    /**
     * Constructor
     */
  public function __construct() {
       static::$instance =& $this;
       $this->mod_path = PATH_THIRD.strtolower($this->mod_class);
       $this->lib_path = $this->mod_path.DIRECTORY_SEPARATOR.'libraries';
       $this->hook_path = $this->lib_path.DIRECTORY_SEPARATOR.'extension_hooks';

       $this->_load_hooks();
       $this->init();
	}

    public function __call($method, $args)
    {
        if (isset($this->$method) === true) {
            $func = $this->$method;
            return $func($args);
        }
    }

    private function _is_hook_dir($path, $entry) {
          return is_dir($path) && $entry != '.' && $entry != '..';
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

      require_once($entry_path);

      if(isset($hook_method)) {
        $this->$method_name = $hook_method;

        if(isset($tmpl_params)) {
            foreach($tmpl_params as $param) {
                    $this->$param = ee() -> TMPL -> fetch_param($param);
            }
        }

        if(isset($launch_instructor)) {
              $this->extension_launch["instructor"][$method_name] = $launch_instructor;
              unset($launch_instructor);
        }

        if(isset($launch_general)) {
              $this->extension_launch["general"][$method_name] = $launch_general;
              unset($launch_general);
        }

        unset($hook_method);
      }
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

   /**  As of PHP 5.1.0  */
   public function __isset($name)
   {
       return isset($this->hook_vars[$name]);
   }

   /**  As of PHP 5.1.0  */
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
	       $this->member_id =   ee() -> session -> userdata('member_id');

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
              $this -> include_groups = ee() -> TMPL -> fetch_param('include_groups');
          //    $this -> use_resources = ee() -> TMPL -> fetch_param('use_resources');
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
                	require(PATH_THIRD."$plugin/libraries/".$plugin."_text.php");
            	}
            }
        }

        ee() -> lang -> loadfile(strtolower($this -> mod_class));

        $this -> group_id = empty($group_id) ? $this -> group_id : $group_id;

        ee()->load->helper('url');
        $this -> base_url .=  site_url() . DIRECTORY_SEPARATOR . $this -> base_segment; //.$this->get_query_string();

        $this -> context_vars[] = $this -> lti_context();

        if (empty($this -> base_segment)) {
        	if(! ee() -> input ->post('segment')) { // for direct ajax calls
           		 echo "<h2>Please set the template path for this learning tool.</h2><hr><pre>" . var_export($this -> session_info) . "</pre>";
            	 return FALSE;
        	}
        }

        /* download via a clickable link */
        if (isset($_GET['download_lti_resource'])) {
            $id = ee()->input->get('download_lti_resource');

            $this -> return_data = $this -> direct_download($id);
        }

        if (isset($_GET['f']) && isset($_GET['i']) && isset($_GET['t'])) {
            $this -> do_download(ee()->input->get('f'), ee()->input->get('i'), ee()->input->get('t'));
            return;
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

            // convenience functions
            $this->lti_url_host = parse_url($this->launch_presentation_return_url, PHP_URL_HOST);
            $this->lti_url_path = parse_url($this->launch_presentation_return_url, PHP_URL_PATH);//.$this->get_query_string();
        }

        /* process feedback on targeted resource for student */
        if(ee()->input->post('process')) {
                $is = $this->get_instructor_settings();

                if(!$is) {
                	$is = new stdClass;
                	$is->problem_prefix = 'problem_';
                	$is->solution_prefix = 'solution_';
                }

                $rfile = new ResourceFile(LTI_FILE_UPLOAD_PATH.DIRECTORY_SEPARATOR.ee()->input->post('process'), $this->internal_context_id, $this->context_id, $this->institution_id, $this->course_id, $is->problem_prefix, $is->solution_prefix);
                $feed = $rfile->import();

                header('Content-type: application/json');
                echo json_encode(array("feedback" => $feed));
                exit;
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
                  $params = $launch($params);
            }

            $view_data = $params['view_data'];
            $tag_data = $params['tag_data'];

            if(!empty($this->use_resources)) {
            //	$tag_data["resource_settings_form"] = $this -> resource_settings_form();
            	$tag_data["upload_student_resources_form"] = $this -> upload_student_resources_form();
            	$tag_data["random_form"] = $this -> random_form();
              $tag_data["random_remainder_form"] = $this -> random_remainder_form();
            	$tag_data['random_form_error'] = $this -> random_form_error;
            	$tag_data["resource_table"] = $this -> resource_table();

              if (property_exists(ee(), 'TMPL')) {
                 $this->download_redirect =   ee() -> TMPL -> fetch_param('download_redirect');

                 if(empty($this->download_redirect)) {
                    die('Please set a download redirect in the template parameters for resource download.');
                 }
              }
            }
        }

        if(!empty($this->use_resources) && $this->use_resources === 'download_link') {
        	$tag_data["download_resource"] = $this -> download_resource();
        }

        // re-enable CSRF (extension disables it temporarily)
        ee()->config->set_item('disable_csrf_protection', 'n');

        return array_merge(array('error_messages' =>  ee() -> load -> view('lti-context-messages', $view_data, TRUE)), $tag_data);
    }

    public static function logToJavascriptConsole($str) {
            return "<script>(function() { console.log(\"$str\"); })();</script>";
    }

    private function resource_table_heading() {
        if ($this -> isInstructor != 0) {
            return lang('instructor_resource_table_heading');
        }

        return lang('student_resource_table_heading');
    }

    public function upload_student_resources_form() {

        $errors = "";
        $form = "";

        if (!LTI_FILE_UPLOAD_PATH) {
            return "<p><strong>{upload_student_resources_form} says &quot;Please set an upload path.&quot;</strong></p>";
        }

        $problem_prefix = 'problem_';
        $solution_prefix = 'solution_';

        $settings = new Settings($this);
        $row = $settings->get_instructor_settings();

        if($row) {
             $problem_prefix = $row -> problem_prefix;
             $solution_prefix = $row -> solution_prefix;
        }

        $working_dir = LTI_FILE_UPLOAD_PATH.DIRECTORY_SEPARATOR.$this->context_id.$this->institution_id.$this->course_id;

        $form .= "<p><br><b>You can upload ZIP files larger than 50MB to the course folder via SSH/SFTP</b></em></p>";

        function _f($i) {
        	return strpos(strtolower($i), '.zip') !== FALSE;
        }

        if(file_exists($working_dir)) {
            $files = scandir($working_dir);
            $files = array_filter($files, "_f");
            if(count($files) > 0) {
	            $form .= "<p>ZIP files ready for processing.</p>";
	            $form .= "<p><ul>";

	            foreach($files as $zip) {
	                    $form .= "<li><a href='#' class='process_file' data-filename='$zip'>$zip</a></li>";
	            }

	            $mod_dir = strtolower($this->mod_class);
	            $form .= "</ul></p>";
	            $form .= "<script type='text/javascript'>";
	            $js = file_get_contents(PATH_THIRD.$mod_dir.DIRECTORY_SEPARATOR."js".DIRECTORY_SEPARATOR.'process_file.js');

	            $tokens = array("%suburl%", "%loaderurl%");
	            $replace = array(ee()->uri->uri_string, URL_THIRD_THEMES.$mod_dir.DIRECTORY_SEPARATOR."img".DIRECTORY_SEPARATOR."processing-file.gif");

	            $js = str_replace($tokens, $replace, $js);

	            $form .= $js;
	            $form .= "</script>";
            } else {
                $form .= "<p>There are currently no Resource ZIP files available for processing.</p>";
            }
        } else {
        	if(!mkdir($working_dir)) {
        		$form .= "<p><b>Could not create working directory for resource zip files.</b></p>";
        	}
        }

        $config = array();

        if (isset($_POST['do_resource_upload'])) {
            $config['upload_path'] = LTI_FILE_UPLOAD_PATH;
            $config['allowed_types'] = 'zip';
            $config['max_size'] = '51200';

            ee() -> load -> library('upload', $config);

            if (! ee() -> upload -> do_upload()) {
                $errors = "<br>" .  ee() -> upload -> display_errors();
            } else {
                $file_data =    ee() -> upload -> data();

                $resourceFile = new ResourceFile($file_data['full_path'], $this->internal_context_id, $this->context_id, $this->institution_id, $this->course_id, $problem_prefix, $solution_prefix);

                $form .= $resourceFile->import();
           }
        }
        ee() -> load -> helper('form');

        $form .= "<p>&nbsp;</p><p><em>Upload smaller resource zip files here (50MB max). $errors</br></br>";
        $form .= form_open_multipart($this->base_url);
        $form .= form_upload('userfile', 'userfile');
        $form .= form_hidden('do_resource_upload', 'yep');
        $form .= form_hidden('ee_lti_token', $this -> cookie_name);
        $form .= form_submit("upload", "Upload");
        $form .= form_close();
        $form.="</p>";

        return $form;
    }

    private function random_form() {
        $form = "";
        if (isset($_POST['do_random'])) {
            $result =   ee() -> db -> where(array('uploader_internal_context_id' => $this -> internal_context_id, 'type' => 'P'));
            $result =  ee() -> db -> get('lti_member_resources');
            $res = $result -> result();
            $res_count = $result -> num_rows();
            shuffle($res);

            ee() -> db -> where(array('context_id' => $this -> context_id, 'tool_consumer_instance_id' => $this -> tool_consumer_instance_id, 'is_instructor' => '0'));
            ee() -> db -> from('lti_member_contexts');

            $_m_res =   ee() -> db -> get();
            $mem_count = $_m_res -> num_rows();

            if($mem_count > 0) {
            	$mem_res = $_m_res->result();
            } else {
            	return;
            }

            $used_contexts = array();
           // $used_resources = array();
            $deb = "";

            $batch = array();
            foreach ($res as $row) {
	                foreach ($mem_res as $mem_row) {
	                    if(in_array($mem_row->id, $used_contexts) === FALSE) {
	                    	$data = array('internal_context_id' => $mem_row->id, 'base_name' => $row->base_name);
	                      	$batch[] = $data;

	                        $used_contexts[] = $mem_row->id;
	                        $used_resources[] = $row->id;
	                        break;
	                   }
	                }
            }
            $__i = count($used_resources);

            if($__i < $mem_count) {
            	$message = "<p>Not enough resources for all members of this course ($__i Resources > $mem_count Students). Therefore, ".((Integer)$mem_count-$__i)." students have no resources assigned to them.</p>";
            }

            $success = ee()->db->update_batch('lti_member_resources', $batch, 'base_name');

            $ur_count = count($used_resources);
            $uc_count = count($used_contexts);
            $error = "";

            if ($mem_count > $uc_count) {
                $this -> random_form_error = lang('_error');
            }

            $form .= "<p>$ur_count resources assigned to $uc_count users.</p>";
        }

        ee() -> load -> helper('form');

        $form .= "<p>By clicking the button below you will randomly assign a unique resource to each student,
							this resource will appear when they click on the link in this course.<br> <strong>
              <span style='color: red;'>WARNING: THIS BUTTON WILL RE-ASSIGN RESOURCES EVERYTIME IT IS CLICKED.
              Use the <u>remainder</u> button below if you wish to assign resources to remaining students.</strong></p>";
        $form .= form_open_multipart($this->base_url);
        $form .= form_hidden('do_random', 'yep');
        $form .= form_submit("Randomly", "Assign a unique resource to each student");
        $form .= form_close();

        return $form;
    }

    private function random_remainder_form() {
    	$form = "";
        $message = "";
    	if (isset($_POST['do_random_remainder'])) {
    		ee() -> db -> where(array('uploader_internal_context_id' => $this -> internal_context_id, 'type' => 'P'));
    		ee()-> db ->where('internal_context_id IS NULL', NULL, FALSE);
    		$result =  ee() -> db -> get('lti_member_resources');
    		$res = $result -> result();

    		$res_count = $result -> num_rows();
    		shuffle($res);

    		ee() -> db -> where(array('context_id' => $this -> context_id, 'tool_consumer_instance_id' => $this -> tool_consumer_instance_id, 'is_instructor' => '0'));
    		ee()->db->where("`id` NOT IN (SELECT `internal_context_id` FROM `".ee()->db->dbprefix."lti_member_resources` WHERE `internal_context_id` IS NOT NULL)", NULL, FALSE);
    		ee() -> db -> from('lti_member_contexts');

    		$_m_res =   ee() -> db -> get();

    		$mem_count = $_m_res -> num_rows();

    		if($mem_count > 0) {
    			$mem_res = $_m_res->result();
    		} else {
    			return;
    		}

    		$used_contexts = array();
    		$used_resources = array();

    		$batch = array();
    		foreach ($res as $row) {
    			foreach ($mem_res as $mem_row) {
    				if(in_array($mem_row->id, $used_contexts) === FALSE) {
    					$data = array('internal_context_id' => $mem_row->id, 'base_name' => $row->base_name);
    					$batch[] = $data;

    					$used_contexts[] = $mem_row->id;
    					$used_resources[] = $row->id;
    					break;
    				}
    			}
    		}
    		$__i = count($used_resources);

    		if($__i < $mem_count) {
    			$message .= "<p>Not enough resources for all members of this course ($__i Resources > $mem_count Students). Therefore, ".((Integer)$mem_count-$__i)." students have no resources assigned to them.</p>";
    		}

    		ee()->db->update_batch('lti_member_resources', $batch, 'base_name');

    		$ur_count = count($used_resources);
    		$uc_count = count($used_contexts);
    		//$error = "";

    		if ($mem_count > $uc_count) {
    			$this -> random_form_error = lang('_error');
    		}

    		$form .= "<p>$ur_count resources assigned to $uc_count users.</p>";
    	}

    	ee() -> load -> helper('form');

    	$form .= "<br><p>By clicking the button below you will randomly assign all remaining resource to students that don't yet have a resource allocated,
    	this resource will appear when they click on the link in this course.</p>";
        $form .= !empty($message) ? "<p>$message</p>" : "";
    	$form .= form_open_multipart($this->base_url);
    	$form .= form_hidden('do_random_remainder', 'yep');
    	$form .= form_submit("Randomly", "Assign a unique resource to remaining students");
    	$form .= form_close();

    	return $form;
    }

    private function unpack_rubric_archive($path, $zip_file_name, $rubric_dir) {
        $zip = new ZipArchive;
	    $res = $zip -> open($path.DIRECTORY_SEPARATOR.$zip_file_name);

	    if ($res === TRUE) {
            // extract it to the path we determined above
            for ($i = 0; $i < $zip -> numFiles; $i++) {
                $filename = $zip -> getNameIndex($i);
                $fileinfo = pathinfo($filename);

                copy("zip://" . $path.DIRECTORY_SEPARATOR.$zip_file_name ."#". $filename, $rubric_dir . DIRECTORY_SEPARATOR . $fileinfo['basename']);
            }
        }
		$zip -> close();

		unlink($path.DIRECTORY_SEPARATOR.$zip_file_name);
    }



    public function render_blackboard_rubric() {
        $raw_id = ee()->input->post("id");
        $id = explode("|", $raw_id)[0];

		$user = ee()->input->post("user");
		$input_id = ee()->input->post("input_id");
		$pre_pop =  ee()->input->post('pre_pop');//ee()->TMPL->fetch_param("pre_pop");

    	$path = build_course_upload_path(LTI_FILE_UPLOAD_PATH.DIRECTORY_SEPARATOR.'cache', $this->context_id, $this->institution_id, $this->course_id);
    	$rubric_dir = $path.DIRECTORY_SEPARATOR."rubrics".DIRECTORY_SEPARATOR."html";
    	$dir = scandir($rubric_dir);
    	$vars = array();

    	foreach($dir as $item) {
    		if(strpos($item, $id) !== FALSE) {

	    			if(strpos($item, "|grid|") !== FALSE) {
	    				$vars['grid'] = file_get_contents($rubric_dir.DIRECTORY_SEPARATOR.$item);
	    			}

	    			if(strpos($item, "|list|") !== FALSE) {
    					$vars['list'] = file_get_contents($rubric_dir.DIRECTORY_SEPARATOR.$item);
	    			}
    		}
    	}

    	$vars['js_controls'] = file_get_contents("$this->mod_path/js/rubric_controls.js");

    	if(empty($user)) {
    		$vars['exit_button_value'] = "Exit";
    	} else {
    		$vars['exit_button_value'] = "Save &amp; Close";
    	}
    	$vars['input_id'] = $input_id;
    	$vars['username'] = htmlentities($user['screen_name']);
    	$vars['pre_pop'] = htmlentities($pre_pop, ENT_QUOTES, 'UTF-8');

    	return ee() -> load -> view('rubric', $vars, TRUE);
    }

    public function is_solution_request() {
        return isset($_REQUEST['custom_provide_solution']);
    }

    public function total_resources() {// only counts 'problem' files, solutions not included
        if ($this -> isInstructor != 0)
            return 0;

        $type = $this -> is_solution_request() ? 'S' : 'P';

        ee() -> db -> select('lti_member_resources.id, lti_member_resources.display_name');
        ee() -> db -> join('lti_member_contexts', 'lti_member_contexts.id = lti_member_resources.internal_context_id');
        ee() -> db -> where(array("lti_member_resources.internal_context_id" => $this -> internal_context_id, 'type' => $type));
        ee() -> db -> from('lti_member_resources');
        $total =   ee() -> db -> count_all_results();

        return $total;
    }

    private function direct_download($id) {
        ee() -> db -> select('lti_member_resources.id, lti_member_resources.display_name, lti_member_resources.file_name, lti_member_resources.type');
        //ee() -> db -> join('lti_member_contexts', 'lti_member_contexts.id = lti_member_resources.internal_context_id');
        ee() -> db -> where(array("lti_member_resources.id" => $id));
        ee() -> db -> from('lti_member_resources');
        $query =   ee() -> db -> get();

        $row = $query -> row();

        return $this -> download_file($row -> file_name, $row -> type == 'S' ? 'solution' : 'problem', $row -> salt);
    }

    public function download_resource() {
        if(isset($_GET['f']) || isset($_GET['i']) || isset($_GET['t'])) {
            // avoids accidental second execution on redirect
            return FALSE;
        }

        $total = $this -> total_resources();
        if($this -> isInstructor != 0) {
          echo "<h2>Nothing for instructors yet sorry.</h2>";
        	return FALSE;
        }

        if ($total != 1) {
           echo "<h2>Nothing to download</h2>";
           return FALSE;
        }

        $type = $this -> is_solution_request() ? 'solution' : 'problem';

        $sqltype = $type == 'solution' ? 'S' : 'P';

        //echo "GOT HERE $total<br>";
        ee() -> db -> select('lti_member_resources.id, lti_member_resources.display_name, lti_member_resources.file_name, lti_member_resources.salt');
        ee() -> db -> join('lti_member_contexts', 'lti_member_contexts.id = lti_member_resources.internal_context_id');
        ee() -> db -> where(array("lti_member_resources.internal_context_id" => $this -> internal_context_id, 'type' => $sqltype));
        ee() -> db -> from('lti_member_resources');
        $query =   ee() -> db -> get();

        $row = $query -> row();

        return $this -> download_file($row -> file_name, $type, $row -> salt);
    }

    public function resource_table() {
        //echo $this -> isInstructor . " -- " . $this->total_resources();
        if ($this -> isInstructor == 0 && $this -> total_resources() < 2) {
            return $this -> download_resource();
        }

        $segments =   ee() -> uri -> segment_array();
        $my_segment = isset($segments[$this->pagination_segment]) ? $segments[$this->pagination_segment] : 'resource_table';

        if (prev($segments) == 'resource_table' && is_numeric($my_segment)) {
            $rownum = $my_segment;
        } else {
            $rownum = 0;
        }

        if ($this -> isInstructor != 0) {

            ee() -> db -> select('lti_member_resources.id, lti_member_resources.display_name');
            ee() -> db -> join('lti_member_contexts', 'lti_member_contexts.id = lti_member_resources.uploader_internal_context_id');
            ee() -> db -> where("lti_member_contexts.context_id = '$this->context_id'");
            ee() -> db -> from('lti_member_resources');
            $total =   ee() -> db -> count_all_results();

            ee() -> db -> select('lti_member_resources.id, lti_member_resources.display_name');
            ee() -> db -> join('lti_member_contexts', 'lti_member_contexts.id = lti_member_resources.uploader_internal_context_id');
            ee() -> db -> where("lti_member_contexts.context_id = '$this->context_id'");
            ee() -> db -> from('lti_member_resources');
            ee() -> db -> limit($this -> perpage, $rownum);

        } else {
            $type = $this -> is_solution_request() ? 'S' : 'P';

            ee() -> db -> select('lti_member_resources.id, lti_member_resources.display_name');
            //ee() -> db -> join('lti_member_contexts', 'lti_member_contexts.id = lti_member_resources.internal_context_id');
            ee() -> db -> where(array('lti_member_resources.internal_context_id' => $this -> internal_context_id, 'type' => $type));
            ee() -> db -> from('lti_member_resources');
            $total =   ee() -> db -> count_all_results();


            ee() -> db -> select('lti_member_resources.id, lti_member_resources.display_name, lti_member_resources.file_name');
            //ee() -> db -> join('lti_member_contexts', 'lti_member_contexts.id = lti_member_resources.internal_context_id');
            ee() -> db -> where(array('lti_member_resources.internal_context_id' => $this -> internal_context_id, 'type' => $type));
            ee() -> db -> from('lti_member_resources');
            ee() -> db -> limit($this -> perpage, $rownum);
        }

        $query =   ee() -> db -> get();

        $vars = array();

        foreach ($query->result_array() as $row) {
            $vars['resources'][$row['id']]['id'] = $row['id'];
            $vars['resources'][$row['id']]['display_name'] = $row['display_name'];
        }

        // Pass the relevant data to the paginate class so it can display the "next page" links
        ee() -> load -> library('pagination');
        $p_config = $this -> pagination_config('resource_table', $total);
        ee() -> pagination -> initialize($p_config);

        $vars['pagination'] =   ee() -> pagination -> create_links();

        return  ee() -> load -> view('resource-table', $vars, TRUE);
    }

    private function download_file($filename, $type, $salt) {

        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

        $enc = mcrypt_encrypt(MCRYPT_BLOWFISH, $salt, $filename, MCRYPT_MODE_CBC, $iv);

        $iv = base64_encode($iv);
        $enc = base64_encode($enc);

        $vars['current_uri'] =  ee() -> functions -> fetch_current_uri();
        $vars['screen_name'] =  ee() -> session -> userdata('screen_name');
        $vars['filename'] = rawurlencode($enc);
        $vars['iv'] = rawurlencode($iv);
        $vars['ee_lti_token'] = $this -> cookie_name;
        $vars['type'] = $type;
        $vars['download_redirect'] = $this->download_redirect;
        $vars['segment'] = $this->base_segment;
        $vars['return_url'] = $this->launch_presentation_return_url;

        return   ee() -> load -> view('download-redirect', $vars, TRUE);
    }

    private function do_download($enc_filename, $iv, $type) {

        if (empty($enc_filename) || !isset($_GET['t']) || !isset($_GET['i']) || !isset($_GET['f'])) {
            echo "<p>" . lang('download_error') . "</p>";
            return;
        }

        if ($_GET['t'] !== 'solution' && $_GET['t'] !== 'problem') {
            echo "<p>Download request was in the wrong format.</p>";
            return;
        }

        $sqltype = $type == 'solution' ? 'S' : 'P';

        $enc_filename = base64_decode($enc_filename);
        $iv = base64_decode($iv);

        $res = ee()->db->get_where('lti_member_resources', array('internal_context_id' => $this->internal_context_id, 'type' => $sqltype));
        $salt = $res->row()->salt;

        $filename = mcrypt_decrypt(MCRYPT_BLOWFISH, $salt, $enc_filename, MCRYPT_MODE_CBC, $iv);
        $filename = trim($filename);

        ee() -> load -> helper('download');

        if(is_readable(LTI_FILE_UPLOAD_PATH.DIRECTORY_SEPARATOR.$this->context_id.$this->institution_id.$this->course_id
                      .DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.$filename)) {
          //$data = file_get_contents(LTI_FILE_UPLOAD_PATH.DIRECTORY_SEPARATOR.$this->context_id.$this->institution_id.$this->course_id.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.$filename);
          $ext = end(explode(".", $filename));
        } else {
          die("File not readable.");
        }

        $this->_push_file(LTI_FILE_UPLOAD_PATH.DIRECTORY_SEPARATOR.$this->context_id.$this->institution_id.$this->course_id.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.$filename,
                          $type . "_" . $this -> context_label . "_data." . $ext);
    }

    private function _push_file($path, $name)
    {
      // make sure it's a file before doing anything!
    if(is_file($path))
    {
    // required for IE
    if(ini_get('zlib.output_compression')) { ini_set('zlib.output_compression', 'Off'); }

      // get the file mime type using the file extension
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime = finfo_file($finfo, $path);
      $length = sprintf("%u", filesize($path));

      if(strpos(strtolower($mime), "pdf") !== FALSE) {
          $disposition = 'inline';
      } else {
          $disposition = 'attachment';
      }

      // Build the headers to push out the file properly.
      header('Pragma: private');     // required
      header('Expires: 0');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Last-Modified: '.gmdate ('D, d M Y H:i:s', filemtime ($path)).' GMT');
      header('Cache-Control: private',false);
      header('Content-Type: '.$mime);  // Add the mime type from Code igniter.
      header('Content-Disposition: $disposition; filename="'.basename($name).'"');  // Add the file name
      header('Content-Transfer-Encoding: binary');
      header('Content-Length: '.$length); // provide file size
      header('Connection: Keep-Alive');

      ob_end_flush();   // <--- instead of ob_clean()
      set_time_limit(0);
      readfile($path); // push it out
      exit();
    }
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

        include_once ("xml/read-grade-envelope.php");
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
