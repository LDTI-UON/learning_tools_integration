<?php
namespace LTI\ExtensionHooks;

class GradebookImport {

private $context;
private $new_user_group_id; // expressionengine group_id for new members
private $lti_plugin_setups = array();

/*
 * Define Blackboard Group Export column headers
 *
 * */
private $col_headers = array(
		"group_name" => "Group Code",
		"user_name" => "User Name",
		"student_id" => "Student Id",
		"first_name" => "First Name",
		"last_name" => "Last Name",
);

private $col_header_indexes = array();

private function define_column_locations($row) {
	foreach($row as $index => $header) {
		foreach($this->col_headers as $name => $val) {
			if(strpos(strtolower($header), strtolower($val)) !== FALSE) {
				$this->col_header_indexes[$name] = $index;
				break;
			}
		}
	}
}

public function __construct($member_id, $context_id, $lti_plugin_setups = array(), $new_user_group_id = 6) {
		// get instructor context
		$c = ee()->db->get_where('lti_member_contexts', array('member_id' => $member_id, 'context_id' => $context_id));
		$this->context = $c->row();

		$this->new_user_group_id = $new_user_group_id;

    $this->lti_plugin_setups = $lti_plugin_setups;
}

public function import_from_blackboard($group_students, $json) {

    $usernames = array();
    $duplicate_groups = array();

     $file_rows = array();

    if(isset($json['cachedBook'])) {
        $gradeBook = $json['cachedBook'];
    } else {
       if(!isset($json["colDefs"])) {
            echo "<pre>";
            var_dump(array_keys($json));
            var_dump($json);
            return array("errors" => "<p>ERROR: No grade book found. See JSON output</p>");
        } else {
            $gradeBook = $json;
        }
    }

    if($group_students === TRUE && !isset($gradeBook['groups'])) {
            return (array("errors" => "No Smart Views defined in Blackboard.  For group import, please define Smart Views for all of your groups"));
    }

    // column definitions for grade book
    foreach($gradeBook['colDefs'] as $index => $coldef) {
        if($coldef['id'] === "UN") {
            $UN_index = $index;
        }
        if($coldef['id'] === "SI") {
            $SI_index = $index;
        }
        if($coldef['id'] === "FN") {
            $FN_index = $index;
        }
        if($coldef['id'] === "LN") {
            $LN_index = $index;
        }
    }
    $this->col_header_indexes['group_name'] = 0;
    $this->col_header_indexes['first_name'] = 1;
    $this->col_header_indexes['last_name'] = 2;
    $this->col_header_indexes['student_id'] = 3;
    $this->col_header_indexes['user_name'] = 4;

    $file_rows = array();

    foreach ($gradeBook['rows'] as $gradebook_row_index => $gradebook_row) {
       $row = array();

        $row[$this->col_header_indexes['first_name']] = $gradebook_row[$FN_index]['v'];
        $row[$this->col_header_indexes['last_name']] = $gradebook_row[$LN_index]['v'];
        $row[$this->col_header_indexes['student_id']] = empty($gradebook_row[$SI_index]['v']) ? $gradebook_row[$UN_index]['v'] : $gradebook_row[$SI_index]['v'];
        $row[$this->col_header_indexes['user_name']] = $gradebook_row[$UN_index]['v'];
        $uid = $gradebook_row[$LN_index]['uid'];

        $added_to_group = FALSE;
        foreach ($gradeBook['groups'] as $group_index => $group) {

            if(in_array($uid, $group['uids'])) {
                $view_alias = $group['id'];

                 foreach ($gradeBook['customViews'] as $custom_view_index => $custom_view) {
                     if(isset($custom_view['aliases'][0])) {
                         if($custom_view['aliases'][0]['val'] == $view_alias) {
                            $group_name = $custom_view["name"];
                         }
                     }
                 }

                $row[$this->col_header_indexes['group_name']] = $group_name;
                $file_rows[$group_name][] = $row;
                $added_to_group = TRUE;
            }
        }

        if(!$added_to_group) {
                $file_rows["__no_group__"][] = $row; // mark for removal
        }
    }

    ksort($file_rows);

    $errors = "";

    return $this->import_data($file_rows, $errors, $group_students);
}


public function import($group_students, $file_path) {
		$errors = '';
		$schar = ',';
		$handle = fopen($file_path, "r");
		$i = 0;
		$csv = array();
		while (($nextline = fgetcsv($handle, 500, $schar)) !== FALSE) {

			if ($i++ > 0) {
				$csv[] = $nextline;
				$i = $i + 1;
			} else {
				$this->define_column_locations($nextline);
			}
		}

		if(count($csv) == 0) {
			$errors .= "Ensure that the file format is <strong>Windows Comma Separated (.csv)</strong><br>";
		}

		$file_rows = array();

		// create array sorted on group name, this way we can determine what groups there already are
		foreach($csv as $row) {
			if(isset($this->col_header_indexes['group_name'])) {
				$file_rows[
						$row[
								$this->col_header_indexes['group_name']
						]
				][] = $row;
			} else {
				$file_rows['course_group'][] = $row;
			}
		}

		ksort($file_rows);

        unlink($file_path);

     return $this->import_data($file_rows, $errors, $group_students);
}

private function import_data(& $file_rows, & $errors = "", $group_students, $start_row = 1) {

        $message = '';
		$usernames = array();
        $duplicate_groups = array();
		$row_num = $start_row;

        $dupcount = 0;
		$dupdetails = "<ol>";
		$affr = 0;
		$gaffr_int = 0;
		$last_group_no = 0;
		$last_group_name = "";
        $tempid = 0;

		foreach ($file_rows as $group_name => $group) {

			if(isset($this->col_header_indexes['group_name'])) {

			foreach($group as $group_member_index => $row){
				$fail = FALSE;
				$row_num++;

				// remove rows with empty cells
				foreach($row as $i => $col) {
					if(empty($col)) {
                        // instructors don't have student id, so this is important for preview users.
                        if($i == $this->col_header_indexes['student_id']) {
                            $temp_id++;
                            $un = strtolower($row[$this->col_header_indexes['user_name']]);
                            $row[$i] = $un."_".$temp_id;
                        } else {
						      unset($group[$group_member_index]);
						      $fail = TRUE;
                              break;
                        }
					}
				}

				if(!$fail) {
                    // check for duplicate groups
					$un = strtolower($row[$this->col_header_indexes['user_name']]);

					if(array_key_exists($un, $usernames) === TRUE) {
					    if(!array_key_exists($row[$this->col_header_indexes['user_name']], $duplicate_groups)) {
							$duplicate_groups[$row[$this->col_header_indexes['user_name']]] =  array('username' => $row[$this->col_header_indexes['user_name']], 'full_name' => $row[$this->col_header_indexes['first_name']]." ".$row[$this->col_header_indexes['last_name']], 'groups' => array($usernames[$un], $group_name));
						} else {
							if(!in_array($duplicate_groups[$row[$this->col_header_indexes['user_name']]]['groups'], $group_name)) {
								$duplicate_groups[$row[$this->col_header_indexes['user_name']]]['groups'][] = $group_name;
							}
						}
					} else {
						$usernames[$un] = $group_name;
					}

					$query =   ee() -> db -> get_where('lti_group_contexts', array('group_name' => $group_name, 'context_id' => $this->context->context_id, 'tool_consumer_instance_id' => $this->context->tool_consumer_instance_id));

					if($query->num_rows() > 0) {
						$group['group_id'] = $query->row()->group_id;
					} else {
						$group['group_id'] = -1;
					}
				}

				$culled_group = $group;
			}

				if(isset($culled_group)) {
					$file_rows[$group_name] = $culled_group;
				}

			} else {
				$file_rows[$group_name]['group_id'] = 1;
			}
		}

		$current_group_id = 0;

		foreach ($file_rows as $group_name => $group) {

			if(isset($group['group_id'])) {
				$current_group_id = $group['group_id']; // LTI group id (NOT EE group_id!!)

				foreach($group as $group_member_index => $row){

					if($group_member_index !== 'group_id') {
						$count = 0;

						$fullname = $row[$this->col_header_indexes['first_name']] . " " . $row[$this->col_header_indexes['last_name']];
						//$data = array('Last Name' => $row[0], 'First Name' => $row[1], 'Username' => $row[2], 'lti_course_key' => $lti_course_key);
						$rows =  ee() -> db -> get_where('members', array('username' => $row[$this->col_header_indexes['user_name']]));
						$count += $rows -> num_rows();

						$id = 0;

						if ($count == 0) {
							$sql_data = array('username' => $row[$this->col_header_indexes['user_name']], 'screen_name' => $fullname, 'group_id' => $this->new_user_group_id);
							ee() -> db -> insert('members', $sql_data);
							$id = ee() -> db -> insert_id();
							++$affr;
						} else {
							$res_row = $rows -> row();
							$id = $res_row -> member_id;
							++$dupcount;
							$dupdetails .= "<li>$fullname ($row[2]).</li>";
						}

						// $members[$id] = array('group_id' => $current_group_id);

						$dupdetails = "</ol>";

						$clause = array('member_id' => $id, 'username' => $row[$this->col_header_indexes['user_name']], 'context_id' => $this -> context -> context_id, 'tool_consumer_instance_id' => $this -> context -> tool_consumer_instance_id);
						ee() -> db -> where($clause);

						$cres =   ee() -> db -> get('lti_member_contexts');

						if ($cres -> num_rows() == 0) {

							$context_data = array('member_id' => $id, 'username' => $row[$this->col_header_indexes['user_name']], 'context_id' => $this -> context -> context_id, 'context_label' => $this -> context -> context_label, 'ext_lms' => $this -> context -> ext_lms, 'tool_consumer_instance_id' => $this -> context -> tool_consumer_instance_id, 'tool_consumer_instance_name' => $this -> context -> tool_consumer_instance_name, 'imported_on' => 'CURRENT_TIMESTAMP');

							ee() -> db -> insert('lti_member_contexts', $context_data);
							$int_context_id =   ee() -> db -> insert_id();

						} else {
							ee() -> db -> where($clause);
							ee() -> db -> update('lti_member_contexts', array('context_label' => $this -> context -> context_label, 'ext_lms' => $this -> context -> ext_lms, 'tool_consumer_instance_name' => $this -> context -> tool_consumer_instance_name));
							$success =   ee() -> db -> affected_rows();

							if ($success != -1) {
								ee() -> db -> where($clause);
								$int_context_id =   ee() -> db -> get('lti_member_contexts') -> row() -> id;
							} else {
								$errors .= "<p style='color:red'>Could not update member context for ".$row[$this->col_header_indexes['user_name']]."</p>";
							}
						}

						// carry these over for plugins
						$row['internal_context_id'] = $int_context_id;
						$row['member_id'] = $id;
						// $row[] = array('internal_context_id' => $int_context_id, 'member_id' => $id);
						$group[$group_member_index] = $row;

						if($current_group_id == -1 && $group_name !== "__no_group__") {
							ee() -> db -> select_max('group_id');
							$maxq =   ee() -> db -> get('lti_group_contexts');
							$next_group_id = 1;

							if ($maxq -> num_rows() > 0) {
								$next_group_id = $maxq -> row() -> group_id;
								$next_group_id = $next_group_id + 1;
							}

							if ($group_name != $last_group_name || $group_id == 0) {
								$group_id = $next_group_id;
								$last_group_name = $row[$this->col_header_indexes['group_name']];
							}
						} else {
							$group_id = $current_group_id;
						}

						// set this for any additional plugins to use later
						$group['group_id'] = $group_id;
						$file_rows[$group_name] = $group;

						// group students tickbox checked...
						if (!empty($group_students)) {

                        if($group_name === "__no_group__") {
                                $where = array('member_id' => $id, 'internal_context_id' => $int_context_id);

                                ee() -> db -> where($where);
                                ee() -> db -> delete('lti_group_contexts');
                        } else {
                                $where = array('member_id' => $id, 'internal_context_id' => $int_context_id);
                                $group_data = array('member_id' => $id, 'internal_context_id' => $int_context_id, 'group_name' => $row[$this->col_header_indexes['group_name']], 'group_id' => $group_id, 'context_id' => $this->context->context_id, 'tool_consumer_instance_id' => $this->context->tool_consumer_instance_id);

                                ee() -> db -> where($where);
                                $cres =   ee() -> db -> get('lti_group_contexts');

                                if ($cres -> num_rows() == 0) {
                                    ee() -> db -> insert('lti_group_contexts', $group_data);
                                    $gaffr_int += 1;
                                } else {
                                    ee() -> db -> where($where);
                                    ee() -> db -> update('lti_group_contexts', $group_data);

                                    if ($success == 1) {
                                        $gaffr_int += $success;
                                    } else if($success == -1) {
                                        $errors .= "<p style='color: red'><b>Database error</b> : Could not update group context for user: ".$row[$this->col_header_indexes['first_name']]." ".$row[$this->col_header_indexes['last_name']]."</p>";
                                    }
                                }
                            }
                        }
					}
				}
			}
		} // end file_rows parse

		// apply plugins
		if(!empty(Learning_tools_integration::$lti_plugins)) {
			foreach(Learning_tools_integration::$lti_plugins as $plugin) {
			    if(array_key_exists($plugin, $this->lti_plugin_setups) === TRUE && !empty($this->lti_plugin_setups[$plugin])) {
					require_once(PATH_THIRD."$plugin/libraries/$plugin"."_setup.php");
				}
			}
		}

		$message .= "<h2>Import Complete</h2><h3>".$this->context->course_name."</h3><p>" . $affr . " students were added. $dupcount students were already in the database, but I've made sure they are members of the correct group and are in this course.</p>";

		if($group_students) {
			if(count($duplicate_groups) > 0) {
				$errors .= "<p style='color: darkgreen'><b>The following students are in multiple groups.  Please amend this in Blackboard.</b><br><ol>";
				foreach ($duplicate_groups as $username => $student) {
					$errors .= "<li>".$student['full_name']." (".$username.")<ol>";

					$c = count($student['groups']) - 1;
					foreach($student['groups'] as $i => $group) {
						$g = $group;
						if($i == $c) {
							$g = "<strong>$group</strong>";
						}
						$errors .= "<li>$g</li>";
					}

					$errors.= "</ol></li>";
				}
				$errors .= "</ol><br> These students are currently assigned to the last (bolded) group they were listed with in Blackboard Groups.</p>";
			}
	}

		return array('message' => $message, 'errors' => $errors);
	}

}
