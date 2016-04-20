
<h1><?= lang('institutions_title') ?></h1>
<?php if (count($institutions) > 0): ?>

<?=form_open($action_url, '', $form_hidden)?>


<?php
    ee()->table->set_template($cp_table_template);
    ee()->table->set_heading(
        lang('institution_id'),
        lang('institution_name'),
        form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"'));

    foreach($institutions as $institution)
    {
        ee()->table->add_row(
                $institution['id'],
                "<a class='contexts' href='#_$institution[id]'>".$institution['name']."</a>",
                form_checkbox($institution['toggle'])
            );
    }

echo ee()->table->generate();

?>

<div class="tableFooter">
    <div class="tableSubmit">
        <?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit')).NBS.NBS.form_dropdown('action', $options)?>
        <?=form_close()?>
    </div>
    <?= form_open($action_add_institution, '', null) ?>
    <?= form_hidden('type', 'render_form') ?>
    <?= form_submit(array('name'=> 'submit', 'value' => lang('add_institution'), 'class' => 'submit')) ?>
    <?= form_close() ?>
    <span class="js_hide"><?=$pagination?></span>
    <span class="pagination" id="filter_pagination"></span>
</div>

<?=form_open($contexts_url, array("id" => "show_instances"), $form_hidden)?>
    <?= form_input(array("type" => 'hidden', "id" => 'inid', 'name' => 'inid', 'value' => '0')); ?>
    <?= form_input(array("type" => 'hidden', "id" => 'inname', 'name' => 'inname', 'value' => '0')); ?>
<?=form_close()?>

<script src="//ajax.googleapis.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>
<script type='text/javascript'>
    $(document).ready(function() {
        $('a.contexts').click('click', function(e) {
            //console.log("facakldfjglk");
            e.preventDefault();
            var a = $(e.target).attr('href').split('_');

            $('#show_instances input#inid').attr('value', a[1]);
             $('#show_instances input#inname').attr('value', $(e.target).text());
            $('#show_instances').submit();
        });
    });
</script>

<?php else: ?>
<div class="tableFooter">
    <div class="tableSubmit">
        <?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit')).NBS.NBS.form_dropdown('action', $options)?>
        <?=form_close()?>
    </div>
<?=lang('no_institutions_registered')?>
<br>
    <?= form_open($action_add_institution, '', null) ?>
    <?= form_hidden('type', 'render_form') ?>
    <?= form_submit(array('name'=> 'submit', 'value' => lang('add_institution'), 'class' => 'submit')) ?>
    <?= form_close() ?>
</div>
<?php endif; ?>
