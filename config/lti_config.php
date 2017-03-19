<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// cache areas
$config['lti_cache'] = '/var/www/lti/cache/';
$config['lti_upload'] = "/var/www/lti/cache/upload/";
$config['lti_secret'] = '/var/www/lti/cache/secret/';
$config['lti_ghost'] = '/var/www/lti/cache/ghost/'; // user for creating new users
$config['lti_cookies'] = '/var/www/lti/cache/cookies/';

// jquery dist.
$config['jquery_src'] = '//cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js';

//LTI config items
//

$config['blackboard_auth_path'] = 'webapps/login/';
$config['blackboard_gradebook_uri_query'] = '/webapps/gradebook/do/instructor/getJSONData?course_id=';
