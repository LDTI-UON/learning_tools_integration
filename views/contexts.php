
<h1><?= lang('contexts_title') ?></h1>

<?php if (count($contexts) > 0): ?>

<?=form_open($action_url, '', $form_hidden)?>

<?php
    ee()->table->set_template($cp_table_template);
    ee()->table->set_heading(
        'ID',
        lang('context_member_id'),
        lang('context_user_id'),
        lang('context_username'),
        lang('context_context_id'),
        lang('context_tool_consumer_instance_name'),
         lang('context_is_instructor'),
        form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"'));

    foreach($contexts as $context)
    {
        ee()->table->add_row(
                "<a title='".lang('context_launch_title')."' href='#|".$context['id']."|".$context['username']."|".$context['email']."' class='launcher'>".lang('context_launch')."</a>",
                $context['member_id'],
                $context['user_id'],
                $context['username'],
                $context['context_id'],
                $context['tool_consumer_instance_name'],
                $context['is_instructor'],
                form_checkbox($context['toggle'])
            );
    }

echo ee()->table->generate();

?>

<div class="tableFooter">
    <div class="tableSubmit">
        <?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit')).NBS.NBS.form_dropdown('action', $options)?>
    </div>

    <span><?=$pagination?></span>
    <span class="pagination" id="filter_pagination"></span>
</div>
<?=form_close()?>

<?=form_open($launch_url, array("id" => "launch_tool"), $form_hidden)?>
    <?= form_input(array("type" => 'hidden', "id" => 'hci', 'name' => 'hci', 'value' => '0')); ?>
    <?= form_input(array("type" => 'hidden', "id" => 'un', 'name' => 'un', 'value' => '')); ?>
     <?= form_input(array("type" => 'hidden', "id" => 'em', 'name' => 'em', 'value' => '')); ?>
<?=form_close()?>
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>

<script type='text/javascript'>
    $(document).ready(function() {
        $('a.launcher').click('click', function(e) {
            //console.log("facakldfjglk");
            e.preventDefault();
            var a = $(e.target).attr('href').split('|');

            $('#launch_tool input#hci').attr('value', a[1]);
            $('#launch_tool input#un').attr('value', a[2]);
            $('#launch_tool input#em').attr('value', a[3]);
            $('#launch_tool').submit();
        });
    });
</script>


<?php else: ?>
<?=lang('no_contexts_registered')?>
<?php endif; ?>
