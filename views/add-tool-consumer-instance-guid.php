
<h1><?= lang('add_consumer_guid') ?> &rarr; <?= $institution_name ?></h1>
<p><em><?= lang('add_consumer_guid_description') ?></em></p>
<?=form_open($action, '', null)?>
    <p><?= validation_errors(); ?></p>
    <p><?=lang('add_consumer_guid')?>&nbsp;&nbsp;
    <?= form_hidden('inid', $inid) ?>
        <?= form_input(array(
              'name'        => 'add_guid',
              'id'          => 'add_guid',
              'value'       => '',
              'maxlength'   => '100',
              'size'        => '20',
              'style' => 'width: 300px')) ?></p>

    <p><?=form_submit('add_consumer_guid', lang('add_consumer_guid'), 'class="submit"')?></p>

<?=form_close()?>
