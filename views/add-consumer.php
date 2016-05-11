
<h1><?= lang('add_consumer') ?></h1>

<?=form_open($action)?>
	<p><?= validation_errors(); ?></p>
	<p><?=lang('add_consumer_name')?></p>
	<p>
	<?= form_input(array(
              'name'        => 'add_name',
              'id'          => 'add_name',
              'value'       => '',
              'maxlength'   => '100',
              'size'        => '20',
			  'style' => 'width: 300px')) ?></p>
	<p><?=lang('add_consumer_key')?></p>
		<p>
	<?= form_input(array(
              'name'        => 'add_key',
              'id'          => 'add_key',
              'value'       => $key,
              'maxlength'   => '100',
              'size'        => '20',
			  'style' => 'width: 200px')) ?></p>
<p><?=lang('add_consumer_secret')?></p>
<p>
	<?= form_input(array(
              'name'        => 'add_secret',
              'id'          => 'add_secret',
              'value'       => $secret,
              'maxlength'   => '100',
              'size'        => '20',
			  'style' => 'width: 200px')) ?></p>
<p><?=lang('add_url_segment')?></p>
<p>
	<?= form_input(array(
              'name'        => 'add_url_segment',
              'id'          => 'add_url_segment',
              'value'       => '',
              'maxlength'   => '100',
              'size'        => '20',
			  'style' => 'width: 200px')) ?></p>

	<p><?=form_submit('add_consumer', lang('add_consumer'), 'class="submit"')?></p>

<?=form_close()?>
