<?php
use LTI\ExtensionHooks\Utils;

if (isset($students) && count($students) > 0 || isset($_POST['st_search'])) {
	ee()->load->library('table');
	$tmpl = array (
                    'table_open'  => '<table border="0" cellpadding="4" cellspacing="0" class="'.$table_class.'">',
	);

	ee()->table->set_template($tmpl);

	if($include_groups) {
		$heading = array(
				lang('screen_name'),
				lang('username'),
				lang('email'),
				lang('file_name'),
				lang('group_name')
		 );
	} else {
			$heading = array(
					lang('screen_name'),
					lang('username'),
					lang('email'),
					lang('file_name')
		 	);
	}

$indexes = array();
$scripts = array();
$actions = array();
if(isset($student_table_plugin_headers) && is_array($student_table_plugin_headers)) {
	foreach($student_table_plugin_headers as $plugin => $plugin_headers) {
 				$heading = array_merge($heading, $plugin_headers);
				$indexes = array_merge($indexes, $student_table_plugin_col_indexes[$plugin]);
				$actions[$plugin] = $student_table_actions[$plugin];
				$scripts[] = $student_table_scripts[$plugin];
	}
}

$json_actions = json_encode($actions);

ee()->table->set_heading($heading);

if(isset($students)) {
	    foreach($students as $index => $student)
	    {
				$class = '';
				if($student['last_launched_on'] > 0) {
						$class .='success ';
				}

				//if($student['has_assessed'])

				$row = null;
				$email_trunc = substr($student['email'], 0, 12);
				if(strpos($email_trunc, 'apeg_import')) {
						$email_trunc = 'unknown';
				}
				if($include_groups) {
							$row = array(
										Utils::add_class_to_cell($student['screen_name'], $class),
										$student['username'],
									"<a href='mailto:$student[email]'>$email_trunc...</a>",
									empty($student['file_display_name']) ? " - " : $student['file_display_name'],
									$student['group_name']
							);
				} else {
							$row = array(
											$student['screen_name'],
											$student['username'],
											"<a href='mailto:$student[email]'>$email_trunc...</a>",
											empty($student['file_display_name']) ? " - " : $student['file_display_name']
							);
				}

				if(count($indexes) > 0) {
						$fields = array();
						foreach($indexes as $index) {
							if(isset($student[$index])) {
										$fields[] = $student[$index];
							} else {
										$fields[] = "----";
							}
						}

						$row = array_merge($row, $fields);
				}

				ee()->table->add_row($row);
	    }

	} else {
		 ee()->table->add_row(array('colspan' => 6, 'style' => 'text-align: left', 'data' => "Nothing to show"));
	}


	foreach($lti_plugins as $plugin) {
		      if(isset($vars[$plugin])) {
						?>
						<span class='plugin-heading-text'> <?= $vars[$plugin]["heading"]["text"] ?></span>
								<div class="plugin-summary">
								<?php
							foreach($vars[$plugin] as $item) {
									if(! isset($item["text"]) ) {
										$id = hash("sha256", json_encode($item));
										 ?>
										 <label for="<?= $id ?>">
											 		<?= $item["label"] ?>
										 </label>
										 <p id="<?= $id ?>">
											 		<?= $item["value"] ?>
										 </p>
										 <?php
									 }
							}
						?>
							</div>

							<hr />
						<?php
					}
		}
?>
<div class="<?= $table_wrapper_class ?>">
<?php	echo ee()->table->generate(); ?>
</div>

<div class="tableFooter">
    <?= $pagination ?>
    <?= $per_page ?>
</div>
<?php

/* add EE actions */
echo "<script>";
echo "var acts = $json_actions;";
echo "var base_url = '$base_url';";
		foreach($scripts as $script) {
						echo $script;
		}

echo "</script>";
} else {
		echo lang('no_students_in_this_context');
}
?>
