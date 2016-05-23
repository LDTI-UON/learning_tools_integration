<?php
$hook_method = function($args) {

    $enc_filename = $args[0];
    $iv = $args[1];
    $type = $args[2];

      if (empty($enc_filename) || empty($iv) || empty($type)) {
          echo "<p>" . lang('download_error') . "</p>";
          return;
      }

      if ($type !== 'solution' && $type !== 'problem') {
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

      ResourceFile::_push_file(LTI_FILE_UPLOAD_PATH.DIRECTORY_SEPARATOR.$this->context_id.$this->institution_id.$this->course_id.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.$filename,
                        $type . "_" . $this -> context_label . "_data." . $ext);
};

/*
* Generate inline tags for instructor
*/
$launch_no_template = function() {
        if (isset($_GET['f']) && isset($_GET['i']) && isset($_GET['t'])) {
              $this -> do_download(ee()->input->get('f'), ee()->input->get('i'), ee()->input->get('t'));
        }
};
?>
