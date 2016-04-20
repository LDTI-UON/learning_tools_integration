<?php if (isset($resources) && count($resources) > 0) {

	ee()->load->library('table');
	$tmpl = array (
                    'table_open'  => '<table border="0" cellpadding="4" cellspacing="0" class="gbTable">',
	);

	ee()->table->set_template($tmpl);

    ee()->table->set_heading(
        "ID",
        lang('file_name')
     );

	$uri = ee()->uri->uri_string();

    foreach($resources as $resource)
    {
        ee()->table->add_row(
                $resource['id'],
                "<a target='_blank' href='$uri?download_lti_resource=$resource[id]'>$resource[display_name]</a>"
            );
    }

echo ee()->table->generate();
?>

<div class="tableFooter">
    <?= $pagination ?>
</div>

<?php
} else {
		echo lang('no_resources_in_this_context');
}
?>
