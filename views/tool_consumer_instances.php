
<h1><?= $institution_name ?></h1>
<?php if (count($instances) > 0): ?>

<?=form_open($action_url, '', $form_hidden)?>

<?php
    ee()->table->set_template($cp_table_template);
    ee()->table->set_heading(
        lang('instance_guid'),
        form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"'));

    foreach($instances as $instance)
    {
        ee()->table->add_row(
                $instance['guid'],
                form_checkbox($instance['toggle'])
            );
    }

echo ee()->table->generate();
?>
<?php else: ?>

    <?=lang('no_instances_registered')?>

<?php endif; ?>

<div class="tableFooter">
    <div class="tableSubmit">
        <?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit')).NBS.NBS.form_dropdown('action', $options)?>
        <?=form_close()?>
    </div>
    <?= form_open($action_add_instance, '', null) ?>
    <?= form_hidden('type', 'render_form') ?>
    <?= form_hidden('inid', $inid) ?>
    <?= form_hidden('inname', $institution_name) ?>
    <?= form_submit(array('name'=> 'submit', 'value' => lang('add_instance'), 'class' => 'submit')) ?>
    <?= form_close() ?>
    <span class="js_hide"><?=$pagination?></span>
    <span class="pagination" id="filter_pagination"></span>
</div>





