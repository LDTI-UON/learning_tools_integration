<?php
namespace LTI\ExtensionHooks;

class Utils {

static function urlsafe_b64encode($string)
{
  $data = base64_encode($string);
  $data = str_replace(array('+','/','='),array('-','_','.'),$data);
  return $data;
}

static function urlsafe_b64decode($string)
{
  $data = str_replace(array('-','_','.'),array('+','/','='),$string);
  $mod4 = strlen($data) % 4;
  if ($mod4) {
    $data .= substr('====', $mod4);
  }
  return base64_decode($data);
}

static function get_js_file_for_output($file) {
	$str = "<script type='text/javascript'>\n//<![CDATA[\n";
	$str .= file_get_contents(PATH_THIRD.'learning_tools_integration/js/'.$file.'.js');
	$str .= "//]]>\n</script>";

	return $str;
}

static function build_course_upload_path($full_path, $context_id, $institution_id, $course_id) {

	$_t = explode(DIRECTORY_SEPARATOR, $full_path);
	array_pop($_t);

	$base_path = implode(DIRECTORY_SEPARATOR, $_t);

	$course_upload_dir = $base_path.DIRECTORY_SEPARATOR.$context_id.$institution_id.$course_id;
	if(!file_exists($course_upload_dir)) {
		if(!mkdir($course_upload_dir)) {
			die("<p>Unable to create course upload directory, check folder permissions.</p> <p><strong>($course_upload_dir)</strong></p>");
		}
	}

	return $course_upload_dir;
}

static function getRandomUserAgent() {
    $userAgents = array("Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6", "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)", "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)", "Opera/9.20 (Windows NT 6.0; U; en)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; en) Opera 8.50", "Mozilla/4.0 (compatible; MSIE 6.0; MSIE 5.5; Windows NT 5.1) Opera 7.02 [en]", "Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; fr; rv:1.7) Gecko/20040624 Firefox/0.9", "Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/48 (like Gecko) Safari/48");
    $random = rand(0, count($userAgents) - 1);

    return $userAgents[$random];
}
}
