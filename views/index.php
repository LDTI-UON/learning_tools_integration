
<h1><?= lang('consumers_title') ?></h1>

<style>
.server_instructions {
width: 400px;
margin: 0.2em;
float: left;
margin-left: 2em;
}

.server_instructions > pre {
background-color: #333;
color: lightyellow;
border: thin solid lightyellow;
padding: 1em;
overflow: auto;
}

</style>


<?php if (count($consumers) > 0): ?>

<?=form_open($action_url, '', $form_hidden)?>

<?php
   // ee()->table->set_template($cp_table_template);
    ee()->table->set_heading(
    	'ID',
        lang('add_consumer_name'),
        lang('add_consumer_key'),
        lang('add_consumer_secret'),
        lang('add_url_segment'),
        form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"'));

    foreach($consumers as $consumer)
    {
        ee()->table->add_row(
       			$consumer['id'],
                $consumer['name'],
                $consumer['key'],
                $consumer['secret'],
                $consumer['url_segment'],
                form_checkbox($consumer['toggle'])
            );
    }

    echo ee()->table->generate();
?>

<div class="tableFooter">
    <div class="tableSubmit">
        <?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit')).NBS.NBS.form_dropdown('action', $options)?>
        <?=form_close()?>
    </div>
    <?= form_open($add_consumer_action, '', null) ?>
    <?= form_hidden('type', 'render_form') ?>
    <?= form_submit(array('name'=> 'submit', 'value' => lang('add_consumer'), 'class' => 'submit')) ?>
    <?= form_close() ?>
    <span class="js_hide"><?=$pagination?></span>
    <span class="pagination" id="filter_pagination"></span>
</div>

<?php else: ?>
    <div class="tableFooter">
        <?=lang('no_consumers_registered')?>
        <br>
        <?= form_open($add_consumer_action, '', null) ?>
        <?= form_hidden('type', 'render_form') ?>
        <?= form_submit(array('name'=> 'submit', 'value' => lang('add_consumer'), 'class' => 'submit')) ?>
        <?= form_close() ?>
        <span class="js_hide"><?=$pagination?></span>
        <span class="pagination" id="filter_pagination"></span>
    </div>
<?php endif; ?>

<hr>

<div class='server_instructions'>
<?= lang('blackboard_custom_msg') ?>
<pre>
<?= lang('blackboard_custom_vars').$maintenance_key ?></pre>
</div>

<!-- <div class='server_instructions'>
To setup automatic email checking on this server:
<!-- //<//?= //$cron_command; ?> -->


<!-- </div> -->
