<?php
namespace LTI\ExtensionHooks;
/*
*
*   this class requires the EE config item to be set in the config.php file.
*   note that Blackboard seems to like a trailing slash...
*  example format:  $config['blackboard_auth_path'] = '/path/to/webapp/auth/';
*/
class Auth {

  private $host;
  private $path;
  private $lti_module;

  public $cookies;
  public $url;
  public $curl;

  const SUCCESS = 0;
  const LOGIN_ERROR = 1;
  const FAIL = 2;

  function __construct(&$lti_module)  {
      $this->scheme = $lti_module->use_SSL ? "https" : "http";
      $this->host = $lti_module->lti_url_host;
      $this->path = ee()->config->item('blackboard_auth_path');

      $this->lti_module = $lti_module;
  }

  public function get_blackboard_url() {
      return $this->scheme.'://'.$this->host;
  }

   public function bb_lms_login($user, $pass) {
    $url = $this->scheme.'://'.$this->host.'/'.$this->path;
//    $url = "https://uonline.newcastle.edu.au/webapps/login/";

    // contextualise cookies
    $cookies = PATH_THIRD."learning_tools_integration/data/".$this->lti_module->member_id."_".$this->lti_module->context_id."_".$this->lti_module->institution_id."_cookie.txt";

    if(file_exists($cookies)) {
        unlink($cookies);
    }

    $data = array('action' => 'login', 'login' => 'Login', 'password' => $pass, 'user_id' => $user, 'new_loc' => '');
    $post_str = http_build_query($data);
    $length = strlen($post_str);
    $agent = Utils::getRandomUserAgent();

    $this->curl = curl_init();

    curl_setopt($this->curl, CURLOPT_URL, $url);
    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array("Content-Length: $length", "Content-Type: application/x-www-form-urlencoded", "Cache-Control:max-age=0", "Host: $this->host"));
    curl_setopt($this->curl, CURLOPT_COOKIEJAR, $cookies);
    curl_setopt($this->curl, CURLOPT_COOKIEFILE, $cookies);
    curl_setopt($this->curl, CURLOPT_USERAGENT, $agent);
    curl_setopt($this->curl, CURLOPT_POST, 5);
    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post_str);
    curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($this->curl, CURLOPT_FRESH_CONNECT, TRUE);

    $page = curl_exec($this->curl);

    $doc = new \DOMDocument();

    $page = htmlspecialchars($page);

    if(strlen($page) > 0) {
        if($doc->loadHTML($page)) {
            $el = $doc->getELementById("loginErrorMessage");

            if($el !== NULL) {
                return Auth::LOGIN_ERROR;
            }

            $el = $doc->getELementById("paneTabs");

            if($el === NULL) {
               if(strpos($page, "redirect") === FALSE) {
                    return Auth::FAIL;
               }
            }
        }
    }

    $this->cookies = $cookies;
    $this->url = $url;

    return Auth::SUCCESS;
}

public function gradebook_login()
{
       $query = ee()->db->get_where('lti_instructor_credentials', array('member_id' => $this->lti_module->member_id, 'context_id' => $this->lti_module->context_id));

    if(!empty($query->row()->password)) {
        $decrypted = Encryption::decrypt($query->row()->password, Encryption::get_salt($this->lti_module->user_id.$this->lti_module->context_id));

       return $this->bb_lms_login($this->lti_module->username, $decrypted);
    } else {
        return 1;
    }
}
}