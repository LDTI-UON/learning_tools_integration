<?php if (isset($students) && count($students) > 0 || isset($_POST['st_search'])) {
	ee()->load->library('table');
	$tmpl = array (
                    'table_open'  => '<table border="0" cellpadding="4" cellspacing="0" class="gbTable">',
	);

	ee()->table->set_template($tmpl);

	if($include_groups) {
		 ee()->table->set_heading(
	        lang('screen_name'),
	        lang('username'),
	        lang('email'),
	        lang('file_name'),
	        lang('group_no'),
	        lang('group_name')
	     );

	} else {
	    ee()->table->set_heading(
	        lang('screen_name'),
	        lang('username'),
	        lang('email'),
	        lang('file_name')
	     );
	}

	if(isset($students)) {
	    foreach($students as $index => $student)
	    {
				if($include_groups) {
							ee()->table->add_row(
									$student['screen_name'],
									$student['username'],
									"<a href='mailto:$student[email]'>$student[email]</a>",
									empty($student['display_name']) ? "None assigned" : $student['display_name'],
									$student['group_no'],
									$student['group_name']
							);
				} else {
					ee()->table->add_row(
									$student['screen_name'],
									$student['username'],
									"<a href='mailto:$student[email]'>$student[email]</a>",
									empty($student['display_name']) ? "None assigned" : $student['display_name']
							);
				}
	    }

	} else {
		 ee()->table->add_row(array('colspan' => 6, 'style' => 'text-align: left','data' => "Nothing to show"));
	}
	echo ee()->table->generate();
?>

<div class="tableFooter">
    <?= $pagination ?>
    <?= $per_page ?>
</div>

<?php
} else {
		echo lang('no_students_in_this_context');
}
?>
