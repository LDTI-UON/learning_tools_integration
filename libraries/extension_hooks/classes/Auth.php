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
  private $port;
  private $lti_module;

  public $cookies;
  public $url;
  public $curl;

  const SUCCESS = 0;
  const LOGIN_ERROR = 1;
  const FAIL = 2;

  function __construct(&$lti_module)  {

      ee()->config->load('lti_config', TRUE);
      $this->scheme = $lti_module->use_SSL ? "https" : "http";
      $this->host = $lti_module->lti_url_host;
      $this->path = ee()->config->item('blackboard_auth_path');
      $this->port = $lti_module->lti_url_port;
      $this->lti_module = $lti_module;
  }

  public function get_blackboard_url() {

      $base = $this->scheme.'://'.$this->host;

      if($this->port !== NULL) {
          $base .= ":".$this->port;
      }

      return $base;
  }

  private function get_auth_url() {
    if($this->lti_module->dev) {
        return "http://dev.bb.local:8080/webapps/login/";
    }

    return $this->get_blackboard_url().'/'.$this->path;
  }

  public function bb_lms_rest($user) {

  }

   public function bb_lms_login($user, $pass) {
    $url = $this->get_auth_url();
    $cookie_path = ee()->config->item('lti_cookies');

    // contextualise cookies
    $cookies = $cookie_path.$this->lti_module->member_id."_".$this->lti_module->context_id."_".$this->lti_module->institution_id."_cookie.txt";

    if(file_exists($cookies)) {
        unlink($cookies);
    }

    $data = array('action' => 'login', 'login' => 'Login', 'password' => $pass, 'user_id' => $user, 'new_loc' => '');
    $post_str = http_build_query($data);
    $length = strlen($post_str);
    $agent = Utils::getRandomUserAgent();

    $this->curl = curl_init();
    $host = $this->port ? $this->host.":".$this->port : $this->host;

    curl_setopt($this->curl, CURLOPT_URL, $url);
    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array("Content-Length: $length", "Content-Type: application/x-www-form-urlencoded", "Cache-Control:max-age=0", "Host: $host"));
    curl_setopt($this->curl, CURLOPT_COOKIEJAR, $cookies);
    curl_setopt($this->curl, CURLOPT_COOKIEFILE, $cookies);
    curl_setopt($this->curl, CURLOPT_USERAGENT, $agent);
    curl_setopt($this->curl, CURLOPT_POST, 5);
    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post_str);
    curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($this->curl, CURLOPT_FRESH_CONNECT, TRUE);

    $page = curl_exec($this->curl);
      $ob = $page;
    $doc = new \DOMDocument("1.0", "utf-8");

    $page = htmlspecialchars($page);

    if(strlen($page) > 0) {
        $loaded = $doc->loadHTML($page);
        if($loaded) {
            //$el = $doc->getELementById("loginErrorMessage");
            $el = $doc->getELementById("loginErrorMessage");
          /*  echo "Element: ";
            var_dump($el);
            echo "<hr><div>$page</div>";
            exit;*/
            if($el !== NULL) {
                return Auth::LOGIN_ERROR;
            }

            $el = $doc->getELementById("globalNavPageNavArea");

            if($el === NULL) {
               if(strpos($page, "redirect") === FALSE) {
                    return Auth::FAIL;
               }
            }
        }
    } else {
        return Auth::FAIL;
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
