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

        $this->working = $course_dir.DIRECTORY_SEPARATOR."working".DIRECTORY_SEPARATOR;

        if(!file_exists($this->working)) {
                if(!mkdir($this->working)) { die('cache is not writable'); }
	}

        $this->data_dir = $course_dir.DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR;

	if(!file_exists($this->data_dir)) {
                if(!mkdir($this->data_dir)) { die('cache is not writable'); }
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
	//echo "<pre>";
	//echo $this->file_name.", ".$this->full_path.", ".$this->$this->working."\n";
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

					//echo "$name $base_name $index $this->problem_prefix $solution_prefix<br>";
					if ($index > -1) {
						$where = array('base_name' => $base_name, 'type' => $type, 'course_id' => $this->course_id);

						$cr = ee()->db->get_where('lti_member_resources', $where);

						if($cr->num_rows() == 0) {
							$where = array('file_name' => $new_filename, 'uploader_internal_context_id' => $this -> internal_context_id, 'base_name' => $base_name, 'display_name' => $name, 'type' => $type, 'course_id' => $this->course_id);

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

}
