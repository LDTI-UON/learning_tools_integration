<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$export_email_subject = 'Bulk Export Complete';
$export_email_from = 'uonline@newcastle.edu.au';

function urlsafe_b64encode($string)
{
  $data = base64_encode($string);
  $data = str_replace(array('+','/','='),array('-','_','.'),$data);
  return $data;
}

function urlsafe_b64decode($string)
{
  $data = str_replace(array('-','_','.'),array('+','/','='),$string);
  $mod4 = strlen($data) % 4;
  if ($mod4) {
    $data .= substr('====', $mod4);
  }
  return base64_decode($data);
}

function get_js_file_for_output($file) {
	$str = "<script type='text/javascript'>\n//<![CDATA[\n";
	$str .= file_get_contents(PATH_THIRD.'learning_tools_integration/js/'.$file.'.js');
	$str .= "//]]>\n</script>";

	return $str;
}

function build_course_upload_path($full_path, $context_id, $institution_id, $course_id) {

	$_t = explode(DIRECTORY_SEPARATOR, $full_path);
	array_pop($_t);

	$base_path = implode(DIRECTORY_SEPARATOR, $_t);

	$course_upload_dir = $base_path.DIRECTORY_SEPARATOR.$context_id.$institution_id.$course_id;
	if(!file_exists($course_upload_dir)) {
		if(!mkdir($course_upload_dir)) {
			die("Unable to create course upload directory, check folder permissions. ($course_upload_dir)");
		}
	}

	return $course_upload_dir;
}


