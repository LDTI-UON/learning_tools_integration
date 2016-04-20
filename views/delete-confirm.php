<?=form_open($form_action)?>
<?php foreach($binned as $language):?>
	<?=form_hidden('delete[]', $language)?>
<?php endforeach;?>

<p class="notice"><?=lang('action_can_not_be_undone')?></p>

<?php if ($type == 'consumers') : ?>
<h3><?=lang('consumer_delete_question')?></h3>
<?php elseif ($type == 'institutions') : ?>
<h3><?=lang('institution_delete_question')?></h3>
<?php endif; ?>
<p>
	<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
</p>

<?=form_close()?>
