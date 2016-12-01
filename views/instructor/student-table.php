<?php
use LTI\ExtensionHooks\Utils;

if (isset($students) && count($students) > 0 || isset($_POST['st_search'])) {
	ee()->load->library('table');
	$tmpl = array (
                    'table_open'  => '<table border="0" cellpadding="4" cellspacing="0" class="'.$table_class.'">',
	);

	ee()->table->set_template($tmpl);

	if($include_groups) {
		 ee()->table->set_heading(
	        lang('screen_name'),
	        lang('username'),
	        lang('email'),
	        lang('file_name'),
	        //lang('group_no'),
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
				$class = '';
				if($student['last_launched_on'] > 0) {
						$class='success';
				}



				if($include_groups) {
							ee()->table->add_row(
										Utils::add_class_to_cell($student['screen_name'], $class),
								//	array($student['screen_name'], 'class' => $class),
									$student['username'],
									"<a href='mailto:$student[email]'>$student[email]</a>",
									empty($student['file_display_name']) ? " - " : $student['file_display_name'],
									$student['group_name']
							);

				} else {

					ee()->table->add_row(
									$student['screen_name'],
									$student['username'],
									"<a href='mailto:$student[email]'>$student[email]</a>",
									empty($student['file_display_name']) ? " - " : $student['file_display_name']
							);
				}

	    }

	} else {
		 ee()->table->add_row(array('colspan' => 6, 'style' => 'text-align: left','data' => "Nothing to show"));
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
} else {
		echo lang('no_students_in_this_context');
}
?>
