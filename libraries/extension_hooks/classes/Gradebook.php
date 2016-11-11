<?php
namespace LTI\ExtensionHooks;

class Gradebook {

private $lti_module;
private $cachedGradeBook;
private $settings;

function __construct($lti_module) {
      $this->lti_module = $lti_module;
}

public function bb_import_groups_from_gradebook($lastLogEntryTS) {

    $full_gradebook = $this->bb_fetch_gradebook();

    $stored_gradebook = NULL;

    $row = Settings::get_instructor_settings();

    if($row !== FALSE) {
        if(!empty($row->gradebook)) {
            $stored_gradebook = unserialize($row->gradebook);
        }
        $group_students = $row->enable_group_import;
    }

    if($full_gradebook) {
    $gbook = isset($full_gradebook['cachedBook']) ? $full_gradebook['cachedBook'] : $full_gradebook;

    // update last log entry
    if(!empty($gbook)) {
        if(array_key_exists('lastLogEntryTS', $gbook) === TRUE) {
              $parsed = date_parse_from_format("d M Y H:i", $gbook['lastLogEntryTS']);

              $new = mktime(
                      $parsed['hour'],
                      $parsed['minute'],
                      $parsed['second'],
                      $parsed['month'],
                      $parsed['day'],
                      $parsed['year']
              );
        } else {
            $new = time();
        }

        if(array_key_exists('customViews', $gbook) === TRUE && array_key_exists('groups', $gbook) === TRUE) {
            $gb_signature = array($gbook['customViews'], $gbook['groups']);
        } else {
            return array("errors" => "Please setup groups and Smart Views for the groups you need to import.");
        }

        if($row === FALSE) {
            ee()->db->insert("lti_instructor_settings", array("course_key" => $this->lti_module->course_key, "institution_id" => $this->lti_module->institution_id, "gradebook" => serialize($gb_signature)));

            $lastLogEntryTS = $new;
            $stored_gradebook = $gb_signature;
        }

        if($new != $lastLogEntryTS || $stored_gradebook != $gb_signature) {
            $lastLogEntryTS = $new;

            ee()->db->where(array("course_key" => $this->lti_module->course_key, "institution_id" => $this->lti_module->institution_id));
            ee()->db->update("lti_instructor_settings", array("gradebook" => serialize($gb_signature)));
        } else {
            return array("message" => "Grade Centre is synchronized.", "lastLogEntryTS" => FALSE);
        }

    } else {
      die(__LINE__);
        return array("errors" => "Unable to get date of last grade centre entry.");
    }
        $settings = Settings::get_general_settings();

        $plugin_settings = $settings["plugins_active"];

        $s_file = new GradebookImport($this->lti_module->member_id, $this->lti_module->context_id, $plugin_settings);

        $arr = $s_file->import_from_blackboard($group_students, $full_gradebook);

         // notify process to update DB table
         $arr['lastLogEntryTS'] = $lastLogEntryTS;

         return $arr;
    } else {
        return FALSE;
    }
  }

    public function bb_fetch_gradebook() {
        if(!empty($this->cachedGradeBook)) return $this->cachedGradeBook;

        $bb_auth = new Auth($this->lti_module);

        if(! $bb_auth->curl ) {
            $login_success = $bb_auth->gradebook_login();

            if(! $login_success === Auth::SUCCESS) {
                return "<p>Unable to connect to the gradebook.  Try returning to the course and clicking the link again.</p>";
            }
        }

        if(! $bb_auth->curl ) return FALSE;

        $blackboard_gradebook_uri_query = ee()->config->item('blackboard_gradebook_uri_query');
        $url2 = $bb_auth->get_blackboard_url($this->lti_module).$blackboard_gradebook_uri_query.$this->lti_module->pk_string;

        curl_setopt($bb_auth->curl, CURLOPT_HEADER, 0);
        curl_setopt($bb_auth->curl, CURLOPT_URL, $url2);
        curl_setopt($bb_auth->curl, CURLOPT_REFERER, $bb_auth->url);
        curl_setopt($bb_auth->curl, CURLOPT_COOKIEFILE, $bb_auth->cookies);
        $page2 = curl_exec($bb_auth->curl);

        curl_close($bb_auth->curl);
        
        $this->cachedGradeBook = json_decode($page2, TRUE);

        return $this->cachedGradeBook;
    }


}
?>
