<?php

class ResourceFile {

private $full_path;
private $file_name;
private $working;
private $data_dir;
private $internal_context_id;
private $problem_prefix;
private $solution_prefix;
private $context_id;
private $institution_id;
private $course_id;

function __construct($full_path,
		     $internal_context_id, $context_id, $institution_id, $course_id,
                     $problem_prefix, $solution_prefix) {

		$_t = explode(DIRECTORY_SEPARATOR, $full_path);
		$this->file_name = array_pop($_t);

	    $base_path = implode(DIRECTORY_SEPARATOR, $_t);
	    $course_dir = $base_path.DIRECTORY_SEPARATOR.$context_id.$institution_id.$course_id;

        if(!file_exists($course_dir)) {
            if(!mkdir($course_dir)) { die("unable to create course directory: $course_dir"); }
        }

				// try to chmod course folder for big file upload.  can supress this because this can be done manually.
				@chmod($course_dir, 0777);

        $this->working = $course_dir.DIRECTORY_SEPARATOR."working".DIRECTORY_SEPARATOR;

        if(!file_exists($this->working)) {
                if(!mkdir($this->working)) { die('cache is not writable'); }
				}

        $this->data_dir = $course_dir.DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR;

	if(!file_exists($this->data_dir)) {
                if(!mkdir($this->data_dir)) { die('cache is not writable'); }
	}

	if(file_exists($full_path)) {
		if(!rename($full_path, $course_dir.DIRECTORY_SEPARATOR.$this->file_name)) {
				die("Problem renaming the uploaded file, check that the <pre>$course_dir</pre> folder exists and has write permissions.");
		};
	}

	$this->full_path = $course_dir.DIRECTORY_SEPARATOR.$this->file_name;

	$this->problem_prefix = $problem_prefix;
	$this->solution_prefix = $solution_prefix;

	$this->internal_context_id = $internal_context_id;

	$this->context_id = $context_id; // LMSs course context
        $this->institution_id = $institution_id; //institution context
        $this->course_id = $course_id; // course context
}

public function import() {
$feedback = "";
$errors = "";
$ext = strtoupper(end(explode(".", $this->file_name)));

//echo "[$ext]\n";
// safe upload (CI not always good at this)
if (!in_array($ext, array("ZIP"))) {
	$errors .= "<br>'$ext' Filetype not allowed.";
}
//echo __LINE__."<<<< $errors";
if (empty($errors)) {
	//$feedback .= "<p>Zip file name looks okay... attempting import</p>";

	if(!file_exists($this->full_path)) {
		die("Problem with upload ".$this->full_path);
	}

	$zip = new ZipArchive;
	$res = $zip -> open($this->full_path);

	if ($res === TRUE) {
		// extract it to the path we determined above
		for ($i = 0; $i < $zip -> numFiles; $i++) {
			$filename = $zip -> getNameIndex($i);
			$fileinfo = pathinfo($filename);

			copy("zip://" . $this->full_path ."#". $filename, $this->working . $fileinfo['basename']);
		}
		$zip -> close();
		unlink($this->full_path);
	} else {
		if ($res == 19) {
			$errors .= "<p style='color:darkred'><b>This ZIP file is corrupted. I have deleted the file, please upload a clean version.</b></p>";
			unlink($this->full_path);
		} else {
			$errors .= "<p style='color:darkred'><b>Could not open ZIP file [ERROR CODE: $res], is the working folder writable?</b></p>";
		}
	}

	$dir = opendir($this->working);

	$impcount = 0;

	while (FALSE !== ($name = readdir($dir))) {
		if (!is_dir($this->working . $name)) {
			if (!empty($name) && substr($name, 0, 2) != '__' && substr($name, 0, 1) != '.') {
				$extarray = explode(".", $name);
				$fext = end($extarray);
				$count = 0;

				if (strlen($fext) != 0 && count($extarray) > 1) {
					//ensure unique filename
					do {
						$new_filename = $this -> generateRandomString();
						$new_filename .= ".$fext";
						$res =   ee() -> db -> get_where('lti_member_resources', array('file_name' => $new_filename));
						$count = $res -> num_rows();
					} while($count != 0);

					$index = -1;
                                        $i = strpos($name, $this->problem_prefix);

					if ($i !== FALSE) {
						$type = 'P';
						$index = $i + strlen($this->problem_prefix) - 1;
					} else {
              $si = strpos($name, $this->solution_prefix);

              if ($si !== FALSE) {
                      $type = 'S';
                      $index = $si + strlen($this->solution_prefix) - 1;
              }
          }

					$base_name = substr($name, $index);
					$ba = explode('.', $base_name);
					$base_name = $ba[0];
					$ba = explode('-', $ba[0]);
					$base_name = $ba[0];

					if ($index > -1) {
						$where = array('base_name' => $base_name, 'type' => $type, 'course_id' => $this->course_id);

						$cr = ee()->db->get_where('lti_member_resources', $where);

						if($cr->num_rows() == 0) {
							// salt used for encrypting file redirect to reduce chance of cheating.
							$salt = $this->generateRandomString(10);
							$where = array('file_name' => $new_filename, 'salt' => $salt, 'uploader_internal_context_id' => $this -> internal_context_id, 'base_name' => $base_name, 'display_name' => $name, 'type' => $type, 'course_id' => $this->course_id);

							ee() -> db -> insert('lti_member_resources', $where);
						}
					} else {
						$feedback .= "I could not determine the type of:\n\t '$name', \n\t\tso I skipped it, all files must have a problem or solution prefix.\n\n";
					}
					// move to data directory
					rename($this->working . $name, $this->data_dir . $new_filename);

					$impcount++;
				}
			} else {
                                if($name !== '.' && $name !== '..') {
                                    // remove unused file
                                    unlink($this->working . $name);
                                }
			}
		}
	}
	$report = '';
	if (!empty($feedback)) {
		$report = "<p> The following incidents were reported when processing the file: <br> <pre>MESSAGES:\n\n$feedback\n\nERRORS: $errors</pre> </p>";
	}
	if($impcount === 0 && empty($errors)) {
		$feedback = "<p><b>No files were imported because the file names already existed.</b></p>";
	} else {
		$feedback .= "<p><b>Imported $impcount files.</b></p>$errors";
	}
}

return $feedback.$errors;
}

private function generateRandomString($length = 8) {
	return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

public static function _push_file($path, $name)
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

public static function download_file($filename, $type, $salt) {

		$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

		$enc = mcrypt_encrypt(MCRYPT_BLOWFISH, $salt, $filename, MCRYPT_MODE_CBC, $iv);

		$iv = base64_encode($iv);
		$enc = base64_encode($enc);

		$vars['current_uri'] =  ee() -> functions -> fetch_current_uri();
		$vars['screen_name'] =  ee() -> session -> userdata('screen_name');
		$vars['filename'] = rawurlencode($enc);
		$vars['iv'] = rawurlencode($iv);
		$vars['ee_lti_token'] = Learning_tools_integration::get_instance() -> cookie_name;
		$vars['type'] = $type;
		$vars['download_redirect'] = Learning_tools_integration::get_instance() -> download_redirect;
		$vars['segment'] = Learning_tools_integration::get_instance() -> base_segment;
		$vars['return_url'] = Learning_tools_integration::get_instance() -> launch_presentation_return_url;

		return   ee() -> load -> view('download-redirect', $vars, TRUE);
}
/**Notice TODO deal with this shit:

Undefined property via __get(): cookie_name in /var/www/html/lti/1WSk0DUCbR/user/addons/learning_tools_integration/libraries/extension_hooks/secure_resource_delivery/ResourceFile.php on line 204

user/addons/learning_tools_integration/mod.learning_tools_integration.php, line 276 show details

    Severity: E_USER_NOTICE

Notice
Undefined property via __get(): download_redirect in /var/www/html/lti/1WSk0DUCbR/user/addons/learning_tools_integration/libraries/extension_hooks/secure_resource_delivery/ResourceFile.php on line 206

user/addons/learning_tools_integration/mod.learning_tools_integration.php, line 276 show details

    Severity: E_USER_NOTICE

Notice
Undefined property via __get(): base_segment in /var/www/html/lti/1WSk0DUCbR/user/addons/learning_tools_integration/libraries/extension_hooks/secure_resource_delivery/ResourceFile.php on line 207

user/addons/learning_tools_integration/mod.learning_tools_integration.php, line 276 show details

    Severity: E_USER_NOTICE

Warning
array_merge(): Argument #2 is not an array

user/addons/learning_tools_integration/mod.learning_tools_integration.php, line 496 show details

    Severity: E_WARNING

array ( 'launch_presentation_return_url' => 'https://uonline.newcastle.edu.au/webapps/blackboard/execute/blti/launchReturn?course_id=_1383049_1&content_id=_2927058_1&toGC=false&launch_time=1463718537843&launch_id=dbdb4e97-3139-423b-b1c9-baaf0bd48c8a&link_id=_2927058_1', 'tool_consumer_instance_name' => 'University of Newcastle', 'lis_outcome_service_url' => 'No marking service enabled', 'tool_consumer_instance_guid' => 'uonline.newcastle.edu.au', 'tool_consumer_instance_id' => '1', 'lis_result_sourcedid' => 'not set', 'resource_link_id' => '_2927058_1', 'user_id' => '5904cd513df942e9bb409dc210ddf518', 'user_key' => '3b5QXf:5904cd513df942e9bb409dc210ddf518', 'context_id' => '846b6daccc034fd49c64ae342c7957dd', 'internal_context_id' => '1661', 'context_label' => 'CRS.111910.2015.S2', 'ext_lms' => 'bb-9.1.201410.160373', 'isInstructor' => false, 'course_key' => '3b5QXf:846b6daccc034fd49c64ae342c7957dd', 'course_name' => 'STAT1070 STATISTICS FOR THE SCIENCES (S2 2015)', 'user_short_name' => 'Paul', 'resource_title' => 'Solution File', 'resource_link_description' => '', 'ext_launch_presentation_css_url' => 'https://uonline.newcastle.edu.au/common/shared.css,https://uonline.newcastle.edu.au/themes/as_2012/theme.css,https://uonline.newcastle.edu.au/coursethemes/slate/coursetheme.css', 'institution_id' => '1', 'course_id' => '1', 'pk_string' => '_1383049_1', 'base_url' => 'https://bold-space.newcastle.edu.au/lti/', 'css_link_tags' => ' ', 'user_email' => 'paul.sijpkes@newcastle.edu.au', 'base_segment' => 'stat1070', )
Please set the template path for this learning tool.

Notice
Undefined property via __get(): cookie_name in /var/www/html/lti/1WSk0DUCbR/user/addons/learning_tools_integration/libraries/extension_hooks/secure_resource_delivery/ResourceFile.php on line 204

user/addons/learning_tools_integration/mod.learning_tools_integration.php, line 276 show details

    Severity: E_USER_NOTICE

Notice
Undefined property via __get(): download_redirect in /var/www/html/lti/1WSk0DUCbR/user/addons/learning_tools_integration/libraries/extension_hooks/secure_resource_delivery/ResourceFile.php on line 206

user/addons/learning_tools_integration/mod.learning_tools_integration.php, line 276 show details

    Severity: E_USER_NOTICE

Notice
Undefined property via __get(): base_segment in /var/www/html/lti/1WSk0DUCbR/user/addons/learning_tools_integration/libraries/extension_hooks/secure_resource_delivery/ResourceFile.php on line 207

user/addons/learning_tools_integration/mod.learning_tools_integration.php, line 276 show details

    Severity: E_USER_NOTICE

Warning
Cannot modify header information - headers already sent by (output started at /var/www/html/lti/1WSk0DUCbR/user/addons/learning_tools_integration/mod.learning_tools_integration.php:358)

ee/EllisLab/ExpressionEngine/Boot/boot.common.php, line 463 show details

    Severity: E_WARNING

Your download will begin in 2 seconds
*/

}
