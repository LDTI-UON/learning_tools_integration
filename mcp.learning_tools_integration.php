<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * EE Learning Tools Integration Module Control Panel File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Paul Sijpkes
 * @link		http://sijpkes.site11.com
 */

class Learning_tools_integration_mcp {

    public $name = "Learning_tools_integration";

	public $return_data;

	private $_base_url;
	private $module_name = "learning_tools_integration";
	private $perpage = 10;

	private $message = false;
	private $oauth_consumer_key = false;
	private $secret = false;
	private $context_id = false;
	private $context_name = false;
	private $idvalue = false;

	private $maintenance_key = 'working17923';

    private $default_sidebar_item;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		//ee() =& get_instance();

		//$this->_base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->module_name;

        $this->_base_url = ee('CP/URL')->make('addons/settings/learning_tools_integration');

            ee()->cp->set_right_nav(array(
		'show_consumers'  => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'
            .AMP.'module='.$this->module_name.AMP.'method=index',
        'list_contexts'  => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'
            .AMP.'module='.$this->module_name.AMP.'method=list_contexts',
        'list_institutions'  => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'
            .AMP.'module='.$this->module_name.AMP.'method=list_institutions'
    	));

		$cronfig = PATH_THIRD.$this->module_name.'/libraries/cron/cronfig.php';
		/* setup BASEPATH for cron job */
		if(!file_exists($cronfig)) {
			$bp = BASEPATH;
			$b = array();

			// remove codeigniter system folder path, we want the ee system folder path
			if(strpos(BASEPATH,'codeigniter') !== FALSE) {
				$a = explode(DIRECTORY_SEPARATOR, BASEPATH);
				foreach($a as $d) {
					if($d === 'codeigniter') {
						break;
					}

					$b[] = $d;
				}

				$bp = implode(DIRECTORY_SEPARATOR, $b);
			}

			$str = "<?php \n\$cronfig['env_path'] = '".$bp."';\n";
			file_put_contents($cronfig, $str);
		}

		$query = ee()->db->get_where("lti_member_contexts",array("member_id" => ee()->session->userdata('member_id')));

		// create dummy context for admin user
		if($query->num_rows() == 0) {
			$_t_user_id = 'temp_user_id_ee_tool_launch_'.(String)(rand(10000, 99999));
			//echo $_t_user_id;
			$data = array('username' => ee()->session->userdata('username'),
					'member_id' => ee()->session->userdata('member_id'),
					'user_id' => $_t_user_id,
					'session_id' => ee()->session->userdata('session_id'),
					'context_id' => 'temp_ee_context_id',
					'context_label' => 'EE LTI TestContext Label',
					'ext_lms' => 'bb-1.1.1',
					'tool_consumer_instance_id' => '1', // this will change once institution context is included in launch
					'tool_consumer_instance_name' => 'EE LTI Test Tool Consumer',
					'is_instructor' => '1',
                    'course_name' => 'EE LTI Test Course',
			);
			//print "<pre>";
			//var_dump($data);

			ee()->db->insert('lti_member_contexts', $data);
		}

        $sidebar = ee('CP/Sidebar')->make();

        $lti_config = $sidebar->addHeader('LTI Configuration');//->withButton(lang('new'), ee('CP/URL', 'addons/settings/learning_tools_integration/index'));

        $lti_config_list = $lti_config->addBasicList();

       $this->default_sidebar_item = $lti_config_list->addItem('Tool Consumers', ee('CP/URL', 'addons/settings/learning_tools_integration/index'));
        $lti_config_list->addItem('User Contexts', ee('CP/URL', 'addons/settings/learning_tools_integration/list_contexts'));
        $lti_config_list->addItem('Institutions', ee('CP/URL', 'addons/settings/learning_tools_integration/list_institutions'));
	}

	// ----------------------------------------------------------------

	/**
	 * Index Function
	 *
	 * @return 	void
	 */
	public function index()
	{
		ee()->load->library('table');
		ee()->load->helper('form');

		if (version_compare(APP_VER, '2.6', '>=')) {
            ee()->view->cp_page_title = lang('learning_tools_integration_module_name');
        } else {
            ee()->cp->set_variable('cp_page_title', lang('learning_tools_integration_module_name'));
        }

        $this->default_sidebar_item->isActive();
		/**
		 * This is the addons home page, add more code here!
		 */
        $vars['add_consumer_action'] = ee('CP/URL')->make('addons/settings/learning_tools_integration/add_consumer');

        $vars['action_url'] = ee('CP/URL')->make('addons/settings/learning_tools_integration/edit_consumers');

   		$vars['form_hidden'] = NULL;
    	$vars['files'] = array();

    	$vars['options'] = array(
        	'delete'    => lang('delete_selected'),
        	//'add' => lang('add_consumer'),
    	);

		if ( ! $rownum = ee()->input->get_post('rownum'))
		{
    		$rownum = 0;
		}

		ee()->db->order_by("updated_at", "desc");
		$query = ee()->db->get('blti_keys', $this->perpage, $rownum);
		$url = ee()->config->site_url();

        $vars['consumers'] = array();
		foreach($query->result_array() as $row) {
			$vars['consumers'][$row['id']]['id'] = $row['id'];
			$vars['consumers'][$row['id']]['name'] = $row['name'];
			$vars['consumers'][$row['id']]['key'] = $row['oauth_consumer_key'];
			$vars['consumers'][$row['id']]['secret'] = $row['secret'];
			$vars['consumers'][$row['id']]['url_segment'] = $url."/".$row['url_segment'];
			$vars['consumers'][$row['id']]['toggle'] = array(
		        'name'      => 'toggle[]',
		        'id'        => 'edit_box_'.$row['id'],
		        'value'     => $row['id'],
		        'class'     =>'toggle'
    		);
		}

		$total = ee()->db->count_all('blti_keys');

		// Pass the relevant data to the paginate class so it can display the "next page" links
   		 ee()->load->library('pagination');
    	$p_config = $this->pagination_config('index', $total);

    	ee()->pagination->initialize($p_config);

    	$vars['pagination'] = ee()->pagination->create_links();

        // @TODO: add settings options for upload path
        $vars['settings_url'] = ee('CP/URL')->make('addons/settings/learning_tools_integration/save_settings');
        $vars['upload_path'] = '';

        $vars['maintenance_key'] = $this->maintenance_key;

        $cronpath = PATH_THIRD.$this->module_name."/libraries/cron";
        $vars['cron_php_call'] = "* * * * * ".PHP_BINDIR."/php -f\n$cronpath/export_check.php\n>> $cronpath/export.log\n";
		$vars['cron_command'] = "<p>In the terminal type:<pre>sudo crontab -e</pre>Press ENTER</p><p>This will take you to the Vi editor, press <b>I</b> and paste in:<pre>$vars[cron_php_call]</pre><br>then press ZZ (must be capitals).</p>";



       // $vars['sidebar'] = $sidebar;
      //  return ee()->load->view('index', $vars, TRUE);

        return ee('View')->make('learning_tools_integration:index')->render($vars);
	}

    public function list_contexts() {
        ee()->load->library('table');
        ee()->load->helper('form');

        if (version_compare(APP_VER, '2.6', '>=')) {
            ee()->view->cp_page_title = lang('learning_tools_integration_module_name');
        } else {
            ee()->cp->set_variable('cp_page_title', lang('learning_tools_integration_module_name'));
        }

        $vars['action_url'] = ee('CP/URL')->make('addons/settings/learning_tools_integration/edit_contexts');
        $vars['launch_url'] = ee('CP/URL')
                                ->make('addons/settings/learning_tools_integration/prepare_launch_context');

        $vars['form_hidden'] = NULL;

        $vars['options'] = array(
            'delete'    => lang('delete_selected')
        );

        if ( ! $rownum = ee()->input->get_post('rownum'))
        {
            $rownum = 0;
        }

        //ee()->db->where("user_id LIKE '%temp_user_id_ee_tool_launch%'");
        //ee()->db->update("lti_member_contexts", array('user_id' => NULL));

        ee()->db->order_by("lti_member_contexts.username", "desc");
        ee()->db->join('members', 'members.member_id = lti_member_contexts.member_id', 'left outer');
        $query = ee()->db->get('lti_member_contexts', $this->perpage, $rownum);

        $vars['contexts'] = array();
        foreach($query->result_array() as $row) {
            $vars['contexts'][$row['id']]['id'] = $row['id'];
            $vars['contexts'][$row['id']]['member_id'] = $row['member_id'];
            $vars['contexts'][$row['id']]['user_id'] = $row['user_id'];
            $vars['contexts'][$row['id']]['username'] = $row['username'];
            $vars['contexts'][$row['id']]['email'] = $row['email'];
            $vars['contexts'][$row['id']]['member_id'] = $row['member_id'];
            $vars['contexts'][$row['id']]['context_id'] = $row['context_id'];
            $vars['contexts'][$row['id']]['tool_consumer_instance_name'] = $row['tool_consumer_instance_name'];
            $vars['contexts'][$row['id']]['is_instructor'] = $row['is_instructor'];
            $vars['contexts'][$row['id']]['toggle'] = array(
                'name'      => 'toggle[]',
                'id'        => 'edit_box_'.$row['id'],
                'value'     => $row['id'],
                'class'     =>'toggle'
            );
        }

        //@TODO: make this link via the institutions list to filter on context

        $total = ee()->db->count_all('lti_member_contexts');

        // Pass the relevant data to the paginate class so it can display the "next page" links
        ee()->load->library('pagination');
        $p_config = $this->pagination_config('list_contexts', $total);

        ee()->pagination->initialize($p_config);

        $vars['pagination'] = ee()->pagination->create_links();

        return ee()->load->view('contexts', $vars, TRUE);
    }

    public function list_institutions() {
         if (version_compare(APP_VER, '2.6', '>=')) {
            ee()->view->cp_page_title = lang('learning_tools_integration_module_name');
        } else {
            ee()->cp->set_variable('cp_page_title', lang('learning_tools_integration_module_name'));
        }
        ee()->load->library('table');

        $vars['action_url'] = ee('CP/URL')->make('addons/settings/learning_tools_integration/edit_institutions');

        $vars['action_add_institution'] = ee('CP/URL')->make('addons/settings/learning_tools_integration/add_institution');

        $vars['form_hidden'] = NULL;

        $vars['contexts_url'] = ee('CP/URL')->make('addons/settings/learning_tools_integration/list_tool_consumer_instances');

        $vars['options'] = array(
            'delete'    => lang('delete_selected')
        );

        if ( ! $rownum = ee()->input->get_post('rownum'))
        {
            $rownum = 0;
        }

        ee()->db->order_by("name", "desc");
        $query = ee()->db->get('lti_institutions', $this->perpage, $rownum);

        $vars['institutions'] = array();
        foreach($query->result_array() as $row) {
            $vars['institutions'][$row['id']]['id'] = $row['id'];
            $vars['institutions'][$row['id']]['name'] = $row['name'];
            $vars['institutions'][$row['id']]['toggle'] = array(
                'name'      => 'toggle[]',
                'id'        => 'edit_box_'.$row['id'],
                'value'     => $row['id'],
                'class'     =>'toggle'
            );
        }

        $total = ee()->db->count_all('lti_institutions');

        // Pass the relevant data to the paginate class so it can display the "next page" links
        ee()->load->library('pagination');
        $p_config = $this->pagination_config('institutions', $total);

        ee()->pagination->initialize($p_config);

        $vars['pagination'] = ee()->pagination->create_links();
        return ee()->load->view('institutions', $vars, TRUE);
    }

    public function list_tool_consumer_instances() {
         if (version_compare(APP_VER, '2.6', '>=')) {
            ee()->view->cp_page_title = lang('learning_tools_integration_module_name');
        } else {
            ee()->cp->set_variable('cp_page_title', lang('learning_tools_integration_module_name'));
        }
        ee()->load->library('table');

        $vars['action_url'] = ee('CP/URL')->make('addons/settings/learning_tools_integration/edit_instances');
        $vars['action_add_instance'] = ee('CP/URL')->make('addons/settings/learning_tools_integration/add_instances');

        $vars['form_hidden'] = NULL;

        $vars['options'] = array(
            'delete'    => lang('delete_selected')
        );

        if ( ! $rownum = ee()->input->get_post('rownum'))
        {
            $rownum = 0;
        }

        $vars['institution_name'] = ee()->input->get_post('inname');
        $query = ee()->db->get_where('lti_tool_consumer_instances', array('id' =>  ee()->input->get_post('inid')), $this->perpage, $rownum);

        $vars['instances'] = array();
        foreach($query->result_array() as $row) {
            $vars['instances'][$row['guid']]['guid'] = $row['guid'];
            $vars['instances'][$row['guid']]['toggle'] = array(
                'name'      => 'toggle[]',
                'id'        => 'edit_box_'.$row['id'],
                'value'     => $row['guid'],
                'class'     =>'toggle'
            );
        }

        ee()->db->where(array('id' =>  ee()->input->get_post('inid')));
        ee()->db->from('lti_tool_consumer_instances');
        $total = ee()->db->count_all_results();
        //echo "total: $total";
        // Pass the relevant data to the paginate class so it can display the "next page" links
        ee()->load->library('pagination');
        $p_config = $this->pagination_config('instances', $total);

        ee()->pagination->initialize($p_config);

        $vars['pagination'] = ee()->pagination->create_links();
        return ee()->load->view('tool_consumer_instances', $vars, TRUE);
    }

    /* from Justin Richer - http://php.net/manual/en/function.rand.php*/
    private function randomString() {
        $arr = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890!@#$%^&*'); // get all the characters into an array
        shuffle($arr); // randomize the array
        $arr = array_slice($arr, 0, 6); // get the first six (random) characters out
        $str = implode('', $arr); // smush them back into a string
        return $str;
    }

	public function add_consumer() {

		if (version_compare(APP_VER, '2.6', '>=')) {
            ee()->view->cp_page_title = lang('learning_tools_integration_module_name');
        } else {
            ee()->cp->set_variable('cp_page_title', lang('learning_tools_integration_module_name'));
        }

        $vars['action'] = ee('CP/URL')->make('addons/settings/learning_tools_integration/add_consumer');

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules('add_name', lang('add_consumer_name'), 'required')->set_error_delimiters(
                        '<div class="notice">', '</div>');
		ee()->form_validation->set_rules('add_key', lang('add_consumer_key'), 'required')->set_error_delimiters(
                        '<div class="notice">', '</div>');
		ee()->form_validation->set_rules('add_secret', lang('add_consumer_secret'), 'required')->set_error_delimiters(
                        '<div class="notice">', '</div>');
		ee()->form_validation->set_rules('add_url_segment', lang('add_url_segment'), 'required')->set_error_delimiters(
                        '<div class="notice">', '</div>');

        if(!ee()->input->post('type') ==  'render_form') {
    		if (ee()->form_validation->run())
    		{
    			$data = array(
    				'name' => ee()->input->post('add_name'),
    				'oauth_consumer_key' => ee()->input->post('add_key'),
    				'secret' => ee()->input->post('add_secret'),
    				'url_segment' => ee()->input->post('add_url_segment'),
    				'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
    			);

    			ee()->db->insert('blti_keys', $data);

    			$id = ee()->db->insert_id();

                ee()->session->set_flashdata('message_success', lang('link_added'));

                //redirect to index
                ee()->functions->redirect(ee('CP/URL')->make('addons/settings/learning_tools_integration'));

    		}
		}

        $vars['key'] = ee()->input->post('add_secret') ? ee()->input->post('add_secret') : $this->randomString();

        $vars['secret'] = ee()->input->post('add_key') ? ee()->input->post('add_key') : $this->randomString();

		return ee()->load->view('add-consumer', $vars, TRUE);
	}

    public function add_institution() {

        if (version_compare(APP_VER, '2.6', '>=')) {
            ee()->view->cp_page_title = lang('add_institution');
        } else {
            ee()->cp->set_variable('cp_page_title', lang('add_institution'));
        }

        $vars['action'] = ee('CP/URL')->make('addons/settings/learning_tools_integration/add_institution');
        ee()->load->library('form_validation');
        ee()->form_validation->set_rules('add_name', lang('add_consumer_name'), 'required')->set_error_delimiters(
                        '<div class="notice">', '</div>');

        if(!ee()->input->post('type') ==  'render_form') {
            if (ee()->form_validation->run())
            {
                $data = array(
                    'name' => ee()->input->post('add_name')
                );

                ee()->db->insert('lti_institutions', $data);

                $id = ee()->db->insert_id();

                ee()->session->set_flashdata('message_success', lang('link_added'));

                ee()->functions->redirect(ee('CP/URL')->make('addons/settings/learning_tools_integration/list_institutions')); //redirect to index //redirect to list institutions
            }
        }

    return ee()->load->view('add-institution', $vars, TRUE);
    }

    public function prepare_launch_context() {

        $vars['action'] = ee('CP/URL')->make('addons/settings/learning_tools_integration/tool_launch');
        $vars['form_hidden'] = NULL;

        $vars['context_id'] = ee()->input->post("hci");
        $vars['username'] =  ee()->input->post("un");
        $vars['email'] =  ee()->input->post("em");

        $vars['csrf_token'] = CSRF_TOKEN;

        $vars['username'] = ee()->input->post("un");

        $keys = ee()->db->get('blti_keys');
        if($keys->num_rows() > 0) {
            foreach($keys->result_array() as $row) {
                $segments[$row['id'].".".$row['oauth_consumer_key'].".".$row['secret'].".".$row['url_segment']] = $row['name'];
            }
        }

        if(isset($segments)) {
            $vars['segment_dd'] = form_dropdown('segment', $segments, '', "id='segment_dd'");
        } else {
            $vars['user_message'] = lang('please_add_segments_first');
        }

        return ee('View')->make('learning_tools_integration:prepare_launch')->render($vars);
    }

    public function tool_launch() {
        $context_id = ee()->input->post('context_id');

        if(!isset($_POST['segment'])) {
              ee()->functions->redirect(ee('CP/URL')->make('addons/settings/learning_tools_integration/list_contexts'));
        }

        $data = explode(".", ee()->input->post('segment'));

        $vars['key'] = $data[1];
        $vars['secret'] = $data[2];
        $vars['launch_url'] = site_url().'/'.$data[3];

        $vars['username'] = ee()->input->post('username');
        $vars['email'] = ee()->input->post('email');

        ee()->db->where(array('id' => $context_id));
        $query = ee()->db->get('lti_member_contexts');

        if($query->num_rows() > 0) {
        $launch_p = $query->row();

        // get the GUID
        $query = ee()->db->get_where('lti_tool_consumer_instances', array('id' => $launch_p->tool_consumer_instance_id));

        if($query->num_rows() == 0) {
            die("Your institution, identified by: <b>'".$launch_p->tool_consumer_instance_id."'</b> is not registered."); // @TODO change this to consumer context
        }
        $launch_p->tool_consumer_instance_guid = $query->row()->guid;

        $vars['launch_params'] = $launch_p;

      //  setcookie('launch_params',  json_encode($vars), time() + 900, "/", NULL, $this->use_SSL, TRUE);

        return ee()->load->view('tool_launch', $vars, TRUE);
        } else {
        	return FALSE;
        }
    }

    private function clear_all_cookies_prior_to_launch() {
        if (isset($_SERVER['HTTP_COOKIE'])) {
         $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
            foreach($cookies as $cookie) {
                $parts = explode('=', $cookie);
                $context_name = trim($parts[0]);
                setcookie($context_name, '', time()-1000);
                setcookie($context_name, '', time()-1000, '/');
        }
    }
    }

	function delete_consumers()
	{

		if ( ! ee()->input->post('delete'))
		{
			ee()->functions->redirect(ee('CP/URL')->make('addons/settings/learning_tools_integration'));
		}

		foreach ($_POST['delete'] as $key => $val)
		{
			ee()->db->or_where('id', $val);
		}

		ee()->db->delete('blti_keys');

		$message = (count($_POST['delete']) == 1) ? ee()->lang->line('consumer_deleted') : ee()->lang->line('consumer_deleted');

		ee()->session->set_flashdata('message_success', $message);
		ee()->functions->redirect(ee('CP/URL')->make('addons/settings/learning_tools_integration'));

	}

    function edit_contexts() {

        if ( ! ee()->input->post('action') == 'delete')
        {
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/learning_tools_integration/list_contexts'));
        }

        foreach ($_POST['toggle'] as $key => $val)
        {
            ee()->db->or_where('id', $val);
        }

        ee()->db->delete('lti_member_contexts');

        $message = (count($_POST['delete']) == 1) ? ee()->lang->line('member_context_deleted') : ee()->lang->line('member_context_deleted');

        ee()->session->set_flashdata('message_success', $message);
        ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->module_name.AMP.'method=list_contexts');
    }

    function edit_institutions() {
        if (ee()->input->post('action') == 'delete')
        {
             if (version_compare(APP_VER, '2.6', '>=')) {
                    ee()->view->cp_page_title = lang('delete_institutions');
             } else {
                    ee()->cp->set_variable('cp_page_title', lang('delete_institutions'));
             }

            foreach ($_POST['toggle'] as $key => $val)
            {
                $vars['binned'][] = $val;
            }

            $vars['form_action'] = ee('CP/URL')->make('addons/settings/learning_tools_integration/delete_institutions');

            $vars['type'] = 'institution';

            return ee()->load->view('delete-confirm', $vars, TRUE);
        }
    }

    function delete_institutions() {
        if ( ! ee()->input->post('delete'))
        {
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/learning_tools_integration/list_institutions'));
        }

        foreach ($_POST['delete'] as $key => $val)
        {
            ee()->db->or_where('id', $val);
        }

        ee()->db->delete('lti_institutions');

        $message = (count($_POST['delete']) == 1) ? ee()->lang->line('member_context_deleted') : ee()->lang->line('member_context_deleted');

        ee()->session->set_flashdata('message_success', $message);
        ee()->functions->redirect( ee('CP/URL')->make('addons/settings/learning_tools_integration/list_institutions'));
    }

	function edit_consumers() {
		if (ee()->input->post('action') == 'delete')
		{
			if (version_compare(APP_VER, '2.6', '>=')) {
            	ee()->view->cp_page_title = lang('delete_consumers');
       		 } else {
           		 ee()->cp->set_variable('cp_page_title', lang('delete_consumers'));
       		 }

			//ee()->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this, ee()->lang->line('download_module_name'));

			foreach ($_POST['toggle'] as $key => $val)
			{
				$vars['binned'][] = $val;
			}
			$vars['type'] = 'consumers';
			$vars['form_action'] = ee('CP/URL')->make('addons/settings/learning_tools_integration/delete_consumers');

			return ee()->load->view('delete-confirm', $vars, TRUE);

		}
	}

	function pagination_config($method, $total_rows)
	{
	    // Pass the relevant data to the paginate class
	    $config['base_url'] = ee('CP/URL')->make("addons/settings/learning_tools_integration/$method");
	    $config['total_rows'] = $total_rows;
	    $config['per_page'] = $this->perpage;
	    $config['page_query_string'] = TRUE;
	    $config['query_string_segment'] = 'rownum';
	    $config['full_tag_open'] = '<p id="paginationLinks">';
	    $config['full_tag_close'] = '</p>';
	    $config['prev_link'] = '<img src="'.ee()->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="<" />';
	    $config['next_link'] = '<img src="'.ee()->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt=">" />';
	    $config['first_link'] = '<img src="'.ee()->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="< <" />';
	    $config['last_link'] = '<img src="'.ee()->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="> >" />';

    return $config;
	}
	/**
	 * Start on your custom code here...
	 */

}
/* End of file mcp.learning_tools_integration.php */
/* Location: /system/expressionengine/third_party/learning_tools_integration/mcp.learning_tools_integration.php */
